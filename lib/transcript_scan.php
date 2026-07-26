<?php
declare(strict_types=1);

require_once __DIR__ . '/html.php';
require_once __DIR__ . '/transcript_parse.php';

/**
 * @param array<string, mixed> $config
 * @return list<array<string, mixed>>
 */
function tct_scan_transcripts(array $config): array
{
    $dir = $config['transcripts_dir'] ?? '';
    if ($dir === '' || !is_dir($dir)) {
        return [];
    }

    $entries = scandir($dir);
    if ($entries === false) {
        return [];
    }

    $chats = [];
    foreach ($entries as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        if (!tct_is_uuid($name)) {
            continue;
        }
        $folder = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($folder)) {
            continue;
        }
        $mainFile = $folder . DIRECTORY_SEPARATOR . $name . '.jsonl';
        if (!is_file($mainFile)) {
            continue;
        }

        $meta = tct_parse_jsonl_metadata($mainFile);
        $mtime = filemtime($mainFile) ?: 0;
        $subagents = tct_scan_subagents($folder, $name);

        $chats[] = [
            'id' => $name,
            'is_subagent' => false,
            'parent_id' => null,
            'file' => $mainFile,
            'mtime' => $mtime,
            'size' => filesize($mainFile) ?: 0,
            'subagent_count' => count($subagents),
            'subagents' => $subagents,
            'meta' => $meta,
        ];
    }

    usort($chats, static function (array $a, array $b): int {
        return ($b['mtime'] <=> $a['mtime']);
    });

    return $chats;
}

/**
 * @return list<array<string, mixed>>
 */
function tct_scan_subagents(string $parentFolder, string $parentId): array
{
    $subDir = $parentFolder . DIRECTORY_SEPARATOR . 'subagents';
    if (!is_dir($subDir)) {
        return [];
    }

    $entries = scandir($subDir);
    if ($entries === false) {
        return [];
    }

    $list = [];
    foreach ($entries as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        if (!preg_match('/\.jsonl$/i', $name)) {
            continue;
        }
        $subId = preg_replace('/\.jsonl$/i', '', $name) ?? $name;
        if (!tct_is_uuid($subId)) {
            continue;
        }
        $file = $subDir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($file)) {
            continue;
        }
        $meta = tct_parse_jsonl_metadata($file);
        $list[] = [
            'id' => $subId,
            'is_subagent' => true,
            'parent_id' => $parentId,
            'file' => $file,
            'mtime' => filemtime($file) ?: 0,
            'size' => filesize($file) ?: 0,
            'subagent_count' => 0,
            'subagents' => [],
            'meta' => $meta,
        ];
    }

    usort($list, static function (array $a, array $b): int {
        return ($b['mtime'] <=> $a['mtime']);
    });

    return $list;
}

/**
 * @param array<string, mixed> $config
 */
function tct_resolve_transcript_file(array $config, string $parentId, ?string $subId = null): ?string
{
    if (!tct_is_uuid($parentId)) {
        return null;
    }
    $base = rtrim((string) ($config['transcripts_dir'] ?? ''), '\\/');
    if ($base === '') {
        return null;
    }

    if ($subId !== null && $subId !== '') {
        if (!tct_is_uuid($subId)) {
            return null;
        }
        $path = $base . DIRECTORY_SEPARATOR . $parentId . DIRECTORY_SEPARATOR . 'subagents' . DIRECTORY_SEPARATOR . $subId . '.jsonl';
    } else {
        $path = $base . DIRECTORY_SEPARATOR . $parentId . DIRECTORY_SEPARATOR . $parentId . '.jsonl';
    }

    if (!is_file($path)) {
        return null;
    }
    if (!tct_path_is_inside($path, $base)) {
        return null;
    }
    $realPath = realpath($path);

    return $realPath !== false ? $realPath : $path;
}

/**
 * @param array<string, mixed> $config
 */
function tct_transcript_folder_uri(array $config, string $parentId, ?string $subId = null): ?string
{
    $file = tct_resolve_transcript_file($config, $parentId, $subId);
    if ($file === null) {
        return null;
    }

    return tct_path_to_file_uri(dirname($file));
}

/**
 * @param array<string, mixed> $config
 */
function tct_delete_transcript_file(array $config, string $parentId, ?string $subId = null): bool
{
    $file = tct_resolve_transcript_file($config, $parentId, $subId);
    if ($file === null) {
        return false;
    }

    return unlink($file);
}

/**
 * @return array<string, array<string, mixed>>
 */
function tct_load_tracking(string $trackingFile): array
{
    if (!is_file($trackingFile)) {
        return [];
    }
    $raw = file_get_contents($trackingFile);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }

    return $data;
}

/**
 * @param array<string, array<string, mixed>> $data
 */
function tct_save_tracking(string $trackingFile, array $data): bool
{
    $dir = dirname($trackingFile);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    $tmp = $trackingFile . '.tmp';
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        return false;
    }

    return rename($tmp, $trackingFile);
}

/**
 * @param array<string, mixed> $trackingRow
 */
function tct_merge_tracking_defaults(array $trackingRow): array
{
    $rawStatus = $trackingRow['status'] ?? 'open';

    return [
        'starred' => !empty($trackingRow['starred']),
        'status' => in_array($rawStatus, ['open', 'done', 'archived'], true)
            ? (string) $rawStatus
            : 'open',
        'notes' => (string) ($trackingRow['notes'] ?? ''),
        'title_override' => (string) ($trackingRow['title_override'] ?? ''),
    ];
}

/**
 * Default workflow status when the user has not saved tracking yet.
 *
 * @param array<string, mixed> $meta from tct_parse_jsonl_metadata
 */
function tct_infer_status_from_meta(array $meta): string
{
    $turn = $meta['last_turn_status'] ?? null;
    if ($turn === 'success') {
        return 'done';
    }

    return 'open';
}

/**
 * Stored tracking wins; otherwise infer Done from a successful last turn.
 *
 * @param array<string, array<string, mixed>> $storedAll
 * @param array<string, mixed> $meta
 * @return array{starred: bool, status: string, notes: string, title_override: string, inferred: bool}
 */
function tct_resolve_tracking(array $storedAll, string $key, array $meta): array
{
    if (array_key_exists($key, $storedAll)) {
        $row = tct_merge_tracking_defaults($storedAll[$key]);
        $row['inferred'] = false;

        return $row;
    }

    $row = tct_merge_tracking_defaults([]);
    $row['status'] = tct_infer_status_from_meta($meta);
    $row['inferred'] = true;

    return $row;
}

function tct_display_title(array $chatMeta, array $trackingRow): string
{
    $override = trim((string) ($trackingRow['title_override'] ?? ''));
    if ($override !== '') {
        return $override;
    }

    return (string) ($chatMeta['title'] ?? '');
}

function tct_status_badge_class(string $status): string
{
    switch ($status) {
        case 'success':
            return 'badge-success';
        case 'error':
            return 'badge-error';
        default:
            return 'badge-muted';
    }
}

function tct_turn_status_label(?string $status): string
{
    if ($status === null || $status === '') {
        return 'in progress';
    }

    return $status;
}

function tct_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return round($bytes / (1024 * 1024), 1) . ' MB';
}

function tct_format_mtime(int $mtime): string
{
    if ($mtime <= 0) {
        return '—';
    }

    return date('Y-m-d H:i', $mtime);
}
