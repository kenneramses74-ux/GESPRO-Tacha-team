```php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::connect();
} catch (Exception $e) {
    die("Erreur de connexion à la base de données.");
}

$message = "";
$type = "";
$success = false;

/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DU TOKEN
|--------------------------------------------------------------------------
*/
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if ($token === '') {
    $message = "Lien de réinitialisation invalide.";
    $type = "error";
} else {

    /*
    |--------------------------------------------------------------------------
    | RECHERCHE DU TOKEN
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT
            id_utilisateur,
            nom,
            prenom,
            email,
            reset_token,
            reset_expiration
        FROM utilisateurs
        WHERE reset_token = ?
        LIMIT 1
    ");

    $stmt->execute(array($token));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        $message = "Ce lien de réinitialisation est invalide ou a déjà été utilisé.";
        $type = "error";

    } elseif (
        empty($user['reset_expiration']) ||
        strtotime($user['reset_expiration']) <= time()
    ) {

        $message = "Ce lien de réinitialisation a expiré. Veuillez demander un nouveau lien.";
        $type = "error";

    } else {

        /*
        |--------------------------------------------------------------------------
        | TRAITEMENT DU NOUVEAU MOT DE PASSE
        |--------------------------------------------------------------------------
        */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $password = isset($_POST['password'])
                ? $_POST['password']
                : '';

            $confirmation = isset($_POST['confirmation'])
                ? $_POST['confirmation']
                : '';

            if ($password === '' || $confirmation === '') {

                $message = "Veuillez remplir tous les champs.";
                $type = "error";

            } elseif (strlen($password) < 8) {

                $message = "Le mot de passe doit contenir au moins 8 caractères.";
                $type = "error";

            } elseif ($password !== $confirmation) {

                $message = "Les deux mots de passe ne correspondent pas.";
                $type = "error";

            } else {

                /*
                |--------------------------------------------------------------------------
                | HASH DU MOT DE PASSE
                |--------------------------------------------------------------------------
                */
                $hash = password_hash($password, PASSWORD_DEFAULT);

                /*
                |--------------------------------------------------------------------------
                | MISE À JOUR DU COMPTE
                |--------------------------------------------------------------------------
                */
                $stmt = $pdo->prepare("
                    UPDATE utilisateurs
                    SET
                        mot_de_passe = ?,
                        reset_token = NULL,
                        reset_expiration = NULL
                    WHERE id_utilisateur = ?
                ");

                $stmt->execute(array(
                    $hash,
                    $user['id_utilisateur']
                ));

                /*
                |--------------------------------------------------------------------------
                | REDIRECTION VERS LOGIN
                |--------------------------------------------------------------------------
                */
                header("Location: login.php?reset=success");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Réinitialiser le mot de passe - GESPRO</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #ffffff;

            background:
                radial-gradient(
                    circle at 20% 20%,
                    rgba(0, 153, 255, 0.30),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 80% 80%,
                    rgba(153, 51, 255, 0.30),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #050816,
                    #101735,
                    #180d35,
                    #07152c
                );

            background-size: 200% 200%;

            animation: backgroundMove 15s ease infinite;
        }

        /*
        |--------------------------------------------------------------------------
        | FOND ANIMÉ
        |--------------------------------------------------------------------------
        */

        @keyframes backgroundMove {

            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BULLES LUMINEUSES
        |--------------------------------------------------------------------------
        */

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(5px);
            pointer-events: none;
            opacity: 0.55;
        }

        .orb-one {
            width: 280px;
            height: 280px;

            background: rgba(0, 183, 255, 0.35);

            top: -100px;
            left: -80px;

            animation: orbOne 8s ease-in-out infinite;
        }

        .orb-two {
            width: 320px;
            height: 320px;

            background: rgba(180, 0, 255, 0.30);

            right: -120px;
            bottom: -100px;

            animation: orbTwo 10s ease-in-out infinite;
        }

        .orb-three {
            width: 160px;
            height: 160px;

            background: rgba(0, 255, 204, 0.20);

            top: 45%;
            left: 8%;

            animation: orbThree 7s ease-in-out infinite;
        }

        @keyframes orbOne {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(80px, 70px) scale(1.2);
            }
        }

        @keyframes orbTwo {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(-80px, -70px) scale(1.15);
            }
        }

        @keyframes orbThree {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-80px);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PARTICULES
        |--------------------------------------------------------------------------
        */

        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;

            background: rgba(255, 255, 255, 0.7);

            border-radius: 50%;

            animation: floatParticle linear infinite;
        }

        .particle:nth-child(1) {
            left: 10%;
            animation-duration: 9s;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            left: 25%;
            animation-duration: 13s;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            left: 40%;
            animation-duration: 10s;
            animation-delay: 4s;
        }

        .particle:nth-child(4) {
            left: 55%;
            animation-duration: 14s;
            animation-delay: 1s;
        }

        .particle:nth-child(5) {
            left: 70%;
            animation-duration: 11s;
            animation-delay: 3s;
        }

        .particle:nth-child(6) {
            left: 85%;
            animation-duration: 15s;
            animation-delay: 5s;
        }

        @keyframes floatParticle {

            0% {
                bottom: -10px;
                opacity: 0;
                transform: translateX(0);
            }

            20% {
                opacity: 1;
            }

            80% {
                opacity: 1;
            }

            100% {
                bottom: 110%;
                opacity: 0;
                transform: translateX(80px);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CONTENEUR
        |--------------------------------------------------------------------------
        */

        .container {
            position: relative;
            z-index: 10;

            width: 100%;
            max-width: 480px;

            padding: 20px;

            animation: cardAppear 0.9s ease;
        }

        @keyframes cardAppear {

            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CARTE
        |--------------------------------------------------------------------------
        */

        .card {
            position: relative;

            padding: 40px 35px;

            border-radius: 25px;

            background: rgba(10, 15, 40, 0.78);

            border: 1px solid rgba(255, 255, 255, 0.12);

            box-shadow:
                0 30px 80px rgba(0, 0, 0, 0.55),
                0 0 40px rgba(0, 153, 255, 0.08);

            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);

            overflow: hidden;
        }

        .card::before {
            content: "";

            position: absolute;

            top: 0;
            left: -100%;

            width: 100%;
            height: 2px;

            background: linear-gradient(
                90deg,
                transparent,
                #00c6ff,
                #9c27ff,
                transparent
            );

            animation: lineMove 4s linear infinite;
        }

        @keyframes lineMove {

            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | LOGO
        |--------------------------------------------------------------------------
        */

        .logo {
            width: 75px;
            height: 75px;

            margin: 0 auto 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 22px;

            font-size: 28px;
            font-weight: bold;

            background: linear-gradient(
                135deg,
                #00c6ff,
                #0072ff,
                #9c27ff
            );

            box-shadow:
                0 0 25px rgba(0, 174, 255, 0.45);

            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-8px) rotate(3deg);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TITRE
        |--------------------------------------------------------------------------
        */

        h1 {
            text-align: center;

            font-size: 28px;

            margin-bottom: 10px;

            background: linear-gradient(
                90deg,
                #ffffff,
                #6fd8ff,
                #c084fc,
                #ffffff
            );

            background-size: 300% auto;

            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;

            animation: titleMove 5s linear infinite;
        }

        @keyframes titleMove {

            to {
                background-position: 300% center;
            }
        }

        .subtitle {
            text-align: center;

            color: #aeb9d6;

            font-size: 14px;

            line-height: 1.6;

            margin-bottom: 25px;
        }

        /*
        |--------------------------------------------------------------------------
        | MESSAGE
        |--------------------------------------------------------------------------
        */

        .message {
            padding: 13px 15px;

            border-radius: 12px;

            margin-bottom: 20px;

            font-size: 14px;

            line-height: 1.5;

            text-align: center;
        }

        .message.error {
            background: rgba(255, 60, 90, 0.12);

            border: 1px solid rgba(255, 60, 90, 0.35);

            color: #ff9aaa;
        }

        /*
        |--------------------------------------------------------------------------
        | CHAMPS
        |--------------------------------------------------------------------------
        */

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;

            margin-bottom: 8px;

            color: #dce6ff;

            font-size: 14px;

            font-weight: 600;
        }

        .input-wrapper {
            position: relative;
        }

        input {
            width: 100%;

            padding: 15px 48px 15px 16px;

            border-radius: 13px;

            border: 1px solid rgba(255, 255, 255, 0.12);

            outline: none;

            color: #ffffff;

            background: rgba(255, 255, 255, 0.06);

            font-size: 15px;

            transition: 0.3s ease;
        }

        input::placeholder {
            color: #7884a5;
        }

        input:focus {
            border-color: #00bfff;

            background: rgba(0, 191, 255, 0.08);

            box-shadow:
                0 0 0 3px rgba(0, 191, 255, 0.08),
                0 0 20px rgba(0, 191, 255, 0.12);
        }

        /*
        |--------------------------------------------------------------------------
        | BOUTON AFFICHER MOT DE PASSE
        |--------------------------------------------------------------------------
        */

        .show-password {
            position: absolute;

            right: 12px;
            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            color: #7fdcff;

            cursor: pointer;

            font-size: 18px;

            padding: 5px;
        }

        /*
        |--------------------------------------------------------------------------
        | BOUTON
        |--------------------------------------------------------------------------
        */

        .btn {
            position: relative;

            width: 100%;

            border: none;

            border-radius: 13px;

            padding: 15px;

            color: #ffffff;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;

            overflow: hidden;

            background:
                linear-gradient(
                    90deg,
                    #0072ff,
                    #00c6ff,
                    #9c27ff,
                    #0072ff
                );

            background-size: 300% auto;

            box-shadow:
                0 10px 25px rgba(0, 114, 255, 0.25);

            transition: 0.3s ease;

            animation: buttonGradient 5s linear infinite;
        }

        @keyframes buttonGradient {

            to {
                background-position: 300% center;
            }
        }

        .btn:hover {
            transform: translateY(-3px);

            box-shadow:
                0 15px 35px rgba(0, 174, 255, 0.30);
        }

        .btn:active {
            transform: scale(0.98);
        }

        /*
        |--------------------------------------------------------------------------
        | LIEN RETOUR
        |--------------------------------------------------------------------------
        */

        .back {
            display: block;

            text-align: center;

            margin-top: 22px;

            color: #8edcff;

            text-decoration: none;

            font-size: 14px;

            transition: 0.3s ease;
        }

        .back:hover {
            color: #ffffff;

            transform: translateY(-2px);
        }

        /*
        |--------------------------------------------------------------------------
        | SÉCURITÉ
        |--------------------------------------------------------------------------
        */

        .security {
            margin-top: 22px;

            padding-top: 18px;

            border-top: 1px solid rgba(255, 255, 255, 0.08);

            text-align: center;

            color: #687492;

            font-size: 11px;

            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 500px) {

            body {
                overflow-y: auto;
            }

            .container {
                padding: 15px;
            }

            .card {
                padding: 30px 22px;

                border-radius: 20px;
            }

            h1 {
                font-size: 24px;
            }

            .logo {
                width: 65px;
                height: 65px;

                font-size: 24px;
            }
        }

    </style>
</head>

<body>

    <!-- BULLES -->
    <div class="orb orb-one"></div>
    <div class="orb orb-two"></div>
    <div class="orb orb-three"></div>

    <!-- PARTICULES -->
    <div class="particles">

        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>

    </div>

    <div class="container">

        <div class="card">

            <div class="logo">
                GP
            </div>

            <h1>
                Nouveau mot de passe
            </h1>

            <p class="subtitle">
                Créez un nouveau mot de passe sécurisé
                pour récupérer l'accès à votre compte GESPRO.
            </p>

            <?php if ($message !== ''): ?>

                <div class="message <?php echo htmlspecialchars($type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>

            <?php if ($user && $type !== 'error'): ?>

                <form method="POST" action="">

                    <div class="form-group">

                        <label for="password">
                            Nouveau mot de passe
                        </label>

                        <div class="input-wrapper">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Minimum 8 caractères"
                                minlength="8"
                                required
                                autocomplete="new-password"
                            >

                            <button
                                type="button"
                                class="show-password"
                                onclick="togglePassword('password', this)"
                                aria-label="Afficher le mot de passe"
                            >
                                👁
                            </button>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="confirmation">
                            Confirmer le mot de passe
                        </label>

                        <div class="input-wrapper">

                            <input
                                type="password"
                                id="confirmation"
                                name="confirmation"
                                placeholder="Confirmez votre mot de passe"
                                minlength="8"
                                required
                                autocomplete="new-password"
                            >

                            <button
                                type="button"
                                class="show-password"
                                onclick="togglePassword('confirmation', this)"
                                aria-label="Afficher le mot de passe"
                            >
                                👁
                            </button>

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="btn"
                    >
                        🔐 Réinitialiser mon mot de passe
                    </button>

                </form>

            <?php endif; ?>


            <a
                href="login.php"
                class="back"
            >
                ← Retour à la connexion
            </a>


            <div class="security">

                🔒 Votre nouveau mot de passe est
                enregistré de manière sécurisée.

            </div>

        </div>

    </div>


    <script>

        function togglePassword(id, button) {

            var input = document.getElementById(id);

            if (input.type === "password") {

                input.type = "text";

                button.textContent = "🙈";

            } else {

                input.type = "password";

                button.textContent = "👁";

            }
        }

    </script>

</body>
</html>
