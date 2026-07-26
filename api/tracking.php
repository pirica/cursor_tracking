<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/transcript_scan.php';

header('Content-Type: application/json; charset=utf-8');

$config = require dirname(__DIR__) . '/config.php';
$trackingFile = (string) ($config['tracking_file'] ?? '');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $parentId = isset($_GET['id']) ? (string) $_GET['id'] : '';
    $subId = isset($_GET['sub']) ? (string) $_GET['sub'] : '';
    if ($parentId === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing id']);
        exit;
    }
    $key = tct_tracking_key($parentId, $subId !== '' ? $subId : null);
    $file = tct_resolve_transcript_file($config, $parentId, $subId !== '' ? $subId : null);
    $all = tct_load_tracking($trackingFile);
    $meta = $file !== null ? tct_parse_jsonl_metadata($file) : [];
    $row = tct_resolve_tracking($all, $key, $meta);

    echo json_encode(['ok' => true, 'key' => $key, 'tracking' => $row]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

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
    echo json_encode(['ok' => false, 'error' => 'Invalid parent id']);
    exit;
}
if ($subId !== '' && !tct_is_uuid($subId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid sub id']);
    exit;
}

$file = tct_resolve_transcript_file($config, $parentId, $subId !== '' ? $subId : null);
if ($file === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Transcript not found']);
    exit;
}

$key = tct_tracking_key($parentId, $subId !== '' ? $subId : null);
$all = tct_load_tracking($trackingFile);

$status = isset($payload['status']) ? (string) $payload['status'] : 'open';
if (!in_array($status, ['open', 'done', 'archived'], true)) {
    $status = 'open';
}

$all[$key] = [
    'starred' => !empty($payload['starred']),
    'status' => $status,
    'notes' => isset($payload['notes']) ? (string) $payload['notes'] : '',
    'title_override' => isset($payload['title_override']) ? (string) $payload['title_override'] : '',
    'updated_at' => date('c'),
];

if (!tct_save_tracking($trackingFile, $all)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save tracking']);
    exit;
}

echo json_encode([
    'ok' => true,
    'key' => $key,
    'tracking' => tct_merge_tracking_defaults($all[$key]),
]);
