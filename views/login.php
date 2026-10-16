<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração | JOSE BENTOS</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-login">
<?php $flash = consume_flash(); ?>
<div class="login-card auth-card">
    <div class="auth-heading">
        <span class="auth-kicker">ÁREA RESTRITA</span>
        <h1>JOSE BENTOS</h1>
        <p>Painel administrativo</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert <?= e((string) ($flash['type'] ?? 'success')) ?>">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="admin.php?action=login" autocomplete="on">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Email<input type="email" name="email" required autocomplete="username"></label>
        <label>Palavra-passe<input type="password" name="password" required autocomplete="current-password"></label>
        <div class="auth-actions">
            <button class="admin-btn" type="submit">Entrar</button>
        </div>
    </form>

    <small>Por segurança, novas contas administrativas só podem ser criadas depois de entrar com uma conta autorizada. Altere a palavra-passe inicial após o primeiro acesso.</small>
</div>
</body>
</html>
