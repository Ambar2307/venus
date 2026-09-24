<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = require_login_page();

$notificacoes = list_notifications($currentUser['id']);
mark_all_notifications_read($currentUser['id']);

$pageTitle = 'Notificações — Clube do Swing';
$activePage = 'notificacoes';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Notificações</h1>

<div class="card" style="margin-top:1.5rem">
  <?php foreach ($notificacoes as $n): ?>
    <div class="friend-row">
      <div style="display:flex;gap:.75rem;align-items:center">
        <a href="/ver-foto.php?id=<?= e($n['photo_id']) ?>" class="photo-media" style="width:3rem;height:3rem;margin:0;flex-shrink:0;display:block">
          <img src="/photo.php?id=<?= e($n['photo_id']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
        </a>
        <div class="text-sm">
          <strong><?= e($n['actor_name']) ?></strong>
          <?= $n['type'] === 'LIKE' ? 'curtiu sua foto.' : 'comentou: "' . e($n['comment_snippet']) . '"' ?>
          <div class="text-xs text-muted"><?= e(date('d/m/Y H:i', strtotime($n['created_at']))) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$notificacoes): ?>
    <div class="friend-row"><span class="text-sm text-muted">Nenhuma notificação ainda.</span></div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
