<?php
/**
 * Détail d'un projet : informations, membres affectés, accès rapide aux tâches.
 * Remplace l'ancien fichier chef/projets/voir.php, qui pointait vers des
 * chemins (../../config, ../../includes) incohérents avec le reste de
 * l'application et n'était relié à aucun menu.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
protegerPage();

$pdo = db();
$uid = (int)$_SESSION['user_id'];
$idProjet = (int)($_GET['id'] ?? 0);

if (estChef()) {
    $stmt = $pdo->prepare('SELECT * FROM projets WHERE id_projet = ? AND id_chef = ?');
    $stmt->execute([$idProjet, $uid]);
} else {
    $stmt = $pdo->prepare(
        'SELECT p.* FROM projets p
         INNER JOIN projet_membres pm ON pm.id_projet = p.id_projet
         WHERE p.id_projet = ? AND pm.id_utilisateur = ?'
    );
    $stmt->execute([$idProjet, $uid]);
}
$projet = $stmt->fetch();

if (!$projet) {
    http_response_code(404);
    $pageTitle = 'Projet introuvable';
    require __DIR__ . '/../includes/header.php';
    echo '<section class="content"><div class="card empty">Projet introuvable ou accès refusé. <a href="projets.php">Retour aux projets</a></div></section>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// Réserve de membres disponibles : l'équipe du chef, pour ce projet.
$idEquipe = null;
if (estChef()) {
    $stmt = $pdo->prepare('SELECT id_equipe FROM equipes WHERE id_chef = ? LIMIT 1');
    $stmt->execute([$uid]);
    $idEquipe = $stmt->fetchColumn() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && estChef()) {
    verifierCsrf();

    if (isset($_POST['ajouter_membre']) && $idEquipe) {
        $idMembre = (int)($_POST['id_utilisateur'] ?? 0);
        $check = $pdo->prepare('SELECT 1 FROM membres_equipes WHERE id_equipe = ? AND id_utilisateur = ?');
        $check->execute([$idEquipe, $idMembre]);

        if ($check->fetch()) {
            $pdo->prepare('INSERT IGNORE INTO projet_membres (id_projet, id_utilisateur) VALUES (?, ?)')->execute([$idProjet, $idMembre]);
            notifier($pdo, $idMembre, 'Vous avez été ajouté au projet « ' . $projet['nom_projet'] . ' ».');
            setFlash('Membre ajouté au projet.');
        } else {
            setFlash('Cette personne ne fait pas partie de votre équipe.', 'error');
        }
    }

    if (isset($_POST['retirer_membre'])) {
        $idMembre = (int)($_POST['id_utilisateur'] ?? 0);
        $pdo->prepare('DELETE FROM projet_membres WHERE id_projet = ? AND id_utilisateur = ?')->execute([$idProjet, $idMembre]);
        $pdo->prepare('DELETE FROM affectations WHERE id_utilisateur = ? AND id_tache IN (SELECT id_tache FROM taches WHERE id_projet = ?)')->execute([$idMembre, $idProjet]);
        setFlash('Membre retiré du projet.');
    }

    header('Location: projet.php?id=' . $idProjet);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT u.id_utilisateur, u.nom, u.prenom, u.email
     FROM projet_membres pm INNER JOIN utilisateurs u ON u.id_utilisateur = pm.id_utilisateur
     WHERE pm.id_projet = ? ORDER BY u.nom'
);
$stmt->execute([$idProjet]);
$membres = $stmt->fetchAll();
$idEquipe = $projet['id_equipe'] ?? null;
$disponibles = [];
if ($idEquipe) {
    $stmt = $pdo->prepare(
        "SELECT u.id_utilisateur, u.nom, u.prenom, u.email
         FROM membres_equipes me INNER JOIN utilisateurs u ON u.id_utilisateur = me.id_utilisateur
         WHERE me.id_equipe = ? AND u.id_utilisateur NOT IN (
             SELECT id_utilisateur FROM projet_membres WHERE id_projet = ?
         ) ORDER BY u.nom"
    );
    $stmt->execute([$idEquipe, $idProjet]);
    $disponibles = $stmt->fetchAll();
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM taches WHERE id_projet = ?');
$stmt->execute([$idProjet]);
$nbTaches = (int)$stmt->fetchColumn();

$pageTitle = 'Projet · ' . $projet['nom_projet'];
require __DIR__ . '/../includes/header.php';
?>
<section class="content">
  <div class="page-header">
    <div>
      <span class="eyebrow">PROJET</span>
      <h1>📁 <?= e($projet['nom_projet']) ?></h1>
      <p><?= e($projet['description'] ?: 'Aucune description.') ?></p>
    </div>
  </div>

  <div class="cards-grid">
    <div class="card"><h3>Statut</h3><p><span class="badge status"><?= e($projet['id_statut']) ?></span></p></div>
    <div class="card"><h3>Début</h3><p><?= formaterDate($projet['date_debut']) ?></p></div>
    <div class="card"><h3>Fin prévue</h3><p><?= formaterDate($projet['date_fin']) ?></p></div>
    <div class="card"><h3>Tâches</h3><p><?= $nbTaches ?> tâche(s) — <a href="taches.php?id_projet=<?= (int)$idProjet ?>">voir</a></p></div>
  </div>

  <?php if (estChef()): ?>
  <div class="card">
    <h2>Ajouter un membre au projet</h2>
    <?php if (!$idEquipe): ?>
      <div class="alert error">Créez d'abord votre équipe depuis « Mon équipe » pour pouvoir inviter des membres sur vos projets.</div>
    <?php elseif (!$disponibles): ?>
      <p class="hint">Tous les membres de votre équipe sont déjà sur ce projet, ou votre équipe est vide. <a href="equipe.php">Gérer mon équipe</a>.</p>
    <?php else: ?>
      <form method="post" class="inline-form">
        <?= csrfField() ?>
        <select name="id_utilisateur" required>
          <option value="">Choisir un membre de votre équipe</option>
          <?php foreach ($disponibles as $m): ?>
            <option value="<?= (int)$m['id_utilisateur'] ?>"><?= e($m['prenom'] . ' ' . $m['nom']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" name="ajouter_membre">Ajouter au projet</button>
      </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <h2>Membres du projet (<?= count($membres) ?>)</h2>
    <?php if (!$membres): ?>
      <div class="empty">Aucun membre affecté à ce projet.</div>
    <?php else: ?>
      <div class="cards-grid">
        <?php foreach ($membres as $m): ?>
          <div class="card">
            <strong><?= e($m['prenom'] . ' ' . $m['nom']) ?></strong>
            <p><?= e($m['email']) ?></p>
            <?php if (estChef()): ?>
              <form method="post" onsubmit="return confirm('Retirer ce membre du projet ?');">
                <?= csrfField() ?>
                <input type="hidden" name="id_utilisateur" value="<?= (int)$m['id_utilisateur'] ?>">
                <button type="submit" name="retirer_membre" class="btn btn-secondary">Retirer</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <a class="btn btn-secondary" href="projets.php">← Retour aux projets</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
