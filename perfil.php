<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = require_login_page();

$profileId = (string)($_GET['id'] ?? $currentUser['id']);

$stmt = db()->prepare(
    'SELECT u.id, u.created_at, u.plan, u.plan_valid_until,
            p.display_name, p.type, p.interest, p.description
     FROM users u JOIN profiles p ON p.user_id = u.id
     WHERE u.id = ?'
);
$stmt->execute([$profileId]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    $pageTitle = 'Perfil não encontrado';
    require __DIR__ . '/templates/header.php';
    echo '<p class="text-sm text-muted">Perfil não encontrado.</p>';
    require __DIR__ . '/templates/footer.php';
    exit;
}

$isOwnProfile = $profile['id'] === $currentUser['id'];
$amigos = $isOwnProfile ? true : sao_amigos($currentUser['id'], $profile['id']);

$pedidoPendente = false;
if (!$isOwnProfile && !$amigos) {
    $stmt = db()->prepare(
        "SELECT status FROM friend_requests
         WHERE (from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?)"
    );
    $stmt->execute([$currentUser['id'], $profile['id'], $profile['id'], $currentUser['id']]);
    $req = $stmt->fetch();
    $pedidoPendente = $req && $req['status'] === 'PENDING';
}

$stmt = db()->prepare(
    "SELECT * FROM photos WHERE user_id = ? AND moderation_status = 'APPROVED' ORDER BY created_at DESC"
);
$stmt->execute([$profile['id']]);
$todasFotos = $stmt->fetchAll();

$fotosPublicas = array_values(array_filter($todasFotos, fn($f) => $f['visibility'] === 'PUBLIC'));
$fotosReservadas = array_values(array_filter($todasFotos, fn($f) => $f['visibility'] === 'FRIENDS'));

$tipo = PROFILE_TYPE_LABEL[$profile['type']] ?? '';
$desde = date('m/Y', strtotime($profile['created_at']));

$pageTitle = e($profile['display_name']) . ' — Reserva';
$activePage = 'perfil';
require __DIR__ . '/templates/header.php';
?>

<div class="card" style="padding:1.5rem;margin-bottom:1.5rem">
  <div style="display:flex;gap:1rem;align-items:flex-start">
    <div class="avatar" style="width:4rem;height:4rem"></div>
    <div style="flex:1">
      <h1 class="font-serif" style="margin-bottom:.25rem"><?= e($profile['display_name']) ?></h1>
      <div class="text-sm text-muted"><?= e($tipo) ?> · Interesse: <?= e($profile['interest'] ?: '—') ?></div>
      <div class="text-xs text-muted">No Reserva desde <?= e($desde) ?></div>
      <?php if ($isOwnProfile && is_exclusive($profile)): ?>
        <span class="badge badge-gold" style="margin-top:.5rem;display:inline-block">Exclusivo</span>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($profile['description']): ?>
    <p class="text-sm" style="margin-top:1rem"><?= nl2br(e($profile['description'])) ?></p>
  <?php endif; ?>

  <?php if (!$isOwnProfile): ?>
    <div style="margin-top:1rem">
      <?php if ($amigos): ?>
        <span class="badge badge-gold">Amigos</span>
        <?php if (is_exclusive($currentUser)): ?>
          <a href="/chat.php?with=<?= e($profile['id']) ?>" class="btn btn-outline btn-sm" style="margin-left:.5rem">Conversar</a>
        <?php endif; ?>
      <?php elseif ($pedidoPendente): ?>
        <span class="badge">Pedido de amizade enviado</span>
      <?php else: ?>
        <button class="btn btn-outline btn-sm" data-friend-request-btn data-to-id="<?= e($profile['id']) ?>">
          Adicionar como amigo
        </button>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<div class="tabs">
  <button class="tab-btn active" data-tab-btn data-tab="publicas">Públicas (<?= count($fotosPublicas) ?>)</button>
  <button class="tab-btn" data-tab-btn data-tab="reservadas">Reservadas aos amigos (<?= count($fotosReservadas) ?>)</button>
</div>

<div data-tab-panel="publicas" class="stack">
  <?php foreach ($fotosPublicas as $foto): ?>
    <div class="photo-media"><img src="/photo.php?id=<?= e($foto['id']) ?>" alt=""></div>
  <?php endforeach; ?>
  <?php if (!$fotosPublicas): ?><p class="text-sm text-muted">Nenhuma foto pública.</p><?php endif; ?>
</div>

<div data-tab-panel="reservadas" class="stack" style="display:none">
  <?php foreach ($fotosReservadas as $foto): ?>
    <div class="photo-media">
      <?php if ($amigos): ?>
        <img src="/photo.php?id=<?= e($foto['id']) ?>" alt="">
      <?php else: ?>
        <div class="photo-lock">🔒 Reservada aos amigos</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$fotosReservadas): ?><p class="text-sm text-muted">Nenhuma foto reservada.</p><?php endif; ?>
</div>

<?php if ($isOwnProfile): ?>
  <div class="card" style="padding:1.5rem;margin-top:2.5rem;border-color:#5a2733">
    <h3 class="text-sm text-wine" style="margin-bottom:.5rem">Excluir conta</h3>
    <p class="text-xs text-muted" style="margin-bottom:1rem">
      Remove permanentemente seu perfil, fotos, comentários, curtidas, amizades e
      conversas. Não pode ser desfeito. Veja mais na
      <a href="/privacidade.php" class="text-gold">Política de Privacidade</a>.
    </p>
    <form data-delete-account-form style="display:flex;gap:.5rem;flex-wrap:wrap">
      <input class="input" type="password" name="password" placeholder="Confirme sua senha" required style="max-width:220px">
      <button type="submit" class="btn btn-ghost btn-sm" style="border-color:#5a2733;color:#e08a9c">
        Excluir minha conta
      </button>
    </form>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
