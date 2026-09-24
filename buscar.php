<?php
require_once __DIR__ . '/app/bootstrap.php';

$currentUser = require_login_page();

$tipo = (string)($_GET['type'] ?? '');
$uf = strtoupper(trim((string)($_GET['state'] ?? '')));
$cidade = trim((string)($_GET['city'] ?? ''));
$interesse = trim((string)($_GET['interest'] ?? ''));
$idadeMin = (string)($_GET['idade_min'] ?? '') !== '' ? max(18, (int)$_GET['idade_min']) : null;
$idadeMax = (string)($_GET['idade_max'] ?? '') !== '' ? min(120, (int)$_GET['idade_max']) : null;
$temFiltro = $tipo !== '' || $uf !== '' || $cidade !== '' || $interesse !== '' || $idadeMin !== null || $idadeMax !== null;

$resultados = [];
if ($temFiltro) {
    $sql = "SELECT u.id, u.city, u.state, p.display_name, p.type, p.interest,
                   TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) AS idade
            FROM users u JOIN profiles p ON p.user_id = u.id
            WHERE u.id != ? AND u.status = 'active'";
    $params = [$currentUser['id']];

    if ($tipo !== '' && in_array($tipo, ['COUPLE', 'SINGLE_WOMAN', 'SINGLE_MAN'], true)) {
        $sql .= ' AND p.type = ?';
        $params[] = $tipo;
    }
    if ($uf !== '' && in_array($uf, BRAZIL_STATES, true)) {
        $sql .= ' AND u.state = ?';
        $params[] = $uf;
    }
    if ($cidade !== '') {
        $sql .= ' AND u.city = ?';
        $params[] = $cidade;
    }
    if ($interesse !== '' && in_array($interesse, INTEREST_OPTIONS, true)) {
        $sql .= ' AND p.interest = ?';
        $params[] = $interesse;
    }
    if ($idadeMin !== null) {
        $sql .= ' AND TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) >= ?';
        $params[] = $idadeMin;
    }
    if ($idadeMax !== null) {
        $sql .= ' AND TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) <= ?';
        $params[] = $idadeMax;
    }
    $sql .= ' ORDER BY p.display_name ASC LIMIT 50';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll();
}

$miniaturas = fetch_profile_thumbnails(array_column($resultados, 'id'));
$seguindoIds = array_column(list_following($currentUser['id']), 'id');

$pageTitle = 'Buscar — Clube do Swing';
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

  <div class="form" style="flex-direction:row;gap:.75rem">
    <select class="input" name="state" style="flex:1" data-state-select data-city-target="city-select-buscar">
      <option value="">UF</option>
      <?php foreach (BRAZIL_STATES as $ufOpt): ?>
        <option value="<?= e($ufOpt) ?>" <?= $uf === $ufOpt ? 'selected' : '' ?>><?= e($ufOpt) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="input" name="city" style="flex:2" id="city-select-buscar" data-empty-label="Todas as cidades">
      <option value=""><?= $uf ? 'Todas as cidades' : 'Escolha a UF' ?></option>
      <?php foreach (cities_for_state($uf) as $cidadeOpt): ?>
        <option value="<?= e($cidadeOpt) ?>" <?= $cidade === $cidadeOpt ? 'selected' : '' ?>><?= e($cidadeOpt) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <select class="input" name="interest">
    <option value="">Todos os interesses</option>
    <?php foreach (INTEREST_OPTIONS as $opt): ?>
      <option value="<?= e($opt) ?>" <?= $interesse === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
    <?php endforeach; ?>
  </select>

  <div class="form" style="flex-direction:row;gap:.75rem;align-items:flex-end">
    <label class="text-sm text-muted" style="flex:1">
      Idade entre
      <input class="input" type="number" name="idade_min" min="18" max="120" placeholder="18"
             value="<?= e((string)($_GET['idade_min'] ?? '')) ?>">
    </label>
    <label class="text-sm text-muted" style="flex:1">
      e
      <input class="input" type="number" name="idade_max" min="18" max="120" placeholder="99"
             value="<?= e((string)($_GET['idade_max'] ?? '')) ?>">
    </label>
  </div>

  <button class="btn" type="submit">Buscar</button>
</form>

<?php if ($temFiltro): ?>
  <h3 class="text-sm" style="margin-bottom:.75rem">Resultados (<?= count($resultados) ?>)</h3>
  <div class="search-grid">
    <?php foreach ($resultados as $r): ?>
      <?php $jaSegue = in_array($r['id'], $seguindoIds, true); ?>
      <div class="search-card">
        <a href="/perfil.php?id=<?= e($r['id']) ?>" class="search-card-photo">
          <?php if (isset($miniaturas[$r['id']])): ?>
            <img src="/photo.php?id=<?= e($miniaturas[$r['id']]) ?>" alt="Foto de <?= e($r['display_name']) ?>">
          <?php else: ?>
            <div class="search-card-noimg"><?= e(initials($r['display_name'])) ?></div>
          <?php endif; ?>
        </a>
        <div class="search-card-body">
          <a href="/perfil.php?id=<?= e($r['id']) ?>" class="text-sm" style="font-weight:600"><?= e($r['display_name']) ?></a>
          <div class="text-xs text-muted">
            <?= e(PROFILE_TYPE_LABEL[$r['type']] ?? '') ?> · <?= (int)$r['idade'] ?> anos
          </div>
          <div class="text-xs text-muted"><?= e($r['city']) ?>/<?= e($r['state']) ?></div>
          <?php if ($r['interest']): ?>
            <div class="text-xs text-muted"><?= e($r['interest']) ?></div>
          <?php endif; ?>
          <button
            class="btn btn-sm <?= $jaSegue ? 'btn-ghost' : 'btn-outline' ?>" style="margin-top:.5rem;width:100%"
            data-follow-btn data-user-id="<?= e($r['id']) ?>" data-following="<?= $jaSegue ? '1' : '0' ?>"
          ><?= $jaSegue ? 'Deixar de seguir' : 'Seguir' ?></button>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$resultados): ?>
      <p class="text-sm text-muted">Nenhum perfil encontrado com esses filtros.</p>
    <?php endif; ?>
  </div>
<?php else: ?>
  <p class="text-sm text-muted">Use os filtros acima para encontrar perfis por tipo, cidade ou interesse.</p>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
