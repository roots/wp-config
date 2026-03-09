# wp-config

[![Packagist Downloads](https://img.shields.io/packagist/dt/roots/wp-config?label=downloads&colorB=2b3072&colorA=525ddc&style=flat-square)](https://packagist.org/packages/roots/wp-config)
[![Coverage Status](https://img.shields.io/coveralls/github/roots/wp-config/master?style=flat-square)](https://coveralls.io/github/roots/wp-config?branch=master)
[![Follow Roots](https://img.shields.io/badge/follow%20@rootswp-1da1f2?logo=twitter&logoColor=ffffff&message=&style=flat-square)](https://twitter.com/rootswp)
[![Sponsor Roots](https://img.shields.io/badge/sponsor%20roots-525ddc?logo=github&style=flat-square&logoColor=ffffff&message=)](https://github.com/sponsors/roots)

```php
Config::define('WP_DEBUG_DISPLAY', false);

Config::define('WP_DEBUG_DISPLAY', true);

Config::apply();

echo WP_DEBUG_DISPLAY;
// true
```

[Why?](./docs/why.md)

## Support us

We're dedicated to pushing modern WordPress development forward through our open source projects, and we need your support to keep building. You can support our work by purchasing [Radicle](https://roots.io/radicle/), our recommended WordPress stack, or by [sponsoring us on GitHub](https://github.com/sponsors/roots). Every contribution directly helps us create better tools for the WordPress ecosystem.

## \Roots\WPConfig

- [Config](./src/Config.php)
- Exceptions
  - [ConstantAlreadyDefinedException](./src/Exceptions/ConstantAlreadyDefinedException.php)
  - [UndefinedConfigKeyException](./src/Exceptions/UndefinedConfigKeyException.php)

## Requirements

This library requires PHP 7.1+. If you need PHP 5.6 support: use [1.0.0](https://github.com/roots/wp-config/tree/1.0.0).

## Tests

Just run `make` to do everything

We use PHPUnit 7 because it has compatibility for PHP 7.1

[PHPUnit Docs](https://phpunit.readthedocs.io/en/7.5/)

There are 3 test commands

- `make test` run the full test suite from php to 7.1 to 7.4
- `make test-fast` run php 7 tests
- `make test-coverage` generate an html test coverage report in `./coverage`

## Community

Keep track of development and community news.

- Join us on Discord by [sponsoring us on GitHub](https://github.com/sponsors/roots)
- Join us on [Roots Discourse](https://discourse.roots.io/)
- Follow [@rootswp on Twitter](https://twitter.com/rootswp)
- Follow the [Roots Blog](https://roots.io/blog/)
- Subscribe to the [Roots Newsletter](https://roots.io/subscribe/)
