<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
protegerPage();

$pageTitle = estChef() ? 'Tableau de bord' : 'Mon espace';
$pdo = db();
$uid = (int)$_SESSION['user_id'];

$nbProjets = $nbTaches = $nbTerminees = $nbMembres = 0;

if (estChef()) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM projets WHERE id_chef = ?');
    $stmt->execute([$uid]);
    $nbProjets = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM taches t INNER JOIN projets p ON p.id_projet = t.id_projet WHERE p.id_chef = ?');
    $stmt->execute([$uid]);
    $nbTaches = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM taches t INNER JOIN projets p ON p.id_projet = t.id_projet WHERE p.id_chef = ? AND t.statut = 'Terminé'");
    $stmt->execute([$uid]);
    $nbTerminees = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT pm.id_utilisateur) FROM projet_membres pm INNER JOIN projets p ON p.id_projet = pm.id_projet WHERE p.id_chef = ?');
    $stmt->execute([$uid]);
    $nbMembres = (int)$stmt->fetchColumn();
} else {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM projet_membres WHERE id_utilisateur = ?');
    $stmt->execute([$uid]);
    $nbProjets = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM affectations WHERE id_utilisateur = ?');
    $stmt->execute([$uid]);
    $nbTaches = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM affectations a INNER JOIN taches t ON t.id_tache = a.id_tache WHERE a.id_utilisateur = ? AND t.statut = 'Terminé'");
    $stmt->execute([$uid]);
    $nbTerminees = (int)$stmt->fetchColumn();
}

require __DIR__ . '/../includes/header.php';
?>
<section class="content">
  <div class="welcome">
    <span class="eyebrow"><?= estChef() ? 'ESPACE CHEF DE PROJET' : 'ESPACE MEMBRE' ?></span>
    <h1>Bonjour <?= e($_SESSION['prenom'] ?? '') ?> 👋</h1>
    <p><?= estChef()
        ? 'Pilotez vos projets, vos tâches et votre équipe depuis un seul espace.'
        : 'Retrouvez ici vos projets, vos tâches et les activités de votre équipe.' ?></p>
  </div>

  <div class="stats-grid">
    <div class="stat-card blue"><span>📁</span><strong><?= $nbProjets ?></strong><small>Projets</small></div>
    <div class="stat-card orange"><span>✅</span><strong><?= $nbTaches ?></strong><small>Tâches</small></div>
    <div class="stat-card green"><span>✓</span><strong><?= $nbTerminees ?></strong><small>Tâches terminées</small></div>
    <?php if (estChef()): ?>
      <div class="stat-card purple"><span>👥</span><strong><?= $nbMembres ?></strong><small>Membres</small></div>
    <?php endif; ?>
  </div>

  <div class="cards-grid">
    <a href="projets.php" class="card dashboard-link">
      <h2>🚀 Organisation</h2>
      <p>Consultez et gérez vos projets, leurs échéances, priorités et avancements.</p>
    </a>
    <a href="taches.php" class="card dashboard-link">
      <h2>✅ Tâches</h2>
      <p>Consultez les tâches, les responsables et leur état d'avancement.</p>
    </a>
    <?php if (estChef()): ?>
      <a href="equipe.php" class="card dashboard-link">
        <h2>👥 Collaboration</h2>
        <p>Invitez des membres et gérez les équipes associées à vos projets.</p>
      </a>
    <?php endif; ?>
    <a href="notifications.php" class="card dashboard-link">
      <h2>🔔 Notifications</h2>
      <p>Consultez vos notifications et les activités récentes.</p>
    </a>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
