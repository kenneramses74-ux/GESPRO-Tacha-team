<?php
/**
 * Paramètres du compte.
 * Avant : page vide ("pourront être étendus avec..."). Elle permet
 * maintenant réellement de modifier son identité et son mot de passe.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
protegerPage();

$pdo = db();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    if (isset($_POST['modifier_identite'])) {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nom === '' || $prenom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('Veuillez renseigner un nom, un prénom et un email valides.', 'error');
        } else {
            $check = $pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE email = ? AND id_utilisateur <> ?');
            $check->execute([$email, $uid]);
            if ($check->fetch()) {
                setFlash('Cette adresse email est déjà utilisée par un autre compte.', 'error');
            } else {
                $pdo->prepare('UPDATE utilisateurs SET nom=?, prenom=?, email=? WHERE id_utilisateur=?')
                    ->execute([$nom, $prenom, $email, $uid]);
                $_SESSION['nom'] = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['email'] = $email;
                setFlash('Informations mises à jour.');
            }
        }
    }

    if (isset($_POST['modifier_mot_de_passe'])) {
        $actuel = $_POST['mot_de_passe_actuel'] ?? '';
        $nouveau = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id_utilisateur = ?');
        $stmt->execute([$uid]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($actuel, $hash)) {
            setFlash('Mot de passe actuel incorrect.', 'error');
        } elseif (strlen($nouveau) < 8) {
            setFlash('Le nouveau mot de passe doit contenir au moins 8 caractères.', 'error');
        } elseif ($nouveau !== $confirmation) {
            setFlash('Les mots de passe ne correspondent pas.', 'error');
        } else {
            $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?')
                ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $uid]);
            setFlash('Mot de passe modifié avec succès.');
        }
    }

    header('Location: parametres.php');
    exit;
}

$stmt = $pdo->prepare('SELECT nom, prenom, email FROM utilisateurs WHERE id_utilisateur = ?');
$stmt->execute([$uid]);
$moi = $stmt->fetch();

$pageTitle = 'Paramètres';
require __DIR__ . '/../includes/header.php';
?>
<section class="content">
  <div class="page-header">
    <div><span class="eyebrow">COMPTE</span><h1>⚙️ Paramètres</h1></div>
  </div>

  <div class="card">
    <h2>Informations personnelles</h2>
    <form method="post">
      <?= csrfField() ?>
      <div class="form-grid">
        <div><label>Nom</label><input name="nom" value="<?= e($moi['nom']) ?>" required></div>
        <div><label>Prénom</label><input name="prenom" value="<?= e($moi['prenom']) ?>" required></div>
      </div>
      <label>Email</label>
      <input type="email" name="email" value="<?= e($moi['email']) ?>" required>
      <br><br>
      <button type="submit" name="modifier_identite" class="btn btn-primary">💾 Enregistrer</button>
    </form>
  </div>

  <div class="card">
    <h2>Changer de mot de passe</h2>
    <form method="post">
      <?= csrfField() ?>
      <label>Mot de passe actuel</label>
      <input type="password" name="mot_de_passe_actuel" required>
      <label>Nouveau mot de passe</label>
      <input type="password" name="nouveau_mot_de_passe" required minlength="8">
      <label>Confirmation</label>
      <input type="password" name="confirmation" required minlength="8">
      <br><br>
      <button type="submit" name="modifier_mot_de_passe" class="btn btn-primary">🔒 Changer le mot de passe</button>
    </form>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
