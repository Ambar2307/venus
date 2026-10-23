<?php
require_once __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('/index.php');
}

$erro = null;
$sucesso = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada, recarregue a página e tente novamente.';
    } else {
        $ok = attempt_login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''));
        if ($ok) {
            redirect('/index.php');
        }
        $erro = 'E-mail ou senha inválidos.';
    }
}

$pageTitle = 'Entrar — Clube do Swing';
$activePage = 'login';
require __DIR__ . '/templates/header.php';
?>

<div class="auth-center">
  <div class="auth-box">
    <h1 class="font-serif">Entrar</h1>

    <?php if ($sucesso): ?>
      <p class="notice"><?= e($sucesso) ?></p>
    <?php endif; ?>

    <form method="post" class="form">
      <?= csrf_field() ?>
      <input class="input" name="email" type="email" placeholder="E-mail" required>
      <input class="input" name="password" type="password" placeholder="Senha" required>
      <?php if ($erro): ?>
        <p class="error"><?= e($erro) ?></p>
      <?php endif; ?>
      <button class="btn" type="submit">Entrar</button>
    </form>

    <p class="text-sm text-muted" style="margin-top:1rem">
      <a href="/esqueci-senha.php" class="text-gold">Esqueci minha senha</a>
    </p>
    <p class="text-sm text-muted" style="margin-top:.25rem">
      Ainda não tem perfil? <a href="/signup.php" class="text-gold">Criar agora</a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
