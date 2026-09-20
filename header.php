<?php
/**
 * En-tête commun à toutes les pages de l'espace connecté.
 * Ouvre <html>/<body>, affiche la sidebar, la barre du haut et
 * un éventuel message flash. Fermé par includes/footer.php.
 *
 * IMPORTANT : les pages qui incluent ce fichier ne doivent PAS
 * réafficher leur propre sidebar / topbar (c'était un bug fréquent
 * dans l'ancien code, qui produisait deux menus superposés).
 */
$flash = getFlash();
$titre = $pageTitle ?? 'GESPRO';
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($titre) ?> — GESPRO</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
</head>
<body class="app-body">
<?php require __DIR__ . '/sidebar.php'; ?>
<main class="main-content" id="mainContent">
  <header class="topbar">
    <div><strong><?= e($titre) ?></strong></div>
    <div class="user-chip">
      👤 <?= e(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')) ?>
      <span><?= e(roleUtilisateur()) ?></span>
    </div>
  </header>
  <?php if ($flash): ?>
    <div class="content" style="padding-bottom:0;">
      <div class="alert <?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    </div>
  <?php endif; ?>
