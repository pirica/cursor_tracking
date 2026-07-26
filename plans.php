<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/plan_scan.php';
require_once __DIR__ . '/lib/transcript_scan.php';
require_once __DIR__ . '/lib/layout.php';

$config = require __DIR__ . '/config.php';
$plansDir = (string) ($config['plans_dir'] ?? '');
$dirMissing = $plansDir === '' || !is_dir($plansDir);
$plans = $dirMissing ? [] : tct_scan_plans($config);

tct_render_header('Plans', 'plans', $config);
?>
    <?php if ($dirMissing): ?>
        <div class="alert-error">
            Plans directory not found. Edit <code>config.php</code> and set <code>plans_dir</code>.
        </div>
    <?php endif; ?>

    <div class="toolbar">
        <input type="search" id="filter-plan-search" placeholder="Search name, overview, filename…" autocomplete="off">
    </div>

    <p class="stats-bar">
        <span id="plan-visible-count"><?= count($plans) ?></span> of <?= count($plans) ?> plans shown
    </p>

    <div class="table-wrap">
        <?php if (count($plans) === 0): ?>
            <p class="empty-state">No plans found.</p>
        <?php else: ?>
        <table class="chat-table" id="plans-table" data-sortable-table="plans" data-default-sort="modified" data-default-dir="desc">
            <thead>
            <tr>
                <?php tct_sortable_th('Plan', 'plan'); ?>
                <?php tct_sortable_th('Overview', 'overview'); ?>
                <?php tct_sortable_th('Todos', 'todos'); ?>
                <?php tct_sortable_th('Modified', 'modified'); ?>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($plans as $plan):
                $search = strtolower(
                    $plan['file'] . ' ' . $plan['name'] . ' ' . $plan['overview']
                );
                $todoSort = (int) $plan['todos_total'] > 0
                    ? (int) $plan['todos_done'] / (int) $plan['todos_total']
                    : -1;
                $planFile = (string) $plan['file'];
                $resolvedPath = tct_resolve_plan_file($config, $planFile);
                ?>
                <tr data-plan-row
                    data-plan-file="<?= tct_h($planFile) ?>"
                    data-search="<?= tct_h($search) ?>"
                    data-sort-plan="<?= tct_h(mb_strtolower((string) $plan['name'])) ?>"
                    data-sort-overview="<?= tct_h(mb_strtolower((string) $plan['overview'])) ?>"
                    data-sort-todos="<?= $todoSort < 0 ? '-1' : (string) round($todoSort * 10000) ?>"
                    data-sort-modified="<?= (int) $plan['mtime'] ?>">
                    <td class="row-title">
                        <a href="plan.php?f=<?= urlencode((string) $plan['file']) ?>"><?= tct_h((string) $plan['name']) ?></a>
                        <div class="row-meta"><?= tct_h((string) $plan['file']) ?></div>
                    </td>
                    <td><?= tct_h(tct_truncate_plain((string) $plan['overview'], 200)) ?></td>
                    <td class="row-meta">
                        <?= (int) $plan['todos_done'] ?>/<?= (int) $plan['todos_total'] ?> completed
                    </td>
                    <td class="row-meta"><?= tct_h(tct_format_mtime((int) $plan['mtime'])) ?></td>
                    <td class="col-actions">
                        <div class="row-actions">
                            <?php if ($resolvedPath !== null): ?>
                                <button type="button" class="btn-action"
                                        data-open-location
                                        data-open-kind="plan"
                                        data-open-f="<?= tct_h($planFile) ?>"
                                        title="Open in Windows Explorer">Open location</button>
                                <button type="button" class="btn-action" data-copy-path="<?= tct_h($resolvedPath) ?>" title="Copy full path">Copy path</button>
                            <?php endif; ?>
                            <button type="button" class="btn-action btn-action-danger" data-plan-delete="<?= tct_h($planFile) ?>">Delete</button>
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
