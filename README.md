# wp-config

[![Build Status](https://img.shields.io/github/actions/workflow/status/roots/wp-config/main.yml?branch=master&logo=github&label=CI&style=flat-square)](https://github.com/roots/wp-config/actions/workflows/main.yml)
[![Packagist Downloads](https://img.shields.io/packagist/dt/roots/wp-config?label=downloads&colorB=2b3072&colorA=525ddc&style=flat-square)](https://packagist.org/packages/roots/wp-config)
[![Follow Roots](https://img.shields.io/badge/follow%20@rootswp-1da1f2?logo=twitter&logoColor=ffffff&message=&style=flat-square)](https://twitter.com/rootswp)
[![Sponsor Roots](https://img.shields.io/badge/sponsor%20roots-525ddc?logo=github&style=flat-square&logoColor=ffffff&message=)](https://github.com/sponsors/roots)

Fluent configuration management for WordPress

```php
$config = new Config($rootDir);
$config
    ->set('WP_DEBUG', true)
    ->set('WP_HOME', env('WP_HOME'))
    ->when($config->get('WP_ENV') === 'development', function($config) {
        $config
            ->set('SAVEQUERIES', true)
            ->set('SCRIPT_DEBUG', true);
    })
    ->apply();
```

- 🔄 Fluent API for clean, chainable configuration
- 🌍 Built-in environment variable loading
- 🔀 Conditional configuration with `when()`
- 🪝 WordPress-style hook system for extensible configuration
- 📦 Zero dependencies (except `vlucas/phpdotenv`)

## Support us

We're dedicated to pushing modern WordPress development forward through our open source projects, and we need your support to keep building. You can support our work by purchasing [Radicle](https://roots.io/radicle/), our recommended WordPress stack, or by [sponsoring us on GitHub](https://github.com/sponsors/roots). Every contribution directly helps us create better tools for the WordPress ecosystem.

## Requirements

- PHP >= 8.1
- Composer

## Installation

```bash
composer require roots/wp-config:^2.0
```

## Usage

### Basic configuration

```php
use Roots\WPConfig\Config;

$config = new Config($rootDir);
$config->bootstrapEnv();

$config
    ->set('WP_DEBUG', true)
    ->set('WP_HOME', 'https://example.com')
    ->apply();
```

### Conditional configuration

```php
$config
    ->when(env('WP_ENV') === 'development', function($config) {
        $config
            ->set('WP_DEBUG', true)
            ->set('SAVEQUERIES', true)
            ->set('SCRIPT_DEBUG', true);
    });
```

### Accessing values

```php
$home = $config->get('WP_HOME');

$config
    ->set('WP_SITEURL', $config->get('WP_HOME') . '/wp')
    ->apply();
```

### Environment variables

The Config class includes built-in support for loading environment variables:

```php
$config->bootstrapEnv(); // Loads .env and .env.local files

$config
    ->set('DB_NAME', env('DB_NAME'))
    ->set('DB_USER', env('DB_USER'))
    ->apply();
```

### Hook system

The Config class includes a WordPress-style hook system for extensible configuration:

```php
// Register a hook
Config::add_action('security_setup', function($config) {
    $config->set('FORCE_SSL_ADMIN', true);
    $config->set('DISALLOW_FILE_EDIT', true);
});

// Execute the hook
$config
    ->set('WP_ENV', 'production')
    ->do_action('security_setup')
    ->apply();
```

#### Automatic `before_apply` hook

The Config class automatically executes any `before_apply` hooks when `apply()` is called. This enables packages to register configuration logic that runs automatically without requiring manual hook calls:

```php
// Package authors can register automatic configuration
Config::add_action('before_apply', function($config) {
    // This runs automatically when apply() is called
    $config->set('AUTOMATIC_CONFIG', 'set by package');
});

// Users just need to call apply() - no manual hook management required
$config
    ->set('WP_HOME', env('WP_HOME'))
    ->set('WP_SITEURL', env('WP_HOME') . '/wp')
    ->apply(); // Automatically runs all before_apply hooks
```

This pattern is especially useful for packages that need to configure WordPress automatically without requiring users to manually call hooks in their configuration files.

## Upgrading from v1

### Step 1: Update `composer.json`
```json
{
  "require": {
    "roots/wp-config": "^2.0"
  }
}
```

### Step 2: Replace static calls

Before:

```php
Config::define('WP_DEBUG', true);
Config::define('WP_HOME', env('WP_HOME'));
Config::apply();
```

After:

```php
$config = new Config($rootDir);
$config
    ->set('WP_DEBUG', true)
    ->set('WP_HOME', env('WP_HOME'))
    ->apply();
```

### Step 3: Consolidate environment files

Before:

```php
// config/environments/development.php
Config::define('WP_DEBUG', true);
Config::define('SAVEQUERIES', true);

// config/application.php
Config::define('WP_HOME', env('WP_HOME'));
Config::apply();
```

After:

```php
$config
    ->set('WP_HOME', env('WP_HOME'))
    ->when($config->get('WP_ENV') === 'development', function($config) {
        $config
            ->set('WP_DEBUG', true)
            ->set('SAVEQUERIES', true);
    })
    ->apply();
```

### Step 4: Update environment loading

Before:

```php
$dotenv = Dotenv::createImmutable($rootDir);
$dotenv->load();
```

After:

```php
$config = new Config($rootDir);
$config->bootstrapEnv();
```

## API reference

### Config class

#### `__construct(string $rootDir)`
Creates a new Config instance with the specified root directory.

#### `bootstrapEnv(): self`
Loads environment variables from .env files.

#### `set(string $key, mixed $value): self`
Sets a configuration value.

#### `get(string $key): mixed`
Gets a configuration value.

#### `when($condition, callable $callback): self`
Conditionally executes configuration logic.

#### `apply(): void`
Applies all configuration values by defining constants.

#### `add_action(string $tag, callable $callback, int $priority = 10): void`
Adds a hook callback that can be executed later with `do_action()`. Static method.

#### `do_action(string $tag, ...$args): self`
Executes all callbacks registered for the specified hook. Returns `$this` for chaining.

### Exceptions

- `ConstantAlreadyDefinedException`: Thrown when attempting to redefine a constant
- `UndefinedConfigKeyException`: Thrown when accessing an undefined configuration key

## Community

Keep track of development and community news.

- Join us on Discord by [sponsoring us on GitHub](https://github.com/sponsors/roots)
- Join us on [Roots Discourse](https://discourse.roots.io/)
- Follow [@rootswp on Twitter](https://twitter.com/rootswp)
- Follow the [Roots Blog](https://roots.io/blog/)
- Subscribe to the [Roots Newsletter](https://roots.io/subscribe/)
