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
  <title><?= e($pageTitle ?? 'Reserva') ?></title>
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body data-user-id="<?= e($currentUser['id'] ?? '') ?>">
<nav class="top-nav">
  <div class="container">
    <a href="/index.php" class="brand">Reserva</a>
    <div class="nav-links">
      <a href="/index.php" class="<?= $activePage === 'feed' ? 'active' : '' ?>">Feed</a>
      <?php if ($currentUser): ?>
        <a href="/amigos.php" class="<?= $activePage === 'amigos' ? 'active' : '' ?>">Amigos</a>
        <a href="/chat.php" class="<?= $activePage === 'chat' ? 'active' : '' ?>">Chat</a>
        <a href="/perfil.php" class="<?= $activePage === 'perfil' ? 'active' : '' ?>">Perfil</a>
        <a href="/planos.php" class="<?= $activePage === 'planos' ? 'active' : '' ?>">Planos</a>
        <a href="/logout.php">Sair</a>
      <?php else: ?>
        <a href="/login.php" class="<?= $activePage === 'login' ? 'active' : '' ?>">Entrar</a>
        <a href="/signup.php" class="<?= $activePage === 'signup' ? 'active' : '' ?>">Criar perfil</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="page">
  <div class="container">
