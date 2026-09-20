<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

$token = trim($_GET['token'] ?? '');
$message = '';
$invitation = null;

if ($token !== '') {
    $stmt = db()->prepare(
        'SELECT i.*, e.nom_equipe
         FROM invitations i
         INNER JOIN equipes e ON e.id_equipe = i.id_equipe
         WHERE i.token = ? AND i.utilise = 0
           AND (i.date_expiration IS NULL OR i.date_expiration > NOW())
         LIMIT 1'
    );
    $stmt->execute([$token]);
    $invitation = $stmt->fetch();
}

if (!$invitation) {
    $message = 'Invitation invalide, expirée ou déjà utilisée.';
}

if ($invitation && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if ($nom === '' || $prenom === '' || $email === '' || $pass === '' || $pass !== $confirmation) {
        $message = 'Vérifiez les informations saisies (mots de passe identiques, champs remplis).';
    } elseif (strlen($pass) < 8) {
        $message = 'Le mot de passe doit contenir au moins 8 caractères.';
    } else {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch();

        if ($utilisateur) {
            $idUtilisateur = (int)$utilisateur['id_utilisateur'];
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role) VALUES (?, ?, ?, ?, 2)'
            );
            $stmt->execute([$nom, $prenom, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            $idUtilisateur = (int)$pdo->lastInsertId();
        }

        $pdo->prepare('INSERT IGNORE INTO membres_equipes (id_equipe, id_utilisateur) VALUES (?, ?)')
            ->execute([$invitation['id_equipe'], $idUtilisateur]);
        $pdo->prepare('UPDATE invitations SET utilise = 1 WHERE id_invitation = ?')
            ->execute([$invitation['id_invitation']]);

        header('Location: login.php?invitation=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invitation - GESPRO</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/auth.css')) ?>">
</head>
<body class="auth-bg">
<div class="auth-card">
  <div class="brand">GESPRO</div>
  <?php if (!$invitation): ?>
    <h1>Invitation</h1>
    <div class="message error"><?= e($message) ?></div>
    <p class="bottom"><a href="login.php">Retour à la connexion</a></p>
  <?php else: ?>
    <span class="eyebrow">INVITATION ÉQUIPE</span>
    <h1>Rejoindre <?= e($invitation['nom_equipe']) ?></h1>
    <?php if ($message): ?><div class="message error"><?= e($message) ?></div><?php endif; ?>
    <form method="post">
      <?= csrfField() ?>
      <div class="form-grid">
        <div><label>Nom</label><input name="nom" required></div>
        <div><label>Prénom</label><input name="prenom" required></div>
      </div>
      <label>Email</label>
      <input type="email" name="email" value="<?= e($invitation['email'] ?? '') ?>" required>
      <label>Mot de passe</label>
      <input type="password" name="mot_de_passe" required minlength="8">
      <label>Confirmation</label>
      <input type="password" name="confirmation" required minlength="8">
      <button class="btn btn-primary" type="submit">Rejoindre l'équipe</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
