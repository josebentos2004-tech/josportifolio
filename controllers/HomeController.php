<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Content.php';
require_once __DIR__ . '/../models/Project.php';

final class HomeController
{
    public function index(): void
    {
        $settings = Content::settings();
        $sections = Content::sections();
        $hero = Content::hero();
        $about = Content::about();
        $services = Content::services();
        $technologies = Content::technologies();
        $process = Content::process();
        $contacts = Content::contacts();
        $socials = Content::socials();
        $footerLinks = Content::footerLinks();

        $projectSection = Content::section('projetos');
        $limit = (int) ($projectSection['item_limit'] ?? 3);
        if ($limit < 1) {
            $limit = 3;
        }

        $projects = Project::featured($limit);
        $totalProjects = Project::countPublished();

        require __DIR__ . '/../views/home.php';
    }

    public function contact(): void
    {
        check_csrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            http_response_code(422);
            echo 'Preencha nome, email e mensagem.';
            return;
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO contact_messages
                    (name, email, phone, message)
                 VALUES
                    (:name, :email, :phone, :message)'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
            ]);

            echo 'Mensagem recebida com sucesso.';
        } catch (PDOException $e) {
            error_log('JosPortifolio contact error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Não foi possível enviar a mensagem. Tente novamente.';
        }
    }
}
