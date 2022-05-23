# data

Un projet symfony qui permet de journaliser différentes informations.  

## Les informations

### Le carburant

Possibilité de journaliser les différents passage à la pompe
- Enregistrement de voitures
- Enregistrement de stations
- Enregistrement de passages à la pompe ("pleins")

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

Le port exposé est le port 9000