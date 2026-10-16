<?php
declare(strict_types=1);

$settings = $settings ?? [];
$sections = $sections ?? [];
$secMap = [];
foreach ($sections as $section) {
    $secMap[$section['section_key']] = $section;
}

$heroMediaId = !empty($hero['media_id']) ? (int) $hero['media_id'] : 0;
$aboutMediaId = !empty($about['image_id']) ? (int) $about['image_id'] : 0;
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
    <meta name="description" content="<?= e($settings['meta_description'] ?? '') ?>">
    <meta name="author" content="<?= e($settings['owner_name'] ?? 'José Silva Bentos') ?>">
    <title><?= e($settings['site_title'] ?? 'José Silva Bentos | JosDigital') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="#hero" class="nav-logo">
            <span class="logo-bracket">&lt;</span><?= e($settings['brand_name'] ?? 'JosDigital') ?><span class="logo-bracket">/&gt;</span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Abrir menu"><span class="hamburger"></span></button>

        <ul class="nav-menu" id="navMenu">
            <li><a href="#hero" class="nav-link active">Início</a></li>
            <?php foreach ($sections as $section): if (($section['section_key'] ?? '') === 'hero') continue; ?>
                <li>
                    <a href="#<?= e($section['section_key']) ?>"
                       class="nav-link <?= ($section['section_key'] ?? '') === 'contacto' ? 'nav-cta' : '' ?>">
                        <?= e($section['menu_label'] ?: ($section['title'] ?: ucfirst($section['section_key']))) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<section class="hero" id="hero">
    <div class="hero-bg">
        <div class="hero-grid"></div>
        <div class="hero-glow"></div>
        <div class="hero-particles" id="particles"></div>
    </div>

    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-badge" data-animate="fade-up">
                <i class="fas fa-bolt"></i><?= e($hero['badge'] ?? 'Disponível para novos projetos') ?>
            </div>

            <h1 class="hero-name" data-animate="fade-up">
                <?= nl2br(e($hero['title'] ?? ($settings['owner_name'] ?? 'José Silva Bentos'))) ?>
            </h1>

            <h2 class="hero-title" data-animate="fade-up" data-delay="100">
                <?= e($hero['subtitle'] ?? 'Desenvolvedor de Sistemas & Especialista em Soluções Digitais') ?>
            </h2>

            <p class="hero-description" data-animate="fade-up" data-delay="200">
                <?= e($hero['description'] ?? '') ?>
            </p>

            <div class="hero-buttons" data-animate="fade-up" data-delay="300">
                <a href="<?= e($hero['button_primary_url'] ?? '#projetos') ?>" class="btn btn-primary">
                    <span><?= e($hero['button_primary'] ?? 'Ver Projetos') ?></span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="<?= e($hero['button_secondary_url'] ?? '#contacto') ?>" class="btn btn-outline">
                    <span><?= e($hero['button_secondary'] ?? 'Entrar em Contacto') ?></span>
                    <i class="fas fa-envelope"></i>
                </a>
            </div>

            <div class="hero-stats" data-animate="fade-up" data-delay="400">
                <div class="hero-stat"><i class="fas fa-code"></i><span>Full Stack</span></div>
                <div class="hero-stat"><i class="fas fa-desktop"></i><span>Desktop & Web</span></div>
                <div class="hero-stat"><i class="fas fa-palette"></i><span>UI/UX Design</span></div>
            </div>
        </div>

        <div class="hero-visual" data-animate="fade-left">
            <div class="hero-avatar-container">
                <div class="avatar-ring"></div>
                <div class="avatar-ring avatar-ring-2"></div>
                <div class="hero-avatar">
                    <?php if ($heroMediaId): ?>
                        <img src="media.php?id=<?= $heroMediaId ?>" alt="<?= e($settings['owner_name'] ?? 'Perfil') ?>" class="avatar-img">
                    <?php else: ?>
                        <div class="avatar-placeholder"><i class="fas fa-user-astronaut"></i></div>
                    <?php endif; ?>
                </div>
                <div class="floating-card card-1"><i class="fas fa-laptop-code"></i><span>Desenvolvimento</span></div>
                <div class="floating-card card-2"><i class="fas fa-paint-brush"></i><span>Design</span></div>
                <div class="floating-card card-3"><i class="fas fa-server"></i><span>Backend</span></div>
                <div class="floating-card card-4"><i class="fas fa-shield-halved"></i><span>Segurança</span></div>
            </div>
        </div>
    </div>

    <a href="#sobre" class="scroll-indicator">
        <span class="scroll-mouse"><span class="scroll-wheel"></span></span>
        <span class="scroll-text">Scroll</span>
    </a>
</section>

<?php if (!empty($secMap['sobre']['is_active'])): ?>
<section class="section" id="sobre">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <span class="section-tag"><?= e($secMap['sobre']['menu_label'] ?? 'Quem sou eu') ?></span>
            <h2 class="section-title"><?= e($secMap['sobre']['title'] ?? 'Sobre') ?> <span class="text-gradient"><?= e($secMap['sobre']['title_highlight'] ?? 'Mim') ?></span></h2>
            <p class="section-subtitle"><?= e($secMap['sobre']['subtitle'] ?? '') ?></p>
        </div>

        <div class="about-grid">
            <div class="about-content" data-animate="fade-right">
                <?php if ($aboutMediaId): ?>
                    <div class="about-photo"><img src="media.php?id=<?= $aboutMediaId ?>" alt="<?= e($about['title'] ?? 'Sobre') ?>"></div>
                <?php endif; ?>
                <div class="about-text">
                    <p><?= nl2br(e($about['title'] ?? 'Sou José Silva Bentos, fundador da JosDigital.')) ?></p>
                    <p><?= nl2br(e($about['description'] ?? '')) ?></p>
                </div>
                <div class="about-cards">
                    <div class="about-card"><div class="about-card-icon"><i class="fas fa-code"></i></div><h4>Desenvolvedor de Software</h4><p>Soluções personalizadas com tecnologias adequadas ao negócio.</p></div>
                    <div class="about-card"><div class="about-card-icon"><i class="fas fa-window-maximize"></i></div><h4>Apps Desktop & Web</h4><p>Aplicações completas para empresas e organizações.</p></div>
                    <div class="about-card"><div class="about-card-icon"><i class="fas fa-pen-nib"></i></div><h4>Designer Gráfico</h4><p>Identidades visuais e materiais digitais profissionais.</p></div>
                    <div class="about-card"><div class="about-card-icon"><i class="fas fa-lightbulb"></i></div><h4>Soluções Digitais</h4><p>Estratégias e ferramentas para crescimento digital.</p></div>
                </div>
            </div>

            <div class="about-stats" data-animate="fade-left">
                <div class="stat-card"><div class="stat-number" data-count="<?= Project::countPublished() ?>">0</div><div class="stat-plus">+</div><div class="stat-label">Projetos Publicados</div><div class="stat-bar"><span class="stat-bar-fill"></span></div></div>
                <div class="stat-card"><div class="stat-number" data-count="<?= count($services) ?>">0</div><div class="stat-plus">+</div><div class="stat-label">Serviços</div><div class="stat-bar"><span class="stat-bar-fill"></span></div></div>
                <div class="stat-card"><div class="stat-number" data-count="<?= count($technologies) ?>">0</div><div class="stat-plus">+</div><div class="stat-label">Tecnologias</div><div class="stat-bar"><span class="stat-bar-fill"></span></div></div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($secMap['servicos']['is_active'])): ?>
<section class="section section-alt" id="servicos">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <span class="section-tag"><?= e($secMap['servicos']['menu_label'] ?? 'O que faço') ?></span>
            <h2 class="section-title"><?= e($secMap['servicos']['title'] ?? 'Os Meus') ?> <span class="text-gradient"><?= e($secMap['servicos']['title_highlight'] ?? 'Serviços') ?></span></h2>
            <p class="section-subtitle"><?= e($secMap['servicos']['subtitle'] ?? '') ?></p>
        </div>

        <div class="services-grid">
            <?php foreach ($services as $i => $service): ?>
                <div class="service-card" data-animate="fade-up" data-delay="<?= $i * 100 ?>">
                    <div class="service-icon">
                        <?php if (!empty($service['icon_media_id'])): ?>
                            <img class="media-icon" src="media.php?id=<?= (int) $service['icon_media_id'] ?>" alt="<?= e($service['title']) ?>">
                        <?php else: ?>
                            <i class="fas fa-code"></i>
                        <?php endif; ?>
                    </div>
                    <h3><?= e($service['title']) ?></h3>
                    <p><?= e($service['description']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($secMap['projetos']['is_active'])): ?>
<section class="section" id="projetos">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <span class="section-tag"><?= e($secMap['projetos']['menu_label'] ?? 'Portfólio') ?></span>
            <h2 class="section-title"><?= e($secMap['projetos']['title'] ?? 'Projetos') ?> <span class="text-gradient"><?= e($secMap['projetos']['title_highlight'] ?? 'Recentes') ?></span></h2>
            <p class="section-subtitle"><?= e($secMap['projetos']['subtitle'] ?? '') ?></p>
        </div>

        <div class="projects-grid">
            <?php foreach ($projects as $i => $project): ?>
                <article class="project-card" data-animate="fade-up" data-delay="<?= $i * 100 ?>">
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

        <?php if ($totalProjects > $limit): ?>
            <div class="section-actions">
                <a href="project.php?all=1" class="btn btn-outline">Ver mais <i class="fas fa-arrow-right"></i></a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($secMap['tecnologias']['is_active'])): ?>
<section class="section section-alt" id="tecnologias">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <span class="section-tag"><?= e($secMap['tecnologias']['menu_label'] ?? 'Stack') ?></span>
            <h2 class="section-title"><?= e($secMap['tecnologias']['title'] ?? 'Tecnologias') ?> <span class="text-gradient"><?= e($secMap['tecnologias']['title_highlight'] ?? 'que domino') ?></span></h2>
            <p class="section-subtitle"><?= e($secMap['tecnologias']['subtitle'] ?? '') ?></p>
        </div>

        <div class="tech-grid">
            <?php $groups = []; foreach ($technologies as $technology) $groups[$technology['category']][] = $technology; ?>
            <?php foreach ($groups as $category => $items): ?>
                <div class="tech-category" data-animate="fade-up">
                    <div class="tech-category-header"><span><?= e($category) ?></span></div>
                    <div class="tech-items">
                        <?php foreach ($items as $technology): ?>
                            <div class="tech-item">
                                <div class="tech-item-icon">
                                    <?php if (!empty($technology['icon_media_id'])): ?>
                                        <img class="media-icon media-icon-small" src="media.php?id=<?= (int) $technology['icon_media_id'] ?>" alt="<?= e($technology['name']) ?>">
                                    <?php else: ?>
                                        <i class="fas fa-code"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="tech-item-info">
                                    <strong><?= e($technology['name']) ?></strong>
                                    <div class="tech-level"><span style="width:<?= max(0, min(100, (int) $technology['level'])) ?>%"></span></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($secMap['processo']['is_active'])): ?>
<section class="section" id="processo">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <span class="section-tag"><?= e($secMap['processo']['menu_label'] ?? 'Metodologia') ?></span>
            <h2 class="section-title"><?= e($secMap['processo']['title'] ?? 'Processo de') ?> <span class="text-gradient"><?= e($secMap['processo']['title_highlight'] ?? 'Trabalho') ?></span></h2>
            <p class="section-subtitle"><?= e($secMap['processo']['subtitle'] ?? '') ?></p>
        </div>

        <div class="process-grid">
            <?php foreach ($process as $i => $step): ?>
                <div class="process-step" data-animate="fade-up" data-delay="<?= $i * 100 ?>">
                    <div class="process-number"><?= str_pad((string) $step['number'], 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="process-icon">
                        <?php if (!empty($step['icon_media_id'])): ?>
                            <img class="media-icon" src="media.php?id=<?= (int) $step['icon_media_id'] ?>" alt="<?= e($step['title']) ?>">
                        <?php else: ?>
                            <i class="fas fa-check"></i>
                        <?php endif; ?>
                    </div>
                    <h3><?= e($step['title']) ?></h3>
                    <p><?= e($step['description']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($secMap['contacto']['is_active'])): ?>
<section class="section section-alt" id="contacto">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <span class="section-tag"><?= e($secMap['contacto']['menu_label'] ?? 'Fale comigo') ?></span>
            <h2 class="section-title"><?= e($secMap['contacto']['title'] ?? 'Vamos conversar?') ?></h2>
            <p class="section-subtitle"><?= e($secMap['contacto']['subtitle'] ?? '') ?></p>
        </div>

        <div class="contact-grid">
            <div class="contact-info" data-animate="fade-right">
                <?php foreach ($contacts as $contact): ?>
                    <div class="contact-info-card">
                        <div class="contact-info-icon">
                            <?php if (!empty($contact['icon_media_id'])): ?>
                                <img class="media-icon" src="media.php?id=<?= (int) $contact['icon_media_id'] ?>" alt="<?= e($contact['label']) ?>">
                            <?php else: ?>
                                <i class="fas fa-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div><h4><?= e($contact['label']) ?></h4><p><?= e($contact['value']) ?></p></div>
                    </div>
                <?php endforeach; ?>

                <div class="contact-social">
                    <?php foreach ($socials as $social): ?>
                        <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="<?= e($social['platform']) ?>">
                            <?php if (!empty($social['icon_media_id'])): ?>
                                <img class="media-icon media-icon-social" src="media.php?id=<?= (int) $social['icon_media_id'] ?>" alt="<?= e($social['platform']) ?>">
                            <?php else: ?>
                                <i class="fas fa-link"></i>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <form class="contact-form" id="contactForm" method="post" action="index.php" data-animate="fade-left">
                <input type="hidden" name="action" value="contact">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <div class="form-group"><label for="nome">Nome</label><input type="text" id="nome" name="name" placeholder="O seu nome" required><div class="input-line"></div></div>
                <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" placeholder="o.seu@email.com" required><div class="input-line"></div></div>
                <div class="form-group"><label for="phone">Telefone</label><input type="text" id="phone" name="phone" placeholder="+244"><div class="input-line"></div></div>
                <div class="form-group"><label for="mensagem">Mensagem</label><textarea id="mensagem" name="message" rows="5" placeholder="Descreva o seu projeto ou ideia..." required></textarea><div class="input-line"></div></div>
                <button type="submit" class="btn btn-primary btn-full"><span>Enviar Mensagem</span><i class="fas fa-paper-plane"></i></button>
                <div id="contactStatus" class="portfolio-status"></div>
            </form>
        </div>
    </div>
</section>
<?php endif; ?>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                <a href="#hero" class="footer-logo"><span class="logo-bracket">&lt;</span><?= e($settings['brand_name'] ?? 'JosDigital') ?><span class="logo-bracket">/&gt;</span></a>
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

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= e($settings['brand_name'] ?? 'JosDigital') ?>. Todos os direitos reservados.</p>
            <p>Desenvolvido por <strong class="text-gradient"><?= e($settings['owner_name'] ?? 'José Silva Bentos') ?></strong></p>
        </div>
    </div>
</footer>

<button class="back-to-top" id="backToTop" aria-label="Voltar ao topo"><i class="fas fa-chevron-up"></i></button>
<script src="assets/js/script.js"></script>
</body>
</html>
