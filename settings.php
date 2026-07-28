<?php
declare(strict_types=1);

if (isset($_GET['phpinfo'])) {
    require_once __DIR__ . '/lib/php_environment.php';
    tct_render_phpinfo_page();
    exit;
}

require_once __DIR__ . '/lib/config_display.php';
require_once __DIR__ . '/lib/local_config.php';
require_once __DIR__ . '/lib/php_environment.php';
require_once __DIR__ . '/lib/layout.php';

$config = require __DIR__ . '/config.php';
$saveErrors = [];
$saveOk = false;
$resetOk = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'reset') {
        if (tct_reset_local_config()) {
            header('Location: settings.php?reset=1');
            exit;
        }
        $saveErrors[] = 'Could not remove local overrides file.';
    } else {
        $result = tct_validate_local_config_input($_POST);
        if (!$result['ok']) {
            $saveErrors = $result['errors'];
        } elseif (!tct_save_local_config($result['data'])) {
            $saveErrors[] = 'Could not write data/local_config.json (check folder permissions).';
        } else {
            header('Location: settings.php?saved=1');
            exit;
        }
    }
    $config = require __DIR__ . '/config.php';
}

if (isset($_GET['saved'])) {
    $saveOk = true;
    $config = require __DIR__ . '/config.php';
}
if (isset($_GET['reset'])) {
    $resetOk = true;
    $config = require __DIR__ . '/config.php';
}

$pathRows = tct_config_path_rows($config);
$baseUrl = tct_app_base_url();
$urlRows = tct_config_url_rows($baseUrl);
$hasLocalOverrides = is_file(tct_local_config_file());
$localFile = tct_local_config_file();

tct_render_header('Config', 'config', $config);
?>
    <p class="page-lead">
        Edit paths below and save. Values are stored in <code>data/local_config.json</code>
        (overrides built-in <code>config.php</code> defaults). The <code>data/</code> folder is not web-accessible.
    </p>

    <?php if ($saveOk): ?>
        <div class="alert-success">Settings saved.</div>
    <?php endif; ?>
    <?php if ($resetOk): ?>
        <div class="alert-success">Local overrides removed — using <code>config.php</code> defaults again.</div>
    <?php endif; ?>
    <?php foreach ($saveErrors as $err): ?>
        <div class="alert-error"><?= tct_h($err) ?></div>
    <?php endforeach; ?>

    <h2 class="section-heading">Edit paths</h2>
    <form class="config-form" method="post" action="settings.php">
        <input type="hidden" name="action" value="save">

        <label for="cfg-project-label">Project label</label>
        <input type="text" id="cfg-project-label" name="project_label" class="config-input"
               value="<?= tct_h((string) ($config['project_label'] ?? '')) ?>" required>

        <label for="cfg-transcripts">Agent transcripts folder</label>
        <input type="text" id="cfg-transcripts" name="transcripts_dir" class="config-input config-input-path"
               value="<?= tct_h((string) ($config['transcripts_dir'] ?? '')) ?>" required
               placeholder="C:\Users\YOU\.cursor\projects\…\agent-transcripts">

        <label for="cfg-plans">Plans folder</label>
        <input type="text" id="cfg-plans" name="plans_dir" class="config-input config-input-path"
               value="<?= tct_h((string) ($config['plans_dir'] ?? '')) ?>" required
               placeholder="C:\Users\YOU\.cursor\plans">

        <label for="cfg-rules">Cursor rules folder</label>
        <input type="text" id="cfg-rules" name="rules_dir" class="config-input config-input-path"
               value="<?= tct_h((string) ($config['rules_dir'] ?? '')) ?>" required
               placeholder="C:\path\to\repo\.cursor\rules">

        <label for="cfg-tracking">Transcript tracking file</label>
        <input type="text" id="cfg-tracking" name="tracking_file" class="config-input config-input-path"
               value="<?= tct_h((string) ($config['tracking_file'] ?? '')) ?>" required>

        <div class="config-form-actions">
            <button type="submit" class="btn">Save paths</button>
            <?php if ($hasLocalOverrides): ?>
                <button type="submit" class="btn btn-secondary" name="action" value="reset"
                        onclick="return confirm('Reset to config.php defaults?');">Reset to defaults</button>
            <?php endif; ?>
        </div>
        <?php if ($hasLocalOverrides): ?>
            <p class="config-note">Active overrides file: <code><?= tct_h($localFile) ?></code></p>
        <?php else: ?>
            <p class="config-note">No <code>local_config.json</code> yet — saving will create one.</p>
        <?php endif; ?>
    </form>

    <h2 class="section-heading">Current paths (read-only check)</h2>
    <div class="table-wrap config-section">
        <table class="chat-table config-table">
            <thead>
            <tr>
                <th>Setting</th>
                <th>Path</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pathRows as $row):
                if ($row['key'] === '_config_php') {
                    continue;
                }
                ?>
                <tr>
                    <td>
                        <strong><?= tct_h($row['label']) ?></strong>
                        <?php if ($row['key'] !== 'project_label'): ?>
                            <div class="row-meta"><code><?= tct_h($row['key']) ?></code></div>
                        <?php endif; ?>
                        <div class="config-note"><?= tct_h($row['note']) ?></div>
                    </td>
                    <td class="config-path-cell">
                        <code class="config-path"><?= tct_h($row['path']) ?></code>
                    </td>
                    <td>
                        <?php if ($row['exists'] === null): ?>
                            <span class="badge badge-muted">—</span>
                        <?php elseif ($row['exists']): ?>
                            <span class="badge badge-success">found</span>
                        <?php else: ?>
                            <span class="badge badge-error">missing</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2 class="section-heading">Local URLs</h2>
    <p class="config-note block-note">Base URL detected from this request: <code><?= tct_h($baseUrl) ?></code></p>
    <div class="table-wrap config-section">
        <table class="chat-table config-table">
            <thead>
            <tr>
                <th>Page</th>
                <th>URL</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($urlRows as $row): ?>
                <tr>
                    <td><?= tct_h($row['label']) ?></td>
                    <td class="config-path-cell">
                        <?php if (strpos($row['url'], '{') === false): ?>
                            <a href="<?= tct_h($row['url']) ?>"><code class="config-path"><?= tct_h($row['url']) ?></code></a>
                        <?php else: ?>
                            <code class="config-path"><?= tct_h($row['url']) ?></code>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2 class="section-heading">Open location (Windows)</h2>
    <p class="page-lead">
        If <strong>Open location</strong> does nothing, the path is still copied when you click it.
        Paste into Explorer with <kbd>Win+E</kbd>. On Windows, Apache should run in your interactive desktop session (not only as a background service).
        Enable <code>extension=com_dotnet</code> in the <strong>Apache PHP php.ini</strong> and restart the web server.
    </p>
    <p>
        <a class="btn-action" href="scripts/open_location_diag.php" target="_blank" rel="noopener">Run open-location diagnostic</a>
        (opens in a new tab; may launch Explorer once)
    </p>

    <h2 class="section-heading">PHP environment</h2>
    <?php tct_render_php_environment_summary(); ?>
    <p>
        <a href="settings.php?phpinfo=1" target="_blank" rel="noopener">Open phpinfo()</a>
        (new tab — web server PHP, not CLI)
    </p>
<?php
tct_render_footer();
