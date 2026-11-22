# Remise d'aplomb de data : Symfony 8.1, PHP 8.4, briques VPS

Rédigée le 22/11/2026. À implémenter par Sonnet, dans l'ordre des étapes, un commit par étape (gitmoji comme
l'historique, `Co-Authored-By` Claude). Modèle à suivre : la même opération sur secret-santa
(`~/Sites/secret-santa`, commits `e8feca7` et `46ca39b`), et les briques de `~/.claude/sources/symfony/`.

## Contexte

- Appli « vt_data » (data.vincent-trotot.fr) : journal des pleins de carburant (voitures, stations, pleins, graphique
  du prix au litre sur l'accueil) et inventaire du box (catégories, cartons, objets, mouvements), admin EasyAdmin.
- Code : dépôt GitHub `VincentTrotot/data` (cloné dans `~/Sites/data`), dernier commit 06/2022. Le code du serveur
  est identique au dépôt (seul son `composer.json` est plus ancien) : on part du dépôt.
- Aujourd'hui : Symfony **6.0.9** (fin de vie), PHP ≥ 8.0.2, Doctrine ORM 2, Webpack Encore + Stimulus + ux-chartjs,
  sensio/framework-extra-bundle, doctrine/annotations. Les attributs PHP sont déjà utilisés (pas d'annotations).
- En prod sur le VPS OVH (PHP 8.4) depuis le 21/11/2026 : `/srv/sites/data/app` (copie à plat, **pas** de
  `releases/`/`current`), base SQLite `/srv/sites/data/shared/data_prod.db` (convertie depuis MySQL ; 46 pleins,
  201 objets, 12 cartons, 211 mouvements, 1 utilisateur admin). 1000+ dépréciations PHP 8.4 par jour dans
  `/srv/sites/data/logs/php-erreurs.log`.
- Pas de tests. Les migrations sont ignorées par git (`/migrations/*.php`) et celles du serveur sont des migrations
  MySQL de 2022 (marquées jouées dans la base SQLite).

## Étape 1 : dépendances installables sur PHP 8.4 (Symfony 8.1)

1. `composer.json` : `php >=8.4`, `config.platform.php = 8.4`, tous les `symfony/*` en `8.1.*`
   (`extra.symfony.require = 8.1.*`), Doctrine ORM `^3.5`, DBAL `^4.2`, doctrine-bundle `^3.0`,
   doctrine-migrations-bundle `^4.0`, monolog-bundle `^4.0`, twig `^3`, easyadmin `^4.29.17` (failles corrigées dans
   cette version), phpunit `^13`, symfony/browser-kit + css-selector `8.1.*`.
2. Retirer : `sensio/framework-extra-bundle` (+ `config/packages/sensio_framework_extra.yaml`, ligne de
   `bundles.php`), `doctrine/annotations`, `symfony/proxy-manager-bridge`, `phpstan/phpdoc-parser ^1` (→ `^2` si
   encore requis), `symfony/phpunit-bridge` si inutile.
3. **Assets : passer de Webpack Encore à AssetMapper** (comme gaia, adh, ensemble-pour-demain ; plus de npm au
   déploiement) : `symfony/asset-mapper`, `symfony/stimulus-bundle`, `symfony/ux-chartjs` (version 2.x récente),
   `importmap.php` ; supprimer `webpack.config.js`, `package.json`, `yarn.lock`, `symfony/webpack-encore-bundle`,
   `config/packages/webpack_encore.yaml`, `assets/controllers/hello_controller.js`. `base.html.twig` :
   `{{ importmap('app') }}` à la place de `encore_entry_*`. Bootstrap reste servi depuis `public/assets/` (fichiers
   locaux actuels, CSS et `bootstrap.bundle.min.js` tous deux en **5.2.0-beta1** ; ne pas changer de version).
   Bug d'origine du 21/11 : le serveur n'avait que `bootstrap.min.js` alors que `base.html.twig` charge
   `bootstrap.bundle.min.js` → 404, menu mobile (où est « Se connecter ») inopérant. Ajouter un test qui vérifie que
   chaque `<script src>`/`<link href>` local de l'accueil répond 200. `.gitignore` : `/public/assets/`
   **ne doit pas** être ignoré (Bootstrap y vit) → configurer AssetMapper avec `framework.asset_mapper.public_prefix`
   par défaut (`/assets/`) en conflit ? Si oui, déplacer Bootstrap dans `assets/vendor-local/` mappé par AssetMapper,
   ou garder les fichiers dans `public/bootstrap/` et adapter les liens. Choisir la solution la plus simple et la noter
   dans le commit.
4. Recettes Flex : mettre à jour la config (`composer recipes:update` ou à la main) pour framework, doctrine,
   security, monolog, routing, twig, web_profiler (routes en PHP/YAML, plus XML), `config/reference.php` si généré.
5. `composer update -W` puis `composer audit` sans faille.

## Étape 2 : code compatible Symfony 8 / Doctrine 3

Liste repérée (compléter avec `bin/console debug:container --deprecations` et les dépréciations des tests) :
- `Symfony\Component\Routing\Annotation\Route` → `Symfony\Component\Routing\Attribute\Route` partout.
- `VoitureController` : `renderForm()` → `render()`.
- `AppAuthenticator` : `Security::LAST_USERNAME` → `SecurityRequestAttributes::LAST_USERNAME`
  (`Symfony\Component\Security\Http\SecurityRequestAttributes`), `$request->request->get()` avec types stricts
  (`getString`).
- `Utilisateur::eraseCredentials()` : supprimé de l'interface en 8 → retirer la méthode (ou `#[\Deprecated]` vide si
  quelque chose l'appelle).
- `RegistrationFormType` : contraintes en arguments nommés (`new NotBlank(message: …)`, `new Length(min: …)`) au
  lieu de tableaux d'options.
- Entités : `Types::ARRAY`/`OBJECT` éventuels → json (rien trouvé à la première lecture) ; repositories `add()` /
  `remove()` générés par l'ancien maker : à garder s'ils sont utilisés, sinon `EntityManager` direct.
- EasyAdmin 4.29 : `MenuItem::linkToCrud()` déprécié → `MenuItem::linkTo()` ; formulaire caché de suppression
  renommé `action-confirmation-form`.
- Twig : `app.request.get` → `app.request.query.get` / `attributes`.
- `src/*/.gitignore` vides laissés par le maker : supprimer.

## Étape 3 : migrations SQLite

1. Supprimer `/migrations/*.php` de `.gitignore` (les migrations doivent être versionnées, comme dans les autres
   projets).
2. Créer **une seule migration de base** `Version20261122000000` générée pour SQLite depuis le mapping
   (`doctrine:migrations:diff` sur une base vide), qui recrée tout le schéma actuel.
3. Vérifier sur une **copie** de la base de prod (`scp`/`rsync --rsync-path='sudo rsync'` de
   `/srv/sites/data/shared/data_prod.db` vers `var/`, jamais versionnée) que
   `DATABASE_URL=sqlite:///…copie doctrine:schema:update --dump-sql` ne propose rien (ou seulement des index/renommages
   sans perte : sinon, migration corrective `Version20261122000100` testée sur la copie).
4. En prod, la table `doctrine_migration_versions` contient les 4 versions MySQL de 2022 : le premier déploiement
   doit les remplacer par la nouvelle version marquée jouée. Documenter dans `docs/deploiement.md` la commande à
   lancer **une fois** avant le premier `make deploy` (sur le serveur, en `s-data`, sur `shared/data_prod.db`, après
   une copie de sauvegarde `sqlite3 .backup`) :
   `DELETE FROM doctrine_migration_versions; INSERT INTO doctrine_migration_versions VALUES ('DoctrineMigrations\Version20261122000000', datetime('now'), 0);`
   (vérifier le nom des colonnes dans la base de prod avant). Ne rien lancer en prod soi-même : laisser Vincent le
   faire ou demander son accord.

## Étape 4 : sécurité et petits correctifs fonctionnels

- `/api/voiture/add/…` et `/api/station/add/…` n'exigent **aucune** clé : n'importe qui peut écrire en base. Exiger
  la même clé que `/api/plein/add` (dernier segment `{api_key}`), comparer avec `hash_equals`, 404 si la clé est
  fausse ou si `API_KEY` est vide. `/api/stations` et `/api/voitures` : même protection (clé en query `?cle=`).
  Garder les URL GET actuelles de `plein/add` (raccourci Apple de Vincent, peut-être encore utilisé).
- `API_KEY` : variable d'environnement (`.env` : `API_KEY=` vide ; valeur réelle dans `shared/.env.local` du serveur
  — l'ancienne valeur figurait dans le `.env` du serveur, ne jamais la commiter).
- `/register` est ouvert à tous (rôle ROLE_USER, sans effet aujourd'hui car tout est sous `/admin`) : **fermer les
  inscriptions** (supprimer `RegistrationController`, son formulaire et son template ; les comptes se créent par
  l'admin EasyAdmin `UtilisateurCrudController` ou une commande `app:utilisateur:creer`).
- `ApiController::add` : convertir les paramètres (float/int) et refuser une voiture/station inconnue (400) au lieu
  d'enregistrer un plein orphelin.

## Étape 5 : tests

`phpunit.xml.dist` (PHPUnit 13, `failOnDeprecation`/`failOnWarning`), `.env.test` en SQLite
(`var/data_test.db`), base recréée par test (schema tool, comme secret-santa). Tests fonctionnels minimum :
- accueil 200 avec le graphique (au moins un plein en base) ;
- `/login` 200, connexion OK avec un utilisateur créé dans le test, `/admin` 302 vers login sans connexion, 403 pour
  ROLE_USER, 200 pour ROLE_ADMIN ; chaque CRUD EasyAdmin (index) 200 ; `/admin/box/` et `/admin/voiture/` 200 ;
- API : plein ajouté avec la bonne clé, refusé (404) avec une mauvaise clé ou une clé vide ; voiture/station
  protégées ; listes protégées ;
- `/register` 404.
- Cible Makefile `test` (+ `install` : `composer install` puis `importmap:install`).

## Étape 6 : briques (ne pas réinventer : copier depuis `~/.claude/sources/symfony/`)

Lire d'abord chaque `README.md` de brique.
1. **deploiement-vps** : `bin/deployer`, `deploy.env.dist` (`SSH_HOTE=vps-ovh`, `SITE=data`,
   `URL_SITE=https://data.vincent-trotot.fr`, `CHEMIN_VERIF=/login`, `PARTAGES=` vide), `/deploy.env.local` dans
   `.gitignore`, `public/index.php` + `public/maintenance.html` de `deploiement-ssh/` (titre « vt_data »), cibles
   `deploy-essai`, `deploy`, `revenir`, `sauvegarde` ; `docs/deploiement.md` (inclure l'étape 3.4 et la bascule du
   serveur ci-dessous).
2. **sauvegardes** : `Service\Sauvegarde` (préfixe `data-`), `Command\SauvegarderCommand`, `bin/sauvegarder-cron.php`
   (sans `app:donnees:purger`, qui n'existe pas ici), `bin/sauvegardes` version VPS (`deploiement-vps/bin/sauvegardes`,
   tables comptées : `plein`, `objet`, `mouvement`), tests de la brique.
3. **journal-erreurs** : handler + lecteur + `monolog.yaml` + `services.yaml` + tests. Pages admin non demandées.
4. Bascule du serveur, à **documenter** dans `docs/deploiement.md` et à faire avec Vincent (pas par l'agent) :
   `sudo nouveau-site data current/public data.vincent-trotot.fr www.data.vincent-trotot.fr` (racine passe de
   `app/public` à `current/public`), `shared/.env.local` (`APP_ENV=prod`, `APP_SECRET`, `API_KEY`,
   `DATABASE_URL="sqlite:////srv/sites/data/shared/data_prod.db"` ou laisser le lien `var/` du deployer), commande
   SQL de l'étape 3.4, `ssh -O exit vps-ovh`, `make deploy-essai`, `make deploy`, crontab de `s-data`
   (`17 3 * * * cd /srv/sites/data/current && php bin/sauvegarder-cron.php`), puis suppression de
   `/srv/sites/data/app` une fois le site vérifié.

## Critères de fin

- `composer audit` propre, `bin/console lint:container`, `lint:twig templates`, `lint:yaml config`,
  `doctrine:schema:validate` OK ; `make test` vert sans dépréciation.
- `APP_ENV=prod bin/console cache:warmup` et `asset-map:compile` OK ; `make deploy-essai` lisible (sans déployer).
- Accueil, connexion, admin et box testés à la main en local avec une copie de la base de prod (`symfony serve`).
- `docs/roadmap.md` : tâche cochée. Rien poussé sur GitHub ni déployé sans l'accord de Vincent.
