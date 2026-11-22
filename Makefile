CONSOLE = php bin/console
PHPUNIT = ./vendor/bin/phpunit

install:
	composer install
	$(CONSOLE) importmap:install

serve:
	symfony serve -d

migration:
	$(CONSOLE) make:migration

migrate:
	$(CONSOLE) doctrine:migrations:migrate

test:
	$(PHPUNIT)

### Déploiement sur le VPS (docs/deploiement.md)
deploy-essai:
	bin/deployer --essai

deploy:
	bin/deployer

revenir:
	bin/deployer --revenir

# Rapatrie les sauvegardes quotidiennes du serveur (docs/deploiement.md)
sauvegarde:
	bin/sauvegardes

.PHONY: install serve migration migrate test deploy-essai deploy revenir sauvegarde
