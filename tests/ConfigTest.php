<?php

declare(strict_types=1);

use Roots\WPConfig\Config;
use Roots\WPConfig\Exceptions\ConstantAlreadyDefinedException;
use Roots\WPConfig\Exceptions\UndefinedConfigKeyException;

function withDotEnv(Config $config, string $env = "TEST_ENV_VAR=test_value\n"): void
{
    $rootDir = dirname(__DIR__);
    file_put_contents($rootDir . '/.env', $env);
    $config->bootstrapEnv();
    unlink($rootDir . '/.env');
}

beforeEach(function () {
    $this->rootDir = dirname(__DIR__);
    $this->config = new Config($this->rootDir);
});

describe('set', function () {
    it('supports fluent interface', function () {
        $result = $this->config
            ->set('TEST_1', 'value1')
            ->set('TEST_2', 'value2');

        expect($result)->toBeInstanceOf(Config::class);
        expect($this->config->get('TEST_1'))->toBe('value1');
        expect($this->config->get('TEST_2'))->toBe('value2');
    });

    it('accepts an array of key/value pairs', function () {
        $this->config->set([
            'TEST_1' => 'value1',
            'TEST_2' => 'value2',
        ]);

        expect($this->config->get('TEST_1'))->toBe('value1');
        expect($this->config->get('TEST_2'))->toBe('value2');
    });

    it('throws when constant is already defined', function () {
        define('EXISTING_CONSTANT', 'original');
        $this->config->set('EXISTING_CONSTANT', 'new');
    })->throws(ConstantAlreadyDefinedException::class);
});

describe('get', function () {
    it('throws for undefined key', function () {
        $this->config->get('UNDEFINED_KEY');
    })->throws(UndefinedConfigKeyException::class);
});

describe('env', function () {
    it('loads env variables with defaults', function () {
        withDotEnv($this->config);
        $this->config->env('TEST_ENV_VAR');
        $this->config->env('BOGUS_ENV_VAR');
        $this->config->env('BOGUS_ENV_VAR_WITH_DEFAULT', 'default_value');

        expect($this->config->get('TEST_ENV_VAR'))->toBe('test_value');
        expect($this->config->get('BOGUS_ENV_VAR'))->toBeNull();
        expect($this->config->get('BOGUS_ENV_VAR_WITH_DEFAULT'))->toBe('default_value');
    });

    it('accepts an array of env variable names', function () {
        withDotEnv($this->config, <<<ENV
        TEST_ENV_VAR_1=value1
        TEST_ENV_VAR_2=value2
        ENV);
        $this->config->env(['TEST_ENV_VAR_1', 'TEST_ENV_VAR_2', 'BOGUS_ENV_VAR']);

        expect($this->config->get('TEST_ENV_VAR_1'))->toBe('value1');
        expect($this->config->get('TEST_ENV_VAR_2'))->toBe('value2');
        expect($this->config->get('BOGUS_ENV_VAR'))->toBeNull();
    });
});

describe('when', function () {
    it('executes callback when condition is true', function () {
        $this->config->when(true, function ($config) {
            $config->set('CONDITION_TRUE', true);
        });

        expect($this->config->get('CONDITION_TRUE'))->toBeTrue();
    });

    it('skips callback when condition is false', function () {
        $this->config->when(false, function ($config) {
            $config->set('CONDITION_FALSE', true);
        });

        $this->config->get('CONDITION_FALSE');
    })->throws(UndefinedConfigKeyException::class);

    it('accepts a callable condition', function () {
        $this->config->when(function ($config) {
            return true;
        }, function ($config) {
            $config->set('CONDITION_CALLBACK', true);
        });

        expect($this->config->get('CONDITION_CALLBACK'))->toBeTrue();
    });
});

describe('bootstrapEnv', function () {
    it('loads env variables from .env file', function () {
        withDotEnv($this->config);

        expect(getenv('TEST_ENV_VAR'))->toBe('test_value');
    });
});

describe('apply', function () {
    it('defines constants from config values', function () {
        $this->config
            ->set('CONFIG_TEST_1', 'applied1')
            ->set('CONFIG_TEST_2', 'applied2')
            ->apply();

        expect(defined('CONFIG_TEST_1'))->toBeTrue();
        expect(defined('CONFIG_TEST_2'))->toBeTrue();
        expect(CONFIG_TEST_1)->toBe('applied1');
        expect(CONFIG_TEST_2)->toBe('applied2');
    });

    it('defines constants from array config', function () {
        $this->config
            ->set([
                'CONFIG_ARR_1' => 'applied1',
                'CONFIG_ARR_2' => 'applied2',
            ])
            ->apply();

        expect(defined('CONFIG_ARR_1'))->toBeTrue();
        expect(defined('CONFIG_ARR_2'))->toBeTrue();
        expect(CONFIG_ARR_1)->toBe('applied1');
        expect(CONFIG_ARR_2)->toBe('applied2');
    });

    it('throws when constant already exists with different value', function () {
        define('CONFLICT_TEST', 'original');

        $this->config
            ->set('CONFLICT_TEST', 'new')
            ->apply();
    })->throws(ConstantAlreadyDefinedException::class);
});

describe('hooks', function () {
    it('executes before_apply hooks automatically', function () {
        $hookExecuted = false;

        Config::add_action('before_apply', function ($config) use (&$hookExecuted) {
            $hookExecuted = true;
        });

        $this->config
            ->set('HOOK_TEST', 'value')
            ->apply();

        expect($hookExecuted)->toBeTrue();
        expect(defined('HOOK_TEST'))->toBeTrue();
    });

    it('executes hooks in priority order', function () {
        $executionOrder = [];

        Config::add_action('before_apply', function ($config) use (&$executionOrder) {
            $executionOrder[] = 'second';
        }, 20);

        Config::add_action('before_apply', function ($config) use (&$executionOrder) {
            $executionOrder[] = 'first';
        }, 10);

        $this->config
            ->set('PRIORITY_TEST', 'value')
            ->apply();

        expect($executionOrder)->toBe(['first', 'second']);
        expect(defined('PRIORITY_TEST'))->toBeTrue();
    });

    it('passes config instance to hook callbacks', function () {
        $receivedConfig = null;

        Config::add_action('before_apply', function ($config) use (&$receivedConfig) {
            $receivedConfig = $config;
        });

        $this->config
            ->set('INSTANCE_TEST', 'value')
            ->apply();

        expect($receivedConfig)->toBe($this->config);
    });

    it('allows hooks to modify config', function () {
        Config::add_action('before_apply', function ($config) {
            $config->set('HOOK_ADDED', 'added_by_hook');
        });

        $this->config
            ->set('ORIGINAL_CONFIG', 'original')
            ->apply();

        expect(defined('ORIGINAL_CONFIG'))->toBeTrue();
        expect(defined('HOOK_ADDED'))->toBeTrue();
        expect(ORIGINAL_CONFIG)->toBe('original');
        expect(HOOK_ADDED)->toBe('added_by_hook');
    });
});
