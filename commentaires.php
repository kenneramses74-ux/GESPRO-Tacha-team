<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
protegerPage();

$pdo = db();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();
    $idProjet = (int)($_POST['id_projet'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    $check = $pdo->prepare('SELECT 1 FROM projet_membres WHERE id_projet = ? AND id_utilisateur = ?');
    $check->execute([$idProjet, $uid]);
    $autorise = (bool)$check->fetch();

    if (!$autorise && estChef()) {
        $check = $pdo->prepare('SELECT 1 FROM projets WHERE id_projet = ? AND id_chef = ?');
        $check->execute([$idProjet, $uid]);
        $autorise = (bool)$check->fetch();
    }

    if ($autorise && $contenu !== '') {
        $pdo->prepare('INSERT INTO commentaires (contenu, id_tache, id_utilisateur) VALUES (?, ?, ?)')
            ->execute([$contenu, $idProjet, $uid]);
        setFlash('Commentaire publié.');
    } else {
        setFlash('Impossible de publier ce commentaire.', 'error');
    }

    header('Location: commentaires.php');
    exit;
}

if (estChef()) {
    $stmt = $pdo->prepare('SELECT id_projet, nom_projet FROM projets WHERE id_chef = ? ORDER BY nom_projet');
} else {
    $stmt = $pdo->prepare(
        'SELECT p.id_projet, p.nom_projet FROM projet_membres pm
         INNER JOIN projets p ON p.id_projet = pm.id_projet WHERE pm.id_utilisateur = ? ORDER BY p.nom_projet'
    );
}
$stmt->execute([$uid]);
$projets = $stmt->fetchAll();
$idsProjets = array_column($projets, 'id_projet');

$comments = [];
if ($idsProjets) {
    $in = implode(',', array_fill(0, count($idsProjets), '?'));
    $stmt = $pdo->prepare(
      "SELECT c.*,  u.prenom, u.nom
         FROM commentaires c
         INNER JOIN taches t ON t.id_projet = t.id_tache
         INNER JOIN utilisateurs u ON u.id_utilisateur = c.id_utilisateur
         WHERE t.id_projet = ?"
     
    );
    $stmt->execute($idsProjets);
    $comments = $stmt->fetchAll();
}

$pageTitle = 'Commentaires';
require __DIR__ . '/../includes/header.php';
?>
<section class="content">
  <div class="page-header">
    <div>
      <span class="eyebrow">COLLABORATION</span>
      <h1>💬 Commentaires</h1>
      <p>Échangez avec les membres de vos projets.</p>
    </div>
  </div>

  <div class="card">
    <?php if (!$projets): ?>
      <p class="hint">Vous devez appartenir à un projet pour publier un commentaire.</p>
    <?php else: ?>
      <form method="post">
        <?= csrfField() ?>
        <label>Projet</label>
        <select name="id_projet" required>
          <option value="">Choisir</option>
          <?php foreach ($projets as $p): ?>
            <option value="<?= (int)$p['id_projet'] ?>"><?= e($p['nom_projet']) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Message</label>
        <textarea name="contenu" rows="4" required></textarea>
        <br><br>
        <button class="btn btn-primary" type="submit">Publier</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$comments): ?>
    <div class="card empty">Aucun commentaire pour le moment.</div>
  <?php else: ?>
    <?php foreach ($comments as $c): ?>
      <div class="card comment">
        <strong><?= e($c['prenom'] . ' ' . $c['nom']) ?></strong>
        <small><?= e($c['nom_projet']) ?> · <?= e(date('d/m/Y à H:i', strtotime($c['date_creation']))) ?></small>
        <p><?= nl2br(e($c['contenu'])) ?></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
