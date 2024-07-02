# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

PHPBREW_PHAR  = phpbrew
CP            = cp
INSTALL_PATH  = /usr/local/bin

PHAR_SRC_FILES := $(shell find bin/ shell/ src/ -type f)

COMPOSER_BIN_PLUGIN_VENDOR = vendor/bamarni/composer-bin-plugin

RECTOR_BIN = vendor-bin/rector/vendor/bin/rector
RECTOR = $(RECTOR_BIN)

PHP_CS_FIXER_BIN = vendor-bin/php-cs-fixer/vendor/bin/php-cs-fixer
PHP_CS_FIXER = $(PHP_CS_FIXER_BIN)

PHPUNIT_BIN = vendor/phpunit/phpunit/phpunit
PHPUNIT = $(PHPUNIT_BIN)


.DEFAULT_GOAL := help


.PHONY: help
help:
	@printf "\033[33mUsage:\033[0m\n  make TARGET\n\n\033[32m#\n# Commands\n#---------------------------------------------------------------------------\033[0m\n\n"
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | awk 'BEGIN {FS = ":"}; {printf "\033[33m%s:\033[0m%s\n", $$1, $$2}'


.PHONY: check
check: ## Runs all checks
check: cs autoreview test


.PHONY: autoreview
autoreview: 	## Runs the Auto-Review checks
autoreview: cs composer_validate composer_audit rector_lint phpstan phpunit_autoreview

.PHONY: cs
cs: 	 	## Fixes CS
cs: rector php_cs_fixer gitignore_sort composer_normalize

.PHONY: cs_lint
cs_lint:	## Lints CS
cs_lint: rector_lint php_cs_fixer_lint composer_normalize_lint

.PHONY: gitignore_sort
gitignore_sort:
	LC_ALL=C sort -u .gitignore -o .gitignore

.PHONY: composer_validate
composer_validate:
	# TODO: the --strict flag should be used once the warnings are addressed.
	composer validate --ansi

.PHONY: composer_audit
composer_audit:
	composer audit --no-dev --ansi

.PHONY: composer_normalize
composer_normalize:	vendor
	composer normalize

.PHONY: composer_normalize_lint
composer_normalize_lint:	vendor
	composer normalize --dry-run

.PHONY: php_cs_fixer
php_cs_fixer: $(PHP_CS_FIXER_BIN)
	$(PHP_CS_FIXER) fix

.PHONY: php_cs_fixer_lint
php_cs_fixer_lint: $(PHP_CS_FIXER_BIN)
	$(PHP_CS_FIXER) fix --diff --dry-run

.PHONY: rector
rector: $(RECTOR_BIN)
	$(RECTOR)

.PHONY: rector_lint
rector_lint: $(RECTOR_BIN)
	$(RECTOR) --dry-run


.PHONY: test
test: 	 	## Runs all the tests
test: composer_validate phpstan infection

.PHONY: phpunit
phpunit: $(PHPUNIT_BIN)
	$(PHPUNIT) --testsuite=Tests --colors=always

.PHONY: phpunit_autoreview
phpunit_autoreview: $(PHPUNIT_BIN)
	$(PHPUNIT) --testsuite=AutoReview --colors=always

.PHONY: phpunit_infection
phpunit_infection: $(PHPUNIT_BIN) vendor
	$(PHPUNIT_COVERAGE_INFECTION)

.PHONY: phpunit_html
phpunit_html:	## Runs PHPUnit with code coverage with HTML report
phpunit_html: $(PHPUNIT_BIN) vendor
	$(PHPUNIT_COVERAGE_HTML)
	@echo "You can check the report by opening the file \"$(COVERAGE_HTML)/index.html\"."

.PHONY: infection
infection: $(INFECTION_BIN) vendor
	$(INFECTION_WITH_INITIAL_TESTS) --initial-tests-php-options='-dzend_extension=xdebug.so'

.PHONY: _infection
_infection: $(INFECTION_BIN) $(COVERAGE_XML) $(COVERAGE_JUNIT) vendor
	$(INFECTION)


.PHONY: build
build:	## Builds PHPBrew PHAR
build:
	rm $(PHPBREW_PHAR) 2>/dev/null || true
	$(MAKE) _build

.PHONY: _build
_build:
	$(MAKE) $(PHPBREW_PHAR)

install: PHPBREW_PHAR
	$(CP) $(PHPBREW_PHAR) $(INSTALL_PATH)

update/completion:
	bin/phpbrew zsh --bind phpbrew --program phpbrew > completion/zsh/_phpbrew
	bin/phpbrew bash --bind phpbrew --program phpbrew > completion/bash/_phpbrew

test:
	$(TEST)

clean:
	git checkout -- $(PHPBREW_PHAR)

$(PHPBREW_PHAR): vendor \
		$(PHAR_SRC_FILES) \
		box.json.dist \
		.git/HEAD
	box compile
	touch -c $@

PHONY: vendor_install
vendor_install:
	composer install --ansi
	touch -c composer.lock
	touch -c vendor
	touch -c $(PHPUNIT_BIN)

composer.lock: composer.json
	composer update --lock
	touch -c $@
	touch -c $(PHPUNIT_BIN)
vendor: composer.lock
	$(MAKE) vendor_install

$(COMPOSER_BIN_PLUGIN_VENDOR): composer.lock
	$(MAKE) --always-make vendor_install

.PHONY: rector_install
rector_install: $(RECTOR_BIN)

$(RECTOR_BIN): vendor-bin/rector/vendor
	touch -c $@
vendor-bin/rector/vendor: vendor-bin/rector/composer.lock $(COMPOSER_BIN_PLUGIN_VENDOR)
	composer bin rector install --ansi
	touch -c $@
vendor-bin/rector/composer.lock: vendor-bin/rector/composer.json
	composer bin rector update --lock --ansi
	touch -c $@

.PHONY: php_cs_fixer_installg
php_cs_fixer_install: $(PHP_CS_FIXER_BIN)

$(PHP_CS_FIXER_BIN): vendor-bin/php-cs-fixer/vendor
	touch -c $@
vendor-bin/php-cs-fixer/vendor: vendor-bin/php-cs-fixer/composer.lock $(COMPOSER_BIN_PLUGIN_VENDOR)
	composer bin php-cs-fixer install --ansi
	touch -c $@
vendor-bin/php-cs-fixer/composer.lock: vendor-bin/php-cs-fixer/composer.json
	composer bin php-cs-fixer update --lock --ansi
	touch -c $@
