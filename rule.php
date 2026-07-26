<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/rule_scan.php';
require_once __DIR__ . '/lib/transcript_scan.php';
require_once __DIR__ . '/lib/layout.php';

$config = require __DIR__ . '/config.php';
$basename = isset($_GET['f']) ? tct_sanitize_rule_basename((string) $_GET['f']) : '';
$file = $basename !== '' ? tct_resolve_rule_file($config, $basename) : null;
$notFound = $file === null;
$rulesDir = (string) ($config['rules_dir'] ?? '');
$expectedPath = ($basename !== '' && $rulesDir !== '')
    ? $rulesDir . DIRECTORY_SEPARATOR . $basename
    : '';

$meta = $notFound ? ['description' => '', 'always_apply' => false] : tct_parse_rule_frontmatter($file);
$body = $notFound ? '' : tct_rule_body_text($file);
$fullText = $notFound ? '' : (is_readable($file) ? (string) file_get_contents($file) : '');
$fileMtime = (!$notFound && $file !== null) ? (filemtime($file) ?: 0) : 0;
$fileSize = (!$notFound && $file !== null) ? (filesize($file) ?: 0) : 0;

$pageTitle = $notFound ? 'Not found' : $basename;

tct_render_header($pageTitle, 'rules', $config);
?>
    <p class="back-link"><a href="rules.php">← All rules</a></p>
    <?php if (!$notFound && $fileMtime > 0): ?>
        <p class="plan-file-meta">
            File modified: <strong><?= tct_h(tct_format_mtime($fileMtime)) ?></strong>
            · Size: <?= tct_h(tct_format_bytes($fileSize)) ?>
            <?php if (!empty($meta['always_apply'])): ?>
                · <strong>Always apply</strong>
            <?php endif; ?>
        </p>
        <div class="plan-detail-actions row-actions plan-detail-actions-row">
            <button type="button" class="btn-action"
                    data-open-location
                    data-open-kind="rule"
                    data-open-f="<?= tct_h($basename) ?>"
                    title="Open in Windows Explorer">Open location</button>
            <button type="button" class="btn-action" data-copy-path="<?= tct_h($file) ?>">Copy path</button>
            <button type="button" class="btn-action btn-action-danger" data-rule-delete="<?= tct_h($basename) ?>" data-redirect="rules.php">Delete file</button>
        </div>
    <?php endif; ?>

    <?php if ($notFound): ?>
        <div class="alert-error">Rule not found or invalid filename.</div>
        <?php if ($basename !== ''): ?>
            <p class="config-note">Requested file: <code><?= tct_h($basename) ?></code></p>
            <?php if ($expectedPath !== ''): ?>
                <p class="config-note">Expected path: <code><?= tct_h($expectedPath) ?></code>
                    — <?= is_file($expectedPath) ? 'exists on disk' : 'not found on disk' ?>.</p>
            <?php endif; ?>
            <p class="config-note">Check <a href="settings.php">Config</a> — <code>rules_dir</code> must point at your <code>.cursor/rules</code> folder.</p>
        <?php endif; ?>
    <?php else: ?>
    <div class="plan-head">
        <h2 class="page-title"><?= tct_h($basename) ?></h2>
        <?php if ((string) $meta['description'] !== ''): ?>
            <p class="plan-overview"><?= tct_h((string) $meta['description']) ?></p>
        <?php endif; ?>
    </div>

    <div class="plan-body-wrap">
        <pre class="rule-source"><?= tct_h($fullText !== '' ? $fullText : $body) ?></pre>
    </div>
    <?php endif; ?>
<?php
tct_render_footer();
