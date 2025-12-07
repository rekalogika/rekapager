PHP=php
COMPOSER=composer
SYMFONY=symfony

.PHONY: test
test: composer-dump monorepo-validate phpstan phpunit

.PHONY: composer-dump
composer-dump:
	$(COMPOSER) dump-autoload

.PHONY: phpstan
phpstan:
	$(PHP) vendor/bin/phpstan analyse

.PHONY: phpunit
phpunit:
	$(eval c ?=)
	make fixtures
	$(PHP) vendor/bin/phpunit $(c)

.PHONY: lint
lint:
	$(PHP) tests/bin/console lint:container

.PHONY: rector
rector:
	$(PHP) vendor/bin/rector process > rector.log
	make php-cs-fixer

.PHONY: php-cs-fixer
php-cs-fixer: tools/php-cs-fixer
	PHP_CS_FIXER_IGNORE_ENV=1 $(PHP) $< fix --config=.php-cs-fixer.dist.php --verbose --allow-risky=yes

.PHONY: tools/php-cs-fixer
tools/php-cs-fixer:
	phive install php-cs-fixer

.PHONY: doctrine
doctrine:
	rm -f tests/var/data.db
	$(PHP) tests/bin/console doctrine:schema:create
	$(PHP) tests/bin/console doctrine:fixtures:load --no-interaction

.PHONY: fixtures
fixtures:
	rm -f tests/var/data.db
	sqlite3 tests/var/data.db < tests/data.sql

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

.PHONY: monorepo-merge
monorepo-merge:
	$(PHP) vendor/bin/monorepo-builder merge

.PHONY: monorepo-release-%
monorepo-release-%:
	git update-index --really-refresh > /dev/null; git diff-index --quiet HEAD || (echo "Working directory is not clean, aborting" && exit 1)
	[ $$(git branch --show-current) == main ] || (echo "Not on main branch, aborting" && exit 1)
	$(PHP) vendor/bin/monorepo-builder release $*
	git switch -c release/$*
	git add .
	git commit -m "release: $*"

.PHONY: clean
clean:
	rm -rf tests/var/cache/*

.PHONY: sqlite
sqlite:
	sqlite3 tests/var/data.db

.PHONY: sqlite-dump
sqlite-dump:
	sqlite3 tests/var/data.db .dump > tests/data.sql

.PHONY: dump
dump:
	$(PHP) tests/bin/console server:dump
