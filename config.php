<?php
/**
 * Configuration générale de GESPRO.
 *
 * Avant : toutes les pages redirigeaient vers un chemin codé en dur
 * "/GESPRO/..." — l'application cassait dès qu'elle n'était pas
 * installée exactement à la racine d'un site sous ce nom de dossier.
 * Désormais, l'URL de base est déduite automatiquement de l'endroit
 * où l'application est réellement installée (ou peut être forcée via
 * la variable d'environnement GESPRO_BASE_URL).
 */

// Environnement : 'dev' affiche les erreurs, 'prod' les masque.
define('GESPRO_ENV', getenv('GESPRO_ENV') ?: 'dev');

if (GESPRO_ENV === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

// Base URL : ex. "/GESPRO" si installé dans un sous-dossier, "" à la racine.
if (!defined('BASE_URL')) {
    $envBase = getenv('GESPRO_BASE_URL');
    if ($envBase !== false) {
        define('BASE_URL', rtrim($envBase, '/'));
    } else {
        // Déduit le sous-dossier d'installation à partir du script courant.
        // config/config.php est toujours à un niveau connu sous la racine.
        $root = dirname(__DIR__); // dossier racine de l'application
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $rootNormalized = str_replace('\\', '/', $root);
        $sub = ($docRoot !== '' && strpos($rootNormalized, $docRoot) === 0)
            ? substr($rootNormalized, strlen($docRoot))
            : '';
        define('BASE_URL', rtrim($sub, '/'));
    }
}

// Sécurité des sessions.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

/** Construit une URL absolue (depuis la racine du site) vers $path. */
function url($path)
{
    return BASE_URL . '/' . ltrim($path, '/');
}
