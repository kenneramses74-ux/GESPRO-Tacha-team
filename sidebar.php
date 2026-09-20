<?php
/**
 * Menu latéral.
 *
 * Avant : ce fichier pointait vers des chemins qui n'existaient pas
 * (ex. "/GESPRO/chef/projets/index.php", "/GESPRO/utilisateur/taches.php")
 * alors que les pages réelles étaient toutes à plat dans /dashboard.
 * Résultat : la quasi-totalité des liens du menu renvoyaient une 404.
 * Ils pointent maintenant vers les fichiers qui existent vraiment.
 */
$chef = estChef();
$page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$actif = function ($fichier) use ($page) {
    return $page === $fichier ? ' active' : '';
};
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">GP</div>
    <div class="logo-copy"><strong>GESPRO</strong><small>Gestion de projets</small></div>
  </div>
  <nav class="sidebar-menu">
    <a href="<?= e(url('/dashboard/index.php')) ?>" class="menu-link<?= $actif('index.php') ?>"><span>🏠</span><b>Tableau de bord</b></a>
    <a href="<?= e(url('/dashboard/projets.php')) ?>" class="menu-link<?= $actif('projets.php') ?>"><span>📁</span><b><?= $chef ? 'Mes projets' : 'Projets' ?></b></a>
    <a href="<?= e(url('/dashboard/taches.php')) ?>" class="menu-link<?= $actif('taches.php') ?>"><span>✅</span><b><?= $chef ? 'Tâches' : 'Mes tâches' ?></b></a>
    <?php if ($chef): ?>
      <a href="<?= e(url('/dashboard/equipe.php')) ?>" class="menu-link<?= $actif('equipe.php') ?>"><span>👥</span><b>Mon équipe</b></a>
    <?php endif; ?>
    <a href="<?= e(url('/dashboard/commentaires.php')) ?>" class="menu-link<?= $actif('commentaires.php') ?>"><span>💬</span><b>Commentaires</b></a>
    <a href="<?= e(url('/dashboard/notifications.php')) ?>" class="menu-link<?= $actif('notifications.php') ?>"><span>🔔</span><b>Notifications</b></a>
    <a href="<?= e(url('/dashboard/profil.php')) ?>" class="menu-link<?= $actif('profil.php') ?>"><span>👤</span><b>Mon profil</b></a>
    <a href="<?= e(url('/dashboard/parametres.php')) ?>" class="menu-link<?= $actif('parametres.php') ?>"><span>⚙️</span><b>Paramètres</b></a>
    <div class="menu-separator"></div>
    <a href="<?= e(url('/auth/logout.php')) ?>" class="menu-link logout"><span>🚪</span><b>Déconnexion</b></a>
  </nav>
</aside>
<button id="sidebarToggle" class="sidebar-toggle" type="button" aria-label="Menu">☰</button>
