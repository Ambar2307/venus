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
    <a href="/index.php" class="brand">
      <svg width="26" height="26" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="10" cy="14" r="8.5" stroke="#a9782f" stroke-width="1.7"/>
        <circle cx="18" cy="14" r="8.5" stroke="#8a3f52" stroke-width="1.7"/>
      </svg>
      <span>Clube do Swing</span>
    </a>
    <nav class="nav-links">
      <a href="/index.php" class="<?= $activePage === 'feed' ? 'active' : '' ?>">Feed</a>
      <?php if ($currentUser): ?>
        <a href="/buscar.php" class="<?= $activePage === 'buscar' ? 'active' : '' ?>">Buscar</a>
        <a href="/amigos.php" class="<?= $activePage === 'amigos' ? 'active' : '' ?>">Amigos</a>
        <a href="/chat.php" class="<?= $activePage === 'chat' ? 'active' : '' ?>">Chat</a>
        <a href="/perfil.php" class="<?= $activePage === 'perfil' ? 'active' : '' ?>">Meu perfil</a>
        <a href="/notificacoes.php" class="<?= $activePage === 'notificacoes' ? 'active' : '' ?>">
          Notificações<?php $naoLidas = count_unread_notifications($currentUser['id']); ?><?php if ($naoLidas > 0): ?>
            <span class="badge badge-gold" style="margin-left:.25rem"><?= $naoLidas ?></span>
          <?php endif; ?>
        </a>
        <a href="/planos.php" class="<?= $activePage === 'planos' ? 'active' : '' ?>">Planos</a>
        <?php if (is_admin($currentUser)): ?>
          <a href="/admin/moderacao.php" class="text-gold <?= $activePage === 'moderacao' ? 'active' : '' ?>">Moderação</a>
        <?php endif; ?>
        <a href="/logout.php">Sair</a>
      <?php else: ?>
        <a href="/login.php" class="<?= $activePage === 'login' ? 'active' : '' ?>">Entrar</a>
        <a href="/signup.php" class="<?= $activePage === 'signup' ? 'active' : '' ?>">Criar perfil</a>
      <?php endif; ?>
    </nav>
  </aside>
  <main class="page">
    <div class="container">
