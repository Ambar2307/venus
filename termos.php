<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = current_user();
$pageTitle = 'Termos de Uso — Clube do Swing';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Termos de Uso</h1>
<p class="text-xs text-muted" style="margin-bottom:1.5rem">Última atualização: <?= date('d/m/Y') ?></p>

<div class="text-sm" style="display:flex;flex-direction:column;gap:1rem;max-width:640px">
  <p>
    O Clube do Swing é uma plataforma para maiores de 18 anos, voltada a casais e
    solteiros(as) que queiram se conhecer, trocar fotos e conversar. Ao criar
    um perfil, você concorda com estes termos.
  </p>

  <p><strong>1. Idade mínima.</strong> É proibido o cadastro de menores de 18
  anos. A data de nascimento informada é conferida no momento do cadastro;
  fornecer uma data falsa é motivo para exclusão imediata da conta.</p>

  <p><strong>2. Conteúdo permitido.</strong> Fotos e comentários publicados
  são revisados antes de aparecer publicamente (moderação). É proibido
  publicar: conteúdo envolvendo menores de idade sob qualquer forma,
  conteúdo não consensual, violência, discurso de ódio, spam ou conteúdo que
  identifique terceiros sem autorização. Contas que violarem essas regras
  são suspensas.</p>

  <p><strong>3. Denúncias.</strong> Qualquer usuário pode denunciar uma foto
  que considere inadequada, usando o botão "Denunciar" disponível em cada
  publicação. Denúncias são analisadas pela moderação.</p>

  <p><strong>4. Visibilidade do conteúdo.</strong> Fotos marcadas como
  "pública" ficam visíveis a qualquer visitante. Fotos marcadas como "só
  amigos" só ficam visíveis para o próprio autor e para amizades aceitas
  dentro da plataforma.</p>

  <p><strong>5. Planos.</strong> O Acesso Livre é gratuito, com limite diário
  de curtidas e comentários. O plano Exclusivo é pago e libera curtidas e
  comentários ilimitados, postagem de fotos reservadas a amigos e o chat
  interno.</p>

  <p><strong>6. Conta e exclusão.</strong> Você pode excluir sua conta a
  qualquer momento pela página de perfil. A exclusão remove permanentemente
  seu perfil, fotos, comentários, curtidas e conversas — essa ação não pode
  ser desfeita.</p>

  <p><strong>7. Contato.</strong> Dúvidas sobre estes termos podem ser
  encaminhadas pelos canais de suporte informados no rodapé do site (a
  preencher pela operação da plataforma).</p>

  <p>Veja também nossa <a href="/privacidade.php" class="text-gold">Política de Privacidade</a>.</p>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
