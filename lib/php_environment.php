<?php
declare(strict_types=1);

require_once __DIR__ . '/html.php';

function tct_php_environment_exec_enabled(): bool
{
    $disabledRaw = (string) ini_get('disable_functions');
    $disabledFunctions = array_filter(array_map('trim', explode(',', strtolower($disabledRaw))));

    return function_exists('exec') && !in_array('exec', $disabledFunctions, true);
}

/**
 * @return array{sapi: string, version: string, ini: string, exec_enabled: bool, exec_note: string}
 */
function tct_php_environment_summary(): array
{
    $disabledRaw = (string) ini_get('disable_functions');
    $disabledFunctions = array_filter(array_map('trim', explode(',', strtolower($disabledRaw))));
    $execDisabled = in_array('exec', $disabledFunctions, true);
    $execExists = function_exists('exec');

    if ($execExists && !$execDisabled) {
        $execNote = 'enabled (Open location API can use shell)';
    } elseif ($execDisabled) {
        $execNote = 'disabled via disable_functions';
    } else {
        $execNote = 'not available in this build';
    }

    return [
        'sapi' => PHP_SAPI,
        'version' => PHP_VERSION,
        'ini' => (string) php_ini_loaded_file(),
        'exec_enabled' => $execExists && !$execDisabled,
        'exec_note' => $execNote,
    ];
}

function tct_render_php_environment_summary(): void
{
    $env = tct_php_environment_summary();
    ?>
    <div class="php-env-summary">
        <p><strong>SAPI:</strong> <code><?= tct_h($env['sapi']) ?></code></p>
        <p><strong>PHP version:</strong> <code><?= tct_h($env['version']) ?></code></p>
        <p><strong>Loaded ini:</strong> <code class="config-path"><?= tct_h($env['ini']) ?></code></p>
        <p>
            <strong>exec():</strong>
            <?= tct_h($env['exec_note']) ?>
        </p>
    </div>
    <?php
}

function tct_render_phpinfo_page(): void
{
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>phpinfo()</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="app-body">
<main class="content">
    <p class="back-link"><a href="settings.php">← Config</a></p>
<?php
    phpinfo();
?>
</main>
</body>
</html>
    <?php
}
