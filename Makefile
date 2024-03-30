PHP=php
COMPOSER=composer
SYMFONY=symfony

.PHONY: test
test: composer-dump lint monorepo-validate phpstan psalm phpunit

.PHONY: composer-dump
composer-dump:
	$(COMPOSER) dump-autoload

.PHONY: phpstan
phpstan:
	$(PHP) vendor/bin/phpstan analyse

.PHONY: psalm
psalm:
	$(PHP) vendor/bin/psalm

.PHONY: phpunit
phpunit:
	$(eval c ?=)
	$(PHP) vendor/bin/phpunit $(c)

.PHONY: lint
lint:
	$(PHP) tests/bin/console lint:container

.PHONY: php-cs-fixer
php-cs-fixer: tools/php-cs-fixer
	$(PHP) $< fix --config=.php-cs-fixer.dist.php --verbose --allow-risky=yes

.PHONY: tools/php-cs-fixer
tools/php-cs-fixer:
	phive install php-cs-fixer

.PHONY: doctrine
doctrine:
	rm -f tests/var/data.db
	$(PHP) tests/bin/console doctrine:schema:create
	$(PHP) tests/bin/console doctrine:fixtures:load --no-interaction

.PHONY: serve
serve:
	$(PHP) tests/bin/console cache:clear
	$(PHP) tests/bin/console asset:install tests/public/
	cd tests && sh -c "$(SYMFONY) server:start --document-root=public"

.PHONY: monorepo
monorepo: monorepo-validate monorepo-merge

.PHONY: monorepo-validate
monorepo-validate:
	vendor/bin/monorepo-builder validate

.PHONY: merge
monorepo-merge:
	$(PHP) vendor/bin/monorepo-builder merge

.PHONY: clean
clean:
	rm -rf tests/var/cache/*
	$(PHP) vendor/bin/psalm --clear-cache

.PHONY: sqlite
sqlite:
	sqlite3 tests/var/data.db

.PHONY: dump
dump:
	$(PHP) tests/bin/console server:dump