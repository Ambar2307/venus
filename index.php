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

$pageTitle = $currentUser ? 'Feed — Reserva' : 'Reserva — Clube de encontros para casais e solteiros(as)';
$activePage = 'feed';
require __DIR__ . '/templates/header.php';

if (!$currentUser):
?>

<section class="hero" data-reveal>
  <div class="hero-glow hero-glow-1"></div>
  <div class="hero-glow hero-glow-2"></div>
  <div class="hero-content">
    <span class="badge badge-gold hero-badge">Clube privado · só para maiores de 18</span>
    <h1 class="font-serif hero-title">
      Onde o desejo encontra
      <span class="hero-cycle" data-cycle-words='["discrição.","bom gosto.","cumplicidade.","o seu ritmo."]'>discrição.</span>
    </h1>
    <p class="hero-sub">
      Reserva é a rede social de encontros para casais e solteiros(as) que
      preferem ir com calma: nome fictício, fotos moderadas e conversas que só
      acontecem quando os dois topam.
    </p>
    <div class="hero-actions">
      <a href="/signup.php" class="btn">Criar meu perfil</a>
      <a href="/login.php" class="btn btn-outline">Já sou membro</a>
    </div>
  </div>
</section>

<section class="feature-grid" data-reveal>
  <div class="feature-card">
    <div class="feature-icon">✦</div>
    <h3 class="text-sm font-serif">Nome fictício, por escolha</h3>
    <p class="text-sm text-muted">Você decide o quanto revelar. Seu perfil nunca precisa levar o seu nome real.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon">✦</div>
    <h3 class="text-sm font-serif">Fotos com moderação</h3>
    <p class="text-sm text-muted">Toda foto passa por aprovação antes de entrar no feed. Sem surpresas indesejadas.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon">✦</div>
    <h3 class="text-sm font-serif">Seus dados, sigilosos</h3>
    <p class="text-sm text-muted">E-mail, telefone e CPF ficam só com a gente — usados apenas para confirmar sua idade.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon">✦</div>
    <h3 class="text-sm font-serif">Visibilidade sob controle</h3>
    <p class="text-sm text-muted">Escolha o que é público e o que fica reservado só para quem você aceitar como amigo.</p>
  </div>
</section>

<section class="steps" data-reveal>
  <h2 class="font-serif" style="text-align:center;margin-bottom:1.5rem">Como funciona</h2>
  <div class="steps-grid">
    <div class="step-card">
      <span class="step-number">1</span>
      <h3 class="text-sm font-serif">Crie seu perfil</h3>
      <p class="text-sm text-muted">Nome fictício, poucos cliques, total controle do que é seu.</p>
    </div>
    <div class="step-card">
      <span class="step-number">2</span>
      <h3 class="text-sm font-serif">Publique com discrição</h3>
      <p class="text-sm text-muted">Fotos públicas ou reservadas só aos amigos — a decisão é sempre sua.</p>
    </div>
    <div class="step-card">
      <span class="step-number">3</span>
      <h3 class="text-sm font-serif">Conecte-se no seu tempo</h3>
      <p class="text-sm text-muted">Curta, comente, peça amizade. Converse de verdade no plano Exclusivo.</p>
    </div>
  </div>
</section>

<section class="cta-band" data-reveal>
  <h2 class="font-serif">Pronto para começar?</h2>
  <p class="text-sm text-muted">Leva menos de dois minutos. É gratuito.</p>
  <a href="/signup.php" class="btn">Criar meu perfil com Acesso Livre</a>
  <p class="text-xs text-muted" style="margin-top:1rem">
    <a href="/termos.php" class="text-muted">Termos de Uso</a> ·
    <a href="/privacidade.php" class="text-muted">Política de Privacidade</a>
  </p>
</section>

<?php
else:
    $fotos = fetch_feed($currentUser['id']);
?>

<h1 class="font-serif">Feed</h1>

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
          <img src="/photo.php?id=<?= e($foto['id']) ?>" alt="Foto de <?= e($foto['display_name']) ?>">
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

<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
