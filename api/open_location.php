<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/plan_scan.php';
require_once dirname(__DIR__) . '/lib/transcript_scan.php';
require_once dirname(__DIR__) . '/lib/rule_scan.php';
require_once dirname(__DIR__) . '/lib/open_shell.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$config = require dirname(__DIR__) . '/config.php';
$raw = file_get_contents('php://input');
$payload = json_decode($raw !== false ? $raw : '', true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']);
    exit;
}

$kind = isset($payload['kind']) ? (string) $payload['kind'] : '';
$ok = false;

if ($kind === 'plan') {
    $basename = isset($payload['f']) ? tct_sanitize_plan_basename((string) $payload['f']) : '';
    if ($basename === '' || !tct_plan_filename_valid($basename)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid plan file']);
        exit;
    }
    $ok = tct_open_location_for_plan($config, $basename);
} elseif ($kind === 'rule') {
    $basename = isset($payload['f']) ? tct_sanitize_rule_basename((string) $payload['f']) : '';
    if ($basename === '' || !tct_rule_filename_valid($basename)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid rule file']);
        exit;
    }
    $ok = tct_open_location_for_rule($config, $basename);
} elseif ($kind === 'chat') {
    $parentId = isset($payload['id']) ? (string) $payload['id'] : '';
    $subId = isset($payload['sub']) ? (string) $payload['sub'] : '';
    if ($parentId === '' || !tct_is_uuid($parentId)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid transcript id']);
        exit;
    }
    if ($subId !== '' && !tct_is_uuid($subId)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid subagent id']);
        exit;
    }
    $ok = tct_open_location_for_chat($config, $parentId, $subId !== '' ? $subId : null);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid kind']);
    exit;
}

if (!$ok) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Could not open Explorer. Use Copy path, or see hints below.',
        'hints' => tct_open_location_debug_hints(),
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'hint' => 'If Explorer did not appear on your screen, use Copy path or check Laragon is not running Apache as a Windows service.',
]);
