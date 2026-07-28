<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/plan_scan.php';
require_once __DIR__ . '/lib/transcript_scan.php';
require_once __DIR__ . '/lib/cursor_open.php';
require_once __DIR__ . '/lib/layout.php';

$config = require __DIR__ . '/config.php';
$basename = isset($_GET['f']) ? tct_sanitize_plan_basename((string) $_GET['f']) : '';
$file = $basename !== '' ? tct_resolve_plan_file($config, $basename) : null;
$notFound = $file === null;
$plansDir = (string) ($config['plans_dir'] ?? '');
$expectedPath = ($basename !== '' && $plansDir !== '')
    ? $plansDir . DIRECTORY_SEPARATOR . $basename
    : '';

$fm = $notFound ? ['name' => 'Not found', 'overview' => '', 'todos' => []] : tct_parse_plan_frontmatter($file);
$body = $notFound ? '' : tct_plan_body_markdown($file);
$fileMtime = (!$notFound && $file !== null) ? (filemtime($file) ?: 0) : 0;
$fileSize = (!$notFound && $file !== null) ? (filesize($file) ?: 0) : 0;

tct_render_header((string) $fm['name'], 'plans', $config);
?>
    <p class="back-link"><a href="plans.php">← All plans</a></p>
    <?php if (!$notFound && $fileMtime > 0): ?>
        <p class="plan-file-meta">
            File modified: <strong><?= tct_h(tct_format_mtime($fileMtime)) ?></strong>
            · Size: <?= tct_h(tct_format_bytes($fileSize)) ?>
        </p>
        <div class="plan-detail-actions row-actions plan-detail-actions-row">
            <button type="button" class="btn-action"
                    data-open-location
                    data-open-kind="plan"
                    data-open-f="<?= tct_h($basename) ?>"
                    title="Open in Windows Explorer">Open location</button>
            <button type="button" class="btn-action" data-copy-path="<?= tct_h($file) ?>">Copy path</button>
            <button type="button" class="btn-action btn-action-danger" data-plan-delete="<?= tct_h($basename) ?>" data-redirect="plans.php">Delete file</button>
        </div>
        <?php tct_render_cursor_open_links($basename, $file, $config); ?>
    <?php endif; ?>

    <?php if ($notFound): ?>
        <div class="alert-error">Plan not found or invalid filename.</div>
        <?php if ($basename !== ''): ?>
            <p class="config-note">Requested file: <code><?= tct_h($basename) ?></code></p>
            <?php if ($expectedPath !== ''): ?>
                <p class="config-note">Expected path: <code><?= tct_h($expectedPath) ?></code>
                    — <?= is_file($expectedPath) ? 'exists on disk' : 'not found on disk' ?>.</p>
            <?php endif; ?>
            <p class="config-note">Check <a href="settings.php">Config</a> — <code>plans_dir</code> must match where Cursor saves plans and be readable by PHP (Apache).</p>
        <?php endif; ?>
    <?php else: ?>
    <div class="plan-head">
        <h2 class="page-title"><?= tct_h((string) $fm['name']) ?></h2>
        <p class="row-meta"><?= tct_h($basename) ?></p>
        <?php if ((string) $fm['overview'] !== ''): ?>
            <p class="plan-overview"><?= tct_h((string) $fm['overview']) ?></p>
        <?php endif; ?>
    </div>

    <?php if (count($fm['todos']) > 0): ?>
        <section class="plan-todos">
            <h3>Todos</h3>
            <ul class="plan-todo-list">
                <?php foreach ($fm['todos'] as $todo):
                    $done = ($todo['status'] ?? '') === 'completed';
                    ?>
                    <li class="<?= $done ? 'todo-done' : 'todo-pending' ?>">
                        <span class="badge <?= $done ? 'badge-success' : 'badge-muted' ?>"><?= tct_h($done ? 'done' : 'pending') ?></span>
                        <?= tct_h((string) ($todo['content'] ?? $todo['id'] ?? '')) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="plan-body-section">
        <h3>Plan document</h3>
        <div class="plan-markdown"><?= tct_h($body) ?></div>
    </section>
    <?php endif; ?>
<?php
tct_render_footer();
