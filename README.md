# wp-config

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
