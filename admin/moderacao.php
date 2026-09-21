<?php
require_once __DIR__ . '/../app/bootstrap.php';

$currentUser = require_admin_page();

$stmt = db()->prepare(
    "SELECT p.*, pr.display_name
     FROM photos p
     JOIN profiles pr ON pr.user_id = p.user_id
     WHERE p.moderation_status = 'PENDING'
     ORDER BY p.created_at ASC"
);
$stmt->execute();
$pendentes = $stmt->fetchAll();

$stmt = db()->prepare(
    "SELECT p.*, pr.display_name
     FROM photos p
     JOIN profiles pr ON pr.user_id = p.user_id
     WHERE p.moderation_status IN ('APPROVED', 'REJECTED')
     ORDER BY p.created_at DESC
     LIMIT 20"
);
$stmt->execute();
$recentes = $stmt->fetchAll();

$stmt = db()->prepare(
    "SELECT r.*, reporterP.display_name AS reporter_name,
            reportedP.display_name AS reported_name,
            ph.file_path, ph.moderation_status AS photo_status
     FROM reports r
     JOIN profiles reporterP ON reporterP.user_id = r.reporter_id
     JOIN profiles reportedP ON reportedP.user_id = r.reported_user_id
     JOIN photos ph ON ph.id = r.photo_id
     WHERE r.status = 'PENDING'
     ORDER BY r.created_at ASC"
);
$stmt->execute();
$denuncias = $stmt->fetchAll();

$pageTitle = 'Moderação — Reserva';
$activePage = 'moderacao';
require __DIR__ . '/../templates/header.php';
?>

<h1 class="font-serif">Moderação de fotos</h1>
<p class="text-xs text-muted" style="margin-bottom:1.5rem">
  <a href="/admin/usuarios.php" class="text-gold">Ver e suspender usuários →</a>
</p>

<h3 class="text-sm">Denúncias pendentes (<?= count($denuncias) ?>)</h3>
<div class="stack" style="margin-bottom:2.5rem" data-reports-list>
  <?php foreach ($denuncias as $rep): ?>
    <article class="card" data-report-row="<?= e($rep['id']) ?>">
      <div class="photo-header">
        <div class="avatar"></div>
        <div>
          <div class="text-sm" style="font-weight:600">Foto de <?= e($rep['reported_name']) ?></div>
          <div class="text-xs text-muted">
            Denunciada por <?= e($rep['reporter_name']) ?> em
            <?= e(date('d/m/Y H:i', strtotime($rep['created_at']))) ?>
            — status atual: <?= e($rep['photo_status']) ?>
          </div>
        </div>
      </div>
      <div class="photo-media" style="height:10rem">
        <img src="/photo.php?id=<?= e($rep['photo_id']) ?>" alt="">
      </div>
      <div class="photo-body">
        <p class="text-sm"><strong>Motivo:</strong> <?= e($rep['reason']) ?></p>
        <div class="photo-actions">
          <button class="btn btn-ghost btn-sm" data-report-resolve-btn data-report-id="<?= e($rep['id']) ?>" data-action="dismiss">
            Descartar denúncia
          </button>
          <button class="btn btn-sm" data-report-resolve-btn data-report-id="<?= e($rep['id']) ?>" data-action="remove_photo">
            Remover foto
          </button>
          <button class="btn btn-ghost btn-sm" style="border-color:#5a2733;color:#e08a9c"
                  data-user-status-btn data-user-id="<?= e($rep['reported_user_id']) ?>" data-status="suspended">
            Suspender autor
          </button>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$denuncias): ?>
    <p class="text-sm text-muted">Nenhuma denúncia pendente.</p>
  <?php endif; ?>
</div>

<p class="text-sm text-muted" style="margin-bottom:1.5rem">
  <span data-pending-count><?= count($pendentes) ?></span> foto(s) aguardando aprovação.
</p>

<div class="stack" data-moderation-list>
  <?php foreach ($pendentes as $foto): ?>
    <article class="card" data-mod-photo="<?= e($foto['id']) ?>">
      <div class="photo-header">
        <div class="avatar"></div>
        <div>
          <div class="text-sm" style="font-weight:600"><?= e($foto['display_name']) ?></div>
          <div class="text-xs text-muted"><?= e(date('d/m/Y H:i', strtotime($foto['created_at']))) ?></div>
        </div>
        <?php if ($foto['visibility'] === 'FRIENDS'): ?>
          <span class="badge badge-gold" style="margin-left:auto">Amigos</span>
        <?php endif; ?>
      </div>
      <div class="photo-media">
        <img src="/photo.php?id=<?= e($foto['id']) ?>" alt="">
      </div>
      <div class="photo-body">
        <?php if ($foto['caption']): ?>
          <p class="text-sm"><?= e($foto['caption']) ?></p>
        <?php endif; ?>
        <div class="photo-actions">
          <button class="btn btn-sm" data-mod-btn data-photo-id="<?= e($foto['id']) ?>" data-action="approve">
            Aprovar
          </button>
          <button class="btn btn-ghost btn-sm" data-mod-btn data-photo-id="<?= e($foto['id']) ?>" data-action="reject">
            Rejeitar
          </button>
        </div>
      </div>
    </article>
  <?php endforeach; ?>

  <?php if (!$pendentes): ?>
    <p class="text-sm text-muted">Nenhuma foto pendente. 🎉</p>
  <?php endif; ?>
</div>

<h3 class="text-sm" style="margin-top:2.5rem">Últimas decisões</h3>
<div class="card">
  <?php foreach ($recentes as $foto): ?>
    <div class="friend-row">
      <span class="text-sm">
        <?= e($foto['display_name']) ?>
        <span class="text-xs text-muted">— <?= e(date('d/m/Y H:i', strtotime($foto['created_at']))) ?></span>
      </span>
      <span class="badge <?= $foto['moderation_status'] === 'APPROVED' ? 'badge-gold' : '' ?>">
        <?= $foto['moderation_status'] === 'APPROVED' ? 'Aprovada' : 'Rejeitada' ?>
      </span>
    </div>
  <?php endforeach; ?>
  <?php if (!$recentes): ?>
    <div class="friend-row"><span class="text-sm text-muted">Nenhuma decisão ainda.</span></div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
