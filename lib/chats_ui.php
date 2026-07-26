<?php
declare(strict_types=1);

require_once __DIR__ . '/html.php';
require_once __DIR__ . '/transcript_scan.php';

/**
 * @param array<string, mixed> $config
 */
function tct_render_chat_file_actions(
    array $config,
    string $parentId,
    string $subId = '',
    string $redirectAfterDelete = '',
    string $deleteLabel = 'Delete'
): void {
    $subParam = $subId !== '' ? $subId : null;
    $file = tct_resolve_transcript_file($config, $parentId, $subParam);
    if ($file !== null): ?>
            <button type="button" class="btn-action"
                    data-open-location
                    data-open-kind="chat"
                    data-open-id="<?= tct_h($parentId) ?>"
                    data-open-sub="<?= tct_h($subId) ?>"
                    title="Open in Windows Explorer">Open location</button>
            <button type="button" class="btn-action" data-copy-path="<?= tct_h($file) ?>" title="Copy full path">Copy path</button>
    <?php endif; ?>
        <button type="button" class="btn-action btn-action-danger"
                data-chat-delete
                data-id="<?= tct_h($parentId) ?>"
                data-sub="<?= tct_h($subId) ?>"
                <?php if ($redirectAfterDelete !== ''): ?>data-redirect="<?= tct_h($redirectAfterDelete) ?>"<?php endif; ?>><?= tct_h($deleteLabel) ?></button>
    <?php
}

/**
 * @param array{starred: bool, status: string, notes: string, title_override: string} $track
 */
function tct_render_row_status_select(array $track, string $parentId, string $subId = ''): void
{
    $status = $track['status'];
    $options = [
        'open' => 'Open',
        'done' => 'Done',
        'archived' => 'Archived',
    ];
    ?>
    <div class="row-status-field">
        <label class="row-status-label">Status</label>
        <select class="row-status-select"
                data-row-status
                data-id="<?= tct_h($parentId) ?>"
                data-sub="<?= tct_h($subId) ?>"
                data-starred="<?= $track['starred'] ? '1' : '0' ?>"
                data-notes="<?= tct_h($track['notes']) ?>"
                data-title-override="<?= tct_h($track['title_override']) ?>"
                aria-label="Tracking status">
            <?php foreach ($options as $value => $label): ?>
                <option value="<?= tct_h($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= tct_h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}
