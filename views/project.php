<?php
declare(strict_types=1);

$cover = $images[0] ?? null;
$ratingAverage = (float) ($stats['avg_rating'] ?? 0);
$ratingTotal = (int) ($stats['total'] ?? 0);
$ratingFlash = null;
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['rating_flash']) && is_array($_SESSION['rating_flash'])) {
    $ratingFlash = $_SESSION['rating_flash'];
    unset($_SESSION['rating_flash']);
}
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
    <title><?= e($project['title']) ?> | <?= e($settings['brand_name'] ?? 'JosDigital') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<nav class="navbar scrolled">
    <div class="nav-container">
        <a href="index.php#projetos" class="nav-logo">
            <span class="logo-bracket">&lt;</span><?= e($settings['brand_name'] ?? 'JosDigital') ?><span class="logo-bracket">/&gt;</span>
        </a>
        <a class="btn btn-outline" href="index.php#projetos"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</nav>

<main class="project-page">
    <div class="container">
        <div class="project-detail-head">
            <span class="section-tag"><?= e($project['category']) ?></span>
            <h1><?= e($project['title']) ?></h1>
            <p><?= e($project['short_description']) ?></p>
            <div class="project-meta">
                <span><i class="fas fa-eye"></i> <?= number_format(Project::views((int) $project['id']), 0, ',', '.') ?> visualizações</span>
                <span>★ <strong id="ratingAverage"><?= number_format($ratingAverage, 1, ',', '.') ?></strong> (<span id="ratingTotal"><?= number_format($ratingTotal, 0, ',', '.') ?></span>)</span>
            </div>
        </div>

        <?php if ($cover): ?>
            <div class="project-cover">
                <img src="media.php?id=<?= (int) $cover['media_id'] ?>" alt="<?= e($cover['title'] ?: $project['title']) ?>">
            </div>
        <?php endif; ?>

        <?php if (trim((string) $project['description']) !== ''): ?>
            <div class="project-long-description"><?= nl2br(e($project['description'])) ?></div>
        <?php endif; ?>

        <?php if (count($images) > 1): ?>
            <div class="project-gallery">
                <?php foreach (array_slice($images, 1, 3) as $image): ?>
                    <article class="project-gallery-row">
                        <div class="project-gallery-image">
                            <img src="media.php?id=<?= (int) $image['media_id'] ?>" alt="<?= e($image['title'] ?: $project['title']) ?>">
                        </div>
                        <div class="project-gallery-text">
                            <h3><?= e($image['title'] ?: 'Detalhe do projeto') ?></h3>
                            <p><?= nl2br(e($image['description'] ?? '')) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($techs): ?>
            <div class="project-tech-list">
                <h3>Tecnologias</h3>
                <div class="project-tech">
                    <?php foreach ($techs as $tech): ?>
                        <span>
                            <?php if (!empty($tech['icon_media_id'])): ?>
                                <img class="media-icon media-icon-inline" src="media.php?id=<?= (int) $tech['icon_media_id'] ?>" alt="<?= e($tech['name']) ?>">
                            <?php else: ?>
                                <i class="fas fa-code"></i>
                            <?php endif; ?>
                            <?= e($tech['name']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="project-links">
            <?php if (!empty($project['project_url'])): ?>
                <a class="btn btn-primary" href="<?= e($project['project_url']) ?>" target="_blank" rel="noopener noreferrer">Abrir projeto <i class="fas fa-arrow-up-right-from-square"></i></a>
            <?php endif; ?>
            <?php if (!empty($project['github_url'])): ?>
                <a class="btn btn-outline" href="<?= e($project['github_url']) ?>" target="_blank" rel="noopener noreferrer">GitHub <i class="fab fa-github"></i></a>
            <?php endif; ?>
        </div>

        <div class="rating-box">
            <h3>Avalie este projeto</h3>
            <form id="ratingForm" method="post" action="project.php?slug=<?= rawurlencode((string) $project['slug']) ?>#ratingForm">
                <input type="hidden" name="action" value="rate">
                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <div class="stars" role="radiogroup" aria-label="Escolha de 1 a 5 estrelas">
                    <?php for ($star = 1; $star <= 5; $star++): ?>
                        <button type="button" data-rating="<?= $star ?>" aria-pressed="false" aria-label="<?= $star ?> estrela<?= $star > 1 ? 's' : '' ?>">★</button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingValue" value="0">
                <div class="rating-email-field">
                    <label class="rating-email-label" for="ratingEmail">O teu e-mail <span aria-hidden="true">*</span></label>
                    <input class="rating-email-input" type="email" id="ratingEmail" name="email" maxlength="254" autocomplete="email" placeholder="exemplo@email.com" required>
                    <p class="rating-privacy-note">O e-mail serve para validar a avaliação e evitar votos repetidos no mesmo projecto. Não será apresentado publicamente.</p>
                </div>
                <button class="btn btn-primary" id="ratingSubmit" type="submit">Enviar avaliação</button>
                <div id="ratingStatus" class="portfolio-status<?= (($ratingFlash['type'] ?? '') === 'success') ? ' success' : (is_array($ratingFlash) ? ' error' : '') ?>" role="status" aria-live="polite"><?php if (is_array($ratingFlash)): ?><?= e((string) ($ratingFlash['message'] ?? '')) ?><?php endif; ?></div>
            </form>
        </div>
    </div>
</main>

<footer class="footer project-footer">
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

<script src="assets/js/project.js?v=<?= (int) (@filemtime(__DIR__ . '/../assets/js/project.js') ?: 0) ?>"></script>
</body>
</html>
