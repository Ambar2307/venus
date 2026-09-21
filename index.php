<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = current_user();
$erro = null;
$sucesso = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada, recarregue a página e tente novamente.';
    } else {
        try {
            if (empty($_FILES['photo']['name'])) {
                throw new RuntimeException('Selecione uma foto para publicar.');
            }
            create_photo(
                $currentUser,
                $_FILES['photo'],
                trim((string)($_POST['caption'] ?? '')),
                (string)($_POST['visibility'] ?? 'PUBLIC')
            );
            flash('success', 'Foto enviada! Ela aparece no feed assim que for aprovada na moderação.');
            redirect('/index.php');
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
        }
    }
}

$fotos = fetch_feed($currentUser['id'] ?? null);

$pageTitle = 'Feed — Reserva';
$activePage = 'feed';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Feed</h1>

<?php if (!$currentUser): ?>
  <div class="notice" style="margin-bottom:1.5rem">
    Você está vendo o feed como visitante.
    <a href="/login.php" class="text-gold">Entrar</a> ou
    <a href="/signup.php" class="text-gold">criar perfil</a> para curtir e comentar.
  </div>
<?php endif; ?>

<?php if ($sucesso): ?>
  <div class="notice" style="margin-bottom:1.5rem"><?= e($sucesso) ?></div>
<?php endif; ?>

<?php if ($currentUser): ?>
  <form method="post" enctype="multipart/form-data" class="form card" style="padding:1rem;margin-bottom:2rem">
    <?= csrf_field() ?>
    <strong class="text-sm">Publicar foto</strong>
    <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
    <input class="input" type="text" name="caption" placeholder="Legenda (opcional)" maxlength="500">
    <label class="text-sm text-muted">
      <select class="input" name="visibility">
        <option value="PUBLIC">Pública</option>
        <option value="FRIENDS" <?= is_exclusive($currentUser) ? '' : 'disabled' ?>>
          Somente amigos <?= is_exclusive($currentUser) ? '' : '(plano Exclusivo)' ?>
        </option>
      </select>
    </label>
    <?php if ($erro): ?><p class="error"><?= e($erro) ?></p><?php endif; ?>
    <button class="btn" type="submit">Publicar</button>
  </form>
<?php endif; ?>

<div class="stack">
  <?php foreach ($fotos as $foto): ?>
    <?php $tipo = PROFILE_TYPE_LABEL[$foto['type']] ?? ''; ?>
    <article class="card" data-photo-id="<?= e($foto['id']) ?>">
      <div class="photo-header">
        <a href="/perfil.php?id=<?= e($foto['user_id']) ?>" style="display:contents">
          <div class="avatar"></div>
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
        <?php if ($foto['visivel']): ?>
          <img src="/<?= e($foto['file_path']) ?>" alt="Foto de <?= e($foto['display_name']) ?>">
        <?php else: ?>
          <div class="photo-lock">🔒 Reservada aos amigos</div>
        <?php endif; ?>
      </div>

      <div class="photo-body">
        <?php if ($foto['caption']): ?>
          <p class="text-sm"><?= e($foto['caption']) ?></p>
        <?php endif; ?>

        <div class="photo-actions">
          <button
            class="like-btn <?= $foto['curtido_pelo_viewer'] ? 'liked' : '' ?>"
            data-like-btn
            data-photo-id="<?= e($foto['id']) ?>"
            <?= (!$currentUser || $foto['curtido_pelo_viewer']) ? 'disabled' : '' ?>
          >♥ <span data-like-count><?= count($foto['likes']) ?></span></button>
          <span class="text-xs text-muted"><span data-comment-count><?= count($foto['comentarios']) ?></span> comentários</span>
        </div>

        <div class="comment-list" data-comment-list>
          <?php foreach ($foto['comentarios'] as $c): ?>
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
  <?php endforeach; ?>

  <?php if (!$fotos): ?>
    <p class="text-sm text-muted">Nenhuma foto aprovada ainda.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
