<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = require_login_page();

$tipo = (string)($_GET['type'] ?? '');
$cidade = trim((string)($_GET['city'] ?? ''));
$interesse = trim((string)($_GET['interest'] ?? ''));
$temFiltro = $tipo !== '' || $cidade !== '' || $interesse !== '';

$resultados = [];
if ($temFiltro) {
    $sql = "SELECT u.id, u.city, u.state, p.display_name, p.type, p.interest
            FROM users u JOIN profiles p ON p.user_id = u.id
            WHERE u.id != ? AND u.status = 'active'";
    $params = [$currentUser['id']];

    if ($tipo !== '' && in_array($tipo, ['COUPLE', 'SINGLE_WOMAN', 'SINGLE_MAN'], true)) {
        $sql .= ' AND p.type = ?';
        $params[] = $tipo;
    }
    if ($cidade !== '') {
        $sql .= ' AND u.city LIKE ?';
        $params[] = '%' . $cidade . '%';
    }
    if ($interesse !== '') {
        $sql .= ' AND p.interest LIKE ?';
        $params[] = '%' . $interesse . '%';
    }
    $sql .= ' ORDER BY p.display_name ASC LIMIT 50';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll();
}

$seguindoIds = array_column(list_following($currentUser['id']), 'id');

$pageTitle = 'Buscar — Reserva';
$activePage = 'buscar';
require __DIR__ . '/templates/header.php';
?>

<h1 class="font-serif">Buscar</h1>

<form method="get" class="form" style="max-width:480px;margin-bottom:2rem">
  <select class="input" name="type">
    <option value="">Todos os tipos</option>
    <option value="COUPLE" <?= $tipo === 'COUPLE' ? 'selected' : '' ?>>Casal</option>
    <option value="SINGLE_WOMAN" <?= $tipo === 'SINGLE_WOMAN' ? 'selected' : '' ?>>Mulher solteira</option>
    <option value="SINGLE_MAN" <?= $tipo === 'SINGLE_MAN' ? 'selected' : '' ?>>Homem solteiro</option>
  </select>
  <input class="input" type="text" name="city" placeholder="Cidade" value="<?= e($cidade) ?>">
  <input class="input" type="text" name="interest" placeholder="Interesse (ex.: casais, viagens)" value="<?= e($interesse) ?>">
  <button class="btn" type="submit">Buscar</button>
</form>

<?php if ($temFiltro): ?>
  <h3 class="text-sm">Resultados (<?= count($resultados) ?>)</h3>
  <div class="card">
    <?php foreach ($resultados as $r): ?>
      <div class="friend-row">
        <a href="/perfil.php?id=<?= e($r['id']) ?>" class="text-sm">
          <?= e($r['display_name']) ?>
          <span class="text-xs text-muted">
            <?= e(PROFILE_TYPE_LABEL[$r['type']] ?? '') ?> · <?= e($r['city']) ?>/<?= e($r['state']) ?>
            <?php if ($r['interest']): ?> · <?= e($r['interest']) ?><?php endif; ?>
          </span>
        </a>
        <?php $jaSegue = in_array($r['id'], $seguindoIds, true); ?>
        <button
          class="btn btn-sm <?= $jaSegue ? 'btn-ghost' : 'btn-outline' ?>"
          data-follow-btn data-user-id="<?= e($r['id']) ?>" data-following="<?= $jaSegue ? '1' : '0' ?>"
        ><?= $jaSegue ? 'Deixar de seguir' : 'Seguir' ?></button>
      </div>
    <?php endforeach; ?>
    <?php if (!$resultados): ?>
      <div class="friend-row"><span class="text-sm text-muted">Nenhum perfil encontrado com esses filtros.</span></div>
    <?php endif; ?>
  </div>
<?php else: ?>
  <p class="text-sm text-muted">Use os filtros acima para encontrar perfis por tipo, cidade ou interesse.</p>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
