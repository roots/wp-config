<?php

declare(strict_types=1);

namespace Roots\WPConfig\Tests;

use PHPUnit\Framework\Attributes\Depends;
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

    public function testSetArray()
    {
        $this->config->set([
            'TEST_1' => 'value1',
            'TEST_2' => 'value2',
        ]);

        $this->assertEquals('value1', $this->config->get('TEST_1'));
        $this->assertEquals('value2', $this->config->get('TEST_2'));
    }

    #[Depends('testBootstrapEnv')]
    public function testEnv()
    {
        $this->withDotEnv();
        $this->config->env('TEST_ENV_VAR');
        $this->config->env('BOGUS_ENV_VAR');
        $this->config->env('BOGUS_ENV_VAR_WITH_DEFAULT', 'default_value');

        $this->assertEquals('test_value', $this->config->get('TEST_ENV_VAR'));
        $this->assertNull($this->config->get('BOGUS_ENV_VAR'));
        $this->assertEquals('default_value', $this->config->get('BOGUS_ENV_VAR_WITH_DEFAULT'));
    }

    #[Depends('testBootstrapEnv')]
    public function testEnvArray()
    {
        $this->withDotEnv(<<<ENV
        TEST_ENV_VAR_1=value1
        TEST_ENV_VAR_2=value2
        ENV);
        $this->config->env(['TEST_ENV_VAR_1', 'TEST_ENV_VAR_2', 'BOGUS_ENV_VAR']);

        $this->assertEquals('value1', $this->config->get('TEST_ENV_VAR_1'));
        $this->assertEquals('value2', $this->config->get('TEST_ENV_VAR_2'));
        $this->assertNull($this->config->get('BOGUS_ENV_VAR'));
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
        $this->withDotEnv();
        $this->assertEquals('test_value', getenv('TEST_ENV_VAR'));
    }

    public function testApplyWithArray()
    {
        $this->config
            ->set([
                'CONFIG_TEST_1' => 'applied1',
                'CONFIG_TEST_2' => 'applied2',
            ])
            ->apply();

        $this->assertTrue(defined('CONFIG_TEST_1'));
        $this->assertTrue(defined('CONFIG_TEST_2'));
        $this->assertEquals('applied1', CONFIG_TEST_1);
        $this->assertEquals('applied2', CONFIG_TEST_2);
    }

    public function testAutomaticBeforeApplyHook()
    {
        $hookExecuted = false;

        Config::add_action('before_apply', function($config) use (&$hookExecuted) {
            $hookExecuted = true;
        });

        $this->config
            ->set('HOOK_TEST', 'value')
            ->apply();

        $this->assertTrue($hookExecuted);
        $this->assertTrue(defined('HOOK_TEST'));
    }

    public function testMultipleBeforeApplyHooksWithPriority()
    {
        $executionOrder = [];

        Config::add_action('before_apply', function($config) use (&$executionOrder) {
            $executionOrder[] = 'second';
        }, 20);

        Config::add_action('before_apply', function($config) use (&$executionOrder) {
            $executionOrder[] = 'first';
        }, 10);

        $this->config
            ->set('PRIORITY_TEST', 'value')
            ->apply();

        $this->assertEquals(['first', 'second'], $executionOrder);
        $this->assertTrue(defined('PRIORITY_TEST'));
    }

    public function testBeforeApplyHookReceivesConfigInstance()
    {
        $receivedConfig = null;

        Config::add_action('before_apply', function($config) use (&$receivedConfig) {
            $receivedConfig = $config;
        });

        $this->config
            ->set('INSTANCE_TEST', 'value')
            ->apply();

        $this->assertSame($this->config, $receivedConfig);
    }

    public function testBeforeApplyHookCanModifyConfig()
    {
        Config::add_action('before_apply', function($config) {
            $config->set('HOOK_ADDED', 'added_by_hook');
        });

        $this->config
            ->set('ORIGINAL_CONFIG', 'original')
            ->apply();

        $this->assertTrue(defined('ORIGINAL_CONFIG'));
        $this->assertTrue(defined('HOOK_ADDED'));
        $this->assertEquals('original', ORIGINAL_CONFIG);
        $this->assertEquals('added_by_hook', HOOK_ADDED);
    }

    protected function withDotEnv(string $env = "TEST_ENV_VAR=test_value\n"): void
    {
        file_put_contents($this->rootDir . '/.env', $env);
        $this->config->bootstrapEnv();
        unlink($this->rootDir . '/.env');
    }
}
