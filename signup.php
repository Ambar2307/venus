<?php
require_once __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('/index.php');
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada, recarregue a página e tente novamente.';
    } else {
        try {
            register_user([
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'birth_date' => $_POST['birth_date'] ?? '',
                'display_name' => $_POST['display_name'] ?? '',
                'type' => $_POST['type'] ?? '',
                'interest' => $_POST['interest'] ?? '',
                'description' => $_POST['description'] ?? '',
            ]);
            flash('success', 'Perfil criado! Faça login para continuar.');
            redirect('/login.php');
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
        }
    }
}

$pageTitle = 'Criar perfil — Reserva';
$activePage = 'signup';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Criar perfil</h1>

<form method="post" class="form" style="max-width:420px">
  <?= csrf_field() ?>
  <input class="input" name="display_name" placeholder="Nome fictício" required
         value="<?= e($_POST['display_name'] ?? '') ?>">

  <select class="input" name="type" required>
    <option value="">Casal ou solteiro(a)?</option>
    <option value="COUPLE">Casal</option>
    <option value="SINGLE_WOMAN">Mulher solteira</option>
    <option value="SINGLE_MAN">Homem solteiro</option>
  </select>

  <input class="input" name="interest" placeholder="Interesse (ex.: casais e mulheres)"
         value="<?= e($_POST['interest'] ?? '') ?>">

  <textarea class="input" name="description" placeholder="Descrição"><?= e($_POST['description'] ?? '') ?></textarea>

  <label class="text-sm text-muted">
    Data de nascimento
    <input class="input" name="birth_date" type="date" required
           value="<?= e($_POST['birth_date'] ?? '') ?>">
  </label>

  <input class="input" name="email" type="email" placeholder="E-mail" required
         value="<?= e($_POST['email'] ?? '') ?>">

  <input class="input" name="password" type="password" placeholder="Senha (mín. 8 caracteres)"
         required minlength="8">

  <?php if ($erro): ?>
    <p class="error"><?= e($erro) ?></p>
  <?php endif; ?>

  <button class="btn" type="submit">Criar perfil com Acesso Livre</button>
</form>

<p class="text-xs text-muted" style="max-width:420px;margin-top:1rem">
  É necessário ter 18 anos ou mais. Ao se cadastrar você concorda com as regras de
  moderação de conteúdo da plataforma.
</p>

<?php require __DIR__ . '/templates/footer.php'; ?>
