-- =========================================================
-- GESPRO — Schéma de base de données (version consolidée)
-- =========================================================
-- Ce fichier remplace database.sql + database_migration.sql.
-- Il peut être exécuté sur une base vide en une seule fois.
--
-- Corrections apportées par rapport aux anciens fichiers :
--   - clé primaire `utilisateurs.id` renommée `id_utilisateur`
--     pour être cohérente avec le reste du schéma (id_projet,
--     id_tache, id_equipe...) — plusieurs pages utilisaient déjà
--     ce nom par erreur, ce qui provoquait des erreurs SQL.
--   - colonne `taches.priorite` (texte) ajoutée : c'est celle que
--     tout le code utilise réellement. L'ancienne table `priorites`
--     et la colonne `id_priorite` n'étaient utilisées nulle part et
--     ont été supprimées.
--   - colonne `taches.utilisateur_id` (ancienne affectation à un
--     seul utilisateur) supprimée au profit de la table
--     `affectations`, seule utilisée par le code applicatif.
-- =========================================================

CREATE DATABASE IF NOT EXISTS `gespro`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `gespro`;

-- ---------------------------------------------------------
-- Rôles
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
  id_role      INT AUTO_INCREMENT PRIMARY KEY,
  libelle_role VARCHAR(50) NOT NULL UNIQUE,
  description  VARCHAR(255) NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (id_role, libelle_role, description) VALUES
  (1, 'Chef de projet', 'Gestionnaire des projets et des équipes'),
  (2, 'Utilisateur',    'Membre d’une équipe et exécutant des tâches');

-- ---------------------------------------------------------
-- Utilisateurs
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
  id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
  nom            VARCHAR(100) NOT NULL,
  prenom         VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  mot_de_passe   VARCHAR(255) NOT NULL,
  id_role        INT NOT NULL DEFAULT 2,
  date_creation  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_utilisateur_role FOREIGN KEY (id_role)
    REFERENCES roles(id_role) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Équipes (un chef possède une équipe, alimentée par invitation)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS equipes (
  id_equipe     INT AUTO_INCREMENT PRIMARY KEY,
  nom_equipe    VARCHAR(150) NOT NULL,
  id_chef       INT NOT NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_equipe_chef (id_chef),
  FOREIGN KEY (id_chef) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS membres_equipes (
  id_membre      INT AUTO_INCREMENT PRIMARY KEY,
  id_equipe      INT NOT NULL,
  id_utilisateur INT NOT NULL,
  date_ajout     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_equipe_membre (id_equipe, id_utilisateur),
  FOREIGN KEY (id_equipe) REFERENCES equipes(id_equipe) ON DELETE CASCADE,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invitations (
  id_invitation   INT AUTO_INCREMENT PRIMARY KEY,
  token           VARCHAR(100) NOT NULL UNIQUE,
  email           VARCHAR(150) NULL,
  id_equipe       INT NOT NULL,
  id_chef         INT NOT NULL,
  date_creation   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_expiration DATETIME NULL,
  utilise         TINYINT(1) DEFAULT 0,
  FOREIGN KEY (id_equipe) REFERENCES equipes(id_equipe) ON DELETE CASCADE,
  FOREIGN KEY (id_chef) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Projets
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS projets (
  id_projet     INT AUTO_INCREMENT PRIMARY KEY,
  nom_projet    VARCHAR(150) NOT NULL,
  description   TEXT NULL,
  date_debut    DATE NULL,
  date_fin      DATE NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  id_chef       INT NOT NULL,
  statut        VARCHAR(30) NOT NULL DEFAULT 'En préparation',
  FOREIGN KEY (id_chef) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projet_membres (
  id_projet_membre INT AUTO_INCREMENT PRIMARY KEY,
  id_projet        INT NOT NULL,
  id_utilisateur   INT NOT NULL,
  date_ajout       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_projet_membre (id_projet, id_utilisateur),
  FOREIGN KEY (id_projet) REFERENCES projets(id_projet) ON DELETE CASCADE,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tâches
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS taches (
  id_tache      INT AUTO_INCREMENT PRIMARY KEY,
  id_projet     INT NOT NULL,
  titre         VARCHAR(150) NOT NULL,
  description   TEXT NULL,
  priorite      VARCHAR(20) NOT NULL DEFAULT 'Moyenne',
  statut        VARCHAR(30) NOT NULL DEFAULT 'À faire',
  date_echeance DATE NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_projet) REFERENCES projets(id_projet) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS affectations (
  id_affectation   INT AUTO_INCREMENT PRIMARY KEY,
  id_tache         INT NOT NULL,
  id_utilisateur   INT NOT NULL,
  date_affectation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  role_dans_tache  VARCHAR(100) DEFAULT 'Responsable',
  UNIQUE KEY uq_affectation (id_tache, id_utilisateur),
  FOREIGN KEY (id_tache) REFERENCES taches(id_tache) ON DELETE CASCADE,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Commentaires & notifications
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS commentaires (
  id_commentaire INT AUTO_INCREMENT PRIMARY KEY,
  contenu        TEXT NOT NULL,
  date_creation  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  id_projet      INT NOT NULL,
  id_utilisateur INT NOT NULL,
  FOREIGN KEY (id_projet) REFERENCES projets(id_projet) ON DELETE CASCADE,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  id_notification INT AUTO_INCREMENT PRIMARY KEY,
  message         VARCHAR(500) NOT NULL,
  date_creation   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  lu              TINYINT(1) DEFAULT 0,
  id_utilisateur  INT NOT NULL,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;
