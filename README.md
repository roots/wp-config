# wp-config ![100% Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)


## Tests

Just run `make` to do everything

We use PHPUnit 5 because it has compatibility for PHP 5.6

[PHPUnit Docs](https://phpunit.de/manual/5.7/en/index.html)

There are 3 test commands

- `make test` run the full test suite from php 5.6 to 7.x
- `make test-fast` run php 7 tests
- `make test-coverage` generate an html test coverage report in `./coverage`
