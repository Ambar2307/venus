<?php
require_once __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('/feed.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clube do Swing — encontros discretos para casais e solteiros</title>
  <meta name="description" content="A rede social discreta para casais e solteiros que vivem o swing com liberdade, respeito e privacidade. Perfis verificados, dados sigilosos e moderação ativa.">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="landing-topbar">
  <a href="/" class="brand">
    <svg width="26" height="26" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <circle cx="10" cy="14" r="8.5" stroke="#a9782f" stroke-width="1.7"/>
      <circle cx="18" cy="14" r="8.5" stroke="#8a3f52" stroke-width="1.7"/>
    </svg>
    <span>Clube do Swing</span>
  </a>
  <div class="landing-topbar-actions">
    <a href="/login.php" class="btn btn-ghost btn-sm">Entrar</a>
    <a href="/signup.php" class="btn btn-sm">Criar perfil grátis</a>
  </div>
</header>

<main class="landing">
  <section class="landing-hero">
    <h1 class="font-serif">Viva o swing com liberdade, respeito e privacidade</h1>
    <p class="landing-hero-sub">
      O Clube do Swing é a rede social pensada para casais e solteiros que buscam
      novas conexões com discrição. Perfis verificados, dados sigilosos e um
      ambiente moderado para você explorar com segurança.
    </p>
    <div class="landing-cta-group">
      <a href="/signup.php" class="btn">Criar perfil grátis</a>
      <a href="/login.php" class="btn btn-outline">Já tenho perfil</a>
    </div>
    <p class="landing-hero-badges text-xs text-muted">
      🔞 Somente maiores de 18 anos &nbsp;·&nbsp; 🛡️ Perfis verificados &nbsp;·&nbsp; 🤫 Identidade sigilosa
    </p>
  </section>

  <section class="landing-section">
    <h2 class="font-serif landing-section-title">Como funciona</h2>
    <div class="landing-steps">
      <div class="landing-step">
        <div class="landing-step-num">1</div>
        <h3>Crie seu perfil anônimo</h3>
        <p class="text-sm text-muted">Escolha um nome fictício e conte como você é. Seus dados reais (e-mail, telefone, CPF) ficam sigilosos, usados só para confirmar sua idade e identidade.</p>
      </div>
      <div class="landing-step">
        <div class="landing-step-num">2</div>
        <h3>Explore e converse</h3>
        <p class="text-sm text-muted">Busque por localização, tipo de perfil e interesse. Siga quem te interessa, curta e comente fotos, e converse em um chat privado.</p>
      </div>
      <div class="landing-step">
        <div class="landing-step-num">3</div>
        <h3>Combine com segurança</h3>
        <p class="text-sm text-muted">Você decide o que mostrar e para quem. Conteúdo é moderado e qualquer perfil pode ser denunciado a qualquer momento.</p>
      </div>
    </div>
  </section>

  <section class="landing-section landing-section-alt">
    <h2 class="font-serif landing-section-title">Por que o Clube do Swing</h2>
    <div class="landing-features">
      <div class="landing-feature">
        <span class="landing-feature-icon">🤐</span>
        <h3>Privacidade em primeiro lugar</h3>
        <p class="text-sm text-muted">Cidade, e-mail, telefone e CPF nunca aparecem no seu perfil ou para outros usuários.</p>
      </div>
      <div class="landing-feature">
        <span class="landing-feature-icon">✅</span>
        <h3>Perfis verificados</h3>
        <p class="text-sm text-muted">Confirmamos idade e identidade no cadastro, mantendo a comunidade mais segura.</p>
      </div>
      <div class="landing-feature">
        <span class="landing-feature-icon">🛡️</span>
        <h3>Moderação ativa</h3>
        <p class="text-sm text-muted">Fotos passam por aprovação e qualquer conteúdo ou perfil pode ser denunciado.</p>
      </div>
      <div class="landing-feature">
        <span class="landing-feature-icon">💑</span>
        <h3>Casais e solteiros</h3>
        <p class="text-sm text-muted">Espaço aberto para casais, mulheres e homens solteiros, com interesses variados.</p>
      </div>
      <div class="landing-feature">
        <span class="landing-feature-icon">📍</span>
        <h3>Busca por localização</h3>
        <p class="text-sm text-muted">Encontre perfis da sua cidade, do seu estado ou de todo o Brasil.</p>
      </div>
      <div class="landing-feature">
        <span class="landing-feature-icon">💬</span>
        <h3>Chat privado</h3>
        <p class="text-sm text-muted">Converse diretamente com quem você conectar, no seu próprio ritmo.</p>
      </div>
    </div>
  </section>

  <section class="landing-cta-band">
    <h2 class="font-serif">Pronto para fazer parte do Clube?</h2>
    <p class="text-sm" style="opacity:.9">Crie seu perfil gratuito em poucos minutos.</p>
    <a href="/signup.php" class="btn landing-cta-band-btn">Criar perfil grátis</a>
  </section>
</main>

<footer class="landing-footer">
  <span>&copy; <?= date('Y') ?> Clube do Swing</span>
  <nav>
    <a href="/termos.php">Termos de Uso</a>
    <a href="/privacidade.php">Política de Privacidade</a>
    <a href="/feed.php">Ver comunidade</a>
  </nav>
</footer>

</body>
</html>
