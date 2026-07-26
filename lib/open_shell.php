<?php
declare(strict_types=1);

require_once __DIR__ . '/plan_scan.php';
require_once __DIR__ . '/transcript_scan.php';

function tct_shell_function_disabled(string $name): bool
{
    $disabled = ini_get('disable_functions');
    if (!is_string($disabled) || trim($disabled) === '') {
        return false;
    }
    $list = array_map('trim', explode(',', strtolower($disabled)));

    return in_array(strtolower($name), $list, true);
}

function tct_windows_quote_path(string $path): string
{
    $path = str_replace('/', '\\', $path);

    return '"' . str_replace('"', '""', $path) . '"';
}

function tct_shell_run_detached(string $cmd): bool
{
    if (!tct_shell_function_disabled('exec') && function_exists('exec')) {
        @exec($cmd, $unused, $code);

        return true;
    }

    if (!tct_shell_function_disabled('popen') && function_exists('popen')) {
        $handle = @popen($cmd, 'r');
        if ($handle !== false) {
            pclose($handle);

            return true;
        }
    }

    if (!tct_shell_function_disabled('proc_open') && function_exists('proc_open')) {
        $desc = [
            0 => ['file', 'NUL', 'r'],
            1 => ['file', 'NUL', 'w'],
            2 => ['file', 'NUL', 'w'],
        ];
        $proc = @proc_open($cmd, $desc, $pipes, null, null, ['bypass_shell' => true]);
        if (is_resource($proc)) {
            proc_close($proc);

            return true;
        }
    }

    return false;
}

/**
 * Launch Explorer on the interactive desktop (Windows).
 */
function tct_windows_explorer_select_file(string $realFile): bool
{
    $realFile = str_replace('/', '\\', $realFile);
    if (!is_file($realFile)) {
        return false;
    }
    $quoted = tct_windows_quote_path($realFile);
    $exploreFolder = tct_windows_quote_path(dirname($realFile));

    if (class_exists('COM')) {
        try {
            $wsh = new COM('WScript.Shell');
            $wsh->Run('explorer.exe /select,' . $quoted, 1, false);

            return true;
        } catch (\Throwable $e) {
            try {
                $shell = new COM('Shell.Application');
                $shell->ShellExecute('explorer.exe', '/select,' . $quoted, '', 'open', 1);

                return true;
            } catch (\Throwable $e2) {
                // fall through to CLI
            }
        }
    }

    if (tct_windows_run_vbs_explorer_select($realFile)) {
        return true;
    }

    if (tct_windows_schtasks_explorer_select($realFile)) {
        return true;
    }

    $commands = [
        'explorer.exe /select,' . $quoted,
        'cmd.exe /c start "" explorer.exe /select,' . $quoted,
        'explorer.exe ' . $exploreFolder,
        'cmd.exe /c start "" explorer.exe ' . $exploreFolder,
    ];
    foreach ($commands as $cmd) {
        if (tct_shell_run_detached($cmd)) {
            return true;
        }
    }

    return false;
}

function tct_windows_run_vbs_explorer_select(string $realFile): bool
{
    $realFile = str_replace('/', '\\', $realFile);
    $vbs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tracking_cursor_open_' . md5($realFile) . '.vbs';
  $content = <<<'VBS'
Set args = WScript.Arguments
path = args(0)
CreateObject("WScript.Shell").Run "explorer.exe /select,""" & path & """", 1, False
VBS;
    if (@file_put_contents($vbs, $content) === false) {
        return false;
    }
    $cmd = 'wscript.exe //nologo ' . tct_windows_quote_path($vbs) . ' ' . tct_windows_quote_path($realFile);

    return tct_shell_run_detached($cmd);
}

/**
 * Run Explorer in the interactive user session (helps when PHP runs as a service).
 */
function tct_windows_schtasks_explorer_select(string $realFile): bool
{
    if (tct_shell_function_disabled('exec') || !function_exists('exec')) {
        return false;
    }
    $realFile = str_replace('/', '\\', $realFile);
    if (!is_file($realFile)) {
        return false;
    }
    $user = getenv('USERNAME');
    if (!is_string($user) || $user === '') {
        return false;
    }
    $taskName = 'TCTOpen_' . substr(md5($realFile . (string) microtime(true)), 0, 12);
    $quoted = tct_windows_quote_path($realFile);
    $tr = 'explorer.exe /select,' . $quoted;
    $create = 'schtasks /Create /TN ' . escapeshellarg($taskName)
        . ' /TR ' . escapeshellarg($tr)
        . ' /SC ONCE /ST 00:00 /F /RU ' . escapeshellarg($user)
        . ' /IT 2>NUL';
    $run = 'schtasks /Run /TN ' . escapeshellarg($taskName) . ' 2>NUL';
    $del = 'schtasks /Delete /TN ' . escapeshellarg($taskName) . ' /F 2>NUL';
    @exec($create, $outCreate, $codeCreate);
    if ($codeCreate !== 0) {
        return false;
    }
    @exec($run, $outRun, $codeRun);
    @exec($del);
    if ($codeRun === 0) {
        return true;
    }

    return false;
}

/**
 * Open a folder in the OS file manager (local Laragon / dev only).
 */
function tct_shell_open_folder(string $dir): bool
{
    if ($dir === '' || !is_dir($dir)) {
        return false;
    }
    $real = realpath($dir);
    if ($real === false) {
        return false;
    }

    if (DIRECTORY_SEPARATOR === '\\') {
        $quoted = tct_windows_quote_path($real);

        return tct_shell_run_detached('explorer.exe ' . $quoted);
    }
    if (PHP_OS_FAMILY === 'Darwin') {
        return tct_shell_run_detached('open ' . escapeshellarg($real));
    }

    return tct_shell_run_detached('xdg-open ' . escapeshellarg($real));
}

function tct_shell_reveal_file(string $filePath): bool
{
    if (!is_file($filePath)) {
        return false;
    }
    $real = realpath($filePath);
    if ($real === false) {
        return false;
    }

    if (DIRECTORY_SEPARATOR === '\\') {
        return tct_windows_explorer_select_file($real);
    }

    return tct_shell_open_folder(dirname($real));
}

/**
 * @param array<string, mixed> $config
 */
function tct_open_location_for_plan(array $config, string $basename): bool
{
    $file = tct_resolve_plan_file($config, $basename);

    return $file !== null && tct_shell_reveal_file($file);
}

/**
 * @param array<string, mixed> $config
 */
function tct_open_location_for_chat(array $config, string $parentId, ?string $subId): bool
{
    $file = tct_resolve_transcript_file($config, $parentId, $subId);

    return $file !== null && tct_shell_reveal_file($file);
}

/**
 * @return list<string>
 */
function tct_open_location_debug_hints(): array
{
    $hints = [];
    if (!class_exists('COM')) {
        $hints[] = 'PHP COM extension (com_dotnet) is off — enable extension=com_dotnet in php.ini for best results on Windows.';
    }
    if (tct_shell_function_disabled('exec')) {
        $hints[] = 'exec() is disabled in php.ini disable_functions.';
    }
    $hints[] = 'If Explorer still does not show, Laragon Apache may not be on your desktop session — stop “Apache” Windows service and start Apache only from the Laragon app, or use Copy path.';

    return $hints;
}
