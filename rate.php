<?php
declare(strict_types=1);

/*
 * Endpoint dedicado para avaliações. Separá-lo de project.php evita que
 * regras do servidor ou caminhos relativos da página interfiram no POST.
 * A resposta é sempre JSON, inclusive em erros de arranque do PHP.
 */
$GLOBALS['jos_rate_endpoint_finished'] = false;
ob_start();

register_shutdown_function(static function (): void {
    if (!empty($GLOBALS['jos_rate_endpoint_finished'])) {
        return;
    }

    $lastError = error_get_last();
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!is_array($lastError) || !in_array((int) ($lastError['type'] ?? 0), $fatalTypes, true)) {
        return;
    }

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
    }
    $GLOBALS['jos_rate_endpoint_finished'] = true;
    echo json_encode([
        'success' => false,
        'message' => 'O servidor encontrou um erro interno ao processar a avaliação. Tente novamente mais tarde.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

$sendError = static function (int $status, string $message): void {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
    }
    $GLOBALS['jos_rate_endpoint_finished'] = true;
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
};

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        $sendError(405, 'Este endereço aceita apenas pedidos de avaliação por POST.');
        exit;
    }

    if ((string) ($_POST['action'] ?? '') !== 'rate') {
        $sendError(400, 'Pedido de avaliação inválido. Actualize a página e tente novamente.');
        exit;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/controllers/ProjectController.php';

    $controller = new ProjectController();
    $controller->rate((int) ($_POST['project_id'] ?? 0));

    // O controlador devolve JSON. Limpar avisos acidentais antes de o entregar.
    $raw = '';
    while (ob_get_level() > 0) {
        $part = ob_get_clean();
        if (is_string($part)) {
            $raw = $part . $raw;
        }
    }

    $candidate = trim(preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw);
    $payload = json_decode($candidate, true);
    if (!is_array($payload) || !array_key_exists('success', $payload) || !isset($payload['message'])) {
        $start = strpos($candidate, '{');
        $end = strrpos($candidate, '}');
        if ($start !== false && $end !== false && $end >= $start) {
            $payload = json_decode(substr($candidate, $start, $end - $start + 1), true);
        }
    }

    if (!is_array($payload) || !array_key_exists('success', $payload) || !isset($payload['message'])) {
        error_log('JosPortifolio rate.php: o controlador devolveu conteúdo sem JSON válido.');
        $sendError(500, 'Não foi possível concluir a avaliação. Actualize a página e tente novamente.');
        exit;
    }

    $status = http_response_code();
    if (!headers_sent()) {
        http_response_code(is_int($status) && $status >= 100 ? $status : 200);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
    }
    $GLOBALS['jos_rate_endpoint_finished'] = true;
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('JosPortifolio rate.php failure: ' . $e->getMessage());
    $sendError(500, 'Não foi possível processar a avaliação neste momento. Tente novamente mais tarde.');
}
