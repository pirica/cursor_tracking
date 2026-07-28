<?php
declare(strict_types=1);

require_once __DIR__ . '/html.php';
require_once __DIR__ . '/transcript_scan.php';

/**
 * Resolve an explicit Cursor cloud agent id (bc-…) embedded in text — never from a bare transcript folder uuid.
 */
function tct_cursor_bc_id_from_string(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('/^bc-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $raw)) {
        return strtolower($raw);
    }
    if (preg_match('/(bc-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $raw, $m)) {
        return strtolower($m[1]);
    }

    return '';
}

/**
 * @return array{0: string, 1: string} [webUrl, cursorUrl]
 */
function tct_cursor_open_urls_for_bc_id(string $bcId): array
{
    $bcId = tct_cursor_bc_id_from_string($bcId);
    if ($bcId === '') {
        return ['', ''];
    }

    return [
        'https://cursor.com/agents/' . rawurlencode($bcId),
        'https://cursor.com/background-agent?bcId=' . rawurlencode($bcId),
    ];
}

/**
 * Local agent session transcript in this app (not cursor.com cloud agent).
 *
 * @return array{0: string, 1: string} [webUrl, cursorUrl]
 */
function tct_cursor_open_urls_for_transcript_id(string $transcriptId): array
{
    if (!tct_is_uuid($transcriptId)) {
        return ['', ''];
    }
    $q = 'chat.php?id=' . rawurlencode($transcriptId);

    return [$q, $q];
}

function tct_cursor_bc_id_from_file(string $path, int $maxBytes = 2097152): string
{
    if ($path === '' || !is_readable($path)) {
        return '';
    }
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return '';
    }
    $chunk = fread($handle, $maxBytes);
    fclose($handle);
    if (!is_string($chunk) || $chunk === '') {
        return '';
    }

    if (!preg_match_all('/(bc-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $chunk, $matches)) {
        return '';
    }
    $last = end($matches[1]);

    return is_string($last) ? strtolower($last) : '';
}

/**
 * @param array<string, mixed> $config
 */
function tct_find_transcript_id_in_jsonl_dir(array $config, string $needle): string
{
    $needle = trim($needle);
    if ($needle === '') {
        return '';
    }
    $dir = rtrim((string) ($config['transcripts_dir'] ?? ''), '\\/');
    if ($dir === '' || !is_dir($dir)) {
        return '';
    }

    foreach (tct_scan_transcripts($config) as $chat) {
        $file = (string) ($chat['file'] ?? '');
        if ($file === '' || !is_readable($file)) {
            continue;
        }
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            continue;
        }
        $found = false;
        while (!$found && ($line = fgets($handle)) !== false) {
            if (stripos($line, $needle) !== false) {
                $found = true;
            }
        }
        fclose($handle);
        if ($found) {
            return (string) ($chat['id'] ?? '');
        }
    }

    return '';
}

/**
 * @param array<string, mixed> $config
 */
function tct_find_transcript_id_near_mtime(array $config, int $targetMtime): string
{
    if ($targetMtime <= 0) {
        return '';
    }
    $bestId = '';
    $bestDelta = PHP_INT_MAX;
    foreach (tct_scan_transcripts($config) as $chat) {
        $mtime = (int) ($chat['mtime'] ?? 0);
        $delta = abs($mtime - $targetMtime);
        if ($delta < $bestDelta) {
            $bestDelta = $delta;
            $bestId = (string) ($chat['id'] ?? '');
        }
    }
    // Why: Plan files are written in the same session as the parent transcript; 48h covers timezone/delay edge cases.
    if ($bestId === '' || $bestDelta > 172800) {
        return '';
    }

    return $bestId;
}

/**
 * @param array<string, mixed> $config
 */
function tct_resolve_transcript_id_for_plan(array $config, string $basename, string $planPath): string
{
    $basename = trim($basename);
  if ($basename === '') {
      return '';
  }
  if (preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $basename, $m)) {
      return strtolower($m[1]);
  }
  $byName = tct_find_transcript_id_in_jsonl_dir($config, $basename);
  if ($byName !== '') {
      return $byName;
  }
  $mtime = is_readable($planPath) ? (filemtime($planPath) ?: 0) : 0;

  return tct_find_transcript_id_near_mtime($config, $mtime);
}

/**
 * @param array<string, mixed> $config
 */
function tct_resolve_transcript_id_for_rule(array $config, string $basename, string $rulePath): string
{
    $basename = trim($basename);
    if ($basename === '') {
        return '';
    }
    if (preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $basename, $m)) {
        return strtolower($m[1]);
    }
    $byName = tct_find_transcript_id_in_jsonl_dir($config, $basename);
    if ($byName !== '') {
        return $byName;
    }
    $mtime = is_readable($rulePath) ? (filemtime($rulePath) ?: 0) : 0;

    return tct_find_transcript_id_near_mtime($config, $mtime);
}

/**
 * @param array<string, mixed>|null $config
 */
function tct_cursor_resolve_open_urls(string $rawId, ?string $contentFilePath = null, ?array $config = null): array
{
    $bcId = tct_cursor_bc_id_from_string($rawId);
    if ($bcId === '' && $contentFilePath !== null && $contentFilePath !== '') {
        $bcId = tct_cursor_bc_id_from_file($contentFilePath);
    }

    $transcriptId = tct_is_uuid($rawId) ? strtolower($rawId) : '';
    if ($transcriptId === '' && $config !== null && $contentFilePath !== null && $contentFilePath !== '') {
        if (preg_match('/\.plan\.md$/i', $rawId)) {
            $transcriptId = tct_resolve_transcript_id_for_plan($config, $rawId, $contentFilePath);
        } elseif (preg_match('/\.mdc$/i', $rawId)) {
            $transcriptId = tct_resolve_transcript_id_for_rule($config, $rawId, $contentFilePath);
        }
    }

    if ($bcId === '' && $transcriptId !== '' && $contentFilePath === null && $config !== null) {
        $file = tct_resolve_transcript_file($config, $transcriptId, null);
        if ($file !== null) {
            $bcId = tct_cursor_bc_id_from_file($file);
        }
    } elseif ($bcId === '' && $transcriptId !== '' && $contentFilePath !== null && $contentFilePath !== '') {
        $file = tct_resolve_transcript_file($config ?? [], $transcriptId, null);
        if ($file !== null) {
            $bcId = tct_cursor_bc_id_from_file($file);
        }
    }

    if ($bcId !== '') {
        return tct_cursor_open_urls_for_bc_id($bcId);
    }
    if ($transcriptId !== '') {
        return tct_cursor_open_urls_for_transcript_id($transcriptId);
    }

    return ['', ''];
}

/**
 * @param array<string, mixed>|null $config
 */
function tct_render_cursor_open_links(string $rawId, ?string $contentFilePath = null, ?array $config = null): void
{
    [$agentsUrl, $cursorUrl] = tct_cursor_resolve_open_urls($rawId, $contentFilePath, $config);

    if ($agentsUrl === '' || $cursorUrl === '') {
        return;
    }

    $webDark = 'https://cursor.com/assets/images/open-in-web-dark.png';
    $webLight = 'https://cursor.com/assets/images/open-in-web-light.png';
    $cursorDark = 'https://cursor.com/assets/images/open-in-cursor-dark.png';
    $cursorLight = 'https://cursor.com/assets/images/open-in-cursor-light.png';
    ?>
<br>
<div class="cursor-open-links" dir="auto">
    <a href="<?= tct_h($agentsUrl) ?>" rel="nofollow noopener noreferrer" target="_blank">
        <picture>
            <source media="(prefers-color-scheme: dark)" srcset="<?= tct_h($webDark) ?>">
            <source media="(prefers-color-scheme: light)" srcset="<?= tct_h($webLight) ?>">
            <img src="<?= tct_h($webDark) ?>" alt="Open in Web" width="114" height="28" loading="lazy" decoding="async">
        </picture>
    </a>
    <a href="<?= tct_h($cursorUrl) ?>" rel="nofollow noopener noreferrer" target="_blank">
        <picture>
            <source media="(prefers-color-scheme: dark)" srcset="<?= tct_h($cursorDark) ?>">
            <source media="(prefers-color-scheme: light)" srcset="<?= tct_h($cursorLight) ?>">
            <img src="<?= tct_h($cursorDark) ?>" alt="Open in Cursor" width="131" height="28" loading="lazy" decoding="async">
        </picture>
    </a>
</div>
    <?php
}
