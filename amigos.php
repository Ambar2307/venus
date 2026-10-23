<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = require_login_page();

$stmt = db()->prepare(
    "SELECT fr.id AS request_id, p.user_id, p.display_name, p.type
     FROM friend_requests fr
     JOIN profiles p ON p.user_id = fr.from_id
     WHERE fr.to_id = ? AND fr.status = 'PENDING'
     ORDER BY fr.created_at DESC"
);
$stmt->execute([$currentUser['id']]);
$pendentes = $stmt->fetchAll();

$amigos = list_friends($currentUser['id']);

$busca = trim((string)($_GET['q'] ?? ''));
$resultados = [];
if ($busca !== '') {
    $stmt = db()->prepare(
        "SELECT user_id, display_name, type FROM profiles
         WHERE display_name LIKE ? AND user_id != ? LIMIT 20"
    );
    $stmt->execute(['%' . $busca . '%', $currentUser['id']]);
    $resultados = $stmt->fetchAll();
}

$pageTitle = 'Amigos — Clube do Swing';
$activePage = 'amigos';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Amigos</h1>

<form method="get" class="form" style="flex-direction:row;max-width:420px;margin-bottom:2rem">
  <input class="input" type="text" name="q" placeholder="Buscar por nome fictício" value="<?= e($busca) ?>">
  <button class="btn btn-sm" type="submit">Buscar</button>
</form>

<?php if ($resultados): ?>
  <h3 class="text-sm">Resultados</h3>
  <div class="card" style="margin-bottom:2rem">
    <?php foreach ($resultados as $r): ?>
      <div class="friend-row">
        <a href="/perfil.php?id=<?= e($r['user_id']) ?>" class="text-sm">
          <?= e($r['display_name']) ?>
          <span class="text-xs text-muted"><?= e(PROFILE_TYPE_LABEL[$r['type']] ?? '') ?></span>
        </a>
        <button class="btn btn-outline btn-sm" data-friend-request-btn data-to-id="<?= e($r['user_id']) ?>">
          Adicionar
        </button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h3 class="text-sm">Pedidos pendentes</h3>
<div class="card" style="margin-bottom:2rem">
  <?php foreach ($pendentes as $p): ?>
    <div class="friend-row" data-request-row="<?= e($p['request_id']) ?>">
      <a href="/perfil.php?id=<?= e($p['user_id']) ?>" class="text-sm">
        <?= e($p['display_name']) ?>
        <span class="text-xs text-muted"><?= e(PROFILE_TYPE_LABEL[$p['type']] ?? '') ?></span>
      </a>
      <div style="display:flex;gap:.5rem">
        <button class="btn btn-sm" data-friend-respond-btn data-request-id="<?= e($p['request_id']) ?>" data-aceitar="1">Aceitar</button>
        <button class="btn btn-ghost btn-sm" data-friend-respond-btn data-request-id="<?= e($p['request_id']) ?>" data-aceitar="0">Recusar</button>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$pendentes): ?>
    <div class="friend-row"><span class="text-sm text-muted">Nenhum pedido pendente.</span></div>
  <?php endif; ?>
</div>

<h3 class="text-sm">Seus amigos (<?= count($amigos) ?>)</h3>
<div class="card">
  <?php foreach ($amigos as $a): ?>
    <div class="friend-row">
      <a href="/perfil.php?id=<?= e($a['id']) ?>" class="text-sm">
        <?= e($a['display_name']) ?>
        <span class="text-xs text-muted"><?= e(PROFILE_TYPE_LABEL[$a['type']] ?? '') ?></span>
      </a>
      <span class="badge badge-gold">Acesso ao reservado</span>
    </div>
  <?php endforeach; ?>
  <?php if (!$amigos): ?>
    <div class="friend-row"><span class="text-sm text-muted">Você ainda não tem amigos.</span></div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
