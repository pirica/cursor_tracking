<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/plan_scan.php';

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

$basename = isset($payload['f']) ? tct_sanitize_plan_basename((string) $payload['f']) : '';
if ($basename === '' || !tct_plan_filename_valid($basename)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid plan filename']);
    exit;
}

if (!tct_delete_plan_file($config, $basename)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Could not delete plan file']);
    exit;
}

echo json_encode(['ok' => true, 'f' => $basename]);
