```php
<?php
/**
 * GESPRO
 * Mot de passe oublié
 * PHP 7.3 compatible
 */

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
$lien = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = isset($_POST['email'])
        ? trim($_POST['email'])
        : '';

    if ($email === '') {

        $message = "Veuillez saisir votre adresse e-mail.";
        $type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Veuillez saisir une adresse e-mail valide.";
        $type = "error";

    } else {

        $stmt = $pdo->prepare("
            SELECT id_utilisateur, nom, prenom, email
            FROM utilisateurs
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute(array($email));

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            /*
             * Génération d'un token sécurisé
             */
            $token = bin2hex(random_bytes(32));

            /*
             * Token valable 30 minutes
             */
            $expiration = date(
                'Y-m-d H:i:s',
                time() + (30 * 60)
            );

            /*
             * Enregistrement du token
             */
            $stmt = $pdo->prepare("
                UPDATE utilisateurs
                SET
                    reset_token = ?,
                    reset_expiration = ?
                WHERE id_utilisateur = ?
            ");

            $stmt->execute(array(
                $token,
                $expiration,
                $user['id_utilisateur']
            ));

            /*
             * Lien local de réinitialisation
             */
            $lien =
                "http://localhost/codes/GESPRO/auth/reinitialiser_mot_de_passe.php?token="
                . urlencode($token);

            $message =
                "Votre lien de réinitialisation est prêt.";

            $type = "success";

        } else {

            $message =
                "Aucun compte ne correspond à cette adresse e-mail.";

            $type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Mot de passe oublié | GESPRO</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =====================================================
   BODY
===================================================== */

body {

    min-height: 100vh;

    font-family:
        "Segoe UI",
        Arial,
        Helvetica,
        sans-serif;

    display: flex;

    justify-content: center;

    align-items: center;

    overflow: hidden;

    background:
        linear-gradient(
            125deg,
            #020617,
            #0f172a,
            #172554,
            #312e81,
            #111827
        );

    background-size: 500% 500%;

    animation:
        gradientMove 15s ease infinite;

    position: relative;
}


/* =====================================================
   ANIMATION DU FOND
===================================================== */

@keyframes gradientMove {

    0% {
        background-position: 0% 50%;
    }

    25% {
        background-position: 50% 100%;
    }

    50% {
        background-position: 100% 50%;
    }

    75% {
        background-position: 50% 0%;
    }

    100% {
        background-position: 0% 50%;
    }
}


/* =====================================================
   CERCLES LUMINEUX
===================================================== */

.orb {

    position: absolute;

    border-radius: 50%;

    filter: blur(2px);

    opacity: .55;

    pointer-events: none;
}


.orb-1 {

    width: 300px;
    height: 300px;

    top: -120px;
    left: -100px;

    background:
        radial-gradient(
            circle,
            #00d9ff,
            transparent 70%
        );

    animation:
        orbOne 9s ease-in-out infinite alternate;
}


.orb-2 {

    width: 350px;
    height: 350px;

    right: -120px;
    bottom: -150px;

    background:
        radial-gradient(
            circle,
            #8b5cf6,
            transparent 70%
        );

    animation:
        orbTwo 11s ease-in-out infinite alternate;
}


.orb-3 {

    width: 180px;
    height: 180px;

    left: 15%;
    bottom: 5%;

    background:
        radial-gradient(
            circle,
            #2563eb,
            transparent 70%
        );

    animation:
        orbThree 7s ease-in-out infinite alternate;
}


@keyframes orbOne {

    from {
        transform:
            translate(0, 0)
            scale(1);
    }

    to {
        transform:
            translate(120px, 100px)
            scale(1.3);
    }
}


@keyframes orbTwo {

    from {
        transform:
            translate(0, 0)
            scale(1);
    }

    to {
        transform:
            translate(-100px, -120px)
            scale(1.25);
    }
}


@keyframes orbThree {

    from {
        transform:
            translate(0, 0)
            scale(1);
    }

    to {
        transform:
            translate(80px, -100px)
            scale(1.4);
    }
}


/* =====================================================
   PARTICULES
===================================================== */

.particles {

    position: absolute;

    inset: 0;

    overflow: hidden;

    pointer-events: none;
}


.particles span {

    position: absolute;

    width: 5px;
    height: 5px;

    background: #38bdf8;

    border-radius: 50%;

    opacity: .5;

    animation:
        particleMove
        linear
        infinite;
}


.particles span:nth-child(1) {
    left: 10%;
    animation-duration: 12s;
    animation-delay: 1s;
}

.particles span:nth-child(2) {
    left: 20%;
    animation-duration: 17s;
    animation-delay: 3s;
}

.particles span:nth-child(3) {
    left: 35%;
    animation-duration: 14s;
    animation-delay: 2s;
}

.particles span:nth-child(4) {
    left: 50%;
    animation-duration: 19s;
    animation-delay: 5s;
}

.particles span:nth-child(5) {
    left: 65%;
    animation-duration: 13s;
    animation-delay: 1s;
}

.particles span:nth-child(6) {
    left: 80%;
    animation-duration: 16s;
    animation-delay: 4s;
}

.particles span:nth-child(7) {
    left: 90%;
    animation-duration: 20s;
    animation-delay: 2s;
}


@keyframes particleMove {

    0% {

        bottom: -20px;

        transform:
            translateX(0)
            scale(.5);

        opacity: 0;
    }

    20% {
        opacity: .7;
    }

    80% {
        opacity: .7;
    }

    100% {

        bottom: 110%;

        transform:
            translateX(100px)
            scale(1.4);

        opacity: 0;
    }
}


/* =====================================================
   CONTAINER
===================================================== */

.container {

    width: 100%;

    max-width: 480px;

    padding: 20px;

    position: relative;

    z-index: 10;

    animation:
        cardAppear .8s ease;
}


@keyframes cardAppear {

    from {

        opacity: 0;

        transform:
            translateY(40px)
            scale(.95);
    }

    to {

        opacity: 1;

        transform:
            translateY(0)
            scale(1);
    }
}


/* =====================================================
   CARD
===================================================== */

.card {

    position: relative;

    padding: 40px;

    border-radius: 28px;

    background:
        rgba(15, 23, 42, .78);

    border:
        1px solid
        rgba(255,255,255,.16);

    box-shadow:

        0 30px 80px
        rgba(0,0,0,.55),

        inset 0 1px 0
        rgba(255,255,255,.08);

    backdrop-filter:
        blur(25px);

    -webkit-backdrop-filter:
        blur(25px);

    overflow: hidden;
}


/* ligne lumineuse */

.card::before {

    content: "";

    position: absolute;

    top: 0;
    left: -100%;

    width: 100%;
    height: 2px;

    background:
        linear-gradient(
            90deg,
            transparent,
            #00d9ff,
            #8b5cf6,
            transparent
        );

    animation:
        lightLine 4s linear infinite;
}


@keyframes lightLine {

    0% {
        left: -100%;
    }

    100% {
        left: 100%;
    }
}


/* =====================================================
   LOGO
===================================================== */

.logo {

    width: 82px;
    height: 82px;

    margin:
        0 auto 22px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 24px;

    color: white;

    font-size: 25px;

    font-weight: 900;

    background:
        linear-gradient(
            135deg,
            #00d9ff,
            #2563eb,
            #7c3aed
        );

    box-shadow:

        0 0 25px
        rgba(0,217,255,.35),

        0 15px 35px
        rgba(37,99,235,.30);

    animation:
        logoFloat 4s ease-in-out infinite;
}


@keyframes logoFloat {

    0%,100% {
        transform:
            translateY(0)
            rotate(0deg);
    }

    50% {
        transform:
            translateY(-7px)
            rotate(2deg);
    }
}


/* =====================================================
   TITRE
===================================================== */

h1 {

    text-align: center;

    color: white;

    font-size: 29px;

    margin-bottom: 12px;

    letter-spacing: -.5px;
}


.subtitle {

    text-align: center;

    color: #cbd5e1;

    line-height: 1.6;

    margin-bottom: 30px;

    font-size: 15px;
}


/* =====================================================
   MESSAGE
===================================================== */

.message {

    padding: 14px 16px;

    margin-bottom: 20px;

    border-radius: 12px;

    font-size: 14px;

    line-height: 1.5;

    animation:
        messageAppear .4s ease;
}


@keyframes messageAppear {

    from {
        opacity: 0;
        transform:
            translateY(-10px);
    }

    to {
        opacity: 1;
        transform:
            translateY(0);
    }
}


.message.success {

    color: #bbf7d0;

    background:
        rgba(22,163,74,.15);

    border:
        1px solid
        rgba(74,222,128,.35);
}


.message.error {

    color: #fecaca;

    background:
        rgba(220,38,38,.15);

    border:
        1px solid
        rgba(248,113,113,.35);
}


/* =====================================================
   LABEL
===================================================== */

label {

    display: block;

    color: #e2e8f0;

    font-size: 14px;

    font-weight: 700;

    margin-bottom: 9px;
}


/* =====================================================
   INPUT
===================================================== */

.input {

    width: 100%;

    height: 54px;

    padding:
        0 17px;

    color: white;

    background:
        rgba(255,255,255,.07);

    border:
        1px solid
        rgba(255,255,255,.14);

    border-radius: 13px;

    outline: none;

    font-size: 16px;

    transition:
        .3s ease;
}


.input::placeholder {

    color:
        #94a3b8;
}


.input:focus {

    border-color:
        #38bdf8;

    background:
        rgba(255,255,255,.10);

    box-shadow:

        0 0 0 4px
        rgba(56,189,248,.12),

        0 0 25px
        rgba(56,189,248,.08);
}


/* =====================================================
   BOUTON
===================================================== */

.btn {

    width: 100%;

    height: 54px;

    margin-top: 22px;

    border: none;

    border-radius: 13px;

    color: white;

    font-size: 15px;

    font-weight: 800;

    cursor: pointer;

    background:
        linear-gradient(
            100deg,
            #00b4db,
            #2563eb,
            #7c3aed
        );

    background-size:
        200% 100%;

    box-shadow:
        0 10px 30px
        rgba(37,99,235,.30);

    transition:
        .3s ease;

    animation:
        buttonGradient 5s ease infinite;
}


@keyframes buttonGradient {

    0% {
        background-position:
            0% 50%;
    }

    50% {
        background-position:
            100% 50%;
    }

    100% {
        background-position:
            0% 50%;
    }
}


.btn:hover {

    transform:
        translateY(-3px);

    box-shadow:

        0 15px 35px
        rgba(37,99,235,.45),

        0 0 25px
        rgba(0,212,255,.18);
}


.btn:active {

    transform:
        translateY(0);
}


/* =====================================================
   LIEN DE RESET
===================================================== */

.reset-box {

    margin-bottom: 22px;

    padding: 16px;

    border-radius: 13px;

    background:
        rgba(14,165,233,.10);

    border:
        1px solid
        rgba(56,189,248,.25);

    animation:
        linkAppear .5s ease;
}


@keyframes linkAppear {

    from {

        opacity: 0;

        transform:
            scale(.96);
    }

    to {

        opacity: 1;

        transform:
            scale(1);
    }
}


.reset-title {

    color: #7dd3fc;

    font-weight: 800;

    margin-bottom: 8px;

    font-size: 14px;
}


.reset-link {

    color: #bae6fd;

    font-size: 13px;

    line-height: 1.5;

    word-break: break-all;

    text-decoration: none;
}


.reset-link:hover {

    color: white;

    text-decoration: underline;
}


/* =====================================================
   RETOUR
===================================================== */

.back {

    display: block;

    margin-top: 25px;

    text-align: center;

    color: #7dd3fc;

    font-size: 14px;

    font-weight: 700;

    text-decoration: none;

    transition:
        .2s ease;
}


.back:hover {

    color: white;

    transform:
        translateX(-3px);
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 520px) {

    .container {

        padding: 12px;
    }

    .card {

        padding:
            30px 22px;

        border-radius: 22px;
    }

    h1 {

        font-size: 24px;
    }

    .logo {

        width: 70px;
        height: 70px;

        font-size: 22px;
    }
}

</style>

</head>


<body>


<!-- ORBES LUMINEUX -->

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>


<!-- PARTICULES -->

<div class="particles">

    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>

</div>


<!-- CONTENU -->

<div class="container">

    <div class="card">


        <!-- LOGO -->

        <div class="logo">
            GP
        </div>


        <!-- TITRE -->

        <h1>
            Mot de passe oublié ?
        </h1>


        <p class="subtitle">

            Pas de problème.
            Entrez votre adresse e-mail
            pour récupérer votre accès à GESPRO.

        </p>


        <!-- MESSAGE -->

        <?php if ($message !== ''): ?>

            <div class="message <?= htmlspecialchars($type) ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <!-- LIEN -->

        <?php if ($lien !== ''): ?>

            <div class="reset-box">

                <div class="reset-title">

                    🔐 Votre lien de réinitialisation

                </div>

                <a
                    class="reset-link"
                    href="<?= htmlspecialchars($lien) ?>"
                >

                    <?= htmlspecialchars($lien) ?>

                </a>

            </div>

        <?php endif; ?>


        <!-- FORMULAIRE -->

        <form
            method="POST"
            action=""
        >

            <label for="email">

                Adresse e-mail

            </label>


            <input

                class="input"

                type="email"

                id="email"

                name="email"

                placeholder="exemple@email.com"

                autocomplete="email"

                required

            >


            <button
                class="btn"
                type="submit"
            >

                🔐 Envoyer le lien de récupération

            </button>

        </form>


        <!-- RETOUR -->

        <a
            class="back"
            href="login.php"
        >

            ← Retour à la connexion

        </a>


    </div>

</div>


</body>

</html>
```
