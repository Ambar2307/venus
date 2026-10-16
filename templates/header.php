<?php
/** @var array|null $currentUser definido pela página antes de incluir este template */
$currentUser = $currentUser ?? current_user();
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Clube do Swing') ?></title>
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body data-user-id="<?= e($currentUser['id'] ?? '') ?>" data-is-admin="<?= ($currentUser && is_admin($currentUser)) ? '1' : '0' ?>">
<div class="layout">
  <aside class="sidebar">
    <a href="<?= $currentUser ? '/feed.php' : '/' ?>" class="brand">
      <svg width="26" height="26" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="10" cy="14" r="8.5" stroke="#a9782f" stroke-width="1.7"/>
        <circle cx="18" cy="14" r="8.5" stroke="#8a3f52" stroke-width="1.7"/>
      </svg>
      <span>Clube do Swing</span>
    </a>
    <nav class="nav-links">
      <a href="/feed.php" class="<?= $activePage === 'feed' ? 'active' : '' ?>">
        <?= nav_icon('home') ?><span>Feed</span>
      </a>
      <a href="/buscar.php" class="<?= $activePage === 'buscar' ? 'active' : '' ?>">
        <?= nav_icon('search') ?><span>Buscar</span>
      </a>
      <?php if ($currentUser): ?>
        <a href="/amigos.php" class="<?= $activePage === 'amigos' ? 'active' : '' ?>">
          <?= nav_icon('users') ?><span>Amigos e seguidores</span>
        </a>
        <a href="/chat.php" class="<?= $activePage === 'chat' ? 'active' : '' ?>">
          <?= nav_icon('chat') ?><span>Chat</span>
        </a>
        <a href="/notificacoes.php" class="<?= $activePage === 'notificacoes' ? 'active' : '' ?>">
          <?= nav_icon('bell') ?><span>Notificações</span>
          <?php $naoLidas = count_unread_notifications($currentUser['id']); ?>
          <?php if ($naoLidas > 0): ?>
            <span class="badge badge-gold" style="margin-left:auto">&nbsp;<?= $naoLidas ?>&nbsp;</span>
          <?php endif; ?>
        </a>
        <a href="/perfil.php" class="<?= $activePage === 'perfil' ? 'active' : '' ?>">
          <?= nav_icon('user') ?><span>Meu perfil</span>
        </a>
        <a href="/planos.php" class="<?= $activePage === 'planos' ? 'active' : '' ?>">
          <?= nav_icon('gem') ?><span>Planos</span>
        </a>
        <?php if (is_admin($currentUser)): ?>
          <a href="/admin/moderacao.php" class="text-gold <?= $activePage === 'moderacao' ? 'active' : '' ?>">
            <?= nav_icon('shield') ?><span>Moderação</span>
          </a>
        <?php endif; ?>
        <a href="/logout.php">
          <?= nav_icon('logout') ?><span>Sair</span>
        </a>
      <?php else: ?>
        <a href="/login.php" class="<?= $activePage === 'login' ? 'active' : '' ?>">
          <?= nav_icon('login') ?><span>Entrar</span>
        </a>
        <a href="/signup.php" class="<?= $activePage === 'signup' ? 'active' : '' ?>">
          <?= nav_icon('user-plus') ?><span>Criar perfil</span>
        </a>
      <?php endif; ?>
    </nav>
    <?php if ($currentUser): ?>
      <a href="/perfil.php#minhas-fotos" class="btn-postar">
        <?= nav_icon('plus') ?><span>Postar</span>
      </a>
    <?php endif; ?>
  </aside>
  <main class="page">
    <div class="container">
