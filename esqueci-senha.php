<?php
require_once __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('/index.php');
}

$enviado = false;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada, recarregue a página e tente novamente.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        if ($email !== '') {
            request_password_reset($email);
        }
        // Sempre mostra a mesma mensagem, exista ou não o e-mail — evita
        // revelar quais endereços têm conta cadastrada.
        $enviado = true;
    }
}

$pageTitle = 'Recuperar senha — Reserva';
$activePage = 'login';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Recuperar senha</h1>

<?php if ($enviado): ?>
  <p class="notice" style="max-width:360px">
    Se esse e-mail tiver uma conta no Reserva, enviamos um link para redefinir a
    senha. Confira sua caixa de entrada (e o spam).
  </p>
<?php else: ?>
  <form method="post" class="form" style="max-width:360px">
    <?= csrf_field() ?>
    <input class="input" name="email" type="email" placeholder="Seu e-mail de cadastro" required>
    <?php if ($erro): ?><p class="error"><?= e($erro) ?></p><?php endif; ?>
    <button class="btn" type="submit">Enviar link de recuperação</button>
  </form>
<?php endif; ?>

<p class="text-sm text-muted" style="max-width:360px;margin-top:1rem">
  <a href="/login.php" class="text-gold">Voltar para o login</a>
</p>

<?php require __DIR__ . '/templates/footer.php'; ?>
