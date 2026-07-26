<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/rule_scan.php';
require_once __DIR__ . '/lib/transcript_scan.php';
require_once __DIR__ . '/lib/layout.php';

$config = require __DIR__ . '/config.php';
$rulesDir = (string) ($config['rules_dir'] ?? '');
$dirMissing = $rulesDir === '' || !is_dir($rulesDir);
$rules = $dirMissing ? [] : tct_scan_rules($config);

tct_render_header('Rules', 'rules', $config);
?>
    <?php if ($dirMissing): ?>
        <div class="alert-error">
            Rules directory not found. Edit <a href="settings.php">Config</a> and set <code>rules_dir</code>
            (e.g. your repo <code>.cursor/rules</code> folder).
        </div>
    <?php endif; ?>

    <p class="page-lead">Cursor project rules (<code>*.mdc</code>) from your configured rules folder.</p>

    <div class="toolbar">
        <input type="search" id="filter-rule-search" placeholder="Search filename, description…" autocomplete="off">
    </div>

    <p class="stats-bar">
        <span id="rule-visible-count"><?= count($rules) ?></span> of <span id="rule-total-count"><?= count($rules) ?></span> rules shown
    </p>

    <div class="table-wrap">
        <?php if (count($rules) === 0): ?>
            <p class="empty-state">No rules found.</p>
        <?php else: ?>
        <table class="chat-table" id="rules-table" data-sortable-table="rules" data-default-sort="file" data-default-dir="asc">
            <thead>
            <tr>
                <?php tct_sortable_th('Rule file', 'file'); ?>
                <?php tct_sortable_th('Description', 'description'); ?>
                <?php tct_sortable_th('Always apply', 'always'); ?>
                <?php tct_sortable_th('Modified', 'modified'); ?>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rules as $rule):
                $ruleFile = (string) $rule['file'];
                $resolvedPath = tct_resolve_rule_file($config, $ruleFile);
                if ($resolvedPath === null && isset($rule['path']) && is_file((string) $rule['path'])) {
                    $resolvedPath = realpath((string) $rule['path']) ?: (string) $rule['path'];
                }
                $search = strtolower($ruleFile . ' ' . ($rule['description'] ?? '') . ' ' . ($rule['label'] ?? ''));
                ?>
                <tr data-rule-row
                    data-search="<?= tct_h($search) ?>"
                    data-sort-file="<?= tct_h(strtolower($ruleFile)) ?>"
                    data-sort-description="<?= tct_h(strtolower((string) ($rule['description'] ?? ''))) ?>"
                    data-sort-always="<?= !empty($rule['always_apply']) ? '1' : '0' ?>"
                    data-sort-modified="<?= (int) $rule['mtime'] ?>">
                    <td class="row-title">
                        <a href="rule.php?f=<?= urlencode($ruleFile) ?>"><?= tct_h($ruleFile) ?></a>
                    </td>
                    <td><?= tct_h(tct_truncate_plain((string) ($rule['description'] ?? ''), 200)) ?></td>
                    <td class="row-meta"><?= !empty($rule['always_apply']) ? 'Yes' : '—' ?></td>
                    <td class="row-meta"><?= tct_h(tct_format_mtime((int) $rule['mtime'])) ?></td>
                    <td class="col-actions">
                        <div class="row-actions">
                            <?php if ($resolvedPath !== null): ?>
                                <button type="button" class="btn-action"
                                        data-open-location
                                        data-open-kind="rule"
                                        data-open-f="<?= tct_h($ruleFile) ?>"
                                        title="Open in Windows Explorer">Open location</button>
                                <button type="button" class="btn-action" data-copy-path="<?= tct_h($resolvedPath) ?>" title="Copy full path">Copy path</button>
                            <?php endif; ?>
                            <button type="button" class="btn-action btn-action-danger" data-rule-delete="<?= tct_h($ruleFile) ?>">Delete</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
<?php
tct_render_footer();
