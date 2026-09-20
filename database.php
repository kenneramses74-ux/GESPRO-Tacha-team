<?php
/**
 * Connexion à la base de données.
 *
 * Avant : les identifiants (host, utilisateur "root", mot de passe vide,
 * nom de base "database") étaient codés en dur dans le fichier — cela
 * ne fonctionne qu'en local et oblige à modifier le code pour déployer
 * ailleurs. Désormais les identifiants viennent de variables
 * d'environnement, avec des valeurs par défaut pratiques pour le
 * développement local.
 */
class Database
{
    private static $pdo = null;

    public static function connect()
    {
        if (self::$pdo === null) {
            $host = getenv('GESPRO_DB_HOST') ?: 'localhost';
            $name = getenv('GESPRO_DB_NAME') ?: 'database';
            $user = getenv('GESPRO_DB_USER') ?: 'root';
            $pass = getenv('GESPRO_DB_PASS') ?: '';
            $port = getenv('GESPRO_DB_PORT') ?: '3306';

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // On ne montre jamais le détail de la connexion en production.
                http_response_code(500);
                if (defined('GESPRO_ENV') && GESPRO_ENV === 'dev') {
                    die('Connexion à la base de données impossible : ' . $e->getMessage());
                }
                die('Le service est momentanément indisponible. Merci de réessayer plus tard.');
            }
        }

        return self::$pdo;
    }
}
