<?php
require_once __DIR__ . '/../app/bootstrap.php';

$currentUser = require_admin_page();

$busca = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT u.id, u.email, u.plan, u.status, u.is_admin, u.created_at, p.display_name
        FROM users u JOIN profiles p ON p.user_id = u.id";
$params = [];
if ($busca !== '') {
    $sql .= " WHERE p.display_name LIKE ? OR u.email LIKE ?";
    $params = ['%' . $busca . '%', '%' . $busca . '%'];
}
$sql .= " ORDER BY u.created_at DESC LIMIT 200";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$pageTitle = 'Usuários — Reserva';
$activePage = 'moderacao';
require __DIR__ . '/../templates/header.php';
?>

<h1 class="font-serif">Usuários</h1>
<p class="text-xs text-muted" style="margin-bottom:1.5rem">
  <a href="/admin/moderacao.php" class="text-gold">← Voltar para moderação de fotos</a>
</p>

<form method="get" class="form" style="flex-direction:row;max-width:420px;margin-bottom:1.5rem">
  <input class="input" type="text" name="q" placeholder="Buscar por nome ou e-mail" value="<?= e($busca) ?>">
  <button class="btn btn-sm" type="submit">Buscar</button>
</form>

<div class="card">
  <?php foreach ($usuarios as $u): ?>
    <div class="friend-row" data-user-row="<?= e($u['id']) ?>">
      <div>
        <a href="/perfil.php?id=<?= e($u['id']) ?>" class="text-sm" style="font-weight:600">
          <?= e($u['display_name']) ?>
        </a>
        <div class="text-xs text-muted">
          <?= e($u['email']) ?> ·
          <?= $u['plan'] === 'EXCLUSIVE' ? 'Exclusivo' : 'Livre' ?> ·
          desde <?= e(date('d/m/Y', strtotime($u['created_at']))) ?>
          <?php if ($u['is_admin']): ?> · <span class="text-gold">admin</span><?php endif; ?>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:.75rem">
        <span class="badge <?= $u['status'] === 'active' ? '' : 'text-wine' ?>" data-user-status-label>
          <?= $u['status'] === 'active' ? 'Ativa' : 'Suspensa' ?>
        </span>
        <?php if ($u['id'] !== $currentUser['id']): ?>
          <?php if ($u['status'] === 'active'): ?>
            <button class="btn btn-ghost btn-sm" data-user-status-btn
                    data-user-id="<?= e($u['id']) ?>" data-status="suspended">
              Suspender
            </button>
          <?php else: ?>
            <button class="btn btn-sm" data-user-status-btn
                    data-user-id="<?= e($u['id']) ?>" data-status="active">
              Reativar
            </button>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$usuarios): ?>
    <div class="friend-row"><span class="text-sm text-muted">Nenhum usuário encontrado.</span></div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
