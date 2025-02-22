<?php

declare(strict_types=1);

namespace Roots\WPConfig\Tests;

use PHPUnit\Framework\TestCase;
use Roots\WPConfig\Config;
use Roots\WPConfig\Exceptions\ConstantAlreadyDefinedException;
use Roots\WPConfig\Exceptions\UndefinedConfigKeyException;

/**
 * @runTestsInSeparateProcesses
 */
class ConfigTest extends TestCase
{
    protected string $rootDir;
    protected Config $config;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->config = new Config($this->rootDir);
    }

    public function testFluentInterface()
    {
        $result = $this->config
            ->set('TEST_1', 'value1')
            ->set('TEST_2', 'value2');

        $this->assertInstanceOf(Config::class, $result);
        $this->assertEquals('value1', $this->config->get('TEST_1'));
        $this->assertEquals('value2', $this->config->get('TEST_2'));
    }

    public function testGetUndefinedKey()
    {
        $this->expectException(UndefinedConfigKeyException::class);
        $this->config->get('UNDEFINED_KEY');
    }

    public function testDefineConflict()
    {
        $this->expectException(ConstantAlreadyDefinedException::class);

        define('EXISTING_CONSTANT', 'original');
        $this->config->set('EXISTING_CONSTANT', 'new');
    }

    public function testWhenCondition()
    {
        $this->config
            ->when(true, function ($config) {
                $config->set('CONDITION_TRUE', true);
            })
            ->when(false, function ($config) {
                $config->set('CONDITION_FALSE', true);
            })
            ->when(function ($config) {
                return true;
            }, function ($config) {
                $config->set('CONDITION_CALLBACK', true);
            });

        $this->assertEquals(true, $this->config->get('CONDITION_TRUE'));
        $this->expectException(UndefinedConfigKeyException::class);
        $this->config->get('CONDITION_FALSE');
        $this->assertEquals(true, $this->config->get('CONDITION_CALLBACK'));
    }

    public function testApply()
    {
        $this->config
            ->set('CONFIG_TEST_1', 'applied1')
            ->set('CONFIG_TEST_2', 'applied2')
            ->apply();

        $this->assertTrue(defined('CONFIG_TEST_1'));
        $this->assertTrue(defined('CONFIG_TEST_2'));
        $this->assertEquals('applied1', CONFIG_TEST_1);
        $this->assertEquals('applied2', CONFIG_TEST_2);
    }

    public function testApplyWithConflict()
    {
        $this->expectException(ConstantAlreadyDefinedException::class);

        define('CONFLICT_TEST', 'original');

        $this->config
            ->set('CONFLICT_TEST', 'new')
            ->apply();
    }

    public function testBootstrapEnv()
    {
        // Create temporary .env file
        file_put_contents($this->rootDir . '/.env', "TEST_ENV_VAR=test_value\n");

        $this->config->bootstrapEnv();

        $this->assertEquals('test_value', getenv('TEST_ENV_VAR'));

        // Cleanup
        unlink($this->rootDir . '/.env');
    }
}
