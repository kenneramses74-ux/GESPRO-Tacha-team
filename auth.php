<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function db()
{
    return Database::connect();
}

function utilisateurConnecte()
{
    return isset($_SESSION['user_id']);
}

function roleUtilisateur()
{
    return $_SESSION['role'] ?? '';
}

function estChef()
{
    return strtolower(roleUtilisateur()) === 'chef de projet';
}

function estMembre()
{
    return strtolower(roleUtilisateur()) === 'utilisateur';
}

/** Échappe une valeur pour un affichage HTML sûr. */
function e($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function protegerPage()
{
    if (!utilisateurConnecte()) {
        header('Location: ' . url('/auth/login.php'));
        exit;
    }
}

function protegerChef()
{
    protegerPage();
    if (!estChef()) {
        header('Location: ' . url('/dashboard/index.php'));
        exit;
    }
}

function redirigerSiConnecte()
{
    if (utilisateurConnecte()) {
        header('Location: ' . url('/dashboard/index.php'));
        exit;
    }
}
