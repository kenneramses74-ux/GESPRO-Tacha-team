<?php
/**
 * Profil utilisateur.
 *
 * Corrections :
 *  - avant, un chef pouvait consulter le profil de N'IMPORTE QUEL
 *    utilisateur de l'application (?id=...) sans aucune restriction :
 *    une fuite de données entre équipes non liées. Il ne peut
 *    désormais consulter que les profils des membres de sa PROPRE
 *    équipe.
 *  - le lien "Voir" du tableau était mal formé (« profils.php=5 »
 *    au lieu de « profil.php?id=5 »).
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
protegerPage();

$pdo = db();
$uid = (int)$_SESSION['user_id'];
$idProfil = $uid;

if (estChef() && isset($_GET['id'])) {
    $idDemande = (int)$_GET['id'];
    $stmt = $pdo->prepare(
        'SELECT 1 FROM membres_equipes me
         INNER JOIN equipes e ON e.id_equipe = me.id_equipe
         WHERE e.id_chef = ? AND me.id_utilisateur = ?'
    );
    $stmt->execute([$uid, $idDemande]);
    if ($stmt->fetch()) {
        $idProfil = $idDemande;
    }
}

$stmt = $pdo->prepare('SELECT id_utilisateur, nom, prenom, email, date_creation FROM utilisateurs WHERE id_utilisateur = ?');
$stmt->execute([$idProfil]);
$profil = $stmt->fetch();

if (!$profil) {
    http_response_code(404);
    die('Utilisateur introuvable.');
}

$stmt = $pdo->prepare('SELECT COUNT(DISTINCT id_projet) FROM projet_membres WHERE id_utilisateur = ?');
$stmt->execute([$idProfil]);
$nombreProjets = (int)$stmt->fetchColumn();

$equipeMembres = [];
if (estChef()) {
    $stmt = $pdo->prepare(
        'SELECT u.id_utilisateur, u.nom, u.prenom, u.email
         FROM projet_membres me INNER JOIN utilisateurs u ON u.id_utilisateur = me.id_utilisateur
         INNER JOIN equipes e ON e.id_equipe
         WHERE e.id_chef = ? ORDER BY u.nom'
    );
    $stmt->execute([$uid]);
    $equipeMembres = $stmt->fetchAll();
}

$pageTitle = 'Profil';
require __DIR__ . '/../includes/header.php';
?>
<section class="content">
  <div class="page-header">
    <div>
      <span class="eyebrow">PROFIL UTILISATEUR</span>
      <h1>👤 <?= e($profil['prenom'] . ' ' . $profil['nom']) ?></h1>
      <p>Informations du profil utilisateur.</p>
    </div>
  </div>

  <div class="profile card">
    <div style="display:flex;align-items:center;gap:20px;margin-bottom:25px;">
      <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;">
        <?= e(strtoupper(substr($profil['prenom'], 0, 1))) ?>
      </div>
      <div>
        <h2 style="margin:0;"><?= e($profil['prenom'] . ' ' . $profil['nom']) ?></h2>
        <p style="margin:5px 0;color:#64748b;"><?= e($profil['email']) ?></p>
      </div>
    </div>
    <div class="cards-grid">
      <div class="card"><h3>👤 Nom</h3><p><?= e($profil['nom']) ?></p></div>
      <div class="card"><h3>🧑 Prénom</h3><p><?= e($profil['prenom']) ?></p></div>
      <div class="card"><h3>📧 Email</h3><p><?= e($profil['email']) ?></p></div>
      <div class="card"><h3>📁 Projets</h3><p><?= $nombreProjets ?></p></div>
    </div>
  </div>

  <?php if (estChef() && $idProfil === $uid): ?>
    <div class="card">
      <h2>👥 Membres de mon équipe</h2>
      <p>Consultez le profil des membres de votre équipe.</p>
      <?php if (!$equipeMembres): ?>
        <div class="empty">Aucun membre pour l'instant. <a href="equipe.php">Inviter des membres</a>.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Utilisateur</th><th>Email</th><th>Profil</th></tr></thead>
            <tbody>
            <?php foreach ($equipeMembres as $user): ?>
              <tr>
                <td>👤 <?= e($user['prenom'] . ' ' . $user['nom']) ?></td>
                <td><?= e($user['email']) ?></td>
                <td><a href="profil.php?id=<?= (int)$user['id_utilisateur'] ?>" class="btn btn-primary">👁️ Voir</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  <?php elseif (estChef()): ?>
    <a class="btn btn-secondary" href="profil.php">← Retour à mon profil</a>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
