<?php
declare(strict_types=1);

/**
 * @param array<string, mixed> $config
 * @return list<array{label: string, key: string, path: string, kind: string, exists: ?bool, note: string}>
 */
function tct_config_path_rows(array $config): array
{
    $rows = [
        [
            'label' => 'Project label',
            'key' => 'project_label',
            'path' => (string) ($config['project_label'] ?? ''),
            'kind' => 'text',
            'exists' => null,
            'note' => 'Display name in the header (not a folder).',
        ],
        [
            'label' => 'Agent transcripts',
            'key' => 'transcripts_dir',
            'path' => (string) ($config['transcripts_dir'] ?? ''),
            'kind' => 'dir',
            'exists' => null,
            'note' => 'Cursor chat JSONL files (read-only). Used by Chats.',
        ],
        [
            'label' => 'Plan mode files',
            'key' => 'plans_dir',
            'path' => (string) ($config['plans_dir'] ?? ''),
            'kind' => 'dir',
            'exists' => null,
            'note' => 'Cursor Plan mode *.plan.md (read-only). Used by Plans.',
        ],
        [
            'label' => 'Cursor project rules',
            'key' => 'rules_dir',
            'path' => (string) ($config['rules_dir'] ?? ''),
            'kind' => 'dir',
            'exists' => null,
            'note' => 'Cursor *.mdc rules (read-only). Used by Rules.',
        ],
        [
            'label' => 'Chat tracking data',
            'key' => 'tracking_file',
            'path' => (string) ($config['tracking_file'] ?? ''),
            'kind' => 'file',
            'exists' => null,
            'note' => 'Your stars, status, notes (written by this app).',
        ],
        [
            'label' => 'PHP defaults file',
            'key' => '_config_php',
            'path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php',
            'kind' => 'file',
            'exists' => null,
            'note' => 'Built-in defaults; overridden by data/local_config.json when you save on Config.',
        ],
        [
            'label' => 'Local overrides file',
            'key' => '_local_config',
            'path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'local_config.json',
            'kind' => 'file',
            'exists' => null,
            'note' => 'Written when you use Save paths on the Config page.',
        ],
    ];

    foreach ($rows as $i => $row) {
        if ($row['kind'] === 'dir' && $row['path'] !== '') {
            $rows[$i]['exists'] = is_dir($row['path']);
        } elseif ($row['kind'] === 'file' && $row['path'] !== '') {
            if ($row['key'] === 'tracking_file') {
                $rows[$i]['exists'] = is_file($row['path'])
                    || (is_dir(dirname($row['path'])) && is_writable(dirname($row['path'])));
            } else {
                $rows[$i]['exists'] = is_file($row['path']);
            }
        }
    }

    return $rows;
}

function tct_app_base_url(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/tracking_cursor/settings.php');
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '/' || $dir === '.') {
        $dir = '';
    }

    return $scheme . '://' . $host . rtrim($dir, '/');
}

/**
 * @return list<array{label: string, url: string}>
 */
function tct_config_url_rows(string $baseUrl): array
{
    $base = rtrim($baseUrl, '/');

    return [
        ['label' => 'Home', 'url' => $base . '/index.php'],
        ['label' => 'Chats list', 'url' => $base . '/chats.php'],
        ['label' => 'Plans list', 'url' => $base . '/plans.php'],
        ['label' => 'Rules list', 'url' => $base . '/rules.php'],
        ['label' => 'Config (this page)', 'url' => $base . '/settings.php'],
        ['label' => 'Chat detail', 'url' => $base . '/chat.php?id={chat-uuid}'],
        ['label' => 'Plan detail', 'url' => $base . '/plan.php?f={name}.plan.md'],
        ['label' => 'Rule detail', 'url' => $base . '/rule.php?f={name}.mdc'],
        ['label' => 'Tracking API', 'url' => $base . '/api/tracking.php'],
    ];
}
