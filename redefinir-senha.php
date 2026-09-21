<?php
require_once __DIR__ . '/app/bootstrap.php';

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$reset = validate_reset_token($token);

$erro = null;
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada, recarregue a página e tente novamente.';
    } elseif (!$reset) {
        $erro = 'Este link expirou ou já foi usado. Peça um novo.';
    } else {
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        if (strlen($password) < 8) {
            $erro = 'A senha precisa ter pelo menos 8 caracteres.';
        } elseif ($password !== $confirm) {
            $erro = 'As senhas não coincidem.';
        } else {
            consume_reset_token($reset['id'], $reset['user_id'], $password);
            $sucesso = true;
        }
    }
}

$pageTitle = 'Redefinir senha — Reserva';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Redefinir senha</h1>

<?php if ($sucesso): ?>
  <p class="notice" style="max-width:360px">
    Senha redefinida! <a href="/login.php" class="text-gold">Entrar agora</a>.
  </p>
<?php elseif (!$reset): ?>
  <p class="notice" style="max-width:360px">
    Este link é inválido, expirou, ou já foi usado.
    <a href="/esqueci-senha.php" class="text-gold">Pedir um novo link</a>.
  </p>
<?php else: ?>
  <form method="post" class="form" style="max-width:360px">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <input class="input" name="password" type="password" placeholder="Nova senha (mín. 8 caracteres)" required minlength="8">
    <input class="input" name="password_confirm" type="password" placeholder="Confirme a nova senha" required minlength="8">
    <?php if ($erro): ?><p class="error"><?= e($erro) ?></p><?php endif; ?>
    <button class="btn" type="submit">Redefinir senha</button>
  </form>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
