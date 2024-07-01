# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

PHPBREW_PHAR  = phpbrew
CP            = cp
INSTALL_PATH  = /usr/local/bin
TEST          = phpunit

PHAR_SRC_FILES := $(shell find bin/ shell/ src/ -type f)

COMPOSER_BIN_PLUGIN_VENDOR = vendor/bamarni/composer-bin-plugin

RECTOR_BIN = vendor-bin/rector/vendor/bin/rector
RECTOR = $(RECTOR_BIN)

PHP_CS_FIXER_BIN = vendor-bin/php-cs-fixer/vendor/bin/php-cs-fixer
PHP_CS_FIXER = $(PHP_CS_FIXER_BIN)


.DEFAULT_GOAL := help


.PHONY: help
help:
	@printf "\033[33mUsage:\033[0m\n  make TARGET\n\n\033[32m#\n# Commands\n#---------------------------------------------------------------------------\033[0m\n\n"
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | awk 'BEGIN {FS = ":"}; {printf "\033[33m%s:\033[0m%s\n", $$1, $$2}'


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

.PHONY: composer_validate
composer_validate:
	# TODO: the --strict flag should be used once the warnings are addressed.
	composer validate --ansi

update/completion:
	bin/phpbrew zsh --bind phpbrew --program phpbrew > completion/zsh/_phpbrew
	bin/phpbrew bash --bind phpbrew --program phpbrew > completion/bash/_phpbrew

.PHONY: rector
rector: $(RECTOR_BIN)
	$(RECTOR)

.PHONY: rector_lint
rector_lint: $(RECTOR_BIN)
	$(RECTOR) --dry-run

.PHONY: php_cs_fixer
php_cs_fixer: $(PHP_CS_FIXER_BIN)
	$(PHP_CS_FIXER) fix

.PHONY: php_cs_fixer_lint
php_cs_fixer_lint: $(PHP_CS_FIXER_BIN)
	$(PHP_CS_FIXER) fix --diff --dry-run

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

composer.lock: composer.json
	composer update --lock
	touch -c $@
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

.PHONY: php-cs-fixer_install
php-cs-fixer_install: $(PHP_CS_FIXER_BIN)

$(PHP_CS_FIXER_BIN): vendor-bin/php-cs-fixer/vendor
	touch -c $@
vendor-bin/php-cs-fixer/vendor: vendor-bin/php-cs-fixer/composer.lock $(COMPOSER_BIN_PLUGIN_VENDOR)
	composer bin php-cs-fixer install --ansi
	touch -c $@
vendor-bin/php-cs-fixer/composer.lock: vendor-bin/php-cs-fixer/composer.json
	composer bin php-cs-fixer update --lock --ansi
	touch -c $@
