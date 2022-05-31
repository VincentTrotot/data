# data

Un projet symfony qui permet de journaliser différentes informations.  

## Les informations

### Le carburant

Possibilité de journaliser les différents passage à la pompe

* Enregistrement de voitures
* Enregistrement de stations
* Enregistrement de passages à la pompe ("pleins")

### Le box
Possibilité d'enregistrer les objets stockés dans le box.

* Enregistrement de la catégorie de l'objet
* Enregistrement du carton qui contient l'objet, avec la possibilité de renseigner son emplacement dans le box.
* Enregistrement des mouvements de l'objet (dépôt/retrait), avec enregistrement de la date et de l'heure et de la personne ayant effectué l'action.

## Initialisation

Conteneur docker de développement inspiré par [yoandev.co](https://yoandev.co/) : [Symfony 6 + PHP 8.0.13 with Docker](https://github.com/yoanbernabeu/symfony6-php8-in-docker-compose)

```bash
docker-compose build
docker-compose up -d
docker exec -it data bash
cd data
composer install
symfony serve -d
symfony console make:migration
symfony console d:m:m
```

Les ports exposés sont le port 9000 pour le serveur et le port 9080 pour phpmyadmin