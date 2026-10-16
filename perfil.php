<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = require_login_page();

$profileId = (string)($_GET['id'] ?? $currentUser['id']);

$stmt = db()->prepare(
    'SELECT u.id, u.created_at, u.plan, u.plan_valid_until,
            p.display_name, p.display_name_changed_at, p.type, p.interest, p.description,
            p.avatar_photo_id, p.cover_photo_1_id, p.cover_photo_2_id
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

$erroNome = null;
if ($isOwnProfile && !$previewMode && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_nome'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $erroNome = 'Sessão expirada, recarregue a página e tente novamente.';
    } else {
        try {
            update_display_name($currentUser['id'], (string)($_POST['display_name'] ?? ''));
            flash('success', 'Nome atualizado!');
            redirect('/perfil.php');
        } catch (RuntimeException $e) {
            $erroNome = $e->getMessage();
        }
    }
}

$tipo = PROFILE_TYPE_LABEL[$profile['type']] ?? '';
$desde = date('m/Y', strtotime($profile['created_at']));

$stmt = db()->prepare(
    "SELECT * FROM photos WHERE user_id = ? AND moderation_status = 'APPROVED' ORDER BY created_at DESC"
);
$stmt->execute([$profile['id']]);
$todasFotos = $stmt->fetchAll();

// Fotos que podem entrar na capa/grade: tudo pro dono editando; só o que o
// visitante (real ou simulado no preview) tem permissão de ver nos outros casos.
$podeVerTudo = $isOwnProfile && !$previewMode;
$fotosVisiveis = $podeVerTudo ? $todasFotos : array_values(array_filter(
    $todasFotos,
    fn($f) => $f['visibility'] === 'PUBLIC' || $amigos
));

// As 2 fotos do mural e o avatar usam a escolha do dono do perfil quando
// disponível; quando não tiver (ou a foto fixada não estiver visível pra
// quem está olhando agora), cai de volta nas fotos mais recentes.
$fotoCapa1 = resolve_pinned_photo($profile['cover_photo_1_id'], $fotosVisiveis);
$fotoCapa2 = resolve_pinned_photo($profile['cover_photo_2_id'], $fotosVisiveis);
$fotoAvatar = resolve_pinned_photo($profile['avatar_photo_id'], $fotosVisiveis);

$idsJaUsados = array_filter([$fotoCapa1['id'] ?? null, $fotoCapa2['id'] ?? null]);
$poolReserva = array_values(array_filter($fotosVisiveis, fn($f) => !in_array($f['id'], $idsJaUsados, true)));
if (!$fotoCapa1) {
    $fotoCapa1 = array_shift($poolReserva);
}
if (!$fotoCapa2) {
    $fotoCapa2 = array_shift($poolReserva);
}
$fotosCapa = array_values(array_filter([$fotoCapa1, $fotoCapa2]));

$podeAlterarNomeAgora = empty($profile['display_name_changed_at'])
    || (strtotime($profile['display_name_changed_at']) + DISPLAY_NAME_CHANGE_COOLDOWN_DAYS * 86400) <= time();
$proximaDataNome = !empty($profile['display_name_changed_at'])
    ? date('d/m/Y', strtotime($profile['display_name_changed_at']) + DISPLAY_NAME_CHANGE_COOLDOWN_DAYS * 86400)
    : null;

$qtdSeguindo = count_following($profile['id']);
$qtdSeguidores = count_followers($profile['id']);
$qtdVisualizacoes = count_profile_visits($profile['id']);
$qtdRecomendacoes = count_recommendations($profile['id']);

if ($isOwnProfile && !$previewMode) {
    $minhasFotos = fetch_own_photos($currentUser['id']);
    $meusAmigos = list_friends($currentUser['id']);
    $meusSeguidores = list_followers($currentUser['id']);
    $recomendados = list_recommended_profiles($currentUser);
    $recomendacoesRecebidas = list_recommenders($currentUser['id']);
    $visitasRecebidas = list_recent_visitors($currentUser['id']);
} else {
    $seguindo = is_following($currentUser['id'], $profile['id']);
    $recomendado = is_recommended($currentUser['id'], $profile['id']);
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

<div class="profile-header card">
  <div class="profile-cover">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <?php if (isset($fotosCapa[$i])): ?>
        <a href="/ver-foto.php?id=<?= e($fotosCapa[$i]['id']) ?>" class="profile-cover-half">
          <img src="/photo.php?id=<?= e($fotosCapa[$i]['id']) ?>" alt="">
        </a>
      <?php else: ?>
        <div class="profile-cover-half profile-cover-empty"></div>
      <?php endif; ?>
    <?php endfor; ?>
  </div>

  <div class="profile-avatar-wrap">
    <?php if ($fotoAvatar): ?>
      <div class="avatar profile-avatar profile-avatar-photo">
        <img src="/photo.php?id=<?= e($fotoAvatar['id']) ?>" alt="Foto de <?= e($profile['display_name']) ?>">
      </div>
    <?php else: ?>
      <div class="avatar profile-avatar"><?= e(initials($profile['display_name'])) ?></div>
    <?php endif; ?>
  </div>

  <div class="profile-info">
    <h1 class="font-serif profile-name" style="text-align:center;margin-bottom:0">
      <?= e($profile['display_name']) ?>
      <?php if ($isOwnProfile && is_exclusive($profile)): ?>
        <span class="badge badge-gold" style="vertical-align:middle">Exclusivo</span>
      <?php endif; ?>
    </h1>

    <?php if ($isOwnProfile && !$previewMode): ?>
      <div style="margin-top:.35rem">
        <button type="button" class="btn btn-ghost btn-sm" data-toggle-btn data-toggle-target="editar-nome-box">
          ✏️ Alterar nome
        </button>
        <div id="editar-nome-box" hidden style="margin-top:.5rem">
          <form method="post" class="form" style="max-width:320px;margin:0 auto">
            <?= csrf_field() ?>
            <input type="hidden" name="alterar_nome" value="1">
            <input class="input" type="text" name="display_name" maxlength="80" required
                   value="<?= e($profile['display_name']) ?>" <?= $podeAlterarNomeAgora ? '' : 'disabled' ?>>
            <?php if ($podeAlterarNomeAgora): ?>
              <p class="text-xs text-muted">O nome só pode ser alterado 1x por ano.</p>
            <?php else: ?>
              <p class="text-xs text-muted">
                Você já alterou seu nome recentemente. Só poderá mudar de novo em
                <?= e($proximaDataNome) ?>.
              </p>
            <?php endif; ?>
            <?php if ($erroNome): ?><p class="error"><?= e($erroNome) ?></p><?php endif; ?>
            <button class="btn btn-sm" type="submit" <?= $podeAlterarNomeAgora ? '' : 'disabled' ?>>
              Salvar novo nome
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="profile-stats">
      <div><strong><?= $qtdVisualizacoes ?></strong><span>visualizações</span></div>
      <div><strong><?= $qtdSeguindo ?></strong><span>seguindo</span></div>
      <div><strong><?= $qtdSeguidores ?></strong><span>seguidores</span></div>
      <?php if ($isOwnProfile && !$previewMode): ?>
        <div>
          <a href="/amigos.php#recomendacoes" style="text-decoration:none"><strong><?= $qtdRecomendacoes ?></strong></a>
          <span>recomendações</span>
        </div>
        <div>
          <a href="/amigos.php#visitas" style="text-decoration:none"><strong>?</strong></a>
          <span>quem visitou seu perfil</span>
        </div>
      <?php else: ?>
        <div><strong><?= $qtdRecomendacoes ?></strong><span>recomendações</span></div>
      <?php endif; ?>
    </div>

    <div class="profile-meta">
      <div class="profile-meta-row"><?= nav_icon('user') ?> <?= e($tipo) ?></div>
      <?php if ($profile['interest']): ?>
        <div class="profile-meta-row"><?= nav_icon('search') ?> Busca: <?= e($profile['interest']) ?></div>
      <?php endif; ?>
      <div class="profile-meta-row"><?= nav_icon('star') ?> No Clube do Swing desde <?= e($desde) ?></div>
    </div>

    <?php if ($profile['description']): ?>
      <p class="text-sm profile-bio"><?= nl2br(e($profile['description'])) ?></p>
    <?php endif; ?>

    <?php if ($isOwnProfile && !$previewMode): ?>
      <div class="profile-actions">
        <a href="/perfil.php?preview=1" class="btn btn-outline btn-sm">Ver como terceiros veem meu perfil</a>
      </div>
    <?php endif; ?>

    <?php if (!$isOwnProfile): ?>
      <div class="profile-actions">
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

        <button
          class="btn btn-outline btn-sm <?= $recomendado ? 'btn-ghost' : '' ?>"
          data-recommend-btn
          data-user-id="<?= e($profile['id']) ?>"
          data-recommended="<?= $recomendado ? '1' : '0' ?>"
          title="Recomende este perfil publicamente para outros usuários"
        ><?= $recomendado ? '★ Recomendado' : '☆ Recomendar' ?></button>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($isOwnProfile && !$previewMode): ?>

  <div class="tabs" style="flex-wrap:wrap">
    <button class="tab-btn active" data-tab-btn data-tab="minhas-fotos">Minhas fotos (<?= count($minhasFotos) ?>)</button>
    <button class="tab-btn" data-tab-btn data-tab="meus-amigos">Meus amigos (<?= count($meusAmigos) ?>)</button>
    <button class="tab-btn" data-tab-btn data-tab="meus-seguidores">Meus seguidores (<?= count($meusSeguidores) ?>)</button>
    <button class="tab-btn" data-tab-btn data-tab="recomendados">Recomendados</button>
    <button class="tab-btn" data-tab-btn data-tab="recomendacoes-recebidas">Recomendações recebidas (<?= count($recomendacoesRecebidas) ?>)</button>
    <button class="tab-btn" data-tab-btn data-tab="visitas">Visitas recebidas (<?= count($visitasRecebidas) ?>)</button>
  </div>

  <div data-tab-panel="minhas-fotos">
    <form method="post" enctype="multipart/form-data" class="form card" style="padding:1rem;margin-bottom:1.5rem">
      <?= csrf_field() ?>
      <input type="hidden" name="publicar_foto" value="1">
      <strong class="text-sm">Publicar foto</strong>
      <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required data-photo-input>

      <div class="photo-adjust" data-photo-adjust hidden>
        <p class="text-xs text-muted">
          Essa foto não encaixa direito no quadro do feed. Arraste pra posicionar e
          use o zoom como preferir — nada é cortado sem você decidir.
        </p>
        <div class="photo-adjust-frame">
          <canvas data-photo-adjust-canvas></canvas>
        </div>
        <label class="text-xs text-muted" style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;max-width:220px">
          Zoom
          <input type="range" min="100" max="250" value="100" data-photo-adjust-zoom style="flex:1">
        </label>
        <button type="button" class="btn btn-ghost btn-sm" data-photo-adjust-reset style="margin-top:.35rem">Redefinir</button>
      </div>

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
            <?php if ($foto['moderation_status'] === 'APPROVED'): ?>
              <div class="photo-actions" style="margin-top:.4rem">
                <button
                  type="button"
                  class="btn btn-sm <?= $profile['avatar_photo_id'] === $foto['id'] ? '' : 'btn-ghost' ?>"
                  data-set-profile-photo-btn data-slot="avatar" data-photo-id="<?= e($foto['id']) ?>"
                  data-active="<?= $profile['avatar_photo_id'] === $foto['id'] ? '1' : '0' ?>"
                >
                  <?= $profile['avatar_photo_id'] === $foto['id'] ? '✓ Foto de perfil' : 'Usar como foto de perfil' ?>
                </button>
                <button
                  type="button"
                  class="btn btn-sm <?= $profile['cover_photo_1_id'] === $foto['id'] ? '' : 'btn-ghost' ?>"
                  data-set-profile-photo-btn data-slot="cover1" data-photo-id="<?= e($foto['id']) ?>"
                  data-active="<?= $profile['cover_photo_1_id'] === $foto['id'] ? '1' : '0' ?>"
                >
                  <?= $profile['cover_photo_1_id'] === $foto['id'] ? '✓ Mural (1)' : 'Fixar no mural (1)' ?>
                </button>
                <button
                  type="button"
                  class="btn btn-sm <?= $profile['cover_photo_2_id'] === $foto['id'] ? '' : 'btn-ghost' ?>"
                  data-set-profile-photo-btn data-slot="cover2" data-photo-id="<?= e($foto['id']) ?>"
                  data-active="<?= $profile['cover_photo_2_id'] === $foto['id'] ? '1' : '0' ?>"
                >
                  <?= $profile['cover_photo_2_id'] === $foto['id'] ? '✓ Mural (2)' : 'Fixar no mural (2)' ?>
                </button>
              </div>
            <?php endif; ?>
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

  <div data-tab-panel="recomendacoes-recebidas" class="card" style="display:none">
    <?php foreach ($recomendacoesRecebidas as $r): ?>
      <div class="friend-row">
        <a href="/perfil.php?id=<?= e($r['id']) ?>" class="text-sm">
          <?= e($r['display_name']) ?>
          <span class="text-xs text-muted"><?= e(PROFILE_TYPE_LABEL[$r['type']] ?? '') ?></span>
        </a>
        <span class="text-xs text-muted">desde <?= e(date('d/m/Y', strtotime($r['created_at']))) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$recomendacoesRecebidas): ?>
      <div class="friend-row"><span class="text-sm text-muted">Ninguém te recomendou publicamente ainda.</span></div>
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

  <div class="tabs">
    <button class="tab-btn active" data-tab-btn data-tab="principal">Principal</button>
    <button class="tab-btn" data-tab-btn data-tab="fotos-visitante">Fotos (<?= count($todasFotos) ?>)</button>
  </div>

  <div data-tab-panel="principal" class="stack">
    <?php foreach ($todasFotos as $foto): ?>
      <?php $podeVer = $foto['visibility'] === 'PUBLIC' || $amigos; ?>
      <article class="card feed-card">
        <div class="photo-media">
          <?php if ($podeVer): ?>
            <a href="/ver-foto.php?id=<?= e($foto['id']) ?>">
              <img src="/photo.php?id=<?= e($foto['id']) ?>" alt="Foto de <?= e($profile['display_name']) ?>">
            </a>
          <?php else: ?>
            <div class="photo-lock">🔒 Reservada aos amigos</div>
          <?php endif; ?>
        </div>
        <?php if ($podeVer && $foto['caption']): ?>
          <div class="photo-body"><p class="text-sm"><?= e($foto['caption']) ?></p></div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
    <?php if (!$todasFotos): ?>
      <p class="text-sm text-muted">Nenhuma foto ainda.</p>
    <?php endif; ?>
  </div>

  <div data-tab-panel="fotos-visitante" class="photo-grid" style="display:none">
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
    <?php if (!$todasFotos): ?>
      <p class="text-sm text-muted" style="grid-column:1/-1">Nenhuma foto ainda.</p>
    <?php endif; ?>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
