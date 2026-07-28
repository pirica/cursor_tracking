<?php
declare(strict_types=1);

require_once __DIR__ . '/html.php';
require_once __DIR__ . '/transcript_scan.php';

/**
 * Resolve a Cursor cloud agent id (bc-…) from a transcript uuid or string containing one.
 */
function tct_cursor_bc_id_from_string(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('/^bc-[0-9a-f-]{36}$/i', $raw)) {
        return $raw;
    }
    if (tct_is_uuid($raw)) {
        return 'bc-' . $raw;
    }
    if (preg_match('/(bc-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $raw, $m)) {
        return $m[1];
    }
    if (preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $raw, $m)) {
        return 'bc-' . $m[1];
    }

    return '';
}

function tct_render_cursor_open_links(string $rawId): void
{
    $bcId = tct_cursor_bc_id_from_string($rawId);
    if ($bcId === '') {
        return;
    }

    $agentsUrl = 'https://cursor.com/agents/' . rawurlencode($bcId);
    $cursorUrl = 'https://cursor.com/background-agent?bcId=' . rawurlencode($bcId);
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
