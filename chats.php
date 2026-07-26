<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/transcript_scan.php';
require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/chats_ui.php';

$config = require __DIR__ . '/config.php';
$trackingFile = (string) ($config['tracking_file'] ?? '');
$tracking = tct_load_tracking($trackingFile);

$transcriptsDir = (string) ($config['transcripts_dir'] ?? '');
$dirMissing = $transcriptsDir === '' || !is_dir($transcriptsDir);
$chats = $dirMissing ? [] : tct_scan_transcripts($config);

tct_render_header('Chats', 'chats', $config);
?>
    <?php if ($dirMissing): ?>
        <div class="alert-error">
            Transcripts directory not found. Edit <code>config.php</code> and set <code>transcripts_dir</code>.
        </div>
    <?php endif; ?>

    <div class="toolbar">
        <input type="search" id="filter-search" placeholder="Search title, notes, UUID…" autocomplete="off">
        <select id="filter-status" aria-label="Tracking status">
            <option value="">All statuses</option>
            <option value="open">Open</option>
            <option value="done">Done</option>
            <option value="archived">Archived</option>
        </select>
        <label><input type="checkbox" id="filter-starred"> Starred only</label>
        <label><input type="checkbox" id="filter-hide-archived" checked> Hide archived</label>
    </div>

    <p class="stats-bar">
        <span id="visible-count"><?= count($chats) ?></span> of <?= count($chats) ?> chats shown
    </p>

    <div class="table-wrap">
        <?php if (count($chats) === 0): ?>
            <p class="empty-state">No transcripts found.</p>
        <?php else: ?>
        <table class="chat-table" id="chats-table" data-sortable-table="chats" data-default-sort="activity" data-default-dir="desc">
            <thead>
            <tr>
                <th style="width:2rem" scope="col" aria-label="Star"></th>
                <?php tct_sortable_th('Chat', 'chat'); ?>
                <?php tct_sortable_th('Activity', 'activity'); ?>
                <?php tct_sortable_th('Turn', 'turn'); ?>
                <?php tct_sortable_th('Msgs', 'msgs'); ?>
                <?php tct_sortable_th('Size', 'size'); ?>
                <?php tct_sortable_th('Status', 'status'); ?>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <?php foreach ($chats as $chat):
                $id = (string) $chat['id'];
                $key = tct_tracking_key($id);
                $meta = $chat['meta'];
                $track = tct_resolve_tracking($tracking, $key, $meta);
                $title = tct_display_title($meta, $track);
                $turnStatus = $meta['last_turn_status'] ?? null;
                $searchBlob = strtolower($id . ' ' . $title . ' ' . $track['notes']);
                $subCount = (int) $chat['subagent_count'];
                $msgTotal = (int) $meta['user_messages'] + (int) $meta['assistant_messages'];
                $turnSort = tct_turn_sort_value($turnStatus);
                $statusSort = tct_status_sort_value($track['status']);
                ?>
            <tbody class="chat-group">
                <tr data-chat-row
                    data-chat-parent="1"
                    data-search="<?= tct_h($searchBlob) ?>"
                    data-track-status="<?= tct_h($track['status']) ?>"
                    data-starred="<?= $track['starred'] ? '1' : '0' ?>"
                    data-sort-chat="<?= tct_h(mb_strtolower($title)) ?>"
                    data-sort-activity="<?= (int) $chat['mtime'] ?>"
                    data-sort-turn="<?= $turnSort ?>"
                    data-sort-msgs="<?= $msgTotal ?>"
                    data-sort-size="<?= (int) $chat['size'] ?>"
                    data-sort-status="<?= $statusSort ?>">
                    <td>
                        <button type="button" class="star-btn <?= $track['starred'] ? 'is-starred' : '' ?>"
                                data-quick-star
                                data-id="<?= tct_h($id) ?>"
                                data-sub=""
                                data-status="<?= tct_h($track['status']) ?>"
                                data-notes="<?= tct_h($track['notes']) ?>"
                                data-title-override="<?= tct_h($track['title_override']) ?>"
                                aria-pressed="<?= $track['starred'] ? 'true' : 'false' ?>"
                                title="Toggle star"><?= $track['starred'] ? '★' : '☆' ?></button>
                    </td>
                    <td class="row-title">
                        <?php if ($subCount > 0): ?>
                            <button type="button" class="expand-btn" data-expand="sub-<?= tct_h($id) ?>" aria-expanded="false" title="Show subagents">▶</button>
                        <?php endif; ?>
                        <a href="chat.php?id=<?= urlencode($id) ?>"><?= tct_h($title) ?></a>
                        <div class="row-meta"><?= tct_h($id) ?><?php if ($subCount > 0): ?> · <?= $subCount ?> subagent<?= $subCount === 1 ? '' : 's' ?><?php endif; ?></div>
                    </td>
                    <td>
                        <?php if (!empty($meta['started_at'])): ?>
                            <span title="First message"><?= tct_h((string) $meta['started_at']) ?></span><br>
                        <?php endif; ?>
                        <span class="row-meta"><?= tct_h(tct_format_mtime((int) $chat['mtime'])) ?></span>
                    </td>
                    <td>
                        <span class="badge <?= tct_h(tct_status_badge_class(tct_turn_status_label($turnStatus))) ?>">
                            <?= tct_h(tct_turn_status_label($turnStatus)) ?>
                        </span>
                    </td>
                    <td class="row-meta"><?= (int) $meta['user_messages'] ?>u / <?= (int) $meta['assistant_messages'] ?>a</td>
                    <td class="row-meta"><?= tct_h(tct_format_bytes((int) $chat['size'])) ?></td>
                    <td class="col-status">
                        <?php tct_render_row_status_select($track, $id, ''); ?>
                    </td>
                    <td class="col-actions">
                        <div class="row-actions">
                        <?php tct_render_chat_file_actions($config, $id, ''); ?>
                        </div>
                    </td>
                </tr>
            </tbody>
                <?php if ($subCount > 0): ?>
                <tbody id="sub-<?= tct_h($id) ?>" class="subagent-rows">
                    <?php foreach ($chat['subagents'] as $sub):
                        $subId = (string) $sub['id'];
                        $subKey = tct_tracking_key($id, $subId);
                        $subMeta = $sub['meta'];
                        $subTrack = tct_resolve_tracking($tracking, $subKey, $subMeta);
                        $subTitle = tct_display_title($subMeta, $subTrack);
                        $subSearch = strtolower($subId . ' ' . $subTitle . ' ' . $subTrack['notes']);
                        $subTurn = $subMeta['last_turn_status'] ?? null;
                        ?>
                        <tr data-chat-row
                            data-search="<?= tct_h($subSearch) ?>"
                            data-track-status="<?= tct_h($subTrack['status']) ?>"
                            data-starred="<?= $subTrack['starred'] ? '1' : '0' ?>">
                            <td>
                                <button type="button" class="star-btn <?= $subTrack['starred'] ? 'is-starred' : '' ?>"
                                        data-quick-star
                                        data-id="<?= tct_h($id) ?>"
                                        data-sub="<?= tct_h($subId) ?>"
                                        data-status="<?= tct_h($subTrack['status']) ?>"
                                        data-notes="<?= tct_h($subTrack['notes']) ?>"
                                        data-title-override="<?= tct_h($subTrack['title_override']) ?>"
                                        aria-pressed="<?= $subTrack['starred'] ? 'true' : 'false' ?>"><?= $subTrack['starred'] ? '★' : '☆' ?></button>
                            </td>
                            <td class="row-title">
                                <span class="badge badge-subagent">subagent</span>
                                <a href="chat.php?id=<?= urlencode($id) ?>&amp;sub=<?= urlencode($subId) ?>"><?= tct_h($subTitle) ?></a>
                                <div class="row-meta"><?= tct_h($subId) ?></div>
                            </td>
                            <td><span class="row-meta"><?= tct_h(tct_format_mtime((int) $sub['mtime'])) ?></span></td>
                            <td>
                                <span class="badge <?= tct_h(tct_status_badge_class(tct_turn_status_label($subTurn))) ?>">
                                    <?= tct_h(tct_turn_status_label($subTurn)) ?>
                                </span>
                            </td>
                            <td class="row-meta"><?= (int) $subMeta['user_messages'] ?>u / <?= (int) $subMeta['assistant_messages'] ?>a</td>
                            <td class="row-meta"><?= tct_h(tct_format_bytes((int) $sub['size'])) ?></td>
                            <td class="col-status">
                                <?php tct_render_row_status_select($subTrack, $id, $subId); ?>
                            </td>
                            <td class="col-actions">
                                <div class="row-actions">
                                <?php tct_render_chat_file_actions($config, $id, $subId); ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
<?php
tct_render_footer();
