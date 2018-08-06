.PHONY: default test test-fast clean install update init phpcs phpcbf test-coverage

DRUN=docker run --rm -v $(shell pwd):/app -w /app
RUN=${DRUN} php:7-cli
COMPOSER=${DRUN} composer

default: vendor test

composer.lock: composer.json
	${MAKE} update

vendor: composer.lock
	${MAKE} install

test: vendor
	${DRUN} php:5.6 vendor/bin/phpunit
	${DRUN} php:7.0 vendor/bin/phpunit
	${DRUN} php:7.1 vendor/bin/phpunit
	${DRUN} php:7.2 vendor/bin/phpunit
	${DRUN} php:7 vendor/bin/phpunit

test-fast: vendor
	${DRUN} php:7 vendor/bin/phpunit

test-coverage: vendor
	${DRUN} php:7 phpdbg -qrr vendor/bin/phpunit --coverage-html coverage

clean:
	${RUN} rm -rf vendor composer.lock

install:
	${COMPOSER} install --no-ansi --no-interaction --no-progress --no-scripts --optimize-autoloader

update:
	${COMPOSER} composer update

init: clean ${VENDOR_TARGET}

phpcs: vendor
	${RUN} vendor/bin/phpcs src

phpcbf: vendor
	${RUN} php:7-cli vendor/bin/phpcbf src

