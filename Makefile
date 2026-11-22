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

.PHONY: install serve migration migrate test
