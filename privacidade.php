<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = current_user();
$pageTitle = 'Política de Privacidade — Clube do Swing';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Política de Privacidade</h1>
<p class="text-xs text-muted" style="margin-bottom:1.5rem">Última atualização: <?= date('d/m/Y') ?></p>

<div class="text-sm" style="display:flex;flex-direction:column;gap:1rem;max-width:640px">
  <p>
    Esta política explica quais dados o Clube do Swing coleta, para que servem, e
    quais são seus direitos, em linha com a Lei Geral de Proteção de Dados
    (LGPD).
  </p>

  <p><strong>1. Dados coletados.</strong></p>
  <ul class="plan-features" style="color:inherit">
    <li>E-mail e senha (a senha é armazenada com hash, nunca em texto puro).</li>
    <li>Data de nascimento (só para confirmar que você tem 18 anos ou mais).</li>
    <li>Nome fictício, tipo de perfil, interesse e descrição, escolhidos por você no cadastro.</li>
    <li>Fotos que você publica, e as legendas e comentários associados.</li>
    <li>Mensagens trocadas no chat interno (plano Exclusivo).</li>
    <li>Registros técnicos de uso (ex.: quantas curtidas/comentários você fez no dia,
    para aplicar o limite do plano Livre).</li>
  </ul>

  <p><strong>2. Como os dados são usados.</strong> Só para operar a
  plataforma: autenticar seu login, mostrar seu perfil e conteúdo a outros
  usuários conforme a visibilidade que você escolheu, aplicar os limites do
  seu plano, e permitir o chat entre assinantes. Nenhum dado é vendido a
  terceiros.</p>

  <p><strong>3. Metadados de imagem.</strong> Toda foto enviada é
  reprocessada no servidor, o que remove automaticamente metadados EXIF
  (como a localização exata de onde a foto foi tirada) antes de ser
  publicada.</p>

  <p><strong>4. Quem vê o quê.</strong> Fotos marcadas "pública" são
  visíveis a qualquer visitante do site. Fotos marcadas "só amigos" só são
  entregues pelo servidor a você mesmo ou a amizades aceitas — essa checagem
  é feita a cada acesso, nunca só na tela.</p>

  <p><strong>5. Seus direitos (LGPD).</strong> Você pode, a qualquer
  momento:</p>
  <ul class="plan-features" style="color:inherit">
    <li>Acessar e corrigir seus dados de perfil diretamente na página de Perfil.</li>
    <li>Excluir permanentemente sua conta e todo o conteúdo associado
    (fotos, comentários, curtidas, pedidos de amizade e conversas), também
    pela página de Perfil.</li>
  </ul>

  <p><strong>6. Retenção.</strong> Seus dados ficam armazenados enquanto sua
  conta existir. Ao excluir a conta, os dados são apagados do banco e as
  fotos removidas do armazenamento.</p>

  <p>Veja também nossos <a href="/termos.php" class="text-gold">Termos de Uso</a>.</p>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
