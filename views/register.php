<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta | JOSE BENTOS</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-login">
<div class="login-card auth-card">
    <div class="auth-heading">
        <span class="auth-kicker">NOVO ACESSO</span>
        <h1>JOSE BENTOS</h1>
        <p>Criar conta administrativa</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert <?= e((string) ($flash['type'] ?? 'success')) ?>">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="admin.php?action=register" autocomplete="on">
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <label>Nome completo
            <input type="text" name="name" value="<?= e((string) ($_POST['name'] ?? '')) ?>" maxlength="150" required autocomplete="name">
        </label>

        <label>Email
            <input type="email" name="email" value="<?= e((string) ($_POST['email'] ?? '')) ?>" maxlength="255" required autocomplete="email">
        </label>

        <label>Palavra-passe
            <input type="password" name="password" minlength="10" required autocomplete="new-password">
        </label>

        <label>Confirmar palavra-passe
            <input type="password" name="password_confirmation" minlength="10" required autocomplete="new-password">
        </label>

        <button class="admin-btn" type="submit">Criar conta</button>
    </form>

    <p class="auth-switch">Já tem uma conta? <a href="admin.php">Entrar no painel</a></p>
    <small>Use uma palavra-passe com pelo menos 10 caracteres.</small>
</div>
</body>
</html>
