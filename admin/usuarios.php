<?php
require_once __DIR__ . '/../app/bootstrap.php';

$currentUser = require_admin_page();

$busca = trim((string)($_GET['q'] ?? ''));
$estadoFiltro = (string)($_GET['estado'] ?? '');
if (!in_array($estadoFiltro, BRAZIL_STATES, true)) {
    $estadoFiltro = '';
}

$sql = "SELECT u.id, u.email, u.city, u.state, u.plan, u.status, u.is_admin, u.created_at, p.display_name, p.type
        FROM users u JOIN profiles p ON p.user_id = u.id
        WHERE 1=1";
$params = [];
if ($busca !== '') {
    $sql .= " AND (p.display_name LIKE ? OR u.email LIKE ?)";
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
}
if ($estadoFiltro !== '') {
    $sql .= " AND u.state = ?";
    $params[] = $estadoFiltro;
}
$sql .= " ORDER BY u.state ASC, p.display_name ASC LIMIT 500";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$miniaturas = fetch_profile_thumbnails(array_column($usuarios, 'id'));

$pageTitle = 'Usuários — Clube do Swing';
$activePage = 'moderacao';
require __DIR__ . '/../templates/header.php';
?>

<h1 class="font-serif">Usuários</h1>
<p class="text-xs text-muted" style="margin-bottom:1.5rem">
  <a href="/admin/moderacao.php" class="text-gold">← Voltar para moderação de fotos</a>
</p>

<form method="get" class="form" style="flex-direction:row;flex-wrap:wrap;max-width:560px;margin-bottom:1.5rem">
  <input class="input" type="text" name="q" placeholder="Buscar por nome ou e-mail" value="<?= e($busca) ?>" style="flex:2">
  <select class="input" name="estado" style="flex:1">
    <option value="">Todos os estados</option>
    <?php foreach (BRAZIL_STATES as $uf): ?>
      <option value="<?= e($uf) ?>" <?= $estadoFiltro === $uf ? 'selected' : '' ?>><?= e($uf) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-sm" type="submit">Filtrar</button>
</form>

<p class="text-xs text-muted" style="margin-bottom:1rem">
  <?= count($usuarios) ?> usuário(s) encontrado(s).
</p>

<?php $estadoAtual = null; ?>
<?php foreach ($usuarios as $u): ?>
  <?php if ($u['state'] !== $estadoAtual): ?>
    <?php $estadoAtual = $u['state']; ?>
    <h2 class="font-serif" style="font-size:1rem;margin:1.5rem 0 .5rem"><?= e($estadoAtual) ?></h2>
  <?php endif; ?>
  <div class="card" style="margin-bottom:.5rem">
    <div class="friend-row" data-user-row="<?= e($u['id']) ?>">
      <div style="display:flex;align-items:center;gap:.75rem;min-width:0">
        <div class="user-thumb">
          <?php if (isset($miniaturas[$u['id']])): ?>
            <img src="/photo.php?id=<?= e($miniaturas[$u['id']]) ?>" alt="Foto de <?= e($u['display_name']) ?>">
          <?php else: ?>
            <div class="user-thumb-noimg"><?= e(initials($u['display_name'])) ?></div>
          <?php endif; ?>
        </div>
        <div style="min-width:0">
          <a href="/perfil.php?id=<?= e($u['id']) ?>" class="text-sm" style="font-weight:600">
            <?= e($u['display_name']) ?>
          </a>
          <div class="text-xs text-muted">
            <?= e($u['city']) ?>/<?= e($u['state']) ?> ·
            <?= $u['plan'] === 'EXCLUSIVE' ? 'Exclusivo' : 'Livre' ?> ·
            desde <?= e(date('d/m/Y', strtotime($u['created_at']))) ?>
            <?php if ($u['is_admin']): ?> · <span class="text-gold">admin</span><?php endif; ?>
          </div>
          <a href="/admin/usuario.php?id=<?= e($u['id']) ?>" class="text-xs text-gold">Ver dados cadastrados</a>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:.75rem;flex-shrink:0">
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
  </div>
<?php endforeach; ?>

<?php if (!$usuarios): ?>
  <p class="text-sm text-muted">Nenhum usuário encontrado.</p>
<?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
