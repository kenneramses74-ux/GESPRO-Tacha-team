<?php
/**
 * Gestion des tâches.
 *
 * Corrections / ajouts par rapport à l'ancien fichier :
 *  - l'ancien code n'affectait JAMAIS une tâche à un utilisateur
 *    (la table `affectations` restait vide) : un membre ne voyait
 *    donc jamais aucune tâche, la fonctionnalité était cassée de
 *    bout en bout. Le formulaire permet maintenant de choisir un ou
 *    plusieurs responsables parmi les membres du projet.
 *  - un membre peut désormais changer le statut des tâches qui lui
 *    sont assignées (avant : cette action n'existait que pour le chef).
 *  - la colonne `priorite` utilisée par ce fichier n'existait pas
 *    dans le schéma d'origine (elle a été ajoutée à la table `taches`).
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
protegerPage();

$pdo = db();
$uid = (int)$_SESSION['user_id'];
$idProjetFiltre = (int)($_GET['id_projet'] ?? 0);

/* Projets accessibles (pour les formulaires et le filtre). */
if (estChef()) {
    $stmt = $pdo->prepare('SELECT * FROM projets WHERE id_chef = ? ORDER BY nom_projet');
    $stmt->execute([$uid]);
} else {
    $stmt = $pdo->prepare(
        'SELECT DISTINCT p.* FROM projets p
         INNER JOIN projet_membres pm ON pm.id_projet = p.id_projet
         WHERE pm.id_utilisateur = ? ORDER BY p.nom_projet'
    );
    $stmt->execute([$uid]);
}
$projets = $stmt->fetchAll();
$idsProjetsAutorises = array_column($projets, 'id_projet');

/* Membres de chaque projet (pour le formulaire d'affectation, côté chef). */
$membresParProjet = [];
if (estChef() && $idsProjetsAutorises) {
    $in = implode(',', array_fill(0, count($idsProjetsAutorises), '?'));
    $stmt = $pdo->prepare(
        "SELECT pm.id_projet, u.id_utilisateur, u.nom, u.prenom
         FROM projet_membres pm INNER JOIN utilisateurs u ON u.id_utilisateur = pm.id_utilisateur
         WHERE pm.id_projet IN ($in) ORDER BY u.nom"
    );
    $stmt->execute($idsProjetsAutorises);
    foreach ($stmt->fetchAll() as $row) {
        $membresParProjet[$row['id_projet']][] = $row;
    }
}

function projetAutorise($idProjet, $idsAutorises)
{
    return in_array($idProjet, $idsAutorises, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    if (isset($_POST['ajouter_tache']) && estChef()) {
        $idProjet = (int)($_POST['id_projet'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priorite = trim($_POST['priorite'] ?? 'Moyenne');
        $statut = trim($_POST['statut'] ?? 'À faire');
        $dateEcheance = $_POST['date_echeance'] ?: null;
        $assignes = array_map('intval', $_POST['id_utilisateur'] ?? []);

        if (!projetAutorise($idProjet, $idsProjetsAutorises) || $titre === '') {
            setFlash('Le projet et le titre de la tâche sont obligatoires.', 'error');
        } else {
            $pdo->prepare(
                'INSERT INTO taches (id_projet, titre, description, id_statut, date_echeance) VALUES ( ?, ?, ?, ?, ?)'
            )->execute([$idProjet, $titre, $description ?: null, $priorite, $statut, $dateEcheance]);
            $idTache = (int)$pdo->lastInsertId();

            foreach ($assignes as $idMembre) {
                if (isset($membresParProjet[$idProjet]) && in_array($idMembre, array_column($membresParProjet[$idProjet], 'id_utilisateur'))) {
                    $pdo->prepare('INSERT IGNORE INTO affectations (id_tache, id_utilisateur) VALUES (?, ?)')->execute([$idTache, $idMembre]);
                    notifier($pdo, $idMembre, 'Une nouvelle tâche vous a été assignée : « ' . $titre . ' ».');
                }
            }
            setFlash('Tâche ajoutée avec succès.');
        }
    }

    if (isset($_POST['modifier_tache']) && estChef()) {
        $idTache = (int)($_POST['id_tache'] ?? 0);
        $idProjet = (int)($_POST['id_projet'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priorite = trim($_POST['priorite'] ?? 'Moyenne');
        $statut = trim($_POST['statut'] ?? 'À faire');
        $dateEcheance = $_POST['date_echeance'] ?: null;
        $assignes = array_map('intval', $_POST['id_utilisateur'] ?? []);

        $check = $pdo->prepare('SELECT t.id_tache FROM taches t INNER JOIN projets p ON p.id_projet = t.id_projet WHERE t.id_tache = ? AND p.id_chef = ?');
        $check->execute([$idTache, $uid]);

        if (!$check->fetch() || !projetAutorise($idProjet, $idsProjetsAutorises) || $titre === '') {
            setFlash('Tâche introuvable ou informations invalides.', 'error');
        } else {
            $pdo->prepare(
                'UPDATE taches SET id_projet=?, titre=?, description=?, priorite=?, statut=?, date_echeance=? WHERE id_tache=?'
            )->execute([$idProjet, $titre, $description ?: null, $priorite, $statut, $dateEcheance, $idTache]);

            $pdo->prepare('DELETE FROM affectations WHERE id_tache = ?')->execute([$idTache]);
            foreach ($assignes as $idMembre) {
                if (isset($membresParProjet[$idProjet]) && in_array($idMembre, array_column($membresParProjet[$idProjet], 'id_utilisateur'))) {
                    $pdo->prepare('INSERT IGNORE INTO affectations (id_tache, id_utilisateur) VALUES (?, ?)')->execute([$idTache, $idMembre]);
                }
            }
            setFlash('Tâche modifiée avec succès.');
        }
    }

    if (isset($_POST['supprimer_tache']) && estChef()) {
        $idTache = (int)($_POST['id_tache'] ?? 0);
        $check = $pdo->prepare('SELECT t.id_tache FROM taches t INNER JOIN projets p ON p.id_projet = t.id_projet WHERE t.id_tache = ? AND p.id_chef = ?');
        $check->execute([$idTache, $uid]);

        if ($check->fetch()) {
            $pdo->prepare('DELETE FROM affectations WHERE id_tache = ?')->execute([$idTache]);
            $pdo->prepare('DELETE FROM taches WHERE id_tache = ?')->execute([$idTache]);
            setFlash('Tâche supprimée avec succès.');
        } else {
            setFlash('Tâche introuvable ou accès refusé.', 'error');
        }
    }

    if (isset($_POST['changer_statut'])) {
        $idTache = (int)($_POST['id_tache'] ?? 0);
        $statut = trim($_POST['statut'] ?? 'À faire');

        if (estChef()) {
            $check = $pdo->prepare('SELECT t.id_tache FROM taches t INNER JOIN projets p ON p.id_projet = t.id_projet WHERE t.id_tache = ? AND p.id_chef = ?');
            $check->execute([$idTache, $uid]);
        } else {
            $check = $pdo->prepare('SELECT id_tache FROM affectations WHERE id_tache = ? AND id_utilisateur = ?');
            $check->execute([$idTache, $uid]);
        }

        if ($check->fetch() && in_array($statut, ['À faire', 'En cours', 'Terminé'], true)) {
            $pdo->prepare('UPDATE taches SET statut = ? WHERE id_tache = ?')->execute([$statut, $idTache]);
            setFlash('Statut mis à jour.');
        } else {
            setFlash('Action refusée.', 'error');
        }
    }

    header('Location: taches.php' . ($idProjetFiltre ? '?id_projet=' . $idProjetFiltre : ''));
    exit;
}

/* Liste des tâches visibles. */
$conditions = [];
$params = [];

if (estChef()) {
    $sql = "SELECT t.*, p.nom_projet,
                   GROUP_CONCAT(DISTINCT CONCAT(u.prenom, ' ', u.nom) SEPARATOR ', ') AS assignes,
                   GROUP_CONCAT(DISTINCT a.id_utilisateur) AS assignes_ids
            FROM taches t
            INNER JOIN projets p ON p.id_projet = t.id_projet
            LEFT JOIN affectations a ON a.id_tache = t.id_tache
            LEFT JOIN utilisateurs u ON u.id_utilisateur = a.id_utilisateur
            WHERE p.id_chef = ?";
    $params[] = $uid;
} else {
    $sql = "SELECT t.*, p.nom_projet,
                   GROUP_CONCAT(DISTINCT CONCAT(u.prenom, ' ', u.nom) SEPARATOR ', ') AS assignes,
                   GROUP_CONCAT(DISTINCT a.id_utilisateur) AS assignes_ids
            FROM taches t
            INNER JOIN projets p ON p.id_projet = t.id_projet
            INNER JOIN affectations moi ON moi.id_tache = t.id_tache AND moi.id_utilisateur = ?
            LEFT JOIN affectations a ON a.id_tache = t.id_tache
            LEFT JOIN utilisateurs u ON u.id_utilisateur = a.id_utilisateur
            WHERE 1=1";
    $params[] = $uid;
}

if ($idProjetFiltre && projetAutorise($idProjetFiltre, $idsProjetsAutorises)) {
    $sql .= ' AND t.id_projet = ?';
    $params[] = $idProjetFiltre;
}

$sql .= ' GROUP BY t.id_tache ORDER BY t.id_tache DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$taches = $stmt->fetchAll();

$pageTitle = 'Tâches';
require __DIR__ . '/../includes/header.php';
?>
<section class="content">
  <div class="page-header">
    <div>
      <span class="eyebrow">GESTION DES TÂCHES</span>
      <h1>✅ <?= estChef() ? 'Tâches' : 'Mes tâches' ?></h1>
      <p><?= estChef() ? 'Ajoutez, modifiez, affectez et suivez les tâches de vos projets.' : 'Suivez vos tâches assignées et mettez à jour leur statut.' ?></p>
    </div>
  </div>

  <?php if (estChef()): ?>
  <div class="card task-create-card">
    <h2>➕ Ajouter une tâche</h2>
    <?php if (!$projets): ?>
      <p class="hint">Créez d'abord un projet pour pouvoir y ajouter des tâches.</p>
    <?php else: ?>
    <form method="post" id="formAjoutTache">
      <?= csrfField() ?>
      <div class="project-form">
        <div>
          <label>Projet *</label>
          <select name="id_projet" id="ajout_id_projet" required onchange="majMembres('ajout')">
            <option value="">-- Sélectionner un projet --</option>
            <?php foreach ($projets as $p): ?>
              <option value="<?= (int)$p['id_projet'] ?>"><?= e($p['nom_projet']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Titre de la tâche *</label>
          <input type="text" name="titre" placeholder="Ex : Créer la page de connexion" required>
        </div>
        <div>
          <label>Priorité</label>
          <select name="priorite">
            <option>Basse</option><option selected>Moyenne</option><option>Haute</option><option>Urgente</option>
          </select>
        </div>
        <div class="full">
          <label>Description</label>
          <textarea name="description" rows="3" placeholder="Décrivez le travail à réaliser..."></textarea>
        </div>
        <div>
          <label>Date d'échéance</label>
          <input type="date" name="date_echeance">
        </div>
        <div>
          <label>Statut</label>
          <select name="statut"><option>À faire</option><option>En cours</option><option>Terminé</option></select>
        </div>
        <div class="full">
          <label>Responsable(s)</label>
          <select name="id_utilisateur[]" id="ajout_membres" multiple size="4">
            <option disabled>Choisissez d'abord un projet</option>
          </select>
          <p class="hint">Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs personnes.</p>
        </div>
      </div>
      <br>
      <button type="submit" name="ajouter_tache" class="btn btn-primary">➕ Ajouter la tâche</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="task-toolbar">
      <?php if (count($projets) > 1): ?>
        <select onchange="location.href='taches.php' + (this.value ? '?id_projet=' + this.value : '')">
          <option value="">Tous les projets</option>
          <?php foreach ($projets as $p): ?>
            <option value="<?= (int)$p['id_projet'] ?>" <?= $idProjetFiltre == $p['id_projet'] ? 'selected' : '' ?>><?= e($p['nom_projet']) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
      <div class="task-search">
        <span>🔎</span>
        <input type="text" id="searchInput" placeholder="Rechercher une tâche...">
      </div>
    </div>

    <?php if (!$taches): ?>
      <div class="empty">📂 Aucune tâche disponible.</div>
    <?php else: ?>
      <div class="task-list">
        <?php foreach ($taches as $tache): ?>
          <div class="task-item searchable">
            <div class="task-main">
              <div class="task-icon">✅</div>
              <div class="task-content">
                <h3><?= e($tache['titre']) ?></h3>
                <?php if (!empty($tache['description'])): ?><p><?= e($tache['description']) ?></p><?php endif; ?>
                <div class="task-meta">
                  <span>📁 <?= e($tache['nom_projet']) ?></span>
                  <span class="badge <?= classePriorite($tache['priorite']) ?>"><?= e($tache['priorite']) ?></span>
                  <span>📅 <?= formaterDate($tache['date_echeance']) ?></span>
                  <span>👤 <?= e($tache['assignes'] ?: 'Non affectée') ?></span>
                </div>
              </div>
            </div>
            <div class="task-right">
              <form method="post" onsubmit="this.querySelector('select').disabled=false;">
                <?= csrfField() ?>
                <input type="hidden" name="id_tache" value="<?= (int)$tache['id_tache'] ?>">
                <select name="statut" onchange="this.form.submit()">
                  <?php foreach (['À faire', 'En cours', 'Terminé'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $tache['statut'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" name="changer_statut" value="1">
              </form>
              <?php if (estChef()): ?>
                <button type="button" class="btn btn-primary" onclick='ouvrirTache(<?= json_encode($tache, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>✏️</button>
                <form method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer cette tâche ?');">
                  <?= csrfField() ?>
                  <input type="hidden" name="id_tache" value="<?= (int)$tache['id_tache'] ?>">
                  <button type="submit" name="supprimer_tache" class="btn-delete">🗑️</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if (estChef()): ?>
<div id="modalTache" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.70);z-index:3000;padding:25px;overflow:auto;">
  <div class="card" style="max-width:700px;margin:40px auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <h2>✏️ Modifier la tâche</h2>
      <button type="button" class="btn btn-secondary" onclick="fermerTache()">✕</button>
    </div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="id_tache" id="edit_id_tache">
      <label>Projet *</label>
      <select name="id_projet" id="edit_id_projet" required onchange="majMembres('edit')">
        <?php foreach ($projets as $p): ?>
          <option value="<?= (int)$p['id_projet'] ?>"><?= e($p['nom_projet']) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Titre *</label>
      <input type="text" name="titre" id="edit_titre" required>
      <label>Description</label>
      <textarea name="description" id="edit_description" rows="3"></textarea>
      <label>Priorité</label>
      <select name="priorite" id="edit_priorite">
        <option>Basse</option><option>Moyenne</option><option>Haute</option><option>Urgente</option>
      </select>
      <label>Date d'échéance</label>
      <input type="date" name="date_echeance" id="edit_date_echeance">
      <label>Statut</label>
      <select name="statut" id="edit_statut"><option>À faire</option><option>En cours</option><option>Terminé</option></select>
      <label>Responsable(s)</label>
      <select name="id_utilisateur[]" id="edit_membres" multiple size="4"></select>
      <br><br>
      <button type="submit" name="modifier_tache" class="btn btn-primary">💾 Enregistrer</button>
    </form>
  </div>
</div>

<script>
const membresParProjet = <?= json_encode($membresParProjet, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function remplirMembres(select, idProjet, selectionnes) {
  selectionnes = selectionnes || [];
  select.innerHTML = '';
  const membres = membresParProjet[idProjet] || [];
  if (!membres.length) {
    select.innerHTML = '<option disabled>Aucun membre sur ce projet</option>';
    return;
  }
  membres.forEach(function (m) {
    const opt = document.createElement('option');
    opt.value = m.id_utilisateur;
    opt.textContent = m.prenom + ' ' + m.nom;
    if (selectionnes.includes(String(m.id_utilisateur)) || selectionnes.includes(m.id_utilisateur)) opt.selected = true;
    select.appendChild(opt);
  });
}

function majMembres(prefixe) {
  const idProjet = document.getElementById(prefixe + '_id_projet').value;
  remplirMembres(document.getElementById(prefixe + '_membres'), idProjet, []);
}

function ouvrirTache(t) {
  document.getElementById('edit_id_tache').value = t.id_tache;
  document.getElementById('edit_id_projet').value = t.id_projet;
  document.getElementById('edit_titre').value = t.titre;
  document.getElementById('edit_description').value = t.description || '';
  document.getElementById('edit_priorite').value = t.priorite || 'Moyenne';
  document.getElementById('edit_date_echeance').value = t.date_echeance || '';
  document.getElementById('edit_statut').value = t.statut || 'À faire';
  const idsAssignes = (t.assignes_ids || '').split(',').filter(Boolean);
  remplirMembres(document.getElementById('edit_membres'), t.id_projet, idsAssignes);
  document.getElementById('modalTache').style.display = 'block';
}

function fermerTache() {
  document.getElementById('modalTache').style.display = 'none';
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
