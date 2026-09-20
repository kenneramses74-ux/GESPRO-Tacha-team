# GESPRO — Gestion de projets, tâches & équipes

Application web PHP / MySQL de gestion de projets, permettant à un
**chef de projet** de créer des projets, constituer une équipe par
invitation, créer des tâches et les affecter à ses membres ; et à un
**membre** de suivre ses projets, ses tâches assignées et d'échanger
en commentaires.

## Installation

**Prérequis** : PHP 8.0+ (extension PDO MySQL activée), MySQL/MariaDB.

1. Copiez tout le dossier `GESPRO/` à la racine de votre serveur web
   (ou dans un sous-dossier — l'application détecte automatiquement
   son emplacement, voir `config/config.php`).
2. Créez la base de données en important le schéma :
   ```
   mysql -u root -p < database/schema.sql
   ```
3. Définissez vos identifiants de connexion (variables d'environnement,
   voir `.env.example`). Par défaut : hôte `localhost`, base `gespro`,
   utilisateur `root`, mot de passe vide — pratique en local, à changer
   impérativement en production.
4. Ouvrez `index.php` dans votre navigateur, créez un compte
   (« Chef de projet » pour commencer à créer des projets).

En développement, `GESPRO_ENV=dev` (valeur par défaut) affiche les
erreurs PHP à l'écran. Pensez à passer `GESPRO_ENV=prod` en production
pour les masquer.

## Ce qui a été corrigé

Le code fourni initialement ne fonctionnait pas : erreurs SQL
immédiates, pages inaccessibles, fonctionnalités jamais réellement
câblées. Principaux problèmes corrigés :

- **Colonne inexistante** : plusieurs pages utilisaient
  `utilisateurs.id_utilisateur` alors que la colonne s'appelait `id`
  dans le schéma d'origine → erreur SQL fatale. La clé primaire a été
  renommée `id_utilisateur` partout, pour rester cohérente avec le
  reste du schéma (`id_projet`, `id_tache`...).
- **Fichier `config/functions.php` vide** alors que `includes/header.php`
  appelait déjà `getFlash()` → erreur fatale sur toute page connectée.
  Les fonctions manquantes (messages flash, jeton CSRF, notifications...)
  ont été implémentées.
- **Menu latéral cassé** : `includes/sidebar.php` pointait vers des
  chemins (`/GESPRO/chef/projets/index.php`, `/GESPRO/utilisateur/...`)
  qui ne correspondaient à aucun fichier réel. Les liens pointent
  maintenant vers les pages qui existent effectivement.
- **Fichiers dupliqués** : `projets.php` et `taches.php` contenaient
  chacun deux versions du code collées à la suite l'une de l'autre
  (accident de copier-coller), ce qui générait la page en double à
  chaque affichage (sidebar et menu dupliqués, HTML invalide).
- **Chemins codés en dur** (`/GESPRO/...`) : remplacés par une
  détection automatique du dossier d'installation
  (`config/config.php` + fonction `url()`), l'application fonctionne
  désormais à la racine d'un domaine ou dans n'importe quel sous-dossier.
- **Identifiants de base de données codés en dur** (`root` / mot de
  passe vide / base `database`) : remplacés par des variables
  d'environnement configurables (`config/database.php`).
- **Affectation des tâches jamais implémentée** : la table
  `affectations` n'était alimentée nulle part dans le code d'origine ;
  un membre ne voyait donc **jamais aucune tâche**. Le formulaire de
  tâche permet maintenant de choisir un ou plusieurs responsables
  parmi les membres du projet.
- **Système d'invitation non fonctionnel** : `auth/invitation.php`
  permettait de rejoindre une équipe, mais aucune page ne permettait
  d'en créer une. La page « Mon équipe » génère maintenant de vrais
  liens d'invitation (avec expiration).
- **Fuite de données** : un chef pouvait consulter le profil de
  n'importe quel utilisateur de l'application, pas seulement les
  membres de sa propre équipe. Accès désormais restreint.
- **Gestion d'équipe incohérente** : l'ancien `Mon_equipe.php`
  permettait d'ajouter n'importe quel utilisateur du système
  directement à un projet, sans invitation ni lien avec l'équipe du
  chef — en contradiction avec le système d'invitation existant par
  ailleurs. Les deux logiques ont été unifiées.
- **Colonne `priorite` inexistante** dans le schéma d'origine, utilisée
  partout dans le code applicatif → erreur SQL. Colonne ajoutée ;
  l'ancienne table `priorites` (jamais utilisée) a été supprimée.
- Divers formulaires sans protection CSRF, resoumission de formulaire
  au rechargement de page (pas de redirection après un `POST`), lien
  cassé (`profils.php=5` au lieu de `profils.php?id=5`), sélecteur CSS
  invalide dans `auth.css` (virgule manquante), deux scripts JS
  redondants et incohérents entre eux dans `app.js`.

## Améliorations apportées

- Jeton CSRF sur tous les formulaires (`config/functions.php`).
- Redirection après chaque action d'écriture (*Post/Redirect/Get*) :
  un rafraîchissement de page ne resoumet plus le formulaire.
- Limitation simple des tentatives de connexion (anti brute-force).
- Mots de passe : exigence de 8 caractères minimum à l'inscription et
  à l'invitation.
- Page **Paramètres** réellement fonctionnelle (avant : texte
  d'annonce sans formulaire) : modification du profil et changement
  de mot de passe (avec vérification de l'ancien).
- Nouvelle page **Détail d'un projet** (`dashboard/projet.php`) :
  informations, membres affectés, ajout/retrait de membres,
  raccourci vers les tâches du projet.
- Notifications automatiques : un membre est notifié quand on
  l'ajoute à un projet ou qu'on lui assigne une tâche.
- Un membre peut désormais changer le statut de ses propres tâches
  (avant : réservé au chef).
- Recherche instantanée sur la liste des tâches, filtre par projet.
- En-têtes de sécurité HTTP de base et blocage de l'accès direct aux
  dossiers `config/`, `database/` et `includes/` (`.htaccess`).
- Sessions : cookies `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS ;
  regénération de l'identifiant de session à la connexion.

## Arborescence

```
GESPRO/
├── index.php                  Page d'accueil publique
├── config/                    Connexion BDD, session, fonctions communes
├── includes/                  header.php / sidebar.php / footer.php
├── auth/                      login, inscription, invitation, logout
├── dashboard/                 Toutes les pages de l'espace connecté
├── assets/{css,js}            Styles et scripts (repris et corrigés)
└── database/schema.sql        Schéma SQL consolidé
```

Le modèle de données et l'identité visuelle d'origine (couleurs,
mise en page, animations) ont été conservés à l'identique ; seules
les briques cassées ont été réparées ou complétées.
