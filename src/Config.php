<?php

declare(strict_types=1);

namespace Roots\WPConfig;

use Closure;
use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Roots\WPConfig\Exceptions\ConstantAlreadyDefinedException;
use Roots\WPConfig\Exceptions\UndefinedConfigKeyException;

class Config
{
    /**
     * @var array<string, mixed>
     */
    protected array $configMap = [];

    /**
     * @var array<string, array<int, array{callback: callable, priority: int}>>
     */
    protected array $hooks = [];

    protected bool $beforeApplyFired = false;

    public function __construct(
        protected string $rootDir,
    ) {}

    /**
     * Load environment variables from .env files
     */
    public function bootstrapEnv(): self
    {
        $envFiles = file_exists($this->rootDir . '/.env.local')
            ? ['.env', '.env.local']
            : ['.env'];

        $repository = RepositoryBuilder::createWithNoAdapters()
            ->addAdapter(EnvConstAdapter::class)
            ->addAdapter(PutenvAdapter::class)
            ->immutable()
            ->make();

        $dotenv = Dotenv::create($repository, $this->rootDir, $envFiles, false);
        $dotenv->load();

        return $this;
    }

    /**
     * Set a configuration value
     *
     * Calling set() with a key that already exists in the config map will
     * overwrite the previous value. This is intentional for use in when()
     * blocks where environment-specific values override defaults.
     *
     * @param  string|array<string, mixed>  $key
     * @return self
     *
     * @throws ConstantAlreadyDefinedException
     */
    public function set(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            return $this->setMany($key);
        }

        if ($this->isConstantDefined($key)) {
            throw new ConstantAlreadyDefinedException(
                "Aborted trying to redefine constant '$key'. `define('$key', ...)` has already occurred elsewhere.",
            );
        }

        $this->configMap[$key] = $value;

        return $this;
    }

    /**
     * Set a configuration value from an environment variable
     *
     * Reads from $_ENV then falls back to getenv(). If the environment
     * variable is not defined, the default value will be used.
     *
     * If $key is an array, it will be treated as an indexed array of
     * environment variable names.
     *
     * @param  string|string[]  $key
     * @return self
     *
     * @throws ConstantAlreadyDefinedException
     */
    public function env(string|array $key, mixed $default = null): self
    {
        if (is_array($key)) {
            return $this->envMany($key, $default);
        }

        $value = $_ENV[$key] ?? match (getenv($key)) {
            false => $default,
            default => getenv($key),
        };

        return $this->set($key, $value);
    }

    /**
     * Get a configuration value
     *
     * @throws UndefinedConfigKeyException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->configMap)) {
            if (func_num_args() >= 2) {
                return $default;
            }

            throw new UndefinedConfigKeyException(
                "'$key' has not been defined. Use `set('$key', ...)` first.",
            );
        }

        return $this->configMap[$key];
    }

    /**
     * Conditionally execute configuration logic
     *
     * @param  bool|Closure  $condition
     */
    public function when(bool|Closure $condition, callable $callback): self
    {
        $result = $condition instanceof Closure ? $condition($this) : $condition;

        if ($result) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Add an action hook
     *
     * @param  string  $tag  The hook name
     * @param  callable  $callback  The callback function
     * @param  int  $priority  The priority (lower numbers = higher priority)
     */
    public function add_action(string $tag, callable $callback, int $priority = 10): self
    {
        $this->hooks[$tag][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        return $this;
    }

    /**
     * Execute actions for a hook
     *
     * @param  string  $tag  The hook name
     * @param  mixed  ...$args  Additional arguments to pass to callbacks
     */
    public function do_action(string $tag, mixed ...$args): self
    {
        if ($tag === 'before_apply') {
            $this->beforeApplyFired = true;
        }

        if (! isset($this->hooks[$tag])) {
            return $this;
        }

        $hooks = $this->hooks[$tag];
        usort($hooks, fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($hooks as $hook) {
            $hook['callback']($this, ...$args);
        }

        return $this;
    }

    /**
     * Define all configuration values
     *
     * Automatically executes any registered `before_apply` hooks before
     * defining constants. The guard ensures `before_apply` only fires once
     * per `apply()` call, even if `do_action('before_apply')` was called
     * manually beforehand.
     *
     * @throws ConstantAlreadyDefinedException
     */
    public function apply(): void
    {
        if (! $this->beforeApplyFired) {
            $this->do_action('before_apply');
        }

        $this->beforeApplyFired = false;

        foreach ($this->configMap as $key => $value) {
            if ($this->isConstantDefined($key) && constant($key) !== $value) {
                throw new ConstantAlreadyDefinedException(
                    "Cannot redefine constant '$key' with different value.",
                );
            }
        }

        foreach ($this->configMap as $key => $value) {
            if (! defined($key)) {
                define($key, $value);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $configMap
     */
    protected function setMany(array $configMap): self
    {
        foreach ($configMap as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * @param  string[]  $configMap
     */
    protected function envMany(array $configMap, mixed $default): self
    {
        foreach ($configMap as $key) {
            $this->env($key, $default);
        }

        return $this;
    }

    protected function isConstantDefined(string $key): bool
    {
        return defined($key);
    }
}
