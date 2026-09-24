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
                'phone' => $_POST['phone'] ?? '',
                'cpf' => $_POST['cpf'] ?? '',
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? '',
                'display_name' => $_POST['display_name'] ?? '',
                'type' => $_POST['type'] ?? '',
                'interest' => $_POST['interest'] ?? '',
                'description' => $_POST['description'] ?? '',
                'terms_accepted' => !empty($_POST['terms_accepted']),
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

  <select class="input" name="interest">
    <option value="">Interesse (opcional)</option>
    <?php foreach (INTEREST_OPTIONS as $opt): ?>
      <option value="<?= e($opt) ?>" <?= ($_POST['interest'] ?? '') === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
    <?php endforeach; ?>
  </select>

  <textarea class="input" name="description" placeholder="Descrição"><?= e($_POST['description'] ?? '') ?></textarea>

  <label class="text-sm text-muted">
    Data de nascimento
    <input class="input" name="birth_date" type="date" required
           value="<?= e($_POST['birth_date'] ?? '') ?>">
  </label>

  <div class="form" style="flex-direction:row;gap:.75rem">
    <label class="text-sm text-muted" style="flex:1">
      UF
      <select class="input" name="state" required data-state-select data-city-target="city-select">
        <option value="">UF</option>
        <?php foreach (BRAZIL_STATES as $uf): ?>
          <option value="<?= e($uf) ?>" <?= ($_POST['state'] ?? '') === $uf ? 'selected' : '' ?>><?= e($uf) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="text-sm text-muted" style="flex:2">
      Cidade
      <select class="input" name="city" required id="city-select">
        <option value=""><?= ($_POST['state'] ?? '') ? 'Selecione a cidade' : 'Escolha a UF primeiro' ?></option>
        <?php foreach (cities_for_state((string)($_POST['state'] ?? '')) as $cidade): ?>
          <option value="<?= e($cidade) ?>" <?= ($_POST['city'] ?? '') === $cidade ? 'selected' : '' ?>><?= e($cidade) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>

  <p class="text-xs text-muted" style="margin:.5rem 0 0">
    Os dados abaixo são sigilosos: ficam guardados só para verificação
    interna de idade/identidade e nunca são exibidos no seu perfil, no feed
    ou para outros usuários.
  </p>

  <label class="text-sm text-muted">
    E-mail <span class="text-xs">(sigiloso — não será divulgado)</span>
    <input class="input" name="email" type="email" placeholder="E-mail" required
           value="<?= e($_POST['email'] ?? '') ?>">
  </label>

  <label class="text-sm text-muted">
    Telefone <span class="text-xs">(sigiloso — não será divulgado)</span>
    <input class="input" name="phone" type="tel" placeholder="(11) 91234-5678" required
           value="<?= e($_POST['phone'] ?? '') ?>">
  </label>

  <label class="text-sm text-muted">
    CPF <span class="text-xs">(sigiloso — usado só para confirmar que você é maior de idade; não será divulgado)</span>
    <input class="input" name="cpf" inputmode="numeric" placeholder="Somente números" required
           maxlength="14" value="<?= e($_POST['cpf'] ?? '') ?>">
  </label>

  <input class="input" name="password" type="password" placeholder="Senha (mín. 8 caracteres)"
         required minlength="8">

  <label class="text-xs text-muted" style="display:flex;gap:.5rem;align-items:flex-start">
    <input type="checkbox" name="terms_accepted" value="1" required style="margin-top:.2rem"
           <?= !empty($_POST['terms_accepted']) ? 'checked' : '' ?>>
    <span>
      Li e aceito os <a href="/termos.php" target="_blank" class="text-gold">Termos de Uso</a> e a
      <a href="/privacidade.php" target="_blank" class="text-gold">Política de Privacidade</a>,
      e confirmo que tenho 18 anos ou mais.
    </span>
  </label>

  <?php if ($erro): ?>
    <p class="error"><?= e($erro) ?></p>
  <?php endif; ?>

  <button class="btn" type="submit">Criar perfil com Acesso Livre</button>
</form>

<?php require __DIR__ . '/templates/footer.php'; ?>
