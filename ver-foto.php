<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = current_user();
$viewerId = $currentUser['id'] ?? null;
$photoId = (string)($_GET['id'] ?? '');

$stmt = db()->prepare(
    "SELECT p.*, pr.display_name, pr.type
     FROM photos p JOIN profiles pr ON pr.user_id = p.user_id
     WHERE p.id = ?"
);
$stmt->execute([$photoId]);
$foto = $stmt->fetch();

$allowed = false;
if ($foto) {
    if ($currentUser && is_admin($currentUser)) {
        $allowed = true;
    } elseif ($viewerId !== null && $viewerId === $foto['user_id']) {
        $allowed = true;
    } elseif ($foto['moderation_status'] === 'APPROVED') {
        $allowed = pode_ver_foto($viewerId, $foto);
    }
}

if (!$foto || !$allowed) {
    http_response_code(404);
    $pageTitle = 'Foto não encontrada — Clube do Swing';
    require __DIR__ . '/templates/header.php';
    echo '<p class="text-sm text-muted">Foto não encontrada.</p>';
    require __DIR__ . '/templates/footer.php';
    exit;
}

$likesStmt = db()->prepare('SELECT user_id FROM likes WHERE photo_id = ?');
$likesStmt->execute([$photoId]);
$likes = array_column($likesStmt->fetchAll(), 'user_id');
$curtidoPeloViewer = $viewerId ? in_array($viewerId, $likes, true) : false;

$commentsStmt = db()->prepare(
    "SELECT c.body, pr.display_name
     FROM comments c JOIN profiles pr ON pr.user_id = c.user_id
     WHERE c.photo_id = ? ORDER BY c.created_at ASC"
);
$commentsStmt->execute([$photoId]);
$comentarios = $commentsStmt->fetchAll();

$tipo = PROFILE_TYPE_LABEL[$foto['type']] ?? '';
$pageTitle = 'Foto de ' . $foto['display_name'] . ' — Clube do Swing';
require __DIR__ . '/templates/header.php';
?>

<article class="card feed-card" data-photo-id="<?= e($foto['id']) ?>">
  <div class="photo-header">
    <a href="/perfil.php?id=<?= e($foto['user_id']) ?>" style="display:contents">
      <div class="avatar"><?= e(initials($foto['display_name'])) ?></div>
      <div>
        <div class="text-sm" style="font-weight:600"><?= e($foto['display_name']) ?></div>
        <div class="text-xs text-muted"><?= e($tipo) ?></div>
      </div>
    </a>
    <?php if ($foto['visibility'] === 'FRIENDS'): ?>
      <span class="badge badge-gold" style="margin-left:auto">Amigos</span>
    <?php endif; ?>
  </div>

  <div class="photo-media">
    <img src="/photo.php?id=<?= e($foto['id']) ?>" alt="Foto de <?= e($foto['display_name']) ?>">
  </div>

  <div class="photo-body">
    <?php if ($foto['caption']): ?>
      <p class="text-sm"><?= e($foto['caption']) ?></p>
    <?php endif; ?>

    <div class="photo-actions">
      <button
        class="like-btn <?= $curtidoPeloViewer ? 'liked' : '' ?>"
        data-like-btn
        data-photo-id="<?= e($foto['id']) ?>"
        <?= (!$currentUser || $curtidoPeloViewer) ? 'disabled' : '' ?>
      >♥ <span data-like-count><?= count($likes) ?></span></button>
      <span class="text-xs text-muted"><span data-comment-count><?= count($comentarios) ?></span> comentários</span>
      <?php if ($currentUser && $foto['user_id'] !== $currentUser['id']): ?>
        <button class="btn btn-ghost btn-sm" style="margin-left:auto" data-report-btn data-photo-id="<?= e($foto['id']) ?>">
          Denunciar
        </button>
      <?php endif; ?>
    </div>

    <div class="comment-list" data-comment-list>
      <?php foreach ($comentarios as $c): ?>
        <div><strong><?= e($c['display_name']) ?>:</strong> <?= e($c['body']) ?></div>
      <?php endforeach; ?>
    </div>

    <?php if ($currentUser): ?>
      <form class="comment-form" data-comment-form data-photo-id="<?= e($foto['id']) ?>">
        <input class="input" type="text" name="text" placeholder="Comentar..." maxlength="500" required>
        <button class="btn btn-sm" type="submit">Enviar</button>
      </form>
    <?php endif; ?>
  </div>
</article>

<?php require __DIR__ . '/templates/footer.php'; ?>
