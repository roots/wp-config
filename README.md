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

### Exceptions

- `ConstantAlreadyDefinedException`: Thrown when attempting to redefine a constant
- `UndefinedConfigKeyException`: Thrown when accessing an undefined configuration key
