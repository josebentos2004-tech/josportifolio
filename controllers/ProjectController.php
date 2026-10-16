<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Content.php';

final class ProjectController
{
    public function show(string $slug): void
    {
        $project = Project::findBySlug($slug);
        if (!$project) {
            http_response_code(404);
            exit('Projeto não encontrado.');
        }

        $hash = hash(
            'sha256',
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')
        );

        try {
            Project::addView((int) $project['id'], $hash);
        } catch (Throwable $e) {
            error_log('JosPortifolio view error: ' . $e->getMessage());
        }

        $images = Project::images((int) $project['id']);
        $techs = Project::technologies((int) $project['id']);
        $stats = Project::rating((int) $project['id']);
        $settings = Content::settings();
        $footerLinks = Content::footerLinks();

        require __DIR__ . '/../views/project.php';
    }

    public function rate(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = (string) ($_POST['csrf'] ?? '');
        if ($token === '' || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $token)) {
            $this->jsonResponse(419, [
                'success' => false,
                'message' => 'A sessão de segurança expirou. Actualize a página e tente novamente.',
            ]);
            return;
        }

        $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($id < 1 || $rating === false || $rating === null || $rating < 1 || $rating > 5) {
            $this->jsonResponse(422, [
                'success' => false,
                'message' => 'Escolha uma classificação entre 1 e 5 estrelas.',
            ]);
            return;
        }

        if ($email === '' || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->jsonResponse(422, [
                'success' => false,
                'message' => 'Introduza um endereço de e-mail válido para enviar a avaliação.',
            ]);
            return;
        }

        try {
            $project = Project::findById($id);
            if (!$project || ($project['status'] ?? '') !== 'published') {
                $this->jsonResponse(404, [
                    'success' => false,
                    'message' => 'Este projecto não está disponível para avaliação.',
                ]);
                return;
            }

            // A identidade de avaliação é o e-mail normalizado. Não limitamos
            // votos por IP, porque vários visitantes podem partilhar a mesma rede.
            $saved = Project::rate($id, $email, (int) $rating);
            if (!$saved) {
                $this->jsonResponse(409, [
                    'success' => false,
                    'message' => 'Este e-mail já enviou uma avaliação para este projecto. Obrigado!',
                ]);
                return;
            }

            $stats = Project::rating($id);
            $this->jsonResponse(200, [
                'success' => true,
                'message' => 'Avaliação enviada com sucesso. Obrigado pela sua participação!',
                'average' => (float) $stats['avg_rating'],
                'total' => (int) $stats['total'],
            ]);
        } catch (Throwable $e) {
            error_log('JosPortifolio rating error: ' . $e->getMessage());
            $publicMessage = 'Não foi possível guardar a avaliação neste momento. Recarregue a página e tente novamente.';

            if ($e instanceof InvalidArgumentException) {
                $publicMessage = $e->getMessage();
            } elseif ($e instanceof PDOException) {
                $driverCode = (int) ($e->errorInfo[1] ?? 0);
                if (in_array($driverCode, [1048, 1054, 1146, 1364, 1452], true)) {
                    // Os detalhes técnicos ficam no error_log e não são expostos
                    // ao visitante nem se exige que este execute alterações SQL.
                    $publicMessage = 'O envio não foi concluído devido a uma falha interna. Tente novamente mais tarde.';
                }
            }

            $this->jsonResponse(500, [
                'success' => false,
                'message' => $publicMessage,
            ]);
        }
    }

    private function jsonResponse(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}
