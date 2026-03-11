# wp-config

[![Build Status](https://img.shields.io/github/actions/workflow/status/roots/wp-config/main.yml?branch=master&logo=github&label=CI&style=flat-square)](https://github.com/roots/wp-config/actions/workflows/main.yml)
[![Packagist Downloads](https://img.shields.io/packagist/dt/roots/wp-config?label=downloads&colorB=2b3072&colorA=525ddc&style=flat-square)](https://packagist.org/packages/roots/wp-config)
[![Follow Roots](https://img.shields.io/badge/follow%20@rootswp-1da1f2?logo=twitter&logoColor=ffffff&message=&style=flat-square)](https://twitter.com/rootswp)
[![Sponsor Roots](https://img.shields.io/badge/sponsor%20roots-525ddc?logo=github&style=flat-square&logoColor=ffffff&message=)](https://github.com/sponsors/roots)

Fluent configuration management for WordPress

```php
$config = Config::make($rootDir)->bootstrapEnv();

$config
    ->env('WP_ENV', 'production')
    ->env('WP_HOME')
    ->set('WP_DEBUG', true)
    ->when($config->get('WP_ENV') === 'development', function($config) {
        $config
            ->set('SAVEQUERIES', true)
            ->set('SCRIPT_DEBUG', true);
    })
    ->apply();
```

- Fluent API for clean, chainable configuration
- Built-in environment variable loading via `vlucas/phpdotenv`
- Conditional configuration with `when()`
- Instance-scoped hook system for extensible configuration

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

$config = Config::make($rootDir)->bootstrapEnv();

$config
    ->set('WP_DEBUG', true)
    ->set('WP_HOME', 'https://example.com')
    ->apply();
```

### Conditional configuration

```php
$config
    ->env('WP_ENV', 'production')
    ->when($config->get('WP_ENV') === 'development', function($config) {
        $config
            ->set('WP_DEBUG', true)
            ->set('SAVEQUERIES', true)
            ->set('SCRIPT_DEBUG', true);
    });
```

### Accessing values

```php
$home = $config->get('WP_HOME');

// With a default value (no exception if key is missing)
$env = $config->get('WP_ENV', 'production');

$config
    ->set('WP_SITEURL', $config->get('WP_HOME') . '/wp')
    ->apply();
```

### Environment variables

The Config class includes built-in support for loading environment variables:

```php
$config->bootstrapEnv(); // Loads .env and .env.local files

$config
    ->env('DB_NAME')
    ->env('DB_USER')
    ->env('DB_HOST', 'localhost')
    ->apply();
```

### Hook system

The Config class includes an instance-scoped hook system for extensible configuration:

```php
// Register a hook
$config->addAction('security_setup', function($config) {
    $config->set('FORCE_SSL_ADMIN', true);
    $config->set('DISALLOW_FILE_EDIT', true);
});

// Execute the hook
$config
    ->set('WP_ENV', 'production')
    ->doAction('security_setup')
    ->apply();
```

#### Automatic `before_apply` hook

The Config class automatically executes any `before_apply` hooks when `apply()` is called. This enables packages to register configuration logic that runs automatically without requiring manual hook calls:

```php
// Package authors can register automatic configuration
$config->addAction('before_apply', function($config) {
    // This runs automatically when apply() is called
    $config->set('AUTOMATIC_CONFIG', 'set by package');
});

// Users just need to call apply() - no manual hook management required
$config
    ->env('WP_HOME')
    ->set('WP_SITEURL', $config->get('WP_HOME') . '/wp')
    ->apply(); // Automatically runs all before_apply hooks
```

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
$config = Config::make($rootDir);
$config
    ->set('WP_DEBUG', true)
    ->env('WP_HOME')
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
    ->env('WP_ENV', 'production')
    ->env('WP_HOME')
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
$config = Config::make($rootDir)->bootstrapEnv();
```

### Step 5: Update hook usage

Hooks are now instance methods instead of static methods:

Before:

```php
Config::add_action('before_apply', function($config) { ... });
```

After:

```php
$config->addAction('before_apply', function($config) { ... });
```

### Removed APIs

- `Config::remove()` has been removed. Use `when()` blocks to conditionally set values instead.
- `set()` now overwrites previous values for the same key (useful in `when()` blocks for overriding defaults).

## API reference

### Config class

#### `__construct(string $rootDir)`
Creates a new Config instance with the specified root directory.

#### `make(string $rootDir): static`
Creates a new Config instance with a fluent-friendly named constructor.

#### `bootstrapEnv(): self`
Loads environment variables from .env files.

#### `set(string|array $key, mixed $value = null): self`
Sets a configuration value. Accepts a key/value pair or an associative array. Overwrites existing config map entries. Throws `ConstantAlreadyDefinedException` if a PHP constant with that name already exists.

#### `env(string|array $key, mixed $default = null): self`
Sets a configuration value from an environment variable. Falls back to `$default` if the variable is not set.

#### `get(string $key, mixed $default = null): mixed`
Gets a configuration value. Returns `$default` if the key is not set and a default is provided. Throws `UndefinedConfigKeyException` if the key is not set and no default is provided.

#### `when(bool|Closure $condition, callable $callback): self`
Conditionally executes configuration logic. The condition can be a boolean or a Closure that receives the Config instance.

#### `apply(): void`
Applies all configuration values by defining constants. Automatically runs `before_apply` hooks first.

#### `addAction(string $tag, callable $callback, int $priority = 10): self`
Adds a hook callback that can be executed later with `doAction()`. Returns `$this` for chaining.

#### `doAction(string $tag, ...$args): self`
Executes all callbacks registered for the specified hook. Returns `$this` for chaining.

### Exceptions

- `ConstantAlreadyDefinedException`: Thrown when attempting to redefine a constant
- `UndefinedConfigKeyException`: Thrown when accessing an undefined configuration key without a default

## Full example

A complete Bedrock-style `application.php` configuration file:

```php
<?php

use Roots\WPConfig\Config;

$rootDir = dirname(__DIR__);
$webrootDir = $rootDir . '/web';

$config = Config::make($rootDir)->bootstrapEnv()
       ->doAction('config_loaded');

$config
    /**
     * DB settings
     */
    ->env(['DB_NAME', 'DB_USER', 'DB_PASSWORD'])
    ->env('DB_HOST', 'localhost')
    ->set([
        'DB_CHARSET' => 'utf8mb4',
        'DB_COLLATE' => '',
    ])
    ->doAction('database_configured')

    /**
     * URLs
     */
    ->env('WP_HOME')
    ->set('WP_SITEURL', $config->get('WP_HOME') . '/wp')
    ->doAction('urls_configured')

    /**
     * Environment
     */
    ->env('WP_ENV', 'production')
    ->set('WP_ENVIRONMENT_TYPE', $config->get('WP_ENV'))
    ->doAction('environment_loaded')

    /**
     * Content directory
     */
    ->set([
        'CONTENT_DIR' => '/app',
        'WP_CONTENT_DIR' => "{$webrootDir}/app",
        'WP_CONTENT_URL' => $config->get('WP_HOME') . '/app',
    ])

    /**
     * Authentication unique keys and salts
     */
    ->env([
        'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
        'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT',
    ])

    /**
     * Custom settings
     */
    ->set('AUTOMATIC_UPDATER_DISABLED', true)
    ->env('DISABLE_WP_CRON', false)
    ->set('DISALLOW_FILE_EDIT', true)
    ->env('DISALLOW_FILE_MODS', true)
    ->env('WP_POST_REVISIONS', true)

    /**
     * Performance settings
     */
    ->set('CONCATENATE_SCRIPTS', false)

    /**
     * Default debug settings
     */
    ->env('WP_DEBUG', false)
    ->set('WP_DEBUG_DISPLAY', false)
    ->set('WP_DEBUG_LOG', false)
    ->set('SCRIPT_DEBUG', false)

    /**
     * Development settings
     */
    ->when($config->get('WP_ENV') === 'development', function ($config) {
        $config->set([
            'SAVEQUERIES' => true,
            'WP_DEBUG' => true,
            'WP_DEBUG_DISPLAY' => true,
            'WP_DEBUG_LOG' => true,
            'WP_DISABLE_FATAL_ERROR_HANDLER' => true,
            'SCRIPT_DEBUG' => true,
            'DISALLOW_INDEXING' => true,
            'DISALLOW_FILE_MODS' => false,
        ]);
    })

    /**
     * Handle reverse proxy settings
     */
    ->when(
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https',
        function () { $_SERVER['HTTPS'] = 'on'; },
    )

    ->apply();

$config->doAction('after_apply');

$table_prefix = $_ENV['DB_PREFIX'] ?? 'wp_';

if (! defined('ABSPATH')) {
    define('ABSPATH', "{$webrootDir}/wp/");
}

require_once ABSPATH . 'wp-settings.php';
```

## Community

Keep track of development and community news.

- Join us on Discord by [sponsoring us on GitHub](https://github.com/sponsors/roots)
- Join us on [Roots Discourse](https://discourse.roots.io/)
- Follow [@rootswp on Twitter](https://twitter.com/rootswp)
- Follow the [Roots Blog](https://roots.io/blog/)
- Subscribe to the [Roots Newsletter](https://roots.io/subscribe/)
