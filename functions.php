<?php
/**
 * Fonctions utilitaires partagées.
 *
 * Ce fichier était vide dans le projet d'origine alors que
 * includes/header.php appelait déjà getFlash() — ce qui provoquait
 * une erreur fatale "Call to undefined function" sur toutes les pages.
 */

/**
 * Enregistre un message flash affiché une seule fois, sur la page
 * suivante (pattern Post/Redirect/Get : on évite qu'un rechargement
 * de page ne soumette à nouveau un formulaire).
 */
function setFlash($message, $type = 'success')
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/** Récupère et efface le message flash en attente, s'il existe. */
function getFlash()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/** Génère (ou réutilise) un jeton CSRF pour la session en cours. */
function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Balise <input hidden> à inclure dans chaque formulaire POST. */
function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/** Vérifie le jeton CSRF envoyé par un formulaire. Arrête la requête si invalide. */
function verifierCsrf()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Jeton de sécurité invalide ou expiré. Merci de recharger la page et de réessayer.');
    }
}

/** Formate une date SQL (YYYY-MM-DD) en format français lisible. */
function formaterDate($date)
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : '-';
}

/** Crée une notification pour un utilisateur donné. */
function notifier($pdo, $idUtilisateur, $message)
{
    $pdo->prepare('INSERT INTO notifications (message, id_utilisateur) VALUES (?, ?)')
        ->execute([$message, $idUtilisateur]);
}

/** Classe CSS de badge selon la priorité d'une tâche. */
function classePriorite($priorite)
{
    return 'priority-' . strtolower(str_replace(
        ['é', 'è'],
        ['e', 'e'],
        $priorite
    ));
}
