<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/transcript_scan.php';

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

$parentId = isset($payload['id']) ? (string) $payload['id'] : '';
$subId = isset($payload['sub']) ? (string) $payload['sub'] : '';
if ($parentId === '' || !tct_is_uuid($parentId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid chat id']);
    exit;
}
if ($subId !== '' && !tct_is_uuid($subId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid subagent id']);
    exit;
}

$subParam = $subId !== '' ? $subId : null;
if (!tct_delete_transcript_file($config, $parentId, $subParam)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Could not delete transcript file']);
    exit;
}

echo json_encode([
    'ok' => true,
    'id' => $parentId,
    'sub' => $subId,
]);
