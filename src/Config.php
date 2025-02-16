<?php

declare(strict_types=1);

namespace Roots\WPConfig;

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Roots\WPConfig\Exceptions\ConstantAlreadyDefinedException;
use Roots\WPConfig\Exceptions\UndefinedConfigKeyException;

class Config
{
    /**
     * @var array<string, mixed>
     */
    protected array $configMap = [];

    /**
     * @var string
     */
    protected string $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

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
     * @param string $key
     * @param mixed $value
     * @return self
     * @throws ConstantAlreadyDefinedException
     */
    public function set(string $key, $value): self
    {
        if ($this->isConstantDefined($key)) {
            throw new ConstantAlreadyDefinedException(
                "Aborted trying to redefine constant '$key'. `define('$key', ...)` has already occurred elsewhere.",
            );
        }

        $this->configMap[$key] = $value;

        return $this;
    }

    /**
     * Get a configuration value
     *
     * @param string $key
     * @return mixed
     * @throws UndefinedConfigKeyException
     */
    public function get(string $key)
    {
        if (!array_key_exists($key, $this->configMap)) {
            throw new UndefinedConfigKeyException(
                "'$key' has not been defined. Use `set('$key', ...)` first.",
            );
        }

        return $this->configMap[$key];
    }

    /**
     * Conditionally execute configuration logic
     *
     * @param bool|callable $condition
     * @param callable $callback
     * @return self
     */
    public function when($condition, callable $callback): self
    {
        $result = is_callable($condition) ? $condition($this) : $condition;

        if ($result) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Define all configuration values
     *
     * @throws ConstantAlreadyDefinedException
     */
    public function apply(): void
    {
        // Check for any conflicts before applying
        foreach ($this->configMap as $key => $value) {
            if ($this->isConstantDefined($key) && constant($key) !== $value) {
                throw new ConstantAlreadyDefinedException(
                    "Cannot redefine constant '$key' with different value.",
                );
            }
        }

        // Apply all configurations
        foreach ($this->configMap as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }

    /**
     * Check if a constant is already defined
     *
     * @param string $key
     * @return bool
     */
    protected function isConstantDefined(string $key): bool
    {
        return defined($key);
    }
}
