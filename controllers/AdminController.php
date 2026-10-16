<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Content.php';
require_once __DIR__ . '/../models/Project.php';

final class AdminController
{
    public function run(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

        if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->login();
            return;
        }

        if ($action === 'register') {
            if (empty($_SESSION['admin_id'])) {
                try {
                    $activeAdmins = (int) db()->query('SELECT COUNT(*) FROM admin_users WHERE status = 1')->fetchColumn();
                } catch (Throwable $e) {
                    error_log('JosPortifolio registration guard error: ' . $e->getMessage());
                    $activeAdmins = 1; // Em caso de dúvida, bloquear registo público.
                }
                if ($activeAdmins > 0) {
                    $error = 'Por segurança, novas contas só podem ser criadas por um administrador autenticado. Entre no painel primeiro.';
                    require __DIR__ . '/../views/login.php';
                    return;
                }
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->register();
            } else {
                $error = null;
                require __DIR__ . '/../views/register.php';
            }
            return;
        }

        if ($action === 'logout') {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
            redirect('admin.php');
        }

        if (empty($_SESSION['admin_id'])) {
            $error = null;
            require __DIR__ . '/../views/login.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->requestBodyExceedsPostLimit()) {
                flash('error', 'O formulário excede o limite de envio configurado no servidor. Reduza o tamanho/quantidade das imagens ou configure post_max_size para pelo menos 32M e upload_max_filesize para 8M no PHP.');
                $uploadTab = $this->safeTab((string) ($_GET['tab'] ?? 'projects'));
                if ($uploadTab === 'projects' && (int) ($_GET['edit'] ?? 0) > 0) {
                    redirect('admin.php?tab=projects&edit=' . (int) $_GET['edit']);
                }
                if ($uploadTab === 'projects' && !empty($_GET['new'])) {
                    redirect('admin.php?tab=projects&new=1');
                }
                redirect('admin.php?tab=' . urlencode($uploadTab));
            }
            check_csrf();
            $this->handlePost($action);
            return;
        }

        $this->dashboard();
    }

    private function register(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        try {
            check_csrf();

            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $passwordConfirm = (string) ($_POST['password_confirmation'] ?? '');

            $nameLength = $this->unicodeLength($name);
            $emailLength = $this->unicodeLength($email);
            if ($nameLength < 2 || $nameLength > 150) {
                throw new InvalidArgumentException('Informe um nome válido entre 2 e 150 caracteres.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $emailLength > 255) {
                throw new InvalidArgumentException('Informe um endereço de email válido.');
            }

            if (strlen($password) < 10) {
                throw new InvalidArgumentException('A palavra-passe deve ter pelo menos 10 caracteres.');
            }

            if ($password !== $passwordConfirm) {
                throw new InvalidArgumentException('As palavras-passe não coincidem.');
            }

            $existing = db()->prepare('SELECT id FROM admin_users WHERE email = :email LIMIT 1');
            $existing->execute(['email' => $email]);
            if ($existing->fetchColumn()) {
                throw new InvalidArgumentException('Já existe uma conta com este email.');
            }

            $stmt = db()->prepare(
                'INSERT INTO admin_users (name, email, password, status)
                 VALUES (:name, :email, :password, 1)'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            flash('success', 'Conta criada com sucesso. Agora entre no painel administrativo.');
            redirect('admin.php');
        } catch (Throwable $e) {
            error_log('JosPortifolio registration error: ' . $e->getMessage());
            $error = $e instanceof InvalidArgumentException || $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível criar a conta.';
            require __DIR__ . '/../views/register.php';
        }
    }

    private function login(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        check_csrf();

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = Admin::userByEmail($email);

        if ($user && password_verify($password, (string) $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $user['id'];
            $_SESSION['admin_name'] = (string) $user['name'];
            flash('success', 'Sessão iniciada com sucesso.');
            redirect('admin.php');
        }

        $error = 'Email ou palavra-passe inválidos.';
        require __DIR__ . '/../views/login.php';
    }

    private function handlePost(string $action): void
    {
        $redirectUrl = null;
        try {
            switch ($action) {
                case 'save_settings':
                    $this->saveSettings();
                    break;
                case 'change_password':
                    $this->changePassword();
                    break;
                case 'update_message_status':
                    $this->updateMessageStatus();
                    break;
                case 'save_header':
                    $this->saveHeader();
                    break;
                case 'save_section':
                    $this->saveSection();
                    break;
                case 'save_hero':
                    $this->saveHero();
                    break;
                case 'save_about':
                    $this->saveAbout();
                    break;
                case 'save_service':
                    $this->saveService();
                    break;
                case 'delete_service':
                    $this->deleteService();
                    break;
                case 'save_tech':
                    $this->saveTechnology();
                    break;
                case 'delete_tech':
                    $this->deleteTechnology();
                    break;
                case 'save_process':
                    $this->saveProcess();
                    break;
                case 'delete_process':
                    $this->deleteProcess();
                    break;
                case 'save_project':
                    $savedProjectId = $this->saveProject();
                    $redirectUrl = 'admin.php?tab=projects&edit=' . $savedProjectId;
                    break;
                case 'delete_project':
                    $this->deleteProject();
                    break;
                case 'save_contact':
                    $this->saveContact();
                    break;
                case 'delete_contact':
                    $this->deleteContact();
                    break;
                case 'save_social':
                    $this->saveSocial();
                    break;
                case 'delete_social':
                    $this->deleteSocial();
                    break;
                case 'save_footer':
                    $this->saveFooter();
                    break;
                case 'add_footer':
                    $this->addFooter();
                    break;
                case 'delete_footer':
                    $this->deleteFooter();
                    break;
                default:
                    throw new InvalidArgumentException('Ação administrativa desconhecida.');
            }

            flash('success', 'Alterações guardadas com sucesso.');
        } catch (Throwable $e) {
            error_log('JosPortifolio admin error: ' . $e->getMessage());
            flash('error', $e instanceof InvalidArgumentException || $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível guardar as alterações. Consulte o registo de erros do PHP para mais detalhes.');
            if ($action === 'save_project') {
                $failedId = (int) ($_POST['id'] ?? 0);
                $redirectUrl = $failedId > 0
                    ? 'admin.php?tab=projects&edit=' . $failedId
                    : 'admin.php?tab=projects&new=1';
            }
        }

        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }
        $tab = $this->safeTab((string) ($_POST['tab'] ?? 'dashboard'));
        redirect('admin.php?tab=' . urlencode($tab));
    }

    private function saveSettings(): void
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO site_settings
                (id, site_name, brand_name, owner_name, site_title, meta_description, whatsapp, email)
             VALUES
                (1, :site_name, :brand_name, :owner_name, :site_title, :meta_description, :whatsapp, :email)
             ON DUPLICATE KEY UPDATE
                site_name = VALUES(site_name),
                owner_name = VALUES(owner_name),
                site_title = VALUES(site_title),
                meta_description = VALUES(meta_description),
                whatsapp = VALUES(whatsapp),
                email = VALUES(email)'
        );
        $settings = Content::settings();
        $stmt->execute([
            'site_name' => trim((string) ($_POST['site_name'] ?? '')),
            'brand_name' => (string) ($settings['brand_name'] ?? 'JosDigital'),
            'owner_name' => trim((string) ($_POST['owner_name'] ?? '')),
            'site_title' => trim((string) ($_POST['site_title'] ?? '')),
            'meta_description' => trim((string) ($_POST['meta_description'] ?? '')),
            'whatsapp' => trim((string) ($_POST['whatsapp'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
        ]);
    }

    private function unicodeLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }
        $count = preg_match_all('/./us', $value);
        return $count === false ? strlen($value) : $count;
    }

    private function changePassword(): void
    {
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($adminId < 1) {
            throw new RuntimeException('A sessão expirou. Entre novamente no painel.');
        }
        if (strlen($newPassword) < 10) {
            throw new InvalidArgumentException('A nova palavra-passe deve ter pelo menos 10 caracteres.');
        }
        if ($newPassword !== $confirmation) {
            throw new InvalidArgumentException('A confirmação da palavra-passe não coincide.');
        }

        $stmt = db()->prepare('SELECT password FROM admin_users WHERE id = :id AND status = 1 LIMIT 1');
        $stmt->execute(['id' => $adminId]);
        $currentHash = $stmt->fetchColumn();
        if (!is_string($currentHash) || !password_verify($currentPassword, $currentHash)) {
            throw new InvalidArgumentException('A palavra-passe actual está incorrecta.');
        }
        if (password_verify($newPassword, $currentHash)) {
            throw new InvalidArgumentException('A nova palavra-passe deve ser diferente da actual.');
        }

        $update = db()->prepare('UPDATE admin_users SET password = :password WHERE id = :id');
        $update->execute([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $adminId,
        ]);
        session_regenerate_id(true);
    }

    private function updateMessageStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'new');
        $allowed = ['new', 'read', 'replied', 'archived'];
        if ($id < 1 || !in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Estado ou identificador de mensagem inválido.');
        }
        $exists = db()->prepare('SELECT id FROM contact_messages WHERE id = :id LIMIT 1');
        $exists->execute(['id' => $id]);
        if (!$exists->fetchColumn()) {
            throw new InvalidArgumentException('Esta mensagem já não existe. Actualize a página.');
        }
        db()->prepare('UPDATE contact_messages SET status = :status WHERE id = :id')
            ->execute(['status' => $status, 'id' => $id]);
    }

    private function saveHeader(): void
    {
        $stmt = db()->prepare(
            'INSERT INTO site_settings
                (id, site_name, brand_name, owner_name)
             VALUES (1, :site_name, :brand_name, :owner_name)
             ON DUPLICATE KEY UPDATE brand_name = VALUES(brand_name)'
        );
        $settings = Content::settings();
        $stmt->execute([
            'site_name' => (string) ($settings['site_name'] ?? 'JosPortifolio'),
            'brand_name' => trim((string) ($_POST['brand_name'] ?? 'JosDigital')),
            'owner_name' => (string) ($settings['owner_name'] ?? 'José Silva Bentos'),
        ]);
    }

    private function saveSection(): void
    {
        $stmt = db()->prepare(
            'UPDATE site_sections
             SET menu_label = :menu_label,
                 title = :title,
                 title_highlight = :title_highlight,
                 subtitle = :subtitle,
                 sort_order = :sort_order,
                 item_limit = :item_limit,
                 is_active = :is_active
             WHERE id = :id'
        );
        $itemLimit = trim((string) ($_POST['item_limit'] ?? ''));
        $stmt->execute([
            'menu_label' => trim((string) ($_POST['menu_label'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'title_highlight' => trim((string) ($_POST['title_highlight'] ?? '')),
            'subtitle' => trim((string) ($_POST['subtitle'] ?? '')),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'item_limit' => $itemLimit === '' ? null : (int) $itemLimit,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'id' => (int) ($_POST['id'] ?? 0),
        ]);
    }

    private function saveHero(): void
    {
        $pdo = db();
        $current = Content::hero();
        $mediaId = !empty($current['media_id']) ? (int) $current['media_id'] : null;

        if ($this->hasUpload('hero_file')) {
            $mediaId = Admin::storeMedia($_FILES['hero_file'], trim((string) ($_POST['title'] ?? 'Perfil')), 5 * 1024 * 1024, 6000, 25000000);
        } elseif (isset($_POST['remove_media']) && $mediaId) {
            $oldMedia = $mediaId;
            $mediaId = null;
            Admin::deleteMediaIfUnused($oldMedia);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO hero
                (id, badge, title, subtitle, description, button_primary, button_primary_url,
                 button_secondary, button_secondary_url, media_id)
             VALUES
                (1, :badge, :title, :subtitle, :description, :button_primary, :button_primary_url,
                 :button_secondary, :button_secondary_url, :media_id)
             ON DUPLICATE KEY UPDATE
                badge = VALUES(badge),
                title = VALUES(title),
                subtitle = VALUES(subtitle),
                description = VALUES(description),
                button_primary = VALUES(button_primary),
                button_primary_url = VALUES(button_primary_url),
                button_secondary = VALUES(button_secondary),
                button_secondary_url = VALUES(button_secondary_url),
                media_id = VALUES(media_id)'
        );
        $stmt->bindValue(':badge', trim((string) ($_POST['badge'] ?? '')));
        $stmt->bindValue(':title', trim((string) ($_POST['title'] ?? '')));
        $stmt->bindValue(':subtitle', trim((string) ($_POST['subtitle'] ?? '')));
        $stmt->bindValue(':description', trim((string) ($_POST['description'] ?? '')));
        $stmt->bindValue(':button_primary', trim((string) ($_POST['button_primary'] ?? '')));
        $stmt->bindValue(':button_primary_url', trim((string) ($_POST['button_primary_url'] ?? '')));
        $stmt->bindValue(':button_secondary', trim((string) ($_POST['button_secondary'] ?? '')));
        $stmt->bindValue(':button_secondary_url', trim((string) ($_POST['button_secondary_url'] ?? '')));
        $mediaId === null
            ? $stmt->bindValue(':media_id', null, PDO::PARAM_NULL)
            : $stmt->bindValue(':media_id', $mediaId, PDO::PARAM_INT);
        $stmt->execute();

        if (!empty($current['media_id']) && $mediaId !== (int) $current['media_id']) {
            Admin::deleteMediaIfUnused((int) $current['media_id']);
        }
    }

    private function saveAbout(): void
    {
        $current = Content::about();
        $mediaId = !empty($current['image_id']) ? (int) $current['image_id'] : null;

        if ($this->hasUpload('about_file')) {
            $mediaId = Admin::storeMedia($_FILES['about_file'], trim((string) ($_POST['title'] ?? 'Sobre')), 5 * 1024 * 1024, 6000, 25000000);
        } elseif (isset($_POST['remove_media']) && $mediaId) {
            $oldMedia = $mediaId;
            $mediaId = null;
            Admin::deleteMediaIfUnused($oldMedia);
        }

        $stmt = db()->prepare(
            'INSERT INTO about (id, title, description, image_id)
             VALUES (1, :title, :description, :image_id)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                description = VALUES(description),
                image_id = VALUES(image_id)'
        );
        $stmt->bindValue(':title', trim((string) ($_POST['title'] ?? '')));
        $stmt->bindValue(':description', trim((string) ($_POST['description'] ?? '')));
        $mediaId === null
            ? $stmt->bindValue(':image_id', null, PDO::PARAM_NULL)
            : $stmt->bindValue(':image_id', $mediaId, PDO::PARAM_INT);
        $stmt->execute();

        if (!empty($current['image_id']) && $mediaId !== (int) $current['image_id']) {
            Admin::deleteMediaIfUnused((int) $current['image_id']);
        }
    }

    private function saveService(): void
    {
        $pdo = db();
        $id = (int) ($_POST['id'] ?? 0);
        $current = $id ? Content::getService($id) : [];
        $mediaId = !empty($current['icon_media_id']) ? (int) $current['icon_media_id'] : null;

        if ($this->hasUpload('icon_file')) {
            $mediaId = Admin::storeMedia($_FILES['icon_file'], trim((string) ($_POST['title'] ?? 'Ícone')), 2 * 1024 * 1024, 1500, 4000000);
        } elseif (isset($_POST['remove_icon']) && $mediaId) {
            $oldMedia = $mediaId;
            $mediaId = null;
            Admin::deleteMediaIfUnused($oldMedia);
        }

        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE services
                 SET title = :title, description = :description,
                     icon_media_id = :icon_media_id,
                     sort_order = :sort_order, is_active = :is_active
                 WHERE id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO services
                    (title, description, icon_media_id, sort_order, is_active)
                 VALUES
                    (:title, :description, :icon_media_id, :sort_order, :is_active)'
            );
        }
        $stmt->bindValue(':title', trim((string) ($_POST['title'] ?? '')));
        $stmt->bindValue(':description', trim((string) ($_POST['description'] ?? '')));
        $mediaId === null ? $stmt->bindValue(':icon_media_id', null, PDO::PARAM_NULL) : $stmt->bindValue(':icon_media_id', $mediaId, PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', (int) ($_POST['sort_order'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':is_active', isset($_POST['is_active']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();

        if ($id && !empty($current['icon_media_id']) && $mediaId !== (int) $current['icon_media_id']) {
            Admin::deleteMediaIfUnused((int) $current['icon_media_id']);
        }
    }

    private function deleteService(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $service = Content::getService($id);
        db()->prepare('DELETE FROM services WHERE id = :id')->execute(['id' => $id]);
        if (!empty($service['icon_media_id'])) {
            Admin::deleteMediaIfUnused((int) $service['icon_media_id']);
        }
    }

    private function saveTechnology(): void
    {
        $pdo = db();
        $id = (int) ($_POST['id'] ?? 0);
        $current = $id ? $this->technologyById($id) : [];
        $mediaId = !empty($current['icon_media_id']) ? (int) $current['icon_media_id'] : null;

        if ($this->hasUpload('icon_file')) {
            $mediaId = Admin::storeMedia($_FILES['icon_file'], trim((string) ($_POST['name'] ?? 'Ícone')), 2 * 1024 * 1024, 1500, 4000000);
        } elseif (isset($_POST['remove_icon']) && $mediaId) {
            $oldMedia = $mediaId;
            $mediaId = null;
            Admin::deleteMediaIfUnused($oldMedia);
        }

        $params = [
            'category' => trim((string) ($_POST['category'] ?? '')),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'level' => max(0, min(100, (int) ($_POST['level'] ?? 80))),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE technologies
                 SET category = :category, name = :name,
                     icon_media_id = :icon_media_id, level = :level,
                     sort_order = :sort_order, is_active = :is_active
                 WHERE id = :id'
            );
            $params['id'] = $id;
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO technologies
                    (category, name, icon_media_id, level, sort_order, is_active)
                 VALUES
                    (:category, :name, :icon_media_id, :level, :sort_order, :is_active)'
            );
        }
        $stmt->bindValue(':category', $params['category']);
        $stmt->bindValue(':name', $params['name']);
        $mediaId === null ? $stmt->bindValue(':icon_media_id', null, PDO::PARAM_NULL) : $stmt->bindValue(':icon_media_id', $mediaId, PDO::PARAM_INT);
        $stmt->bindValue(':level', $params['level'], PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', $params['sort_order'], PDO::PARAM_INT);
        $stmt->bindValue(':is_active', $params['is_active'], PDO::PARAM_INT);
        if ($id) {
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        if ($id && !empty($current['icon_media_id']) && $mediaId !== (int) $current['icon_media_id']) {
            Admin::deleteMediaIfUnused((int) $current['icon_media_id']);
        }
    }

    private function deleteTechnology(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $row = $this->technologyById($id);
        db()->prepare('DELETE FROM technologies WHERE id = :id')->execute(['id' => $id]);
        if (!empty($row['icon_media_id'])) {
            Admin::deleteMediaIfUnused((int) $row['icon_media_id']);
        }
    }

    private function saveProcess(): void
    {
        $pdo = db();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('O título da etapa do processo é obrigatório.');
        }
        $current = $id ? $this->processById($id) : [];
        if ($id > 0 && !$current) {
            throw new InvalidArgumentException('Esta etapa já não existe. Actualize a página e tente novamente.');
        }
        $mediaId = !empty($current['icon_media_id']) ? (int) $current['icon_media_id'] : null;

        if ($this->hasUpload('icon_file')) {
            $mediaId = Admin::storeMedia($_FILES['icon_file'], trim((string) ($_POST['title'] ?? 'Ícone')), 2 * 1024 * 1024, 1500, 4000000);
        } elseif (isset($_POST['remove_icon']) && $mediaId) {
            $oldMedia = $mediaId;
            $mediaId = null;
            Admin::deleteMediaIfUnused($oldMedia);
        }

        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE process_steps
                 SET number = :number, title = :title, description = :description,
                     icon_media_id = :icon_media_id, sort_order = :sort_order,
                     is_active = :is_active
                 WHERE id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO process_steps
                    (number, title, description, icon_media_id, sort_order, is_active)
                 VALUES
                    (:number, :title, :description, :icon_media_id, :sort_order, :is_active)'
            );
        }
        $stmt->bindValue(':number', (int) ($_POST['number'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':description', trim((string) ($_POST['description'] ?? '')));
        $mediaId === null ? $stmt->bindValue(':icon_media_id', null, PDO::PARAM_NULL) : $stmt->bindValue(':icon_media_id', $mediaId, PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', (int) ($_POST['sort_order'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':is_active', isset($_POST['is_active']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();

        if ($id && !empty($current['icon_media_id']) && $mediaId !== (int) $current['icon_media_id']) {
            Admin::deleteMediaIfUnused((int) $current['icon_media_id']);
        }
    }

    private function deleteProcess(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $row = $this->processById($id);
        db()->prepare('DELETE FROM process_steps WHERE id = :id')->execute(['id' => $id]);
        if (!empty($row['icon_media_id'])) {
            Admin::deleteMediaIfUnused((int) $row['icon_media_id']);
        }
    }

    private function saveProject(): int
    {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new InvalidArgumentException('O título do projeto é obrigatório.');
            }

            $slug = trim((string) ($_POST['slug'] ?? ''));
            if ($slug === '') {
                $slug = $this->slugify($title);
            }
            if ($slug === '') {
                throw new InvalidArgumentException('Não foi possível gerar o slug do projeto.');
            }
            $slugCheck = $pdo->prepare('SELECT id FROM projects WHERE slug = :slug AND id <> :id LIMIT 1');
            $slugCheck->execute(['slug' => $slug, 'id' => $id]);
            if ($slugCheck->fetchColumn()) {
                throw new InvalidArgumentException('Este slug já está a ser usado por outro projeto. Escolha um slug diferente.');
            }

            $status = (string) ($_POST['status'] ?? 'draft');
            $allowedStatuses = ['draft', 'published', 'archived'];
            if (!in_array($status, $allowedStatuses, true)) {
                $status = 'draft';
            }

            if ($id) {
                $stmt = $pdo->prepare(
                    'UPDATE projects
                     SET title = :title,
                         slug = :slug,
                         category = :category,
                         short_description = :short_description,
                         description = :description,
                         project_url = :project_url,
                         github_url = :github_url,
                         status = :status,
                         featured = :featured,
                         sort_order = :sort_order,
                         published_at = IF(:is_published = 1, COALESCE(published_at, NOW()), NULL)
                     WHERE id = :id'
                );
                $stmt->execute([
                    'title' => $title,
                    'slug' => $slug,
                    'category' => trim((string) ($_POST['category'] ?? '')),
                    'short_description' => trim((string) ($_POST['short_description'] ?? '')),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                    'project_url' => trim((string) ($_POST['project_url'] ?? '')),
                    'github_url' => trim((string) ($_POST['github_url'] ?? '')),
                    'status' => $status,
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                    'is_published' => $status === 'published' ? 1 : 0,
                    'id' => $id,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO projects
                        (title, slug, category, short_description, description,
                         project_url, github_url, status, featured, sort_order, published_at)
                     VALUES
                        (:title, :slug, :category, :short_description, :description,
                         :project_url, :github_url, :status, :featured, :sort_order,
                         IF(:is_published = 1, NOW(), NULL))'
                );
                $stmt->execute([
                    'title' => $title,
                    'slug' => $slug,
                    'category' => trim((string) ($_POST['category'] ?? '')),
                    'short_description' => trim((string) ($_POST['short_description'] ?? '')),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                    'project_url' => trim((string) ($_POST['project_url'] ?? '')),
                    'github_url' => trim((string) ($_POST['github_url'] ?? '')),
                    'status' => $status,
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                    'is_published' => $status === 'published' ? 1 : 0,
                ]);
                $id = (int) $pdo->lastInsertId();
            }

            $existingStmt = $pdo->prepare(
                'SELECT id, slot, media_id FROM project_images WHERE project_id = :project_id'
            );
            $existingStmt->execute(['project_id' => $id]);
            $existing = [];
            foreach ($existingStmt->fetchAll() as $row) {
                $existing[(int) $row['slot']] = $row;
            }

            for ($slot = 1; $slot <= 4; $slot++) {
                $imageTitle = trim((string) ($_POST['image_title_' . $slot] ?? ''));
                $description = trim((string) ($_POST['image_description_' . $slot] ?? ''));
                $remove = isset($_POST['remove_image_' . $slot]);
                $fileField = 'image_' . $slot;

                if ($remove && isset($existing[$slot])) {
                    $oldMediaId = (int) $existing[$slot]['media_id'];
                    $delete = $pdo->prepare('DELETE FROM project_images WHERE id = :id');
                    $delete->execute(['id' => (int) $existing[$slot]['id']]);
                    Admin::deleteMediaIfUnused($oldMediaId);
                    unset($existing[$slot]);
                    continue;
                }

                if ($this->hasUpload($fileField)) {
                    $newMediaId = Admin::storeMedia(
                        $_FILES[$fileField],
                        $imageTitle !== '' ? $imageTitle : $title,
                        5 * 1024 * 1024,
                        6000,
                        25000000
                    );

                    if (isset($existing[$slot])) {
                        $oldMediaId = (int) $existing[$slot]['media_id'];
                        $update = $pdo->prepare(
                            'UPDATE project_images
                             SET media_id = :media_id, title = :title, description = :description
                             WHERE id = :id'
                        );
                        $update->execute([
                            'media_id' => $newMediaId,
                            'title' => $imageTitle,
                            'description' => $description,
                            'id' => (int) $existing[$slot]['id'],
                        ]);
                        Admin::deleteMediaIfUnused($oldMediaId);
                    } else {
                        $insert = $pdo->prepare(
                            'INSERT INTO project_images
                                (project_id, media_id, slot, title, description)
                             VALUES
                                (:project_id, :media_id, :slot, :title, :description)'
                        );
                        $insert->execute([
                            'project_id' => $id,
                            'media_id' => $newMediaId,
                            'slot' => $slot,
                            'title' => $imageTitle,
                            'description' => $description,
                        ]);
                    }
                } elseif (isset($existing[$slot])) {
                    $update = $pdo->prepare(
                        'UPDATE project_images
                         SET title = :title, description = :description
                         WHERE id = :id'
                    );
                    $update->execute([
                        'title' => $imageTitle,
                        'description' => $description,
                        'id' => (int) $existing[$slot]['id'],
                    ]);
                }
            }

            $deleteTechs = $pdo->prepare('DELETE FROM project_technologies WHERE project_id = :project_id');
            $deleteTechs->execute(['project_id' => $id]);

            $techIds = $_POST['technologies'] ?? [];
            if (!is_array($techIds)) {
                $techIds = [];
            }

            $insertTech = $pdo->prepare(
                'INSERT INTO project_technologies (project_id, technology_id, sort_order)
                 VALUES (:project_id, :technology_id, :sort_order)'
            );
            $sortOrder = 0;
            foreach ($techIds as $techId) {
                $techId = (int) $techId;
                if ($techId < 1) {
                    continue;
                }
                $insertTech->execute([
                    'project_id' => $id,
                    'technology_id' => $techId,
                    'sort_order' => $sortOrder++,
                ]);
            }

            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function deleteProject(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $images = Project::images($id);
        $pdo = db();

        $stmt = $pdo->prepare('DELETE FROM projects WHERE id = :id');
        $stmt->execute(['id' => $id]);

        foreach ($images as $image) {
            if (!empty($image['media_id'])) {
                Admin::deleteMediaIfUnused((int) $image['media_id']);
            }
        }
    }

    private function saveContact(): void
    {
        $pdo = db();
        $id = (int) ($_POST['id'] ?? 0);
        $label = trim((string) ($_POST['label'] ?? ''));
        $value = trim((string) ($_POST['value'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? 'outro'));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        if ($label === '' || $value === '') {
            throw new InvalidArgumentException('Preencha a etiqueta e o valor do contacto.');
        }
        if ($id < 0) {
            throw new InvalidArgumentException('Identificador de contacto inválido.');
        }

        $current = $id > 0 ? $this->contactById($id) : [];
        if ($id > 0 && !$current) {
            throw new InvalidArgumentException('Este contacto já não existe. Actualize a página e tente novamente.');
        }
        $oldMediaId = !empty($current['icon_media_id']) ? (int) $current['icon_media_id'] : null;
        $mediaId = $oldMediaId;

        $pdo->beginTransaction();
        try {
            if ($this->hasUpload('icon_file')) {
                $mediaId = Admin::storeMedia(
                    $_FILES['icon_file'],
                    $label,
                    2 * 1024 * 1024,
                    1500,
                    4000000
                );
            } elseif (isset($_POST['remove_icon'])) {
                $mediaId = null;
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE contact_channels
                     SET label = :label, value = :value, icon_media_id = :icon_media_id,
                         sort_order = :sort_order, is_active = :is_active
                     WHERE id = :id'
                );
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO contact_channels (type, label, value, icon_media_id, sort_order, is_active)
                     VALUES (:type, :label, :value, :icon_media_id, :sort_order, :is_active)'
                );
                $stmt->bindValue(':type', $type !== '' ? $type : 'outro');
            }
            $stmt->bindValue(':label', $label);
            $stmt->bindValue(':value', $value);
            $mediaId === null
                ? $stmt->bindValue(':icon_media_id', null, PDO::PARAM_NULL)
                : $stmt->bindValue(':icon_media_id', $mediaId, PDO::PARAM_INT);
            $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
            $stmt->bindValue(':is_active', isset($_POST['is_active']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->execute();

            if ($oldMediaId !== null && $mediaId !== $oldMediaId) {
                Admin::deleteMediaIfUnused($oldMediaId);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function saveSocial(): void
    {
        $pdo = db();
        $id = (int) ($_POST['id'] ?? 0);
        $platform = trim((string) ($_POST['platform'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($url !== '' && !preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        if ($platform === '' || $url === '') {
            throw new InvalidArgumentException('Preencha a plataforma e o URL da rede social.');
        }
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array((string) parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Introduza um URL válido, começando por https:// ou http://.');
        }
        if ($id < 0) {
            throw new InvalidArgumentException('Identificador de rede social inválido.');
        }

        $current = $id > 0 ? $this->socialById($id) : [];
        if ($id > 0 && !$current) {
            throw new InvalidArgumentException('Esta rede social já não existe. Actualize a página e tente novamente.');
        }
        $oldMediaId = !empty($current['icon_media_id']) ? (int) $current['icon_media_id'] : null;
        $mediaId = $oldMediaId;

        $pdo->beginTransaction();
        try {
            if ($this->hasUpload('icon_file')) {
                $mediaId = Admin::storeMedia(
                    $_FILES['icon_file'],
                    $platform,
                    2 * 1024 * 1024,
                    1500,
                    4000000
                );
            } elseif (isset($_POST['remove_icon'])) {
                $mediaId = null;
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE social_links
                     SET platform = :platform, url = :url, icon_media_id = :icon_media_id,
                         sort_order = :sort_order, is_active = :is_active
                     WHERE id = :id'
                );
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO social_links (platform, url, icon_media_id, sort_order, is_active)
                     VALUES (:platform, :url, :icon_media_id, :sort_order, :is_active)'
                );
            }
            $stmt->bindValue(':platform', $platform);
            $stmt->bindValue(':url', $url);
            $mediaId === null
                ? $stmt->bindValue(':icon_media_id', null, PDO::PARAM_NULL)
                : $stmt->bindValue(':icon_media_id', $mediaId, PDO::PARAM_INT);
            $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
            $stmt->bindValue(':is_active', isset($_POST['is_active']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->execute();

            if ($oldMediaId !== null && $mediaId !== $oldMediaId) {
                Admin::deleteMediaIfUnused($oldMediaId);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function deleteContact(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $row = $this->contactById($id);
        db()->prepare('DELETE FROM contact_channels WHERE id = :id')->execute(['id' => $id]);
        if (!empty($row['icon_media_id'])) {
            Admin::deleteMediaIfUnused((int) $row['icon_media_id']);
        }
    }

    private function deleteSocial(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $row = $this->socialById($id);
        db()->prepare('DELETE FROM social_links WHERE id = :id')->execute(['id' => $id]);
        if (!empty($row['icon_media_id'])) {
            Admin::deleteMediaIfUnused((int) $row['icon_media_id']);
        }
    }

    private function saveFooter(): void
    {
        $stmt = db()->prepare(
            'UPDATE footer_links
             SET group_title = :group_title,
                 label = :label,
                 url = :url,
                 sort_order = :sort_order,
                 is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            'group_title' => trim((string) ($_POST['group_title'] ?? '')),
            'label' => trim((string) ($_POST['label'] ?? '')),
            'url' => trim((string) ($_POST['url'] ?? '')),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'id' => (int) ($_POST['id'] ?? 0),
        ]);
    }

    private function addFooter(): void
    {
        $stmt = db()->prepare(
            'INSERT INTO footer_links
                (group_title, label, url, sort_order, is_active)
             VALUES
                (:group_title, :label, :url, :sort_order, 1)'
        );
        $stmt->execute([
            'group_title' => trim((string) ($_POST['group_title'] ?? '')),
            'label' => trim((string) ($_POST['label'] ?? '')),
            'url' => trim((string) ($_POST['url'] ?? '')),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
    }

    private function deleteFooter(): void
    {
        db()->prepare('DELETE FROM footer_links WHERE id = :id')->execute([
            'id' => (int) ($_POST['id'] ?? 0),
        ]);
    }

    private function requestBodyExceedsPostLimit(): bool
    {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $limit = $this->iniSizeToBytes((string) ini_get('post_max_size'));
        return $contentLength > 0 && $limit > 0 && $contentLength > $limit;
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        switch ($unit) {
            case 'g': $number *= 1024;
            case 'm': $number *= 1024;
            case 'k': $number *= 1024;
        }
        return (int) $number;
    }

    private function safeTab(string $tab): string
    {
        $allowed = ['dashboard', 'settings', 'header', 'sections', 'hero', 'about', 'services', 'technologies', 'process', 'projects', 'messages', 'ratings', 'contact', 'social', 'footer'];
        return in_array($tab, $allowed, true) ? $tab : 'dashboard';
    }

    private function hasUpload(string $field): bool
    {
        return isset($_FILES[$field])
            && isset($_FILES[$field]['error'])
            && (int) $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    private function technologyById(int $id): array
    {
        $stmt = db()->prepare(
            'SELECT t.*, m.id AS icon_media_id
             FROM technologies t
             LEFT JOIN media m ON m.id = t.icon_media_id
             WHERE t.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: [];
    }

    private function processById(int $id): array
    {
        $stmt = db()->prepare(
            'SELECT p.*, m.id AS icon_media_id
             FROM process_steps p
             LEFT JOIN media m ON m.id = p.icon_media_id
             WHERE p.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: [];
    }

    private function contactById(int $id): array
    {
        $stmt = db()->prepare(
            'SELECT c.*, m.id AS icon_media_id
             FROM contact_channels c
             LEFT JOIN media m ON m.id = c.icon_media_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: [];
    }

    private function socialById(int $id): array
    {
        $stmt = db()->prepare(
            'SELECT s.*, m.id AS icon_media_id
             FROM social_links s
             LEFT JOIN media m ON m.id = s.icon_media_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: [];
    }

    private function slugify(string $value): string
    {
        $ascii = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
        $ascii = $ascii !== false ? $ascii : $value;
        $slug = strtolower($ascii);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }

    private function dashboard(): void
    {
        $tab = (string) ($_GET['tab'] ?? 'dashboard');

        $counts = Admin::counts();
        $settings = Content::settings();
        $sections = Content::allSections();
        $hero = Content::hero();
        $about = Content::about();
        $services = Content::services(true);
        $technologies = Content::technologies(true);
        $process = Content::process(true);
        $projects = Project::all(true);
        $contacts = Content::contacts(true);
        $socials = Content::socials(true);
        $footerLinks = Content::footerLinks(true);
        $messages = $tab === 'messages' ? Admin::messages() : [];
        $ratings = $tab === 'ratings' ? Admin::ratings() : [];
        $flash = consume_flash();

        $editId = (int) ($_GET['edit'] ?? 0);
        $editingProject = $editId ? Project::findById($editId) : [];
        $editingImages = $editId ? Project::images($editId) : [];
        $editingProjectTechs = $editId ? Project::technologies($editId) : [];
        $selectedTechIds = [];
        foreach ($editingProjectTechs as $tech) {
            $selectedTechIds[] = (int) $tech['id'];
        }

        require __DIR__ . '/../views/admin.php';
    }
}
