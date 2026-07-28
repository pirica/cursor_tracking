<?php
declare(strict_types=1);

require_once __DIR__ . '/html.php';

/**
 * @param array<string, mixed> $config
 */
function tct_render_header(string $pageTitle, string $activeNav, array $config): void
{
    $project = (string) ($config['project_label'] ?? 'Cursor');
    $navItems = [
        'home' => ['href' => 'index.php', 'label' => 'Home'],
        'chats' => ['href' => 'chats.php', 'label' => 'Transcripts'],
        'plans' => ['href' => 'plans.php', 'label' => 'Plans'],
        'rules' => ['href' => 'rules.php', 'label' => 'Rules'],
        'config' => ['href' => 'settings.php', 'label' => 'Config'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= tct_h($pageTitle) ?> — <?= tct_h($project) ?></title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<header class="app-header">
    <div class="app-header-top">
        <div>
            <h1 class="app-brand">Cursor tracker</h1>
            <p class="subtitle"><?= tct_h($project) ?></p>
        </div>
        <nav class="app-nav" aria-label="Main">
            <?php foreach ($navItems as $key => $item): ?>
                <a href="<?= tct_h($item['href']) ?>"
                   class="app-nav-link<?= $activeNav === $key ? ' is-active' : '' ?>"><?= tct_h($item['label']) ?></a>
            <?php endforeach; ?>
            <a href="https://github.com/pirica/cursor_tracking"
               class="app-nav-link app-nav-github"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="GitHub repository">
                <img src="assets/GitHub_Invertocat_White_Clearspace.png"
                     alt=""
                     width="20"
                     height="20">
            </a>
        </nav>
    </div>
</header>
<main class="app-main">
    <?php
}

function tct_render_footer(): void
{
    ?>
</main>
<script src="assets/app.js?v=5"></script>
</body>
</html>
    <?php
}
