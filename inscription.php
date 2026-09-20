<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
redirigerSiConnecte();

$message = '';
$type = '';
$valeurs = ['nom' => '', 'prenom' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $valeurs['nom'] = trim($_POST['nom'] ?? '');
    $valeurs['prenom'] = trim($_POST['prenom'] ?? '');
    $valeurs['email'] = trim($_POST['email'] ?? '');
    $pass = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    $role = (int)($_POST['id_role'] ?? 2);
    if (!in_array($role, [1, 2], true)) {
        $role = 2;
    }

    if ($valeurs['nom'] === '' || $valeurs['prenom'] === '' || $valeurs['email'] === '' || $pass === '' || $confirmation === '') {
        $message = 'Veuillez remplir tous les champs.';
        $type = 'error';
    } elseif (!filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)) {
        $message = 'Email invalide.';
        $type = 'error';
    } elseif ($pass !== $confirmation) {
        $message = 'Les mots de passe ne correspondent pas.';
        $type = 'error';
    } elseif (strlen($pass) < 8) {
        $message = 'Le mot de passe doit contenir au moins 8 caractères.';
        $type = 'error';
    } else {
        try {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE email = ? LIMIT 1');
            $stmt->execute([$valeurs['email']]);

            if ($stmt->fetch()) {
                $message = 'Cette adresse email est déjà utilisée.';
                $type = 'error';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $valeurs['nom'],
                    $valeurs['prenom'],
                    $valeurs['email'],
                    password_hash($pass, PASSWORD_DEFAULT),
                    $role,
                ]);
                header('Location: login.php?inscription=1');
                exit;
            }
        } catch (PDOException $e) {
            $message = (GESPRO_ENV === 'dev')
                ? 'Erreur base de données : ' . $e->getMessage()
                : 'Une erreur est survenue. Merci de réessayer.';
            $type = 'error';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Inscription - GESPRO</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/auth.css')) ?>">
</head>
<body class="auth-bg">
<div class="auth-card">
  <div class="brand">GESPRO</div>
  <span class="eyebrow">NOUVEAU COMPTE</span>
  <h1>Créer votre compte</h1>
  <?php if ($message): ?><div class="message <?= e($type) ?>"><?= e($message) ?></div><?php endif; ?>
  <form method="post">
    <?= csrfField() ?>
    <div class="form-grid">
      <div><label>Nom</label><input name="nom" value="<?= e($valeurs['nom']) ?>" required></div>
      <div><label>Prénom</label><input name="prenom" value="<?= e($valeurs['prenom']) ?>" required></div>
    </div>
    <label>Email</label>
    <input type="email" name="email" value="<?= e($valeurs['email']) ?>" required>
    <label>Type de compte</label>
    <select name="id_role">
      <option value="2">Utilisateur / membre</option>
      <option value="1">Chef de projet</option>
    </select>
    <div class="hint">Un membre ne peut pas ajouter d’autres membres. Seul le chef gère son équipe.</div>
    <label>Mot de passe</label>
    <input type="password" name="mot_de_passe" required minlength="8">
    <label>Confirmation</label>
    <input type="password" name="confirmation" required minlength="8">
    <button class="btn btn-primary" type="submit">Créer mon compte</button>
  </form>
  <p class="bottom">Déjà inscrit ? <a href="login.php">Se connecter</a></p>
</div>
</body>
</html>
