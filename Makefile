.PHONY: default clean init phpcs phpcbf
.PHONY: travis-install travis-test travis-coverage travis-phpcs

DRUN=docker run --rm -v $(shell pwd):/app -w /app
RUN=${DRUN} php:7
COMPOSER=${DRUN} composer
COMPOSER_FLAGS=--no-ansi --no-interaction --no-progress --no-scripts --optimize-autoloader --prefer-dist
PHPUNIT=vendor/bin/phpunit
COVERAGE=phpdbg -qrr ${PHPUNIT} --coverage-html coverage --coverage-clover coverage/clover.xml
PHPCS=vendor/bin/phpcs src

default: vendor test

composer.lock: composer.json
	${COMPOSER} update ${COMPOSER_FLAGS}

vendor: composer.lock
	${COMPOSER} install ${COMPOSER_FLAGS}

.PHONY: test-% test test-fast test-coverage
test-%: vendor
	${DRUN} php:$(@:test-%=%) ${PHPUNIT}

test: test-7.1 test-7.2 test-7.3 test-7.4

test-fast: vendor
	${DRUN} php:7 ${PHPUNIT}

test-coverage: vendor
	${RUN} ${COVERAGE}

clean:
	${RUN} rm -rf vendor composer.lock

init: clean vendor

phpcs: vendor
	${RUN} ${PHPCS}

phpcbf: vendor
	${RUN} vendor/bin/phpcbf src

travis-install:
	composer install ${COMPOSER_FLAGS}

travis-test:
	${PHPUNIT}

travis-coverage:
	${COVERAGE}
	vendor/bin/php-coveralls --coverage_clover coverage/clover.xml --json_path coverage/coveralls.json

travis-phpcs:
	${PHPCS}
