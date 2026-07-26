<?php
declare(strict_types=1);

/**
 * Local Cursor transcript tracker — defaults; overrides in data/local_config.json (Config page).
 */
$defaults = [
    'project_label' => 'it-management (Cursor)',
    'transcripts_dir' => 'C:\\Users\\NelsonSalvador\\.cursor\\projects\\c-Users-NelsonSalvador-Downloads-laragon-portable-www-it-management\\agent-transcripts',
    'plans_dir' => 'C:\\Users\\NelsonSalvador\\.cursor\\plans',
    'rules_dir' => 'C:\\Users\\NelsonSalvador\\Downloads\\laragon-portable\\www\\it-management\\.cursor\\rules',
    'tracking_file' => __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'tracking.json',
];

require_once __DIR__ . '/lib/local_config.php';

return tct_merge_app_config($defaults);
