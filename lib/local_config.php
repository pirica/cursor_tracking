<?php
declare(strict_types=1);

/** @return list<string> */
function tct_editable_config_keys(): array
{
    return ['project_label', 'transcripts_dir', 'plans_dir', 'rules_dir', 'tracking_file'];
}

function tct_local_config_file(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'local_config.json';
}

/**
 * @return array<string, string>
 */
function tct_load_local_config(): array
{
    $file = tct_local_config_file();
    if (!is_file($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach (tct_editable_config_keys() as $key) {
        if (isset($data[$key]) && is_string($data[$key])) {
            $out[$key] = tct_sanitize_config_path_value($data[$key]);
        }
    }

    return $out;
}

function tct_sanitize_config_path_value(string $value): string
{
    $value = str_replace("\0", '', trim($value));

    return $value;
}

/**
 * If overrides still point at an old app folder (e.g. tracking_cursor), use this install's data/tracking.json.
 *
 * @param array<string, mixed> $defaults
 * @param array<string, string> $local
 * @return array<string, string>
 */
function tct_repair_stale_tracking_file_path(array $defaults, array $local): array
{
    if (!isset($local['tracking_file'])) {
        return $local;
    }

    $override = $local['tracking_file'];
    if ($override !== '' && is_file($override)) {
        return $local;
    }

    $default = (string) ($defaults['tracking_file'] ?? '');
    if ($default === '' || $override === $default) {
        return $local;
    }

    $defaultDir = dirname($default);
    if (!is_file($default) && !is_dir($defaultDir)) {
        return $local;
    }

    $local['tracking_file'] = $default;

    $toSave = [];
    foreach (tct_editable_config_keys() as $key) {
        if (isset($local[$key])) {
            $toSave[$key] = $local[$key];
        }
    }
    if ($toSave !== []) {
        tct_save_local_config($toSave);
    }

    return $local;
}

/**
 * @param array<string, mixed> $defaults from config.php
 * @return array<string, mixed>
 */
function tct_merge_app_config(array $defaults): array
{
    $local = tct_load_local_config();
    if ($local === []) {
        return $defaults;
    }

    $local = tct_repair_stale_tracking_file_path($defaults, $local);

    return array_merge($defaults, $local);
}

/**
 * @param array<string, string> $input
 * @return array{ok: bool, errors: list<string>, data: array<string, string>}
 */
function tct_validate_local_config_input(array $input): array
{
    $errors = [];
    $data = [];

    $label = tct_sanitize_config_path_value((string) ($input['project_label'] ?? ''));
    if ($label === '') {
        $errors[] = 'Project label is required.';
    } else {
        $data['project_label'] = $label;
    }

    $transcripts = tct_sanitize_config_path_value((string) ($input['transcripts_dir'] ?? ''));
    if ($transcripts === '') {
        $errors[] = 'Agent transcripts path is required.';
    } else {
        $data['transcripts_dir'] = $transcripts;
    }

    $plans = tct_sanitize_config_path_value((string) ($input['plans_dir'] ?? ''));
    if ($plans === '') {
        $errors[] = 'Plans folder path is required.';
    } else {
        $data['plans_dir'] = $plans;
    }

    $rules = tct_sanitize_config_path_value((string) ($input['rules_dir'] ?? ''));
    if ($rules === '') {
        $errors[] = 'Rules folder path is required.';
    } else {
        $data['rules_dir'] = $rules;
    }

    $tracking = tct_sanitize_config_path_value((string) ($input['tracking_file'] ?? ''));
    if ($tracking === '') {
        $errors[] = 'Tracking file path is required.';
    } else {
        $data['tracking_file'] = $tracking;
    }

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'data' => $data,
    ];
}

/**
 * @param array<string, string> $data
 */
function tct_save_local_config(array $data): bool
{
    $file = tct_local_config_file();
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    $payload = [];
    foreach (tct_editable_config_keys() as $key) {
        if (isset($data[$key])) {
            $payload[$key] = $data[$key];
        }
    }
    $payload['updated_at'] = date('c');

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    $tmp = $file . '.tmp';
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        return false;
    }

    return rename($tmp, $file);
}

function tct_reset_local_config(): bool
{
    $file = tct_local_config_file();
    if (!is_file($file)) {
        return true;
    }

    return unlink($file);
}
