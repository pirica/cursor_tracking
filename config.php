<?php
declare(strict_types=1);

/**
 * Local Cursor transcript tracker — defaults; overrides in data/local_config.json (Config page).
 */
require_once __DIR__ . '/lib/config_defaults.php';
require_once __DIR__ . '/lib/local_config.php';

$defaults = tct_builtin_config_defaults();

return tct_merge_app_config($defaults);
