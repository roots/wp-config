<?php
declare(strict_types=1);

namespace Roots\WPConfig;

use Roots\WPConfig\Exceptions\ConstantAlreadyDefinedException;
use Roots\WPConfig\Exceptions\UndefinedConfigKeyException;

/**
 * Class Config
 * @package Roots\Bedrock
 */
class Config
{
    /**
     * @var array<string, mixed>
     */
    protected static $configMap = [];

    /**
     * @param string $key
     * @param mixed $value
     * @throws ConstantAlreadyDefinedException
     */
    public static function define(string $key, $value): void
    {
        self::defined($key) or self::$configMap[$key] = $value;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws UndefinedConfigKeyException
     */
    public static function get(string $key, $default = null)
    {
        if (!array_key_exists($key, self::$configMap)) {
            if (!empty($default)) {
                return $default;
            }
            
            $class = self::class;
            throw new UndefinedConfigKeyException("'$key' has not been defined. Use `$class::define('$key', ...)`.");
        }
        
        return self::$configMap[$key];
    }
    
    /**
     * @param string $key
     */
    public static function remove(string $key): void
    {
        unset(self::$configMap[$key]);
    }

    /**
     * define() all values in $configMap and throw an exception if we are attempting to redefine a constant.
     *
     * We throw the exception because a silent rejection of a configuration value is unacceptable. This method fails
     * fast before undefined behavior propagates due to unexpected configuration sneaking through.
     *
     * ```
     * define('BEDROCK', 'no');
     * define('BEDROCK', 'yes');
     * echo BEDROCK;
     * // output: 'no'
     * ```
     *
     * vs.
     *
     * ```
     * define('BEDROCK', 'no');
     * Config::define('BEDROCK', 'yes');
     * Config::apply();
     * // output: Fatal error: Uncaught Roots\Bedrock\ConstantAlreadyDefinedException ...
     * ```
     *
     * @throws ConstantAlreadyDefinedException
     */
    public static function apply(): void
    {
        // Scan configMap to see if user is trying to redefine any constants.
        // We do this because we don't want to 'half apply' the configMap. The user should be able to catch the
        // exception, repair their config, and run apply() again
        foreach (self::$configMap as $key => $value) {
            try {
                self::defined($key);
            } catch (ConstantAlreadyDefinedException $e) {
                if (constant($key) !== $value) {
                    throw $e;
                }
            }
        }

        // If all is well, apply the configMap ignoring entries that have already been applied
        foreach (self::$configMap as $key => $value) {
            defined($key) or define($key, $value);
        }
    }
    
    /**
     * @param string $key
     * @return false
     * @throws ConstantAlreadyDefinedException
     */
    protected static function defined(string $key): bool
    {
        if (defined($key)) {
            $message = "Aborted trying to redefine constant '$key'. `define('$key', ...)` has already been occurred elsewhere.";
            throw new ConstantAlreadyDefinedException($message);
        }
        
        return false;
    }
}
