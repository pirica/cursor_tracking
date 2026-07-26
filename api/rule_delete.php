<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/rule_scan.php';

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

$basename = isset($payload['f']) ? tct_sanitize_rule_basename((string) $payload['f']) : '';
if ($basename === '' || !tct_rule_filename_valid($basename)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid rule filename']);
    exit;
}

if (!tct_delete_rule_file($config, $basename)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Could not delete rule file']);
    exit;
}

echo json_encode(['ok' => true, 'f' => $basename]);
