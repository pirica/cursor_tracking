<?php
declare(strict_types=1);

/**
 * Built-in path defaults when data/local_config.json is absent or incomplete.
 *
 * @return array{project_label: string, transcripts_dir: string, plans_dir: string, rules_dir: string, tracking_file: string}
 */
function tct_builtin_config_defaults(): array
{
    $appRoot = dirname(__DIR__);
    $trackingFile = $appRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'tracking.json';

    $home = getenv('HOME');
    if ($home === false || $home === '') {
        $home = getenv('USERPROFILE') ?: '';
    }

    $cursorDir = $home !== ''
        ? $home . DIRECTORY_SEPARATOR . '.cursor'
        : '';

    $plansDir = $cursorDir !== ''
        ? $cursorDir . DIRECTORY_SEPARATOR . 'plans'
        : '';

    $rulesDir = $appRoot . DIRECTORY_SEPARATOR . '.cursor' . DIRECTORY_SEPARATOR . 'rules';

    $transcriptsDir = tct_guess_agent_transcripts_dir($home);

    $label = basename($appRoot) . ' (Cursor)';

    return [
        'project_label' => $label,
        'transcripts_dir' => $transcriptsDir,
        'plans_dir' => $plansDir,
        'rules_dir' => $rulesDir,
        'tracking_file' => $trackingFile,
    ];
}

function tct_guess_agent_transcripts_dir(string $home): string
{
    if ($home === '') {
        return '';
    }

    $projectsRoot = $home . DIRECTORY_SEPARATOR . '.cursor' . DIRECTORY_SEPARATOR . 'projects';
    if (!is_dir($projectsRoot)) {
        return $projectsRoot . DIRECTORY_SEPARATOR . 'workspace' . DIRECTORY_SEPARATOR . 'agent-transcripts';
    }

    $preferred = [
        $projectsRoot . DIRECTORY_SEPARATOR . 'workspace' . DIRECTORY_SEPARATOR . 'agent-transcripts',
    ];

    $appRoot = dirname(__DIR__);
    $workspaceName = basename($appRoot);
    if ($workspaceName !== '' && $workspaceName !== '.' && $workspaceName !== '..') {
        $preferred[] = $projectsRoot . DIRECTORY_SEPARATOR . $workspaceName . DIRECTORY_SEPARATOR . 'agent-transcripts';
    }

    foreach ($preferred as $path) {
        if (is_dir($path)) {
            return $path;
        }
    }

    $found = tct_find_first_agent_transcripts_dir($projectsRoot);
    if ($found !== '') {
        return $found;
    }

    return $preferred[0];
}

function tct_find_first_agent_transcripts_dir(string $projectsRoot): string
{
    if (!is_dir($projectsRoot)) {
        return '';
    }

    $entries = scandir($projectsRoot);
    if ($entries === false) {
        return '';
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $candidate = $projectsRoot . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'agent-transcripts';
        if (is_dir($candidate)) {
            return $candidate;
        }
    }

    return '';
}
