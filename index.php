<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = current_user();
$filtro = (string)($_GET['filtro'] ?? 'todos');
if (!$currentUser || !in_array($filtro, ['estado', 'seguindo'], true)) {
    $filtro = 'todos';
}
$fotos = fetch_feed($currentUser['id'] ?? null, 30, $filtro, $currentUser['state'] ?? null);

$pageTitle = 'Feed — Clube do Swing';
$activePage = 'feed';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Feed</h1>
<p class="text-xs text-muted" style="margin-bottom:1rem">Últimas atualizações da comunidade.</p>

<?php if ($currentUser): ?>
  <div class="tabs" style="margin-bottom:1.5rem">
    <a href="/index.php" class="tab-btn <?= $filtro === 'todos' ? 'active' : '' ?>">Todos</a>
    <a href="/index.php?filtro=estado" class="tab-btn <?= $filtro === 'estado' ? 'active' : '' ?>">Do meu estado</a>
    <a href="/index.php?filtro=seguindo" class="tab-btn <?= $filtro === 'seguindo' ? 'active' : '' ?>">Quem eu sigo</a>
  </div>
<?php endif; ?>

<?php if (!$currentUser): ?>
  <div class="notice" style="margin-bottom:1.5rem">
    Você está vendo o feed como visitante.
    <a href="/login.php" class="text-gold">Entrar</a> ou
    <a href="/signup.php" class="text-gold">criar perfil</a> para curtir e comentar.
  </div>
<?php elseif (!fetch_own_photos($currentUser['id'])): ?>
  <div class="notice" style="margin-bottom:1.5rem">
    Publique sua primeira foto em <a href="/perfil.php" class="text-gold">Meu perfil</a>.
  </div>
<?php endif; ?>

<div class="stack">
  <?php foreach ($fotos as $foto): ?>
    <?php $tipo = PROFILE_TYPE_LABEL[$foto['type']] ?? ''; ?>
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
        <?php if ($foto['visivel']): ?>
          <a href="/ver-foto.php?id=<?= e($foto['id']) ?>">
            <img src="/photo.php?id=<?= e($foto['id']) ?>" alt="Foto de <?= e($foto['display_name']) ?>">
          </a>
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
          <?php if ($currentUser && $foto['user_id'] !== $currentUser['id']): ?>
            <button class="btn btn-ghost btn-sm" style="margin-left:auto" data-report-btn data-photo-id="<?= e($foto['id']) ?>">
              Denunciar
            </button>
          <?php endif; ?>
        </div>

        <div class="comment-list" data-comment-list>
          <?php foreach ($foto['comentarios'] as $c): ?>
            <div data-comment-row="<?= e($c['id']) ?>">
              <strong><?= e($c['display_name']) ?>:</strong> <?= e($c['body']) ?>
              <?php if ($currentUser && is_admin($currentUser)): ?>
                <button type="button" class="comment-delete-btn" data-comment-delete-btn data-comment-id="<?= e($c['id']) ?>" title="Excluir comentário (admin)">✕</button>
              <?php endif; ?>
            </div>
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
    <p class="text-sm text-muted">
      <?php if ($filtro === 'estado'): ?>
        Ninguém do seu estado publicou fotos ainda.
      <?php elseif ($filtro === 'seguindo'): ?>
        Você ainda não segue ninguém, ou quem você segue ainda não publicou fotos.
        Use a <a href="/buscar.php" class="text-gold">Buscar</a> para encontrar perfis.
      <?php else: ?>
        Nenhuma foto aprovada ainda.
      <?php endif; ?>
    </p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
