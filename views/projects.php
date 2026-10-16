<?php
declare(strict_types=1);

$footerGroups = [];
foreach ($footerLinks as $footerLink) {
    $footerGroups[$footerLink['group_title']][] = $footerLink;
}
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos os projetos | <?= e($settings['brand_name'] ?? 'JosDigital') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<nav class="navbar scrolled">
    <div class="nav-container">
        <a href="index.php#hero" class="nav-logo">
            <span class="logo-bracket">&lt;</span><?= e($settings['brand_name'] ?? 'JosDigital') ?><span class="logo-bracket">/&gt;</span>
        </a>
        <a class="btn btn-outline" href="index.php#projetos"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</nav>

<main class="project-page">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Portfólio</span>
            <h1 class="section-title">Todos os <span class="text-gradient">Projetos</span></h1>
            <p class="section-subtitle">Projetos publicados no portfólio.</p>
        </div>

        <div class="projects-grid">
            <?php foreach ($projects as $project): ?>
                <article class="project-card">
                    <div class="project-image">
                        <?php if (!empty($project['cover_media_id'])): ?>
                            <img src="media.php?id=<?= (int) $project['cover_media_id'] ?>" alt="<?= e($project['title']) ?>">
                        <?php else: ?>
                            <div class="project-placeholder"><i class="fas fa-laptop-code"></i></div>
                        <?php endif; ?>
                        <div class="project-overlay">
                            <a href="project.php?slug=<?= urlencode($project['slug']) ?>" class="project-btn-view"><i class="fas fa-eye"></i> Visualizar</a>
                        </div>
                    </div>
                    <div class="project-info">
                        <div class="project-category"><?= e($project['category']) ?></div>
                        <h3 class="project-title"><?= e($project['title']) ?></h3>
                        <p class="project-description"><?= e($project['short_description']) ?></p>
                        <div class="project-meta">
                            <span><i class="fas fa-eye"></i> <?= number_format((int) $project['views'], 0, ',', '.') ?></span>
                            <span>★ <?= number_format((float) $project['rating_average'], 1, ',', '.') ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                <a href="index.php#hero" class="footer-logo"><span class="logo-bracket">&lt;</span><?= e($settings['brand_name'] ?? 'JosDigital') ?><span class="logo-bracket">/&gt;</span></a>
                <p class="footer-tagline">Soluções digitais para empresas modernas.</p>
            </div>
            <?php foreach ($footerGroups as $group => $links): ?>
                <div class="footer-links">
                    <h4><?= e($group) ?></h4>
                    <ul>
                        <?php foreach ($links as $link): ?>
                            <li><a href="<?= e($link['url']) ?>"><?= e($link['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</footer>
</body>
</html>
