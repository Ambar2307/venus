<?php
/** Layout minimalista (sem a barra lateral do app) para telas de autenticação. */
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
    <?php if ($activePage === 'signup'): ?>
      <a href="/login.php" class="btn btn-outline btn-sm">Já tenho perfil</a>
    <?php else: ?>
      <a href="/signup.php" class="btn btn-sm">Criar perfil grátis</a>
    <?php endif; ?>
  </div>
</header>
<main class="page">
  <div class="container">
