<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/transcript_scan.php';
require_once __DIR__ . '/lib/plan_scan.php';
require_once __DIR__ . '/lib/rule_scan.php';
require_once __DIR__ . '/lib/layout.php';

$config = require __DIR__ . '/config.php';

$transcriptsDir = (string) ($config['transcripts_dir'] ?? '');
$plansDir = (string) ($config['plans_dir'] ?? '');
$chatCount = ($transcriptsDir !== '' && is_dir($transcriptsDir)) ? count(tct_scan_transcripts($config)) : null;
$planCount = ($plansDir !== '' && is_dir($plansDir)) ? count(tct_scan_plans($config)) : null;
$rulesDir = (string) ($config['rules_dir'] ?? '');
$ruleCount = ($rulesDir !== '' && is_dir($rulesDir)) ? count(tct_scan_rules($config)) : null;

tct_render_header('Home', 'home', $config);
?>
    <p class="page-lead">Browse Cursor agent chats and Plan mode documents from your local machine.</p>

    <div class="hub-cards">
        <a class="hub-card" href="chats.php">
            <h2>Chats</h2>
            <p>Agent transcripts, tracking, and transcript viewer.</p>
            <?php if ($chatCount !== null): ?>
                <p class="hub-card-count"><?= (int) $chatCount ?> chats</p>
            <?php else: ?>
                <p class="hub-card-meta">Transcripts path not configured</p>
            <?php endif; ?>
        </a>
        <a class="hub-card" href="plans.php">
            <h2>Plans</h2>
            <p>Plan mode <code>.plan.md</code> files from <code>.cursor/plans</code>.</p>
            <?php if ($planCount !== null): ?>
                <p class="hub-card-count"><?= (int) $planCount ?> plans</p>
            <?php else: ?>
                <p class="hub-card-meta">Plans path not configured</p>
            <?php endif; ?>
        </a>
        <a class="hub-card" href="rules.php">
            <h2>Rules</h2>
            <p>Cursor project <code>.mdc</code> rules (e.g. repo <code>.cursor/rules</code>).</p>
            <?php if ($ruleCount !== null): ?>
                <p class="hub-card-count"><?= (int) $ruleCount ?> rules</p>
            <?php else: ?>
                <p class="hub-card-meta">Rules path not configured</p>
            <?php endif; ?>
        </a>
        <a class="hub-card" href="settings.php">
            <h2>Config</h2>
            <p>Filesystem paths and local URLs used by this app.</p>
        </a>
    </div>
<?php
tct_render_footer();
