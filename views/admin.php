<?php
declare(strict_types=1);

$tab = $tab ?? 'dashboard';
$settings = $settings ?? [];
$sections = $sections ?? [];
$hero = $hero ?? [];
$about = $about ?? [];
$services = $services ?? [];
$technologies = $technologies ?? [];
$process = $process ?? [];
$projects = $projects ?? [];
$contacts = $contacts ?? [];
$socials = $socials ?? [];
$footerLinks = $footerLinks ?? [];
$editingProject = $editingProject ?? [];
$editingImages = $editingImages ?? [];
$selectedTechIds = $selectedTechIds ?? [];
$flash = $flash ?? null;
$messages = $messages ?? [];
$ratings = $ratings ?? [];

$imageBySlot = [];
foreach ($editingImages as $image) {
    $imageBySlot[(int) $image['slot']] = $image;
}
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração | <?= e($settings['brand_name'] ?? 'JosDigital') ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <div class="brand">JOSÉ BENTOS</div>
        <button class="side-toggle" id="sideToggle" type="button" aria-label="Abrir menu">☰</button>
        <nav>
            <?php
            $nav = [
                'dashboard' => 'Dashboard',
                'settings' => 'Configurações',
                'header' => 'Header',
                'sections' => 'Seções',
                'hero' => 'Hero',
                'about' => 'Sobre',
                'services' => 'Serviços',
                'technologies' => 'Tecnologias',
                'process' => 'Processo',
                'projects' => 'Projetos',
                'messages' => 'Mensagens recebidas',
                'ratings' => 'Avaliações por estrelas',
                'contact' => 'Contactos',
                'social' => 'Redes sociais',
                'footer' => 'Footer',
            ];
            foreach ($nav as $key => $label):
            ?>
                <a href="admin.php?tab=<?= urlencode($key) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
                    <span><?= e($label) ?></span>
                    <?php if ($key === 'messages' && !empty($counts['messages'])): ?><span class="nav-count"><?= (int) $counts['messages'] ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-top">
            <div>
                <h1><?= e($nav[$tab] ?? 'Dashboard') ?></h1>
                <p>Olá, <?= e($_SESSION['admin_name'] ?? 'Administrador') ?>.</p>
            </div>
            <div class="admin-top-actions">
                <a href="index.php" target="_blank" rel="noopener noreferrer">Ver site</a>
                <a href="admin.php?action=register">Nova conta admin</a>
                <a href="admin.php?action=logout">Sair</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($tab === 'dashboard'): ?>
            <div class="cards">
                <div><strong><?= number_format($counts['projects'], 0, ',', '.') ?></strong><span>Projetos</span></div>
                <div><strong><?= number_format($counts['published'], 0, ',', '.') ?></strong><span>Publicados</span></div>
                <div><strong><?= number_format($counts['views'], 0, ',', '.') ?></strong><span>Visualizações</span></div>
                <div><strong><?= number_format($counts['ratings'], 0, ',', '.') ?></strong><span>Avaliações</span></div>
                <div><strong><?= number_format($counts['messages'], 0, ',', '.') ?></strong><span>Mensagens novas</span></div>
            </div>
            <div class="admin-panel">
                <h2>Estrutura sincronizada</h2>
                <p class="admin-note">As imagens e ícones são servidos a partir da tabela <strong>media</strong> através de <code>media.php?id=...</code>. Cada projeto aceita até quatro slots: 1 capa + 3 imagens.</p>
            </div>

        <?php elseif ($tab === 'settings'): ?>
            <form class="admin-panel form-grid" method="post">
                <input type="hidden" name="action" value="save_settings"><input type="hidden" name="tab" value="settings"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <label>Nome do site<input name="site_name" value="<?= e($settings['site_name'] ?? '') ?>" required></label>
                <label>Nome do proprietário<input name="owner_name" value="<?= e($settings['owner_name'] ?? '') ?>" required></label>
                <label>Título SEO<input name="site_title" value="<?= e($settings['site_title'] ?? '') ?>"></label>
                <label>WhatsApp<input name="whatsapp" value="<?= e($settings['whatsapp'] ?? '') ?>"></label>
                <label>Email<input type="email" name="email" value="<?= e($settings['email'] ?? '') ?>"></label>
                <label class="full">Descrição SEO<textarea name="meta_description"><?= e($settings['meta_description'] ?? '') ?></textarea></label>
                <button class="admin-btn" type="submit">Guardar</button>
            </form>

            <form class="admin-panel form-grid" method="post" action="admin.php?tab=settings" autocomplete="off">
                <input type="hidden" name="action" value="change_password"><input type="hidden" name="tab" value="settings"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <h2 class="full">Alterar palavra-passe</h2>
                <p class="upload-hint full">Recomendado após o primeiro acesso. Use pelo menos 10 caracteres e não reutilize uma palavra-passe usada noutro serviço.</p>
                <label class="full">Palavra-passe actual<input type="password" name="current_password" required autocomplete="current-password"></label>
                <label>Nova palavra-passe<input type="password" name="new_password" minlength="10" required autocomplete="new-password"></label>
                <label>Confirmar nova palavra-passe<input type="password" name="password_confirmation" minlength="10" required autocomplete="new-password"></label>
                <button class="admin-btn" type="submit">Actualizar palavra-passe</button>
            </form>

        <?php elseif ($tab === 'messages'): ?>
            <div class="admin-panel">
                <h2>Mensagens recebidas pelo formulário do site</h2>
                <p class="upload-hint">São mostradas as 200 mensagens mais recentes. Altere o estado para organizar o acompanhamento.</p>
                <?php if (empty($messages)): ?>
                    <p class="admin-note">Ainda não existem mensagens recebidas.</p>
                <?php else: ?>
                    <div class="admin-table">
                        <table>
                            <thead><tr><th>Data</th><th>Remetente</th><th>Mensagem</th><th>Estado</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $message['created_at']))) ?></td>
                                    <td><strong><?= e($message['name']) ?></strong><br><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a><?php if (!empty($message['phone'])): ?><br><small><?= e($message['phone']) ?></small><?php endif; ?></td>
                                    <td class="message-cell"><?= nl2br(e($message['message'])) ?></td>
                                    <td><span class="message-status status-<?= e($message['status']) ?>"><?= e(['new' => 'Nova', 'read' => 'Lida', 'replied' => 'Respondida', 'archived' => 'Arquivada'][$message['status']] ?? $message['status']) ?></span></td>
                                    <td>
                                        <form method="post" action="admin.php?tab=messages" class="message-action-form">
                                            <input type="hidden" name="action" value="update_message_status"><input type="hidden" name="tab" value="messages"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                                            <select name="status" aria-label="Estado da mensagem">
                                                <?php foreach (['new' => 'Nova', 'read' => 'Lida', 'replied' => 'Respondida', 'archived' => 'Arquivada'] as $statusValue => $statusLabel): ?>
                                                    <option value="<?= e($statusValue) ?>" <?= $message['status'] === $statusValue ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="admin-btn" type="submit">Guardar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($tab === 'ratings'): ?>
            <div class="admin-panel">
                <h2>Avaliações dos projectos</h2>
                <p class="upload-hint">Cada linha representa uma avaliação enviada. O e-mail é visível apenas nesta área administrativa. Avaliações antigas sem e-mail são identificadas separadamente.</p>
                <?php if (empty($ratings)): ?>
                    <p class="admin-note">Ainda não existem avaliações registadas.</p>
                <?php else: ?>
                    <div class="admin-table">
                        <table>
                            <thead><tr><th>Data</th><th>Projecto</th><th>E-mail</th><th>Estrelas</th><th>Classificação</th></tr></thead>
                            <tbody>
                            <?php foreach ($ratings as $ratingRow): ?>
                                <?php $ratingScore = max(0, min(5, (int) $ratingRow['rating'])); ?>
                                <tr>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $ratingRow['created_at']))) ?></td>
                                    <td><?= e($ratingRow['project_title'] ?? 'Projecto removido') ?></td>
                                    <td><?php if (!empty($ratingRow['email'])): ?><a href="mailto:<?= e($ratingRow['email']) ?>"><?= e($ratingRow['email']) ?></a><?php else: ?><span class="admin-muted">Sem e-mail (avaliação antiga)</span><?php endif; ?></td>
                                    <td><span class="rating-stars" aria-label="<?= $ratingScore ?> de 5 estrelas"><?php for ($star = 1; $star <= 5; $star++): ?><span class="<?= $star <= $ratingScore ? 'is-filled' : '' ?>">★</span><?php endfor; ?></span></td>
                                    <td><strong><?= $ratingScore ?>/5</strong></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="upload-hint">São apresentadas até 500 avaliações mais recentes.</p>
                <?php endif; ?>
            </div>

        <?php elseif ($tab === 'header'): ?>
            <form class="admin-panel form-grid" method="post">
                <input type="hidden" name="action" value="save_header"><input type="hidden" name="tab" value="header"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <label>Marca exibida no header<input name="brand_name" value="<?= e($settings['brand_name'] ?? '') ?>" required></label>
                <button class="admin-btn" type="submit">Guardar header</button>
            </form>

        <?php elseif ($tab === 'sections'): ?>
            <div class="admin-list">
                <?php foreach ($sections as $section): ?>
                    <form class="admin-panel form-grid" method="post">
                        <input type="hidden" name="action" value="save_section"><input type="hidden" name="tab" value="sections"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $section['id'] ?>">
                        <div class="section-key"><strong><?= e($section['section_key']) ?></strong></div>
                        <label>Menu<input name="menu_label" value="<?= e($section['menu_label']) ?>"></label>
                        <label>Título<input name="title" value="<?= e($section['title']) ?>"></label>
                        <label>Destaque do título<input name="title_highlight" value="<?= e($section['title_highlight']) ?>"></label>
                        <label>Ordem<input type="number" name="sort_order" value="<?= (int) $section['sort_order'] ?>"></label>
                        <label>Limite de itens<input type="number" min="1" name="item_limit" value="<?= $section['item_limit'] !== null ? (int) $section['item_limit'] : '' ?>"></label>
                        <label class="full">Subtítulo<textarea name="subtitle"><?= e($section['subtitle']) ?></textarea></label>
                        <label class="check"><input type="checkbox" name="is_active" <?= !empty($section['is_active']) ? 'checked' : '' ?>> Ativa</label>
                        <button class="admin-btn" type="submit">Guardar seção</button>
                    </form>
                <?php endforeach; ?>
            </div>

        <?php elseif ($tab === 'hero'): ?>
            <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_hero"><input type="hidden" name="tab" value="hero"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <label>Badge<input name="badge" value="<?= e($hero['badge'] ?? '') ?>"></label>
                <label>Título<input name="title" value="<?= e($hero['title'] ?? '') ?>"></label>
                <label>Subtítulo<input name="subtitle" value="<?= e($hero['subtitle'] ?? '') ?>"></label>
                <label>Botão principal<input name="button_primary" value="<?= e($hero['button_primary'] ?? '') ?>"></label>
                <label>URL botão principal<input name="button_primary_url" value="<?= e($hero['button_primary_url'] ?? '') ?>"></label>
                <label>Botão secundário<input name="button_secondary" value="<?= e($hero['button_secondary'] ?? '') ?>"></label>
                <label>URL botão secundário<input name="button_secondary_url" value="<?= e($hero['button_secondary_url'] ?? '') ?>"></label>
                <label class="full">Descrição<textarea name="description" rows="5"><?= e($hero['description'] ?? '') ?></textarea></label>
                <div class="media-upload full">
                    <strong>Imagem do Hero</strong><small class="upload-hint">JPG, PNG, GIF, WEBP ou SVG. Máximo 5 MB; imagens raster até 6000 × 6000 px e 25 megapíxeis.</small>
                    <?php if (!empty($hero['media_id'])): ?><img class="media-preview" src="media.php?id=<?= (int) $hero['media_id'] ?>" alt="Hero"><label class="check"><input type="checkbox" name="remove_media"> Remover imagem atual</label><?php endif; ?>
                    <input type="file" name="hero_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="5242880" data-max-dimension="6000" data-max-pixels="25000000">
                </div>
                <button class="admin-btn" type="submit">Guardar Hero</button>
            </form>

        <?php elseif ($tab === 'about'): ?>
            <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_about"><input type="hidden" name="tab" value="about"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <label class="full">Título<input name="title" value="<?= e($about['title'] ?? '') ?>"></label>
                <label class="full">Descrição<textarea name="description" rows="10"><?= e($about['description'] ?? '') ?></textarea></label>
                <div class="media-upload full">
                    <strong>Imagem da secção Sobre</strong><small class="upload-hint">JPG, PNG, GIF, WEBP ou SVG. Máximo 5 MB; imagens raster até 6000 × 6000 px e 25 megapíxeis.</small>
                    <?php if (!empty($about['image_id'])): ?><img class="media-preview" src="media.php?id=<?= (int) $about['image_id'] ?>" alt="Sobre"><label class="check"><input type="checkbox" name="remove_media"> Remover imagem atual</label><?php endif; ?>
                    <input type="file" name="about_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="5242880" data-max-dimension="6000" data-max-pixels="25000000">
                </div>
                <button class="admin-btn" type="submit">Guardar Sobre</button>
            </form>

        <?php elseif ($tab === 'services'): ?>
            <div class="admin-list">
                <?php foreach ($services as $service): ?>
                    <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_service"><input type="hidden" name="tab" value="services"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                        <label>Título<input name="title" value="<?= e($service['title']) ?>" required></label>
                        <label>Ordem<input type="number" name="sort_order" value="<?= (int) $service['sort_order'] ?>"></label>
                        <label class="full">Descrição<textarea name="description"><?= e($service['description']) ?></textarea></label>
                        <div class="media-upload full"><strong>Ícone</strong><small class="upload-hint">JPG, PNG, GIF, WEBP ou SVG. Máximo 2 MB por ficheiro; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><?php if (!empty($service['icon_media_id'])): ?><img class="media-preview icon-preview" src="media.php?id=<?= (int) $service['icon_media_id'] ?>" alt="<?= e($service['title']) ?>"><label class="check"><input type="checkbox" name="remove_icon"> Remover ícone</label><?php endif; ?><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                        <label class="check"><input type="checkbox" name="is_active" <?= !empty($service['is_active']) ? 'checked' : '' ?>> Ativo</label>
                        <div class="button-row"><button class="admin-btn" type="submit">Guardar</button><button class="admin-btn danger" type="submit" name="action" value="delete_service">Eliminar</button></div>
                    </form>
                <?php endforeach; ?>

                <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_service"><input type="hidden" name="tab" value="services"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <h2 class="full">Adicionar serviço</h2>
                    <label>Título<input name="title" required></label>
                    <label>Ordem<input type="number" name="sort_order" value="0"></label>
                    <label class="full">Descrição<textarea name="description"></textarea></label>
                    <div class="media-upload full"><strong>Ícone</strong><small class="upload-hint">JPG, PNG, GIF, WEBP ou SVG. Máximo 2 MB por ficheiro; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                    <label class="check"><input type="checkbox" name="is_active" checked> Ativo</label>
                    <button class="admin-btn" type="submit">Adicionar</button>
                </form>
            </div>

        <?php elseif ($tab === 'technologies'): ?>
            <div class="admin-list">
                <?php foreach ($technologies as $technology): ?>
                    <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_tech"><input type="hidden" name="tab" value="technologies"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $technology['id'] ?>">
                        <label>Categoria<input name="category" value="<?= e($technology['category']) ?>" required></label>
                        <label>Nome<input name="name" value="<?= e($technology['name']) ?>" required></label>
                        <label>Nível %<input type="number" name="level" value="<?= (int) $technology['level'] ?>" min="0" max="100"></label>
                        <label>Ordem<input type="number" name="sort_order" value="<?= (int) $technology['sort_order'] ?>"></label>
                        <div class="media-upload full"><strong>Ícone</strong><small class="upload-hint">JPG, PNG, GIF, WEBP ou SVG. Máximo 2 MB por ficheiro; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><?php if (!empty($technology['icon_media_id'])): ?><img class="media-preview icon-preview" src="media.php?id=<?= (int) $technology['icon_media_id'] ?>" alt="<?= e($technology['name']) ?>"><label class="check"><input type="checkbox" name="remove_icon"> Remover ícone</label><?php endif; ?><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                        <label class="check"><input type="checkbox" name="is_active" <?= !empty($technology['is_active']) ? 'checked' : '' ?>> Ativo</label>
                        <div class="button-row"><button class="admin-btn" type="submit">Guardar</button><button class="admin-btn danger" type="submit" name="action" value="delete_tech">Eliminar</button></div>
                    </form>
                <?php endforeach; ?>

                <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_tech"><input type="hidden" name="tab" value="technologies"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <h2 class="full">Adicionar tecnologia</h2>
                    <label>Categoria<input name="category" required></label><label>Nome<input name="name" required></label><label>Nível %<input type="number" name="level" value="80" min="0" max="100"></label><label>Ordem<input type="number" name="sort_order" value="0"></label>
                    <div class="media-upload full"><strong>Ícone</strong><small class="upload-hint">JPG, PNG, GIF, WEBP ou SVG. Máximo 2 MB por ficheiro; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                    <label class="check"><input type="checkbox" name="is_active" checked> Ativo</label>
                    <button class="admin-btn" type="submit">Adicionar</button>
                </form>
            </div>

        <?php elseif ($tab === 'process'): ?>
            <div class="admin-list">
                <?php foreach ($process as $step): ?>
                    <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_process"><input type="hidden" name="tab" value="process"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $step['id'] ?>">
                        <label>Número<input type="number" name="number" value="<?= (int) $step['number'] ?>"></label><label>Título<input name="title" value="<?= e($step['title']) ?>" required></label><label>Ordem<input type="number" name="sort_order" value="<?= (int) $step['sort_order'] ?>"></label>
                        <label class="full">Descrição<textarea name="description"><?= e($step['description']) ?></textarea></label>
                        <div class="media-upload full"><strong>Ícone</strong><small class="upload-hint">JPG, PNG, GIF, WEBP ou SVG. Máximo 2 MB por ficheiro; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><?php if (!empty($step['icon_media_id'])): ?><img class="media-preview icon-preview" src="media.php?id=<?= (int) $step['icon_media_id'] ?>" alt="<?= e($step['title']) ?>"><label class="check"><input type="checkbox" name="remove_icon"> Remover ícone</label><?php endif; ?><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                        <label class="check"><input type="checkbox" name="is_active" <?= !empty($step['is_active']) ? 'checked' : '' ?>> Ativo</label>
                        <div class="button-row"><button class="admin-btn" type="submit">Guardar</button><button class="admin-btn danger" type="submit" name="action" value="delete_process">Eliminar</button></div>
                    </form>
                <?php endforeach; ?>

                <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_process"><input type="hidden" name="tab" value="process"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="0">
                    <h2 class="full">Adicionar etapa ao processo</h2>
                    <label>Número<input type="number" name="number" value="<?= count($process) + 1 ?>" min="1" required></label>
                    <label>Título<input name="title" required maxlength="200" placeholder="Ex.: Briefing e planeamento"></label>
                    <label>Ordem<input type="number" name="sort_order" value="<?= count($process) + 1 ?>"></label>
                    <label class="full">Descrição<textarea name="description" placeholder="Descreva brevemente esta etapa."></textarea></label>
                    <div class="media-upload full"><strong>Ícone da etapa</strong><small class="upload-hint">Opcional. Máximo 2 MB; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                    <label class="check"><input type="checkbox" name="is_active" checked> Activo no site</label>
                    <button class="admin-btn" type="submit">Adicionar etapa</button>
                </form>
            </div>

        <?php elseif ($tab === 'projects'): ?>
            <div class="project-admin-head">
                <a class="admin-btn" href="admin.php?tab=projects&new=1">+ Novo projeto</a>
            </div>

            <?php if (!empty($_GET['new']) || !empty($editingProject)): ?>
                <form class="admin-panel project-form" method="post" action="admin.php?tab=projects<?= !empty($editingProject['id']) ? '&amp;edit=' . (int) $editingProject['id'] : '&amp;new=1' ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_project"><input type="hidden" name="tab" value="projects"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) ($editingProject['id'] ?? 0) ?>">
                    <div class="form-grid">
                        <label>Título<input required name="title" value="<?= e($editingProject['title'] ?? '') ?>"></label>
                        <label>Slug<input name="slug" value="<?= e($editingProject['slug'] ?? '') ?>"></label>
                        <label>Categoria<input name="category" value="<?= e($editingProject['category'] ?? '') ?>"></label>
                        <label>Estado<select name="status"><option value="draft" <?= ($editingProject['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option><option value="published" <?= ($editingProject['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option><option value="archived" <?= ($editingProject['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Arquivado</option></select></label>
                        <label>Ordem<input type="number" name="sort_order" value="<?= (int) ($editingProject['sort_order'] ?? 0) ?>"></label>
                        <label class="check"><input type="checkbox" name="featured" <?= !empty($editingProject['featured']) ? 'checked' : '' ?>> Destaque</label>
                        <label class="full">Mini descrição<textarea name="short_description"><?= e($editingProject['short_description'] ?? '') ?></textarea></label>
                        <label class="full">Descrição completa<textarea name="description" rows="10"><?= e($editingProject['description'] ?? '') ?></textarea></label>
                        <label>URL<input name="project_url" value="<?= e($editingProject['project_url'] ?? '') ?>"></label>
                        <label>GitHub<input name="github_url" value="<?= e($editingProject['github_url'] ?? '') ?>"></label>
                    </div>

                    <h3>Imagens do projecto — máximo 4</h3><p class="upload-hint form-help">Cada imagem pode ter até 5 MB, dimensões até 6000 × 6000 px e 25 megapíxeis. Se enviar as quatro imagens de uma vez, o servidor deve permitir post_max_size de pelo menos 32M.</p>
                    <div class="image-slots">
                        <?php for ($slot = 1; $slot <= 4; $slot++): $image = $imageBySlot[$slot] ?? null; ?>
                            <div class="slot">
                                <strong><?= $slot === 1 ? 'CAPA (slot 1)' : 'IMAGEM ' . $slot ?></strong>
                                <?php if ($image): ?><img src="media.php?id=<?= (int) $image['media_id'] ?>" alt="<?= e($image['title'] ?? '') ?>"><?php endif; ?>
                                <label>Título da imagem<input name="image_title_<?= $slot ?>" value="<?= e($image['title'] ?? '') ?>"></label>
                                <input type="file" name="image_<?= $slot ?>" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="5242880" data-max-dimension="6000" data-max-pixels="25000000">
                                <label>Descrição<textarea name="image_description_<?= $slot ?>" placeholder="Descrição desta imagem"><?= e($image['description'] ?? '') ?></textarea></label>
                                <?php if ($image): ?><label class="check"><input type="checkbox" name="remove_image_<?= $slot ?>"> Remover esta imagem</label><?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <h3>Tecnologias usadas</h3>
                    <div class="tech-check-grid">
                        <?php foreach ($technologies as $technology): ?>
                            <label class="check"><input type="checkbox" name="technologies[]" value="<?= (int) $technology['id'] ?>" <?= in_array((int) $technology['id'], $selectedTechIds, true) ? 'checked' : '' ?>> <?= e($technology['category'] . ' — ' . $technology['name']) ?></label>
                        <?php endforeach; ?>
                    </div>

                    <button class="admin-btn" type="submit">Guardar projeto</button>
                </form>
            <?php endif; ?>

            <div class="admin-table">
                <table>
                    <thead><tr><th>Projeto</th><th>Estado</th><th>Visitas</th><th>Estrelas</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?= e($project['title']) ?></td>
                            <td><?= e($project['status']) ?></td>
                            <td><?= number_format((int) $project['views'], 0, ',', '.') ?></td>
                            <td>★ <?= number_format((float) $project['rating_average'], 1, ',', '.') ?></td>
                            <td>
                                <a href="admin.php?tab=projects&edit=<?= (int) $project['id'] ?>">Editar</a>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="action" value="delete_project"><input type="hidden" name="tab" value="projects"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $project['id'] ?>">
                                    <button class="link-danger" type="submit" onclick="return confirm('Eliminar projeto e as relações das imagens?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($tab === 'contact'): ?>
            <div class="admin-list">
                <?php foreach ($contacts as $contact): ?>
                    <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_contact"><input type="hidden" name="tab" value="contact"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">
                        <label>Tipo<input name="type" value="<?= e($contact['type']) ?>" disabled></label>
                        <label>Etiqueta<input name="label" value="<?= e($contact['label']) ?>"></label>
                        <label class="full">Valor<input name="value" value="<?= e($contact['value']) ?>" required></label>
                        <label>Ordem<input type="number" name="sort_order" value="<?= (int) ($contact['sort_order'] ?? 0) ?>"></label>
                        <div class="media-upload full"><strong>Ícone</strong><small class="upload-hint">JPG, PNG, GIF, WEBP ou SVG. Máximo 2 MB por ficheiro; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><?php if (!empty($contact['icon_media_id'])): ?><img class="media-preview icon-preview" src="media.php?id=<?= (int) $contact['icon_media_id'] ?>" alt="<?= e($contact['label']) ?>"><label class="check"><input type="checkbox" name="remove_icon"> Remover ícone</label><?php endif; ?><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                        <label class="check"><input type="checkbox" name="is_active" <?= !empty($contact['is_active']) ? 'checked' : '' ?>> Ativo</label>
                        <div class="button-row"><button class="admin-btn" type="submit">Guardar</button><button class="admin-btn danger" type="submit" name="action" value="delete_contact" onclick="return confirm('Eliminar este contacto?')">Eliminar</button></div>
                    </form>
                <?php endforeach; ?>

                <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_contact"><input type="hidden" name="tab" value="contact"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="0">
                    <h2 class="full">Adicionar contacto</h2>
                    <label>Tipo<select name="type"><option value="email">Email</option><option value="telefone">Telefone</option><option value="whatsapp">WhatsApp</option><option value="localizacao">Localização</option><option value="outro" selected>Outro</option></select></label>
                    <label>Etiqueta<input name="label" required maxlength="100" placeholder="Ex.: WhatsApp profissional"></label>
                    <label class="full">Valor<input name="value" required maxlength="255" placeholder="Número, email ou endereço"></label>
                    <label>Ordem<input type="number" name="sort_order" value="0"></label>
                    <div class="media-upload full"><strong>Ícone do contacto</strong><small class="upload-hint">Opcional. Máximo 2 MB; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                    <label class="check"><input type="checkbox" name="is_active" checked> Activo no site</label>
                    <button class="admin-btn" type="submit">Adicionar contacto</button>
                </form>
            </div>

        <?php elseif ($tab === 'social'): ?>
            <div class="admin-list">
                <?php foreach ($socials as $social): ?>
                    <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_social"><input type="hidden" name="tab" value="social"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $social['id'] ?>">
                        <label>Plataforma<input name="platform" value="<?= e($social['platform']) ?>"></label>
                        <label>URL<input type="url" name="url" value="<?= e($social['url']) ?>" placeholder="https://..." required></label>
                        <label>Ordem<input type="number" name="sort_order" value="<?= (int) ($social['sort_order'] ?? 0) ?>"></label>
                        <div class="media-upload full"><strong>Ícone</strong><small class="upload-hint">JPG, PNG, GIF, WEBP ou SVG. Máximo 2 MB por ficheiro; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><?php if (!empty($social['icon_media_id'])): ?><img class="media-preview icon-preview" src="media.php?id=<?= (int) $social['icon_media_id'] ?>" alt="<?= e($social['platform']) ?>"><label class="check"><input type="checkbox" name="remove_icon"> Remover ícone</label><?php endif; ?><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                        <label class="check"><input type="checkbox" name="is_active" <?= !empty($social['is_active']) ? 'checked' : '' ?>> Ativo</label>
                        <div class="button-row"><button class="admin-btn" type="submit">Guardar</button><button class="admin-btn danger" type="submit" name="action" value="delete_social" onclick="return confirm('Eliminar esta rede social?')">Eliminar</button></div>
                    </form>
                <?php endforeach; ?>

                <form class="admin-panel form-grid" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_social"><input type="hidden" name="tab" value="social"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="0">
                    <h2 class="full">Adicionar rede social</h2>
                    <label>Plataforma<input name="platform" required maxlength="100" placeholder="Ex.: LinkedIn"></label>
                    <label>URL<input type="url" name="url" required placeholder="https://..."></label>
                    <label>Ordem<input type="number" name="sort_order" value="0"></label>
                    <div class="media-upload full"><strong>Ícone da rede social</strong><small class="upload-hint">Opcional. Máximo 2 MB; imagens raster até 1500 × 1500 px e 4 megapíxeis.</small><input type="file" name="icon_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-max-bytes="2097152" data-max-dimension="1500" data-max-pixels="4000000"></div>
                    <label class="check"><input type="checkbox" name="is_active" checked> Activa no site</label>
                    <button class="admin-btn" type="submit">Adicionar rede social</button>
                </form>
            </div>

        <?php elseif ($tab === 'footer'): ?>
            <div class="admin-list">
                <?php foreach ($footerLinks as $link): ?>
                    <form class="admin-panel form-grid" method="post">
                        <input type="hidden" name="action" value="save_footer"><input type="hidden" name="tab" value="footer"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                        <label>Grupo<input name="group_title" value="<?= e($link['group_title']) ?>"></label><label>Texto<input name="label" value="<?= e($link['label']) ?>"></label><label>URL<input name="url" value="<?= e($link['url']) ?>"></label><label>Ordem<input type="number" name="sort_order" value="<?= (int) $link['sort_order'] ?>"></label><label class="check"><input type="checkbox" name="is_active" <?= !empty($link['is_active']) ? 'checked' : '' ?>> Ativo</label>
                        <div class="button-row"><button class="admin-btn" type="submit">Guardar</button><button class="admin-btn danger" type="submit" name="action" value="delete_footer">Eliminar</button></div>
                    </form>
                <?php endforeach; ?>

                <form class="admin-panel form-grid" method="post">
                    <input type="hidden" name="action" value="add_footer"><input type="hidden" name="tab" value="footer"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <h2 class="full">Adicionar link ao footer</h2>
                    <label>Grupo<input name="group_title" required></label><label>Texto<input name="label" required></label><label>URL<input name="url" required></label><label>Ordem<input type="number" name="sort_order" value="0"></label>
                    <button class="admin-btn" type="submit">Adicionar</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>
<script src="assets/js/admin.js"></script>
</body>
</html>
