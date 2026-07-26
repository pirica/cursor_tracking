<?php
declare(strict_types=1);

require_once __DIR__ . '/html.php';

function tct_rule_filename_valid(string $name): bool
{
    return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.mdc$/', $name);
}

function tct_sanitize_rule_basename(string $name): string
{
    $name = str_replace(['/', '\\'], '', trim($name));

    return basename($name);
}

/**
 * @param array<string, mixed> $config
 */
function tct_resolve_rule_file(array $config, string $basename): ?string
{
    $basename = tct_sanitize_rule_basename($basename);
    if (!tct_rule_filename_valid($basename)) {
        return null;
    }
    $base = rtrim((string) ($config['rules_dir'] ?? ''), '\\/');
    if ($base === '') {
        return null;
    }
    $path = $base . DIRECTORY_SEPARATOR . $basename;
    if (!is_file($path)) {
        return null;
    }

    if (tct_path_is_inside($path, $base)) {
        $real = realpath($path);

        return $real !== false ? $real : $path;
    }

    return null;
}

/**
 * @return array{description: string, always_apply: bool}
 */
function tct_parse_rule_frontmatter(string $path): array
{
    $result = [
        'description' => '',
        'always_apply' => false,
    ];
    if (!is_readable($path)) {
        return $result;
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return $result;
    }
    if (!preg_match('/\A---\r?\n(.*?)\r?\n---\r?\n/s', $raw, $m)) {
        return $result;
    }
    foreach (preg_split('/\r?\n/', $m[1]) ?: [] as $line) {
        if (preg_match('/^description:\s*(.+)$/i', $line, $dm)) {
            $result['description'] = trim($dm[1], " \t\"'");
        } elseif (preg_match('/^alwaysApply:\s*(true|1|yes)$/i', $line)) {
            $result['always_apply'] = true;
        }
    }

    return $result;
}

function tct_rule_body_text(string $path): string
{
    if (!is_readable($path)) {
        return '';
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return '';
    }
    if (preg_match('/\A---\r?\n.*?\r?\n---\r?\n(.*)\z/s', $raw, $m)) {
        return $m[1];
    }

    return $raw;
}

/**
 * @param array<string, mixed> $config
 * @return list<array<string, mixed>>
 */
function tct_scan_rules(array $config): array
{
    $dir = (string) ($config['rules_dir'] ?? '');
    if ($dir === '' || !is_dir($dir)) {
        return [];
    }

    $files = glob($dir . DIRECTORY_SEPARATOR . '*.mdc');
    if ($files === false) {
        return [];
    }

    $rules = [];
    foreach ($files as $file) {
        $basename = basename($file);
        if (!tct_rule_filename_valid($basename)) {
            continue;
        }
        $fm = tct_parse_rule_frontmatter($file);
        $label = $basename;
        if ($fm['description'] !== '') {
            $label = $fm['description'];
        }
        $rules[] = [
            'file' => $basename,
            'path' => $file,
            'mtime' => filemtime($file) ?: 0,
            'size' => filesize($file) ?: 0,
            'label' => $label,
            'description' => $fm['description'],
            'always_apply' => $fm['always_apply'],
        ];
    }

    usort($rules, static function (array $a, array $b): int {
        return strcasecmp((string) $a['file'], (string) $b['file']);
    });

    return $rules;
}

/**
 * @param array<string, mixed> $config
 */
function tct_delete_rule_file(array $config, string $basename): bool
{
    $file = tct_resolve_rule_file($config, $basename);
    if ($file === null) {
        return false;
    }

    return unlink($file);
}

/**
 * @param array<string, mixed> $config
 */
function tct_open_location_for_rule(array $config, string $basename): bool
{
    $file = tct_resolve_rule_file($config, $basename);

    return $file !== null && tct_shell_reveal_file($file);
}
