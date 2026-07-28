<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/transcript_scan.php';
require_once __DIR__ . '/lib/transcript_parse.php';
require_once __DIR__ . '/lib/cursor_open.php';
require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/chats_ui.php';

$config = require __DIR__ . '/config.php';
$trackingFile = (string) ($config['tracking_file'] ?? '');
$tracking = tct_load_tracking($trackingFile);

$parentId = isset($_GET['id']) ? (string) $_GET['id'] : '';
$subId = isset($_GET['sub']) ? (string) $_GET['sub'] : '';

$file = null;
if ($parentId !== '' && tct_is_uuid($parentId)) {
    $file = tct_resolve_transcript_file($config, $parentId, $subId !== '' ? $subId : null);
}

$notFound = $file === null;
$key = $notFound ? '' : tct_tracking_key($parentId, $subId !== '' ? $subId : null);
$meta = $notFound ? ['title' => ''] : tct_parse_jsonl_metadata($file);
$track = $notFound
    ? tct_merge_tracking_defaults([])
    : tct_resolve_tracking($tracking, $key, $meta);
$messages = $notFound ? [] : tct_parse_jsonl_messages($file);
$displayTitle = $notFound ? 'Not found' : tct_display_title($meta, $track);
$fileMtime = (!$notFound && $file !== null) ? (filemtime($file) ?: 0) : 0;
$fileSize = (!$notFound && $file !== null) ? (filesize($file) ?: 0) : 0;

tct_render_header($displayTitle, 'chats', $config);
?>
    <p class="back-link"><a href="chats.php">← All transcripts</a></p>

    <?php if ($notFound): ?>
        <div class="alert-error">Transcript not found or invalid id.</div>
    <?php else: ?>
    <?php if ($fileMtime > 0): ?>
        <p class="plan-file-meta">
            File modified: <strong><?= tct_h(tct_format_mtime($fileMtime)) ?></strong>
            · Size: <?= tct_h(tct_format_bytes($fileSize)) ?>
        </p>
        <div class="plan-detail-actions row-actions plan-detail-actions-row">
            <?php tct_render_chat_file_actions($config, $parentId, $subId, 'chats.php', 'Delete file'); ?>
        </div>
        <?php tct_render_cursor_open_links($parentId, $file, $config); ?>
    <?php endif; ?>
    <div class="chat-head">
        <h2 class="page-title"><?= tct_h($displayTitle) ?></h2>
        <p class="uuid">
            <?php if ($subId !== ''): ?>
                Parent <?= tct_h($parentId) ?> · Subagent <?= tct_h($subId) ?>
            <?php else: ?>
                <?= tct_h($parentId) ?>
            <?php endif; ?>
        </p>
        <?php if (!empty($meta['started_at'])): ?>
            <p class="subtitle">Started: <?= tct_h((string) $meta['started_at']) ?></p>
        <?php endif; ?>
    </div>

    <div class="chat-layout">
        <section class="transcript-panel" aria-label="Transcript">
            <?php foreach ($messages as $msg):
                if (($msg['kind'] ?? '') === 'turn_ended'):
                    $st = (string) ($msg['status'] ?? '');
                    $err = (string) ($msg['error'] ?? '');
                    ?>
                    <div class="msg-turn">
                        Turn ended: <strong><?= tct_h($st !== '' ? $st : 'unknown') ?></strong>
                        <?php if ($err !== ''): ?> — <?= tct_h($err) ?><?php endif; ?>
                    </div>
                <?php continue; endif;
                $role = (string) ($msg['role'] ?? '');
                $class = $role === 'user' ? 'msg-user' : 'msg-assistant';
                ?>
                <article class="msg <?= tct_h($class) ?>">
                    <div class="msg-role"><?= tct_h($role) ?></div>
                    <div class="msg-body"><?= tct_format_message_html((string) ($msg['text'] ?? '')) ?></div>
                    <?php if (!empty($msg['tools']) && is_array($msg['tools'])): ?>
                        <?php foreach ($msg['tools'] as $tool): ?>
                            <details class="tool-block">
                                <summary>Tool: <?= tct_h((string) ($tool['name'] ?? 'tool')) ?></summary>
                                <pre><?= tct_h(json_encode($tool['input'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '') ?></pre>
                            </details>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>

        <aside class="tracking-panel">
            <h2>Tracking</h2>
            <form id="tracking-form"
                  data-id="<?= tct_h($parentId) ?>"
                  data-sub="<?= tct_h($subId) ?>">
                <label for="track-title">Custom title</label>
                <input type="text" id="track-title" name="title_override"
                       value="<?= tct_h($track['title_override']) ?>"
                       placeholder="Optional override">

                <label for="track-status">Status</label>
                <select id="track-status" name="status">
                    <option value="open"<?= $track['status'] === 'open' ? ' selected' : '' ?>>Open</option>
                    <option value="done"<?= $track['status'] === 'done' ? ' selected' : '' ?>>Done</option>
                    <option value="archived"<?= $track['status'] === 'archived' ? ' selected' : '' ?>>Archived</option>
                </select>

                <label><input type="checkbox" id="track-starred" name="starred"<?= $track['starred'] ? ' checked' : '' ?>> Starred</label>

                <label for="track-notes">Notes</label>
                <textarea id="track-notes" name="notes" placeholder="Your notes…"><?= tct_h($track['notes']) ?></textarea>

                <button type="submit" class="btn">Save tracking</button>
                <p id="save-status" class="save-status" role="status"></p>
            </form>
        </aside>
    </div>
    <?php endif; ?>
<?php
tct_render_footer();
