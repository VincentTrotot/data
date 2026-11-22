# Déploiement sur le VPS OVH

Site en production : <https://data.vincent-trotot.fr> (journal des pleins et inventaire du box), sur le VPS OVH
(Debian, Caddy + PHP-FPM 8.4, un utilisateur Linux par site). Tout est construit sur le Mac (`composer`, assets
compilés par AssetMapper, sans npm), puis envoyé par SSH dans une nouvelle version `releases/<date>-<sha>` ; le lien
`current` ne bascule qu'une fois la base sauvegardée et migrée. Fonctionnement du serveur : `~/Sites/ovh/README.md`.

Base de prod : SQLite, `/srv/sites/data/shared/data_prod.db` (liée dans `var/` de chaque version). Le `.env` du dépôt
donne `var/data_<environnement>.db` (dev : `var/data_dev.db`, tests : `var/data_test.db`).

## Une fois

1. `~/.ssh/config` : alias `vps-ovh` (utilisateur `debian`, connexion par clé).
2. `cp deploy.env.dist deploy.env.local` (non versionné) : `SSH_HOTE`, `SITE=data`, `URL_SITE`, `CHEMIN_VERIF=/login`.
3. Sur le serveur : voir « Bascule du serveur » ci-dessous (à faire une seule fois, avec Vincent).

## À chaque fois

    make deploy-essai   # construit, envoie dans un dossier d'essai et dit si des migrations sont en attente
    make deploy
    make revenir        # rebascule current sur la version précédente (la base ne bouge pas)

Seul le dernier commit est déployé (`git archive HEAD`), et le script refuse si des modifications ne sont pas
commitées ou si la branche est en retard sur le remote. Pendant les migrations, le site affiche
`public/maintenance.html` (fichier `var/maintenance`). Le détail est en tête de `bin/deployer`. Les 5 dernières
versions sont gardées.

En cas d'échec des migrations, `current` n'a pas bougé mais le site reste en maintenance :
`ssh vps-ovh sudo rm /srv/sites/data/current/var/maintenance`. Avant chaque migration, une copie de la base est faite
dans `shared/sauvegardes/deploiements/` (10 dernières).

`DEPLOY_CONFIG=chemin/vers/config.env bin/deployer` utilise une autre configuration.

## Assets

AssetMapper : `importmap:install` puis `asset-map:compile` sont joués par `bin/deployer` pendant la construction
(`public/assets/` et `assets/vendor/` ne sont pas versionnés). Bootstrap (5.2.0-beta1, CSS et `bootstrap.bundle.min.js`)
vit dans `public/bootstrap/`, versionné, hors de `public/assets/` que `asset-map:compile` vide. Le test
`AccueilTest` vérifie que chaque script et feuille de style de l'accueil répond 200.

## Comptes et API

- Les inscriptions sont fermées (`/register` n'existe plus). Créer un compte :
  `sudo -u s-data php bin/console app:utilisateur:creer email prenom nom [--admin]` dans `/srv/sites/data/current`.
- L'API du raccourci iOS exige `API_KEY` (variable d'environnement, dans `shared/.env.local`, jamais dans le dépôt) :
  `/api/plein/add/{quantite}/{prix}/{kilometrage}/{voiture}/{station}/{cle}`, `/api/voiture/add/{marque}/{modele}/{immatriculation}/{cle}`,
  `/api/station/add/{nom}/{ville}/{cle}`, `/api/stations?cle=…`, `/api/voitures?cle=…`. Clé fausse ou vide : 404.
  Le raccourci qui n'ajoutait que les pleins n'a pas à changer d'URL.

## Sauvegardes et journal d'erreurs

Chaque nuit, la crontab de `s-data` lance `bin/sauvegarder-cron.php` : copie cohérente de la base (`app:sauvegarder`,
`VACUUM INTO`), compressée dans `var/sauvegardes/data-AAAA-MM-JJ.db.gz` (14 derniers jours + la première copie de
chaque mois sur un an), et écrit `var/sauvegardes/derniere-execution.json`. `var/sauvegardes` est un lien vers
`shared/sauvegardes`.

    sudo crontab -u s-data -l
    17 3 * * * cd /srv/sites/data/current && php bin/sauvegarder-cron.php >> /srv/sites/data/logs/cron.log 2>&1

`make sauvegarde` rapatrie par SSH les copies du serveur dans `var/deploiement/sauvegardes/serveur/` et les vérifie
(`sqlite3` : `integrity_check`, comptes de `plein`, `objet`, `mouvement`, alerte si l'un baisse de plus de 10 %).

Les erreurs de l'appli (niveau error) sont écrites en JSONL dans `var/log/erreurs-prod.jsonl` (handler Monolog
`App\Log\JournalErreursHandler`, lecteur `App\Log\JournalErreurs`, rotation à 5 Mo). Aucune page admin pour l'instant.

**Restauration** : `gunzip -k data-AAAA-MM-JJ.db.gz`, vérifier (`sqlite3 … 'PRAGMA integrity_check'`), puis site en
maintenance (`sudo -u s-data touch /srv/sites/data/current/var/maintenance`), remplacer `shared/data_prod.db`
(propriétaire `s-data`, 660), retirer `var/maintenance`. Les copies contiennent des données personnelles : jamais dans
le dépôt ni par email.

## Bascule du serveur (une fois, à faire avec Vincent : rien de tout cela n'est fait automatiquement)

État de départ (21/11/2026) : copie à plat dans `/srv/sites/data/app` (racine du site `app/public`), base
`/srv/sites/data/shared/data_prod.db` convertie depuis MySQL, ancienne clé d'API dans le `.env` de cette copie.

1. **Sauvegarde de la base** : `sudo -u s-data sqlite3 /srv/sites/data/shared/data_prod.db ".backup /srv/sites/data/shared/avant-bascule.db"`.
2. **Remplacer l'historique des migrations.** La table `doctrine_migration_versions` contient les 4 migrations MySQL
   de 2022 ; le code n'a plus qu'une migration de base SQLite, `Version20261122000000`, qui recrée le schéma actuel
   (déjà en place). À lancer **une fois**, avant le premier `make deploy` (vérifier avant les colonnes :
   `sqlite3 … '.schema doctrine_migration_versions'` ; en novembre 2026 : `version`, `executed_at`, `execution_time`) :

       sudo -u s-data sqlite3 /srv/sites/data/shared/data_prod.db \
         "DELETE FROM doctrine_migration_versions; INSERT INTO doctrine_migration_versions VALUES ('DoctrineMigrations\Version20261122000000', datetime('now'), 0);"

   Sans cela, `bin/deployer` refuserait (« migrations absentes de ce code »). Vérifié en local sur une copie de la base
   de prod : `doctrine:migrations:up-to-date` répond « Up-to-date » après cette commande, et
   `doctrine:schema:update --dump-sql` ne propose que la suppression du commentaire legacy `(DC2Type:json)` de
   `utilisateur.roles` (sans effet).
3. **Nouveau site** (racine `current/public` à la place de `app/public`) :
   `sudo nouveau-site data current/public data.vincent-trotot.fr www.data.vincent-trotot.fr`.
4. **`/srv/sites/data/shared/.env.local`** (debian:s-data, 640) :

       APP_ENV=prod
       APP_DEBUG=0
       APP_SECRET=<secret>
       API_KEY=<l'ancienne valeur, reprise du .env de /srv/sites/data/app>
       # DATABASE_URL="sqlite:////srv/sites/data/shared/data_prod.db"  (inutile : le lien var/data_prod.db suffit)

5. `ssh -O exit vps-ovh` (une connexion SSH maître garde les anciens groupes de `debian`).
6. `make deploy-essai`, puis `make deploy`.
7. Crontab de `s-data` : `sudo crontab -u s-data -e` avec la ligne de la section « Sauvegardes ».
8. Après vérification du site (accueil, graphique, `/login`, `/admin`, menu mobile, raccourci iOS) : supprimer
   `/srv/sites/data/app`.
