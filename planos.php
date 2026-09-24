<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = require_login_page();

$pageTitle = 'Planos — Clube do Swing';
$activePage = 'planos';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Planos</h1>

<?php if (is_exclusive($currentUser)): ?>
  <div class="notice" style="margin-bottom:1.5rem">
    Você já é <strong class="text-gold">Exclusivo</strong>
    <?php if ($currentUser['plan_valid_until']): ?>
      até <?= e(date('d/m/Y', strtotime($currentUser['plan_valid_until']))) ?>.
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="plans-grid">
  <div class="plan-card">
    <h3 class="font-serif">Acesso Livre</h3>
    <div class="plan-price">Grátis</div>
    <ul class="plan-features">
      <li>✓ Postar fotos e ver conteúdo público</li>
      <li>✓ Até 3 curtidas por dia</li>
      <li>✓ Até 3 comentários por dia</li>
      <li>✗ Fotos exclusivas para amigos</li>
      <li>✗ Chat interno</li>
    </ul>
  </div>

  <div class="plan-card featured">
    <span class="badge badge-gold">Recomendado</span>
    <h3 class="font-serif">Exclusivo</h3>
    <div class="plan-price">R$ 39<span class="text-sm text-muted">/mês</span></div>
    <ul class="plan-features">
      <li>✓ Curtidas e comentários ilimitados</li>
      <li>✓ Postar fotos só para amigos verem</li>
      <li>✓ Chat interno com outros assinantes</li>
      <li>✓ Selo Exclusivo no perfil</li>
    </ul>
    <?php if (!is_exclusive($currentUser)): ?>
      <button class="btn" disabled title="Integração de pagamento ainda não configurada">
        Assinar (em breve)
      </button>
      <p class="text-xs text-muted" style="margin-top:.5rem">
        A cobrança recorrente (ex.: Mercado Pago) é o próximo passo de integração deste projeto.
      </p>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
