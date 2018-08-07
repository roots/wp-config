# wp-config

[![Build Status](https://travis-ci.com/roots/wp-config.svg?branch=master)](https://travis-ci.com/roots/wp-config) 
[![Coverage Status](https://coveralls.io/repos/github/roots/wp-config/badge.svg?branch=master)](https://coveralls.io/github/roots/wp-config?branch=master)

```php
Config::define('WP_DEBUG_DISPLAY', false);

Config::define('WP_DEBUG_DISPLAY', true);

Config::apply();

echo WP_DEBUG_DISPLAY;
// true
```

[Why?](./docs/why.md)

## \Roots\WPConfig

- [Config](./src/Config.php)
- Exceptions
  - [ConstantAlreadyDefinedException](./src/Exceptions/ConstantAlreadyDefinedException.php)
  - [UndefinedConfigKeyException](./src/Exceptions/UndefinedConfigKeyException.php)


## Tests

Just run `make` to do everything

We use PHPUnit 5 because it has compatibility for PHP 5.6

[PHPUnit Docs](https://phpunit.de/manual/5.7/en/index.html)

There are 3 test commands

- `make test` run the full test suite from php 5.6 to 7.x
- `make test-fast` run php 7 tests
- `make test-coverage` generate an html test coverage report in `./coverage`
