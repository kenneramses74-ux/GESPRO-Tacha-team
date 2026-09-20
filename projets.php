<?php
/**
 * Gestion des projets.
 *
 * Corrections par rapport à l'ancien fichier :
 *  - le fichier contenait deux versions du code collées à la suite
 *    l'une de l'autre (un accident de copier-coller), ce qui générait
 *    deux fois toute la page (deux sidebars, deux <html>...) à chaque
 *    affichage.
 *  - la page réaffichait sa propre sidebar/topbar alors que
 *    includes/header.php s'en charge déjà.
 *  - ajout du jeton CSRF sur les formulaires et redirection après
 *    chaque action (Post/Redirect/Get) pour éviter la double soumission
 *    au rechargement de la page.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
protegerPage();

$pdo = db();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    if (isset($_POST['ajouter_projet']) && estChef()) {
        $nom = trim($_POST['nom_projet'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $dateDebut = $_POST['date_debut'] ?: null;
        $dateFin = $_POST['date_fin'] ?: null;
        // ta table a id_statut pas statut, on met 1 = En preparation
        $id_statut = 1;

        if ($nom === '') {
            setFlash('Le nom du projet est obligatoire.', 'error');
        } else {
            $pdo->prepare(
                'INSERT INTO projets (nom_projet, id_chef, description, date_debut, date_fin, id_tatut) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$nom, $uid, $description ?: null, $dateDebut, $dateFin, $id_statut]);
            setFlash('Projet créé avec succès.');
        }
    }

    if (isset($_POST['modifier_projet']) && estChef()) {
        $idProjet = (int)($_POST['id_projet'] ?? 0);
        $nom = trim($_POST['nom_projet'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $dateDebut = $_POST['date_debut'] ?: null;
        $dateFin = $_POST['date_fin'] ?: null;
        $statut = trim($_POST['statut'] ?? 'En préparation');

        $check = $pdo->prepare('SELECT id_projet FROM projets WHERE id_projet = ? AND id_chef = ?');
        $check->execute([$idProjet, $uid]);

        if ($idProjet <= 0 || $nom === '' || !$check->fetch()) {
            setFlash('Projet introuvable ou informations invalides.', 'error');
        } else {
            $pdo->prepare(
                'UPDATE projets SET nom_projet=?, description=?, date_debut=?, date_fin=?, statut=? WHERE id_projet=? AND id_chef=?'
            )->execute([$nom, $description ?: null, $dateDebut, $dateFin, $statut, $idProjet, $uid]);
            setFlash('Projet modifié avec succès.');
        }
    }

    if (isset($_POST['supprimer_projet']) && estChef()) {
        $idProjet = (int)($_POST['id_projet'] ?? 0);
        $check = $pdo->prepare('SELECT id_projet FROM projets WHERE id_projet = ? AND id_chef = ?');
        $check->execute([$idProjet, $uid]);

        if ($check->fetch()) {
            $pdo->prepare('DELETE FROM affectations WHERE id_tache IN (SELECT id_tache FROM taches WHERE id_projet = ?)')->execute([$idProjet]);
            $pdo->prepare('DELETE FROM taches WHERE id_projet = ?')->execute([$idProjet]);
            $pdo->prepare('DELETE FROM projet_membres WHERE id_projet = ?')->execute([$idProjet]);
            $pdo->prepare('DELETE FROM commentaires WHERE id_projet = ?')->execute([$idProjet]);
            $pdo->prepare('DELETE FROM projets WHERE id_projet = ? AND id_chef = ?')->execute([$idProjet, $uid]);
            setFlash('Projet supprimé avec succès.');
        } else {
            setFlash('Projet introuvable ou accès refusé.', 'error');
        }
    }

    header('Location: projets.php');
    exit;
}

if (estChef()) {
    $stmt = $pdo->prepare('SELECT * FROM projets WHERE id_chef = ? ORDER BY id_projet DESC');
    $stmt->execute([$uid]);
} else {
    $stmt = $pdo->prepare(
        'SELECT DISTINCT p.*, u.prenom, u.nom
         FROM projet_membres pm
         INNER JOIN projets p ON p.id_projet = pm.id_projet
         INNER JOIN utilisateurs u ON u.id_utilisateur = p.id_chef
         WHERE pm.id_utilisateur = ? ORDER BY p.id_projet DESC'
    );
    $stmt->execute([$uid]);
}
$projets = $stmt->fetchAll();

$pageTitle = 'Mes projets';
require __DIR__ . '/../includes/header.php';
?>
<section class="content">
  <div class="page-header">
    <div>
      <span class="eyebrow">GESTION DES PROJETS</span>
      <h1>📁 <?= estChef() ? 'Mes projets' : 'Projets' ?></h1>
      <p><?= estChef() ? 'Créez, modifiez et gérez vos projets depuis cet espace.' : 'Les projets auxquels vous appartenez.' ?></p>
    </div>
  </div>

  <?php if (estChef()): ?>
  <div class="card">
    <h2>➕ Ajouter un projet</h2>
    <form method="post">
      <?= csrfField() ?>
      <div class="project-form">
        <div class="full">
          <label for="nom_projet">Nom du projet *</label>
          <input type="text" id="nom_projet" name="nom_projet" placeholder="Exemple : Développement de GESPRO" required>
        </div>
        <div class="full">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="4" placeholder="Décrivez brièvement le projet..."></textarea>
        </div>
        <div>
          <label for="date_debut">Date de début</label>
          <input type="date" id="date_debut" name="date_debut">
        </div>
        <div>
          <label for="date_fin">Date de fin prévue</label>
          <input type="date" id="date_fin" name="date_fin">
        </div>
        <div>
          <label for="statut">Statut</label>
          <select id="statut" name="statut">
            <option>En préparation</option>
            <option>En cours</option>
            <option>Terminé</option>
            <option>Suspendu</option>
          </select>
        </div>
      </div>
      <br>
      <button type="submit" name="ajouter_projet" class="btn btn-primary">➕ Créer le projet</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="page-header">
      <div><h2>📋 Liste des projets</h2><p><?= count($projets) ?> projet(s)</p></div>
    </div>

    <?php if (!$projets): ?>
      <div class="empty">
        <p>📂 Aucun projet trouvé.</p>
        <?php if (estChef()): ?><p>Commencez par créer votre premier projet ci-dessus.</p><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Projet</th><th>Description</th><th>Début</th><th>Fin</th><th>Statut</th>
              <?php if (!estChef()): ?><th>Chef</th><?php endif; ?>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($projets as $projet): ?>
            <tr>
              <td>#<?= (int)$projet['id_projet'] ?></td>
              <td><strong><?= e($projet['nom_projet']) ?></strong></td>
              <td><?= e($projet['description'] ?? 'Aucune description') ?></td>
              <td><?= formaterDate($projet['date_debut']) ?></td>
              <td><?= formaterDate($projet['date_fin']) ?></td>
              <td><span class="badge id_status"><?= e($projet['id_statut']) ?></span></td>
              <?php if (!estChef()): ?><td><?= e(($projet['prenom'] ?? '') . ' ' . ($projet['nom'] ?? '')) ?></td><?php endif; ?>
              <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  <a href="projet.php?id=<?= (int)$projet['id_projet'] ?>" class="btn btn-secondary">👁️</a>
                  <a href="taches.php?id_projet=<?= (int)$projet['id_projet'] ?>" class="btn btn-secondary">✅</a>
                  <?php if (estChef()): ?>
                    <button type="button" class="btn btn-primary" onclick='ouvrirModification(<?= json_encode($projet, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>✏️</button>
                    <form method="post" style="display:inline" onsubmit="return confirm('Voulez-vous vraiment supprimer ce projet ? Les tâches et membres associés seront également supprimés.');">
                      <?= csrfField() ?>
                      <input type="hidden" name="id_projet" value="<?= (int)$projet['id_projet'] ?>">
                      <button type="submit" name="supprimer_projet" class="btn btn-secondary">🗑️</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if (estChef()): ?>
<div id="modalModification" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);z-index:2000;padding:30px;overflow:auto;">
  <div class="card" style="max-width:700px;margin:50px auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <h2>✏️ Modifier le projet</h2>
      <button type="button" onclick="fermerModification()" class="btn btn-secondary">✕</button>
    </div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="id_projet" id="edit_id_projet">
      <label>Nom du projet *</label>
      <input type="text" name="nom_projet" id="edit_nom_projet" required>
      <label>Description</label>
      <textarea name="description" id="edit_description" rows="4"></textarea>
      <label>Date de début</label>
      <input type="date" name="date_debut" id="edit_date_debut">
      <label>Date de fin</label>
      <input type="date" name="date_fin" id="edit_date_fin">
      <label>Statut</label>
      <select name="statut" id="edit_statut">
        <option>En préparation</option><option>En cours</option><option>Terminé</option><option>Suspendu</option>
      </select>
      <br><br>
      <button type="submit" name="modifier_projet" class="btn btn-primary">💾 Enregistrer les modifications</button>
    </form>
  </div>
</div>
<script>
function ouvrirModification(p) {
  document.getElementById('edit_id_projet').value = p.id_projet;
  document.getElementById('edit_nom_projet').value = p.nom_projet;
  document.getElementById('edit_description').value = p.description || '';
  document.getElementById('edit_date_debut').value = p.date_debut || '';
  document.getElementById('edit_date_fin').value = p.date_fin || '';
  document.getElementById('edit_statut').value = p.statut || 'En préparation';
  document.getElementById('modalModification').style.display = 'block';
}
function fermerModification() {
  document.getElementById('modalModification').style.display = 'none';
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
