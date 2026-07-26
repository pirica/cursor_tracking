<?php
declare(strict_types=1);

/**
 * Local diagnostic for Open location (run in browser only).
 * http://localhost/cursor_tracking/scripts/open_location_diag.php
 */
header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/lib/open_shell.php';

echo "Open location diagnostics\n";
echo "=========================\n\n";
echo 'PHP SAPI: ' . PHP_SAPI . "\n";
echo 'OS: ' . PHP_OS_FAMILY . "\n";
echo 'COM class exists: ' . (class_exists('COM') ? 'yes' : 'no') . "\n";
echo 'exec disabled: ' . (tct_shell_function_disabled('exec') ? 'yes' : 'no') . "\n";
echo 'popen disabled: ' . (tct_shell_function_disabled('popen') ? 'yes' : 'no') . "\n";
echo 'proc_open disabled: ' . (tct_shell_function_disabled('proc_open') ? 'yes' : 'no') . "\n\n";

$testFile = getenv('USERPROFILE') . '\\.cursor\\plans\\cursor_transcript_tracker_443b4222.plan.md';
$testFile = str_replace('/', '\\', $testFile);
echo "Test file: $testFile\n";
echo 'File exists: ' . (is_file($testFile) ? 'yes' : 'no') . "\n\n";

if (is_file($testFile)) {
    echo "Attempting tct_shell_reveal_file()...\n";
    $ok = tct_shell_reveal_file($testFile);
    echo 'Result: ' . ($ok ? 'true (command dispatched)' : 'false') . "\n\n";
}

echo "Hints:\n";
foreach (tct_open_location_debug_hints() as $h) {
    echo "  - $h\n";
}
