<?php
require_once __DIR__ . '/../app/bootstrap.php';

$currentUser = require_admin_page();

$id = (string)($_GET['id'] ?? '');

$stmt = db()->prepare(
    "SELECT u.*, p.display_name, p.type, p.interest, p.description
     FROM users u JOIN profiles p ON p.user_id = u.id
     WHERE u.id = ?"
);
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    redirect('/admin/usuarios.php');
}

$miniaturas = fetch_profile_thumbnails([$usuario['id']]);
$idade = (int)floor((time() - strtotime($usuario['birth_date'])) / (365.25 * 86400));
$cpfFormatado = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $usuario['cpf']);

$stmt = db()->prepare("SELECT COUNT(*) FROM photos WHERE user_id = ?");
$stmt->execute([$usuario['id']]);
$qtdFotos = (int)$stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM reports WHERE reported_user_id = ?");
$stmt->execute([$usuario['id']]);
$qtdDenuncias = (int)$stmt->fetchColumn();

$pageTitle = $usuario['display_name'] . ' — Admin — Clube do Swing';
$activePage = 'moderacao';
require __DIR__ . '/../templates/header.php';
?>

<p class="text-xs text-muted" style="margin-bottom:1rem">
  <a href="/admin/usuarios.php" class="text-gold">← Voltar para a lista de usuários</a>
</p>

<div class="card" style="padding:1.5rem">
  <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem">
    <div class="user-thumb" style="width:4rem;height:4rem">
      <?php if (isset($miniaturas[$usuario['id']])): ?>
        <img src="/photo.php?id=<?= e($miniaturas[$usuario['id']]) ?>" alt="Foto de <?= e($usuario['display_name']) ?>">
      <?php else: ?>
        <div class="user-thumb-noimg" style="font-size:1.3rem"><?= e(initials($usuario['display_name'])) ?></div>
      <?php endif; ?>
    </div>
    <div>
      <h1 class="font-serif" style="margin-bottom:.15rem"><?= e($usuario['display_name']) ?></h1>
      <div class="text-xs text-muted">
        <?= e(PROFILE_TYPE_LABEL[$usuario['type']] ?? '') ?> ·
        <?= $usuario['plan'] === 'EXCLUSIVE' ? 'Plano Exclusivo' : 'Plano Livre' ?> ·
        <span class="<?= $usuario['status'] === 'active' ? '' : 'text-wine' ?>">
          <?= $usuario['status'] === 'active' ? 'Conta ativa' : 'Conta suspensa' ?>
        </span>
        <?php if ($usuario['is_admin']): ?> · <span class="text-gold">administrador</span><?php endif; ?>
      </div>
      <a href="/perfil.php?id=<?= e($usuario['id']) ?>" class="text-xs text-gold">Ver perfil público →</a>
    </div>
  </div>

  <h2 class="font-serif" style="font-size:1rem">Dados cadastrados</h2>
  <p class="text-xs text-muted" style="margin-bottom:1rem">
    Sigilosos — visíveis só para administradores, nunca para outros usuários.
  </p>

  <div class="stack" style="gap:.6rem">
    <div class="text-sm"><strong>E-mail:</strong> <?= e($usuario['email']) ?></div>
    <div class="text-sm"><strong>Telefone:</strong> <?= e($usuario['phone']) ?></div>
    <div class="text-sm"><strong>CPF:</strong> <?= e($cpfFormatado) ?></div>
    <div class="text-sm"><strong>Data de nascimento:</strong> <?= e(date('d/m/Y', strtotime($usuario['birth_date']))) ?> (<?= $idade ?> anos)</div>
    <div class="text-sm"><strong>Cidade/UF:</strong> <?= e($usuario['city']) ?>/<?= e($usuario['state']) ?></div>
    <div class="text-sm"><strong>Interesse:</strong> <?= e($usuario['interest'] ?: '—') ?></div>
    <div class="text-sm"><strong>Cadastrado em:</strong> <?= e(date('d/m/Y H:i', strtotime($usuario['created_at']))) ?></div>
    <div class="text-sm"><strong>Termos aceitos em:</strong> <?= e(date('d/m/Y H:i', strtotime($usuario['terms_accepted_at']))) ?></div>
    <div class="text-sm"><strong>Fotos publicadas:</strong> <?= $qtdFotos ?></div>
    <div class="text-sm"><strong>Denúncias recebidas:</strong> <?= $qtdDenuncias ?></div>
  </div>

  <?php if ($usuario['id'] !== $currentUser['id']): ?>
    <div style="margin-top:1.5rem;display:flex;align-items:center;gap:.75rem" data-user-row="<?= e($usuario['id']) ?>">
      <span class="badge <?= $usuario['status'] === 'active' ? '' : 'text-wine' ?>" data-user-status-label>
        <?= $usuario['status'] === 'active' ? 'Ativa' : 'Suspensa' ?>
      </span>
      <?php if ($usuario['status'] === 'active'): ?>
        <button class="btn btn-ghost btn-sm" data-user-status-btn
                data-user-id="<?= e($usuario['id']) ?>" data-status="suspended">
          Suspender conta
        </button>
      <?php else: ?>
        <button class="btn btn-sm" data-user-status-btn
                data-user-id="<?= e($usuario['id']) ?>" data-status="active">
          Reativar conta
        </button>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
