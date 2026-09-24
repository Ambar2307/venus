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
$previewMode = $isOwnProfile && isset($_GET['preview']);
$showAsVisitor = !$isOwnProfile || $previewMode;

if ($previewMode) {
    $amigos = false; // simula a visão de quem NÃO é seu amigo
} else {
    $amigos = $isOwnProfile ? true : sao_amigos($currentUser['id'], $profile['id']);
}
$erroUpload = null;

if (!$isOwnProfile) {
    log_profile_visit($currentUser['id'], $profile['id']);
}

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

if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publicar_foto'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $erroUpload = 'Sessão expirada, recarregue a página e tente novamente.';
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
            redirect('/perfil.php');
        } catch (RuntimeException $e) {
            $erroUpload = $e->getMessage();
        }
    }
}

$tipo = PROFILE_TYPE_LABEL[$profile['type']] ?? '';
$desde = date('m/Y', strtotime($profile['created_at']));

if ($isOwnProfile && !$previewMode) {
    $minhasFotos = fetch_own_photos($currentUser['id']);
    $meusAmigos = list_friends($currentUser['id']);
    $meusSeguidores = list_followers($currentUser['id']);
    $recomendados = list_recommended_profiles($currentUser);
    $visitasRecebidas = list_recent_visitors($currentUser['id']);
} else {
    $stmt = db()->prepare(
        "SELECT * FROM photos WHERE user_id = ? AND moderation_status = 'APPROVED' ORDER BY created_at DESC"
    );
    $stmt->execute([$profile['id']]);
    $todasFotos = $stmt->fetchAll();
    $seguindo = is_following($currentUser['id'], $profile['id']);
    $fotoDestaque = null;
    foreach ($todasFotos as $f) {
        if ($f['visibility'] === 'PUBLIC' || $amigos) {
            $fotoDestaque = $f;
            break;
        }
    }
}

$pageTitle = $profile['display_name'] . ' — Clube do Swing';
$activePage = 'perfil';
require __DIR__ . '/templates/header.php';
?>

<?php if ($previewMode): ?>
  <div class="notice" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
    <span>👁️ Você está vendo seu perfil como um visitante que não é seu amigo vê.</span>
    <a href="/perfil.php" class="btn btn-sm">Voltar a editar meu perfil</a>
  </div>
<?php endif; ?>

<?php if ($showAsVisitor && $fotoDestaque): ?>
  <a href="/ver-foto.php?id=<?= e($fotoDestaque['id']) ?>" class="card" style="display:block;margin-bottom:1.5rem">
    <div class="photo-media" style="aspect-ratio:1/1;max-height:28rem">
      <img src="/photo.php?id=<?= e($fotoDestaque['id']) ?>" alt="Foto de <?= e($profile['display_name']) ?>">
    </div>
  </a>
<?php endif; ?>

<div class="card" style="padding:1.5rem;margin-bottom:1.5rem">
  <div style="display:flex;gap:1rem;align-items:flex-start">
    <div class="avatar" style="width:4rem;height:4rem;font-size:1.4rem"><?= e(initials($profile['display_name'])) ?></div>
    <div style="flex:1">
      <h1 class="font-serif" style="margin-bottom:.25rem"><?= e($profile['display_name']) ?></h1>
      <div class="text-sm text-muted"><?= e($tipo) ?> · Interesse: <?= e($profile['interest'] ?: '—') ?></div>
      <div class="text-xs text-muted">No Clube do Swing desde <?= e($desde) ?></div>
      <?php if ($isOwnProfile && is_exclusive($profile)): ?>
        <span class="badge badge-gold" style="margin-top:.5rem;display:inline-block">Exclusivo</span>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($profile['description']): ?>
    <p class="text-sm" style="margin-top:1rem"><?= nl2br(e($profile['description'])) ?></p>
  <?php endif; ?>

  <?php if ($isOwnProfile && !$previewMode): ?>
    <div style="margin-top:1rem">
      <a href="/perfil.php?preview=1" class="btn btn-outline btn-sm">Ver como terceiros veem meu perfil</a>
    </div>
  <?php endif; ?>

  <?php if (!$isOwnProfile): ?>
    <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
      <?php if ($amigos): ?>
        <span class="badge badge-gold">Amigos</span>
        <?php if (is_exclusive($currentUser)): ?>
          <a href="/chat.php?with=<?= e($profile['id']) ?>" class="btn btn-outline btn-sm">Conversar</a>
        <?php endif; ?>
      <?php elseif ($pedidoPendente): ?>
        <span class="badge">Pedido de amizade enviado</span>
      <?php else: ?>
        <button class="btn btn-outline btn-sm" data-friend-request-btn data-to-id="<?= e($profile['id']) ?>">
          Adicionar como amigo
        </button>
      <?php endif; ?>

      <button
        class="btn btn-sm <?= $seguindo ? 'btn-ghost' : '' ?>"
        data-follow-btn
        data-user-id="<?= e($profile['id']) ?>"
        data-following="<?= $seguindo ? '1' : '0' ?>"
      ><?= $seguindo ? 'Deixar de seguir' : 'Seguir' ?></button>
    </div>
  <?php endif; ?>
</div>

<?php if ($isOwnProfile && !$previewMode): ?>

  <div class="tabs" style="flex-wrap:wrap">
    <button class="tab-btn active" data-tab-btn data-tab="minhas-fotos">Minhas fotos (<?= count($minhasFotos) ?>)</button>
    <button class="tab-btn" data-tab-btn data-tab="meus-amigos">Meus amigos (<?= count($meusAmigos) ?>)</button>
    <button class="tab-btn" data-tab-btn data-tab="meus-seguidores">Meus seguidores (<?= count($meusSeguidores) ?>)</button>
    <button class="tab-btn" data-tab-btn data-tab="recomendados">Recomendados</button>
    <button class="tab-btn" data-tab-btn data-tab="visitas">Visitas recebidas (<?= count($visitasRecebidas) ?>)</button>
  </div>

  <div data-tab-panel="minhas-fotos">
    <form method="post" enctype="multipart/form-data" class="form card" style="padding:1rem;margin-bottom:1.5rem">
      <?= csrf_field() ?>
      <input type="hidden" name="publicar_foto" value="1">
      <strong class="text-sm">Publicar foto</strong>
      <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
      <input class="input" type="text" name="caption" placeholder="Legenda (opcional)" maxlength="500">
      <select class="input" name="visibility">
        <option value="PUBLIC">Pública</option>
        <option value="FRIENDS" <?= is_exclusive($currentUser) ? '' : 'disabled' ?>>
          Somente amigos <?= is_exclusive($currentUser) ? '' : '(plano Exclusivo)' ?>
        </option>
      </select>
      <?php if ($erroUpload): ?><p class="error"><?= e($erroUpload) ?></p><?php endif; ?>
      <button class="btn" type="submit">Publicar</button>
    </form>

    <div class="stack">
      <?php foreach ($minhasFotos as $foto): ?>
        <article class="card feed-card" data-my-photo="<?= e($foto['id']) ?>">
          <a href="/ver-foto.php?id=<?= e($foto['id']) ?>">
            <div class="photo-media" style="margin-top:1rem">
              <img src="/photo.php?id=<?= e($foto['id']) ?>" alt="">
            </div>
          </a>
          <div class="photo-body">
            <?php if ($foto['caption']): ?><p class="text-sm"><?= e($foto['caption']) ?></p><?php endif; ?>
            <div class="photo-actions">
              <span class="badge <?= $foto['moderation_status'] === 'APPROVED' ? 'badge-gold' : '' ?>">
                <?= [
                  'PENDING' => 'Aguardando aprovação',
                  'APPROVED' => 'Aprovada',
                  'REJECTED' => 'Rejeitada',
                ][$foto['moderation_status']] ?>
              </span>
              <?php if ($foto['visibility'] === 'FRIENDS'): ?>
                <span class="badge">Só amigos</span>
              <?php endif; ?>
              <button class="btn btn-ghost btn-sm btn-danger" style="margin-left:auto"
                      data-delete-photo-btn data-photo-id="<?= e($foto['id']) ?>">
                Excluir
              </button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$minhasFotos): ?>
        <p class="text-sm text-muted">Você ainda não publicou nenhuma foto.</p>
      <?php endif; ?>
    </div>
  </div>

  <div data-tab-panel="meus-amigos" class="card" style="display:none">
    <?php foreach ($meusAmigos as $a): ?>
      <div class="friend-row">
        <a href="/perfil.php?id=<?= e($a['id']) ?>" class="text-sm">
          <?= e($a['display_name']) ?>
          <span class="text-xs text-muted"><?= e(PROFILE_TYPE_LABEL[$a['type']] ?? '') ?></span>
        </a>
        <span class="badge badge-gold">Amigos</span>
      </div>
    <?php endforeach; ?>
    <?php if (!$meusAmigos): ?>
      <div class="friend-row"><span class="text-sm text-muted">Você ainda não tem amigos. Vá em <a href="/amigos.php" class="text-gold">Amigos</a>.</span></div>
    <?php endif; ?>
  </div>

  <div data-tab-panel="meus-seguidores" class="card" style="display:none">
    <?php foreach ($meusSeguidores as $s): ?>
      <div class="friend-row">
        <a href="/perfil.php?id=<?= e($s['id']) ?>" class="text-sm">
          <?= e($s['display_name']) ?>
          <span class="text-xs text-muted"><?= e(PROFILE_TYPE_LABEL[$s['type']] ?? '') ?></span>
        </a>
        <span class="text-xs text-muted">desde <?= e(date('d/m/Y', strtotime($s['created_at']))) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$meusSeguidores): ?>
      <div class="friend-row"><span class="text-sm text-muted">Ninguém está te seguindo ainda.</span></div>
    <?php endif; ?>
  </div>

  <div data-tab-panel="recomendados" class="card" style="display:none">
    <?php foreach ($recomendados as $r): ?>
      <div class="friend-row">
        <a href="/perfil.php?id=<?= e($r['id']) ?>" class="text-sm">
          <?= e($r['display_name']) ?>
          <span class="text-xs text-muted"><?= e(PROFILE_TYPE_LABEL[$r['type']] ?? '') ?> · <?= e($r['city']) ?>/<?= e($r['state']) ?></span>
        </a>
        <button class="btn btn-outline btn-sm" data-follow-btn data-user-id="<?= e($r['id']) ?>" data-following="0">Seguir</button>
      </div>
    <?php endforeach; ?>
    <?php if (!$recomendados): ?>
      <div class="friend-row"><span class="text-sm text-muted">Nenhuma recomendação por enquanto.</span></div>
    <?php endif; ?>
  </div>

  <div data-tab-panel="visitas" class="card" style="display:none">
    <?php foreach ($visitasRecebidas as $v): ?>
      <div class="friend-row">
        <a href="/perfil.php?id=<?= e($v['id']) ?>" class="text-sm">
          <?= e($v['display_name']) ?>
          <span class="text-xs text-muted"><?= e(PROFILE_TYPE_LABEL[$v['type']] ?? '') ?></span>
        </a>
        <span class="text-xs text-muted"><?= e(date('d/m/Y H:i', strtotime($v['last_visit']))) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$visitasRecebidas): ?>
      <div class="friend-row"><span class="text-sm text-muted">Ninguém visitou seu perfil ainda.</span></div>
    <?php endif; ?>
  </div>

  <div class="card card-danger" style="padding:1.5rem;margin-top:2.5rem">
    <h3 class="text-sm text-wine" style="margin-bottom:.5rem">Excluir conta</h3>
    <p class="text-xs text-muted" style="margin-bottom:1rem">
      Remove permanentemente seu perfil, fotos, comentários, curtidas, amizades e
      conversas. Não pode ser desfeito. Veja mais na
      <a href="/privacidade.php" class="text-gold">Política de Privacidade</a>.
    </p>
    <form data-delete-account-form style="display:flex;gap:.5rem;flex-wrap:wrap">
      <input class="input" type="password" name="password" placeholder="Confirme sua senha" required style="max-width:220px">
      <button type="submit" class="btn btn-ghost btn-sm btn-danger">
        Excluir minha conta
      </button>
    </form>
  </div>

<?php else: ?>

  <h3 class="text-sm" style="margin-bottom:.75rem">Fotos (<?= count($todasFotos) ?>)</h3>
  <div class="photo-grid">
    <?php foreach ($todasFotos as $foto): ?>
      <?php $podeVer = $foto['visibility'] === 'PUBLIC' || $amigos; ?>
      <?php if ($podeVer): ?>
        <a href="/ver-foto.php?id=<?= e($foto['id']) ?>" class="photo-grid-item">
          <img src="/photo.php?id=<?= e($foto['id']) ?>" alt="Foto de <?= e($profile['display_name']) ?>">
          <?php if ($foto['visibility'] === 'FRIENDS'): ?>
            <span class="badge badge-gold photo-grid-badge">Amigos</span>
          <?php endif; ?>
        </a>
      <?php else: ?>
        <div class="photo-grid-item photo-grid-locked" title="Reservada aos amigos">🔒</div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php if (!$todasFotos): ?>
    <p class="text-sm text-muted">Nenhuma foto ainda.</p>
  <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
