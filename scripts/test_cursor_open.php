<?php
declare(strict_types=1);

/**
 * Regression checks for Cursor open-link resolution (CLI).
 *
 * Usage: php scripts/test_cursor_open.php
 */

require_once dirname(__DIR__) . '/lib/cursor_open.php';

$failures = 0;

function tct_test_assert(bool $ok, string $label): void
{
    global $failures;
    if ($ok) {
        echo "[OK] {$label}\n";
        return;
    }
    $failures++;
    echo "[FAIL] {$label}\n";
}

$uuid = '03d4380e-8c8d-47bd-9d05-902fa631bf50';
$bc = 'bc-9e6ca11e-8fe7-4588-bd63-f52e8fb50793';

tct_test_assert(
    tct_cursor_bc_id_from_string($uuid) === '',
    'bare transcript uuid must not become bc-id'
);

tct_test_assert(
    tct_cursor_bc_id_from_string($bc) === strtolower($bc),
    'explicit bc-id is preserved'
);

tct_test_assert(
    tct_cursor_bc_id_from_string('cursor_transcript_tracker_443b4222.plan.md') === '',
    'plan basename without bc-id returns empty'
);

$blob = 'see [Review](' . $bc . '#changes) for details';
tct_test_assert(
    tct_cursor_bc_id_from_string($blob) === strtolower($bc),
    'bc-id embedded in text is extracted'
);

$tmp = tempnam(sys_get_temp_dir(), 'tct-bc-');
if ($tmp !== false) {
    file_put_contents($tmp, '{"role":"user","text":"agent ' . $bc . '"}\n');
    tct_test_assert(
        tct_cursor_bc_id_from_file($tmp) === strtolower($bc),
        'bc-id is read from transcript-like file'
    );
    unlink($tmp);
}

[$webLocal, $cursorLocal] = tct_cursor_open_urls_for_transcript_id($uuid);
tct_test_assert(
    $webLocal === 'chat.php?id=' . rawurlencode($uuid) && $cursorLocal === $webLocal,
    'local transcript maps to chat.php session viewer'
);

[$webCloud, $cursorCloud] = tct_cursor_open_urls_for_bc_id($bc);
tct_test_assert(
    strpos($webCloud, 'cursor.com/agents/' . rawurlencode(strtolower($bc))) !== false,
    'cloud bc-id maps to cursor.com agents URL'
);
tct_test_assert(
    strpos($cursorCloud, 'background-agent?bcId=') !== false,
    'cloud bc-id maps to background-agent URL'
);

$config = require dirname(__DIR__) . '/config.php';
$planPath = rtrim((string) ($config['plans_dir'] ?? ''), '\\/') . DIRECTORY_SEPARATOR . 'cursor_transcript_tracker_443b4222.plan.md';
if (is_file($planPath)) {
    $resolved = tct_resolve_transcript_id_for_plan($config, 'cursor_transcript_tracker_443b4222.plan.md', $planPath);
    tct_test_assert($resolved !== '', 'plan resolves to a transcript id via mtime or jsonl search');
    [$planWeb] = tct_cursor_resolve_open_urls('cursor_transcript_tracker_443b4222.plan.md', $planPath, $config);
    tct_test_assert($planWeb !== '', 'plan detail yields open-in-web URL');
} else {
    echo "[SKIP] plan fixture not on disk: {$planPath}\n";
}

if ($failures > 0) {
    fwrite(STDERR, "\n{$failures} test(s) failed.\n");
    exit(1);
}

echo "\nAll cursor_open tests passed.\n";
exit(0);
