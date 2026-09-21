<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = require_login_page();

$pageTitle = 'Chat — Reserva';
$activePage = 'chat';
require __DIR__ . '/templates/header.php';

if (!is_exclusive($currentUser)) {
    ?>
    <h1 class="font-serif">Chat interno</h1>
    <div class="notice">
      O chat interno é exclusivo para assinantes do plano Exclusivo.
      <a href="/planos.php" class="text-gold">Ver planos</a>.
    </div>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

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
