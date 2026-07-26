<?php
declare(strict_types=1);

require_once __DIR__ . '/html.php';

function tct_plan_filename_valid(string $name): bool
{
    return (bool) preg_match('/^[a-z0-9][a-z0-9._-]*\.plan\.md$/i', $name);
}

/**
 * @param array<string, mixed> $config
 */
function tct_resolve_plan_file(array $config, string $basename): ?string
{
    $basename = tct_sanitize_plan_basename($basename);
    if (!tct_plan_filename_valid($basename)) {
        return null;
    }
    $base = rtrim((string) ($config['plans_dir'] ?? ''), '\\/');
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
 * @return array{name: string, overview: string, todos: list<array{id: string, content: string, status: string}>}
 */
function tct_parse_plan_frontmatter(string $path): array
{
    $result = [
        'name' => basename($path, '.plan.md'),
        'overview' => '',
        'todos' => [],
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
    $yaml = $m[1];
    $lines = preg_split('/\r?\n/', $yaml) ?: [];
    $inTodos = false;
    $currentTodo = null;

    foreach ($lines as $line) {
        if (preg_match('/^name:\s*(.+)$/i', $line, $nm)) {
            $result['name'] = trim($nm[1], " \t\"'");
            $inTodos = false;
            continue;
        }
        if (preg_match('/^overview:\s*(.+)$/i', $line, $ov)) {
            $result['overview'] = trim($ov[1], " \t\"'");
            $inTodos = false;
            continue;
        }
        if (preg_match('/^todos:\s*$/i', $line)) {
            $inTodos = true;
            continue;
        }
        if ($inTodos && preg_match('/^\s*-\s*id:\s*(.+)$/i', $line, $idm)) {
            if ($currentTodo !== null) {
                $result['todos'][] = $currentTodo;
            }
            $currentTodo = [
                'id' => trim($idm[1], " \t\"'"),
                'content' => '',
                'status' => 'pending',
            ];
            continue;
        }
        if ($inTodos && $currentTodo !== null && preg_match('/^\s+content:\s*(.+)$/i', $line, $cm)) {
            $currentTodo['content'] = trim($cm[1], " \t\"'");
            continue;
        }
        if ($inTodos && $currentTodo !== null && preg_match('/^\s+status:\s*(.+)$/i', $line, $sm)) {
            $currentTodo['status'] = trim($sm[1], " \t\"'");
        }
    }
    if ($currentTodo !== null) {
        $result['todos'][] = $currentTodo;
    }

    return $result;
}

function tct_plan_body_markdown(string $path): string
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
function tct_scan_plans(array $config): array
{
    $dir = (string) ($config['plans_dir'] ?? '');
    if ($dir === '' || !is_dir($dir)) {
        return [];
    }

    $files = glob($dir . DIRECTORY_SEPARATOR . '*.plan.md');
    if ($files === false) {
        return [];
    }

    $plans = [];
    foreach ($files as $file) {
        $basename = basename($file);
        if (!tct_plan_filename_valid($basename)) {
            continue;
        }
        $fm = tct_parse_plan_frontmatter($file);
        $todos = $fm['todos'];
        $done = 0;
        foreach ($todos as $todo) {
            if (($todo['status'] ?? '') === 'completed') {
                $done++;
            }
        }
        $plans[] = [
            'file' => $basename,
            'path' => $file,
            'mtime' => filemtime($file) ?: 0,
            'name' => $fm['name'],
            'overview' => $fm['overview'],
            'todos' => $todos,
            'todos_done' => $done,
            'todos_total' => count($todos),
        ];
    }

    usort($plans, static function (array $a, array $b): int {
        return ($b['mtime'] <=> $a['mtime']);
    });

    return $plans;
}

/**
 * @param array<string, mixed> $config
 */
function tct_plan_folder_uri(array $config, string $basename): ?string
{
    $file = tct_resolve_plan_file($config, $basename);
    if ($file === null) {
        return null;
    }
    $dir = dirname($file);

    return tct_path_to_file_uri($dir);
}

/**
 * @param array<string, mixed> $config
 */
function tct_delete_plan_file(array $config, string $basename): bool
{
    $file = tct_resolve_plan_file($config, $basename);
    if ($file === null) {
        return false;
    }

    return unlink($file);
}
