<?php
/**
 * Check if exec() is enabled in PHP
 */

// Get disabled functions from php.ini
$disabledFunctions = explode(',', ini_get('disable_functions'));

// Normalize array: trim spaces and lowercase
$disabledFunctions = array_map('trim', array_map('strtolower', $disabledFunctions));

// Check if exec is in the disabled list
if (in_array('exec', $disabledFunctions, true)) {
    echo "exec() is DISABLED in this PHP configuration.";
} else {
    // Also check if function exists (in case it's removed by extensions)
    if (function_exists('exec')) {
        echo "exec() is ENABLED.";
    } else {
        echo "exec() function does not exist in this PHP build.";
    }
}
?>
