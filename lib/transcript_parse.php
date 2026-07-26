<?php
declare(strict_types=1);

/**
 * Parse Cursor agent transcript JSONL lines.
 */

function tct_is_uuid(string $id): bool
{
    return (bool) preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $id);
}

function tct_tracking_key(string $parentId, ?string $subId = null): string
{
    if ($subId !== null && $subId !== '') {
        return $parentId . '/' . $subId;
    }

    return $parentId;
}

function tct_line_text_from_record(array $record): string
{
    $parts = [];
    if (!isset($record['message']['content']) || !is_array($record['message']['content'])) {
        return '';
    }
    foreach ($record['message']['content'] as $block) {
        if (!is_array($block)) {
            continue;
        }
        if (($block['type'] ?? '') === 'text' && isset($block['text']) && is_string($block['text'])) {
            $parts[] = $block['text'];
        }
    }

    return implode("\n", $parts);
}

function tct_extract_timestamp(string $text): ?string
{
    if (preg_match('/<timestamp>(.*?)<\/timestamp>/s', $text, $m)) {
        return trim($m[1]);
    }

    return null;
}

function tct_extract_user_query(string $text): ?string
{
    if (preg_match('/<user_query>\s*(.*?)\s*<\/user_query>/s', $text, $m)) {
        $q = trim($m[1]);
        if ($q !== '') {
            return $q;
        }
    }

    return null;
}

function tct_is_noise_user_text(string $text): bool
{
    if ($text === '') {
        return true;
    }
    if (strpos($text, '<available_subagent_types>') !== false) {
        return true;
    }
    if (strpos($text, '<agent_transcripts>') !== false && strpos($text, '<user_query>') === false) {
        return true;
    }
    if (preg_match('/^<timestamp>/', $text) && strpos($text, '<user_query>') === false) {
        return true;
    }

    return false;
}

function tct_truncate(string $s, int $max = 120): string
{
    if (mb_strlen($s) <= $max) {
        return $s;
    }

    return mb_substr($s, 0, $max - 1) . '…';
}

/**
 * @return array{
 *   title: string,
 *   started_at: ?string,
 *   user_messages: int,
 *   assistant_messages: int,
 *   line_count: int,
 *   last_turn_status: ?string,
 *   last_turn_error: ?string
 * }
 */
function tct_parse_jsonl_metadata(string $filePath): array
{
    $meta = [
        'title' => '',
        'started_at' => null,
        'user_messages' => 0,
        'assistant_messages' => 0,
        'line_count' => 0,
        'last_turn_status' => null,
        'last_turn_error' => null,
    ];

    if (!is_readable($filePath)) {
        $base = basename($filePath, '.jsonl');
        $meta['title'] = $base;

        return $meta;
    }

    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        $meta['title'] = basename($filePath, '.jsonl');

        return $meta;
    }

    $firstQuery = null;
    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $meta['line_count']++;
        $record = json_decode($line, true);
        if (!is_array($record)) {
            continue;
        }

        if (($record['type'] ?? '') === 'turn_ended') {
            $meta['last_turn_status'] = isset($record['status']) ? (string) $record['status'] : null;
            $meta['last_turn_error'] = isset($record['error']) ? (string) $record['error'] : null;
            continue;
        }

        $role = $record['role'] ?? '';
        if ($role === 'user') {
            $meta['user_messages']++;
            $text = tct_line_text_from_record($record);
            if ($meta['started_at'] === null) {
                $ts = tct_extract_timestamp($text);
                if ($ts !== null) {
                    $meta['started_at'] = $ts;
                }
            }
            if ($firstQuery === null) {
                $q = tct_extract_user_query($text);
                if ($q !== null && !tct_is_noise_user_text($q)) {
                    $firstQuery = $q;
                } elseif (!tct_is_noise_user_text($text) && tct_extract_user_query($text) === null && mb_strlen($text) < 500) {
                    $firstQuery = $text;
                }
            }
        } elseif ($role === 'assistant') {
            $meta['assistant_messages']++;
        }
    }
    fclose($handle);

    $id = basename($filePath, '.jsonl');
    if ($firstQuery !== null) {
        $meta['title'] = tct_truncate(preg_replace('/\s+/u', ' ', $firstQuery) ?? $firstQuery);
    } else {
        $meta['title'] = $id;
    }

    return $meta;
}

/**
 * @return list<array<string, mixed>>
 */
function tct_parse_jsonl_messages(string $filePath): array
{
    $messages = [];
    if (!is_readable($filePath)) {
        return $messages;
    }

    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        return $messages;
    }

    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $record = json_decode($line, true);
        if (!is_array($record)) {
            continue;
        }

        if (($record['type'] ?? '') === 'turn_ended') {
            $messages[] = [
                'kind' => 'turn_ended',
                'status' => $record['status'] ?? '',
                'error' => $record['error'] ?? '',
            ];
            continue;
        }

        $role = $record['role'] ?? '';
        if ($role !== 'user' && $role !== 'assistant') {
            continue;
        }

        $textParts = [];
        $toolUses = [];
        if (isset($record['message']['content']) && is_array($record['message']['content'])) {
            foreach ($record['message']['content'] as $block) {
                if (!is_array($block)) {
                    continue;
                }
                $type = $block['type'] ?? '';
                if ($type === 'text' && isset($block['text']) && is_string($block['text'])) {
                    $textParts[] = $block['text'];
                } elseif ($type === 'tool_use') {
                    $toolUses[] = [
                        'name' => (string) ($block['name'] ?? 'tool'),
                        'input' => $block['input'] ?? null,
                    ];
                }
            }
        }

        $messages[] = [
            'kind' => 'message',
            'role' => $role,
            'text' => implode("\n", $textParts),
            'tools' => $toolUses,
        ];
    }
    fclose($handle);

    return $messages;
}

function tct_format_message_html(string $text): string
{
    $q = tct_extract_user_query($text);
    if ($q !== null) {
        $text = $q;
    }
    $text = preg_replace('/<timestamp>.*?<\/timestamp>\s*/s', '', $text) ?? $text;
    $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return nl2br($escaped);
}
