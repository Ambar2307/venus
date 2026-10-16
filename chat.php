<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = require_login_page();

$pageTitle = 'Chat — Clube do Swing';
$activePage = 'chat';
require __DIR__ . '/templates/header.php';

$stmt = db()->prepare(
    "SELECT c.id AS conversation_id,
            IF(c.part_a_id = ?, c.part_b_id, c.part_a_id) AS other_id,
            p.display_name
     FROM conversations c
     JOIN profiles p ON p.user_id = IF(c.part_a_id = ?, c.part_b_id, c.part_a_id)
     WHERE c.part_a_id = ? OR c.part_b_id = ?
     ORDER BY c.created_at DESC"
);
$stmt->execute([$currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id']]);
$conversas = $stmt->fetchAll();

if (!is_exclusive($currentUser) && !$conversas) {
    ?>
    <h1 class="font-serif">Chat interno</h1>
    <div class="notice">
      Você ainda não tem conversas. Quem não assina não pode iniciar uma
      conversa nova — mas quando um assinante do plano Exclusivo chamar
      você no chat, a conversa aparece aqui (com direito a
      <?= CHAT_FREE_MESSAGE_LIMIT ?> mensagens grátis). <a href="/planos.php" class="text-gold">Ver planos</a>.
    </div>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

$withId = (string)($_GET['with'] ?? '');
if ($withId === '' && $conversas) {
    $withId = $conversas[0]['other_id'];
}

$outroNome = null;
if ($withId !== '') {
    $stmt = db()->prepare('SELECT display_name FROM profiles WHERE user_id = ?');
    $stmt->execute([$withId]);
    $row = $stmt->fetch();
    $outroNome = $row['display_name'] ?? null;
}

$avisoLimiteGratis = null;
if ($withId && $outroNome && !is_exclusive($currentUser)) {
    $conversaAtual = array_values(array_filter($conversas, fn($c) => $c['other_id'] === $withId))[0] ?? null;
    if ($conversaAtual) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM messages WHERE conversation_id = ? AND sender_id = ?');
        $stmt->execute([$conversaAtual['conversation_id'], $currentUser['id']]);
        $enviadas = (int)$stmt->fetchColumn();
        $restantes = max(0, CHAT_FREE_MESSAGE_LIMIT - $enviadas);
        $avisoLimiteGratis = $restantes > 0
            ? "Você tem {$restantes} de " . CHAT_FREE_MESSAGE_LIMIT . " mensagens grátis restantes nessa conversa."
            : 'Você já usou suas ' . CHAT_FREE_MESSAGE_LIMIT . ' mensagens grátis. Assine o plano Exclusivo pra continuar conversando.';
    }
}
?>

<h1 class="font-serif">Chat interno</h1>

<div class="chat-layout">
  <div class="conversation-list" data-conversation-list>
    <?php foreach ($conversas as $c): ?>
      <a
        class="conversation-item <?= $c['other_id'] === $withId ? 'active' : '' ?>"
        href="/chat.php?with=<?= e($c['other_id']) ?>"
        data-conversation-with="<?= e($c['other_id']) ?>"
      ><?= e($c['display_name']) ?></a>
    <?php endforeach; ?>
    <?php if (!$conversas): ?>
      <p class="text-xs text-muted" data-no-conversations>
        Nenhuma conversa ainda. Vá em <a href="/amigos.php" class="text-gold">Amigos</a> e comece a conversar
        com alguém pelo perfil da pessoa.
      </p>
    <?php endif; ?>
  </div>

  <?php if ($withId && $outroNome): ?>
    <div class="chat-window" data-chat-window data-with-id="<?= e($withId) ?>" data-with-name="<?= e($outroNome) ?>">
      <?php if ($avisoLimiteGratis): ?>
        <p class="text-xs text-muted" style="padding:.5rem 1rem 0">
          <?= e($avisoLimiteGratis) ?>
          <?php if ($restantes === 0): ?><a href="/planos.php" class="text-gold">Ver planos</a>.<?php endif; ?>
        </p>
      <?php endif; ?>
      <div class="chat-messages" data-chat-messages></div>
      <form class="chat-input-row" data-chat-form>
        <input class="input" type="text" name="text" placeholder="Mensagem para <?= e($outroNome) ?>" maxlength="1000" required>
        <button class="btn btn-sm" type="submit">Enviar</button>
      </form>
    </div>
  <?php else: ?>
    <div class="notice">Selecione uma conversa ao lado.</div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
