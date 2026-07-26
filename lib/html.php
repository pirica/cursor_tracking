<?php
declare(strict_types=1);

function tct_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tct_truncate_plain(string $s, int $max = 160): string
{
    if (mb_strlen($s) <= $max) {
        return $s;
    }

    return mb_substr($s, 0, $max - 1) . '…';
}

function tct_normalize_path_string(string $path): string
{
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    if (DIRECTORY_SEPARATOR === '\\') {
        $path = strtolower($path);
    }

    return rtrim($path, DIRECTORY_SEPARATOR);
}

function tct_path_is_inside(string $childPath, string $parentDir): bool
{
    $childReal = realpath($childPath);
    $parentReal = realpath($parentDir);
    if ($childReal !== false && $parentReal !== false) {
        $childNorm = tct_normalize_path_string($childReal);
        $parentNorm = tct_normalize_path_string($parentReal);
        $sep = DIRECTORY_SEPARATOR;

        return $childNorm === $parentNorm
            || strpos($childNorm, $parentNorm . $sep) === 0;
    }

    $childNorm = tct_normalize_path_string($childPath);
    $parentNorm = tct_normalize_path_string($parentDir);
    $sep = DIRECTORY_SEPARATOR;

    return strpos($childNorm, $parentNorm . $sep) === 0;
}

function tct_sanitize_plan_basename(string $raw): string
{
    $raw = trim(rawurldecode($raw));
    $raw = str_replace("\0", '', $raw);
    $base = basename(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $raw));

    return $base;
}

function tct_turn_sort_value(?string $status): int
{
    if ($status === 'success') {
        return 2;
    }
    if ($status === 'error') {
        return 1;
    }

    return 0;
}

function tct_status_sort_value(string $status): int
{
    switch ($status) {
        case 'archived':
            return 0;
        case 'done':
            return 1;
        case 'open':
        default:
            return 2;
    }
}

function tct_sortable_th(string $label, string $sortKey): void
{
    ?>
    <th scope="col" class="th-sortable" data-sort-key="<?= tct_h($sortKey) ?>" tabindex="0" role="columnheader" aria-sort="none">
        <span class="th-sort-label"><?= tct_h($label) ?></span>
        <span class="th-sort-indicator" aria-hidden="true"></span>
    </th>
    <?php
}

function tct_path_to_file_uri(string $path): string
{
    $path = str_replace('\\', '/', $path);
    if (preg_match('#^[A-Za-z]:/#', $path)) {
        return 'file:///' . $path;
    }

    return 'file://' . $path;
}
