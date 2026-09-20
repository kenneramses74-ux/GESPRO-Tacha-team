
<?php

/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| Si l'utilisateur est déjà connecté
|--------------------------------------------------------------------------
*/

redirigerSiConnecte();


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = '';
$type = '';


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

if (isset($_GET['inscription'])) {

    $message = 'Compte créé avec succès. Vous pouvez vous connecter.';
    $type = 'success';

}

if (isset($_GET['invitation'])) {

    $message = 'Bienvenue dans l’équipe ! Connectez-vous pour commencer.';
    $type = 'success';

}

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {

    $message = 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.';
    $type = 'success';

}


/*
|--------------------------------------------------------------------------
| LIMITATION DES TENTATIVES
|--------------------------------------------------------------------------
| 5 tentatives maximum pendant 60 secondes.
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['login_attempts'])) {

    $_SESSION['login_attempts'] = array(
        'count' => 0,
        'first' => time()
    );

}


/*
|--------------------------------------------------------------------------
| Réinitialisation du compteur après 60 secondes
|--------------------------------------------------------------------------
*/

if (
    time() - $_SESSION['login_attempts']['first'] >= 60
) {

    $_SESSION['login_attempts'] = array(
        'count' => 0,
        'first' => time()
    );

}


$bloque = false;


if (
    $_SESSION['login_attempts']['count'] >= 5 &&
    (time() - $_SESSION['login_attempts']['first']) < 60
) {

    $bloque = true;

}


/*
|--------------------------------------------------------------------------
| TRAITEMENT DU FORMULAIRE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !$bloque
) {

    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    verifierCsrf();


    /*
    |--------------------------------------------------------------------------
    | Récupération des données
    |--------------------------------------------------------------------------
    */

    $email = isset($_POST['email'])
        ? trim($_POST['email'])
        : '';

    $pass = isset($_POST['mot_de_passe'])
        ? $_POST['mot_de_passe']
        : '';


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($email === '' || $pass === '') {

        $message = 'Veuillez remplir tous les champs.';
        $type = 'error';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = 'Veuillez saisir une adresse e-mail valide.';
        $type = 'error';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Connexion à la base
            |--------------------------------------------------------------------------
            */

            $pdo = Database::connect();


            /*
            |--------------------------------------------------------------------------
            | Recherche de l'utilisateur
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                'SELECT
                    u.*,
                    r.libelle_role
                 FROM utilisateurs u
                 LEFT JOIN roles r
                    ON r.id_role = u.id_role
                 WHERE u.email = ?
                 LIMIT 1'
            );

            $stmt->execute(array($email));

            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);


            /*
            |--------------------------------------------------------------------------
            | Vérification du mot de passe
            |--------------------------------------------------------------------------
            */

            if (
                $utilisateur &&
                password_verify(
                    $pass,
                    $utilisateur['mot_de_passe']
                )
            ) {

                /*
                |--------------------------------------------------------------------------
                | Nouvelle session
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);


                /*
                |--------------------------------------------------------------------------
                | Informations utilisateur
                |--------------------------------------------------------------------------
                */

                $_SESSION['user_id'] =
                    $utilisateur['id_utilisateur'];

                $_SESSION['nom'] =
                    $utilisateur['nom'];

                $_SESSION['prenom'] =
                    $utilisateur['prenom'];

                $_SESSION['email'] =
                    $utilisateur['email'];

                $_SESSION['role'] =
                    !empty($utilisateur['libelle_role'])
                        ? $utilisateur['libelle_role']
                        : 'Utilisateur';


                /*
                |--------------------------------------------------------------------------
                | Nettoyage du compteur
                |--------------------------------------------------------------------------
                */

                unset($_SESSION['login_attempts']);


                /*
                |--------------------------------------------------------------------------
                | PAGE D'ACCUEIL INTERMÉDIAIRE
                |--------------------------------------------------------------------------
                |
                | Le dashboard ne sera PAS ouvert directement.
                |
                | Login
                |   ↓
                | Accueil.php
                |   ↓
                | Bouton "Accéder à mon espace"
                |   ↓
                | Dashboard selon le rôle
                |
                |--------------------------------------------------------------------------
                */

                header('Location: ../Accueil.php');
                exit;

            }


            /*
            |--------------------------------------------------------------------------
            | Identifiants incorrects
            |--------------------------------------------------------------------------
            */

            $_SESSION['login_attempts']['count']++;

            $message = 'Email ou mot de passe incorrect.';
            $type = 'error';


        } catch (PDOException $e) {

            if (
                defined('GESPRO_ENV') &&
                GESPRO_ENV === 'dev'
            ) {

                $message =
                    'Erreur base de données : ' .
                    $e->getMessage();

            } else {

                $message =
                    'Une erreur est survenue. Merci de réessayer.';

            }

            $type = 'error';
        }
    }

} elseif ($bloque) {

    $message =
        'Trop de tentatives. Merci de réessayer dans une minute.';

    $type = 'error';

}

?>

<!doctype html>

<html lang="fr">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Connexion - GESPRO</title>


    <link
        rel="stylesheet"
        href="<?= e(url('/assets/css/auth.css')) ?>"
    >

</head>


<body class="auth-bg">


    <!-- =====================================================
         PARTICULES
    ====================================================== -->

    <div class="particles">

        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>

    </div>


    <!-- =====================================================
         CARTE DE CONNEXION
    ====================================================== -->

    <div class="auth-card">


        <!-- LOGO -->

        <div class="brand">
            GESPRO
        </div>


        <span class="eyebrow">
            ESPACE PROFESSIONNEL
        </span>


        <h1>
            Connexion
        </h1>


        <p class="auth-description">
            Connectez-vous à votre espace de gestion
            des projets et des tâches.
        </p>


        <!-- MESSAGE -->

        <?php if ($message): ?>

            <div class="message <?= e($type) ?>">

                <?= e($message) ?>

            </div>

        <?php endif; ?>


        <!-- FORMULAIRE -->

        <form method="post">

            <?= csrfField() ?>


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    Adresse e-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="exemple@email.com"
                    value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>"
                    required
                    autofocus
                    autocomplete="email"
                >

            </div>


            <!-- MOT DE PASSE -->

            <div class="form-group">

                <div class="password-label">

                    <label for="mot_de_passe">
                        Mot de passe
                    </label>


                </div>


                <div class="password-wrapper">

                    <input
                        type="password"
                        id="mot_de_passe"
                        name="mot_de_passe"
                        placeholder="Votre mot de passe"
                        required
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="show-password"
                        onclick="togglePassword()"
                        aria-label="Afficher le mot de passe"
                    >
                        👁
                    </button>

                </div>

            </div>


            <!-- BOUTON -->

            <button
                class="btn btn-primary"
                type="submit"
            >

                <span>
                    Se connecter
                </span>

                <span>
                    →
                </span>

            </button>
                    <a href="mot_de_passe_oublie.php">
                        Mot de passe oublié ?
                    </a>


        </form>


        <!-- INSCRIPTION -->

        <p class="bottom">

            Pas encore de compte ?

            <a href="inscription.php">
                Créer un compte
            </a>

        </p>


        <!-- SÉCURITÉ -->

       
       
        <div class="security">

            🔒 Connexion sécurisée

        </div>


    </div>


    <!-- =====================================================
         JAVASCRIPT
    ====================================================== -->

    <script>

        function togglePassword() {

            var input =
                document.getElementById('mot_de_passe');

            var button =
                document.querySelector('.show-password');


            if (input.type === 'password') {

                input.type = 'text';

                button.textContent = '🙈';

            } else {

                input.type = 'password';

                button.textContent = '👁';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Animation du bouton pendant la connexion
        |--------------------------------------------------------------------------
        */

        var form =
            document.querySelector('form');

        if (form) {

            form.addEventListener('submit', function() {

                var button =
                    form.querySelector('button[type="submit"]');

                if (button) {

                    button.innerHTML =
                        '<span>Connexion...</span> <span>⏳</span>';

                    button.style.opacity = '0.8';

                }

            });

        }

    </script>


</body>

</html>
```
