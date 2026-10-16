<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/ProjectController.php';
require_once __DIR__ . '/models/Project.php';
require_once __DIR__ . '/models/Content.php';

/*
 * Avaliações são processadas nesta mesma página (project.php).
 * A submissão tradicional POST/Redirect/GET evita pedidos AJAX para rotas
 * auxiliares que podem devolver HTTP 404 em alguns alojamentos partilhados.
 */
$isRatingRequest = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && (string) ($_POST['action'] ?? '') === 'rate';

if ($isRatingRequest) {
    $slug = trim((string) ($_GET['slug'] ?? ''));
    $redirectUrl = 'index.php#projetos';
    $message = 'Não foi possível registar a avaliação. Tente novamente.';
    $messageType = 'error';

    if ($slug !== '') {
        // Mantém o utilizador na página do projecto após validar a submissão.
        $redirectUrl = 'project.php?' . http_build_query(
            ['slug' => $slug],
            '',
            '&',
            PHP_QUERY_RFC3986
        ) . '#ratingForm';
    }

    try {
        if ($slug === '') {
            throw new InvalidArgumentException('Não foi possível identificar o projecto a avaliar.');
        }

        $project = Project::findBySlug($slug);
        if (!$project) {
            $redirectUrl = 'index.php#projetos';
            throw new InvalidArgumentException('Este projecto não está disponível para avaliação.');
        }

        $postedProjectId = filter_var($_POST['project_id'] ?? null, FILTER_VALIDATE_INT);
        $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $csrf = (string) ($_POST['csrf'] ?? '');
        $sessionCsrf = (string) ($_SESSION['csrf'] ?? '');

        if ($csrf === '' || $sessionCsrf === '' || !hash_equals($sessionCsrf, $csrf)) {
            throw new InvalidArgumentException('A sessão expirou. Actualize a página e tente enviar a avaliação novamente.');
        }

        if ($postedProjectId === false || (int) $postedProjectId !== (int) $project['id']) {
            throw new InvalidArgumentException('O projecto seleccionado é inválido. Actualize a página e tente novamente.');
        }

        if ($rating === false || $rating === null || (int) $rating < 1 || (int) $rating > 5) {
            throw new InvalidArgumentException('Escolha de 1 a 5 estrelas antes de enviar.');
        }

        if ($email === '' || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Introduza um endereço de e-mail válido.');
        }

        $saved = Project::rate((int) $project['id'], $email, (int) $rating);
        if (!$saved) {
            throw new DomainException('Este e-mail já foi utilizado para avaliar este projecto. Só é permitida uma avaliação por e-mail em cada projecto.');
        }

        $message = 'Avaliação enviada com sucesso. Obrigado pela sua participação!';
        $messageType = 'success';
    } catch (DomainException $e) {
        // Uma avaliação repetida é uma resposta esperada; não é um erro do servidor.
        $message = $e->getMessage();
        $messageType = 'error';
    } catch (InvalidArgumentException $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    } catch (Throwable $e) {
        error_log('JosPortifolio project rating error: ' . $e->getMessage());
        $message = 'Não foi possível guardar a avaliação neste momento. Tente novamente mais tarde.';
        $messageType = 'error';
    }

    $_SESSION['rating_flash'] = [
        'type' => $messageType,
        'message' => $message,
    ];

    header('Location: ' . $redirectUrl, true, 303);
    exit;
}

$controller = new ProjectController();

if (isset($_GET['all'])) {
    $settings = Content::settings();
    $projects = Project::all(false);
    $footerLinks = Content::footerLinks();
    require __DIR__ . '/views/projects.php';
    exit;
}

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    redirect('index.php#projetos');
}

$controller->show($slug);
