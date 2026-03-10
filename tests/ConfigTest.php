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

    it('overwrites existing config map entries', function () {
        $this->config->set('OVERWRITE_TEST', 'first');
        $this->config->set('OVERWRITE_TEST', 'second');

        expect($this->config->get('OVERWRITE_TEST'))->toBe('second');
    });

    it('throws when constant is already defined', function () {
        define('EXISTING_CONSTANT', 'original');
        $this->config->set('EXISTING_CONSTANT', 'new');
    })->throws(ConstantAlreadyDefinedException::class);
});

describe('get', function () {
    it('throws for undefined key without default', function () {
        $this->config->get('UNDEFINED_KEY');
    })->throws(UndefinedConfigKeyException::class);

    it('returns default for undefined key when default is provided', function () {
        expect($this->config->get('MISSING', 'fallback'))->toBe('fallback');
    });

    it('returns null default for undefined key', function () {
        expect($this->config->get('MISSING', null))->toBeNull();
    });

    it('returns actual value even when default is provided', function () {
        $this->config->set('EXISTS', 'real');
        expect($this->config->get('EXISTS', 'fallback'))->toBe('real');
    });
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

    it('does not treat false from getenv as a value', function () {
        $this->config->env('DEFINITELY_NOT_SET_ENV_VAR', 'the_default');

        expect($this->config->get('DEFINITELY_NOT_SET_ENV_VAR'))->toBe('the_default');
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

    it('accepts a closure condition', function () {
        $this->config->when(function ($config) {
            return true;
        }, function ($config) {
            $config->set('CONDITION_CALLBACK', true);
        });

        expect($this->config->get('CONDITION_CALLBACK'))->toBeTrue();
    });

    it('passes config instance to closure condition', function () {
        $this->config->set('CHECK_KEY', 'yes');

        $this->config->when(
            fn($config) => $config->get('CHECK_KEY') === 'yes',
            fn($config) => $config->set('DERIVED', true),
        );

        expect($this->config->get('DERIVED'))->toBeTrue();
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

    it('detects conflicts at apply time for constants defined after set', function () {
        $this->config->set('LATE_CONFLICT', 'from_config');

        // Simulate a constant defined between set() and apply()
        define('LATE_CONFLICT', 'from_elsewhere');

        $this->config->apply();
    })->throws(ConstantAlreadyDefinedException::class);
});

describe('hooks', function () {
    it('executes before_apply hooks automatically', function () {
        $hookExecuted = false;

        $this->config->add_action('before_apply', function ($config) use (&$hookExecuted) {
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

        $this->config->add_action('before_apply', function ($config) use (&$executionOrder) {
            $executionOrder[] = 'second';
        }, 20);

        $this->config->add_action('before_apply', function ($config) use (&$executionOrder) {
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

        $this->config->add_action('before_apply', function ($config) use (&$receivedConfig) {
            $receivedConfig = $config;
        });

        $this->config
            ->set('INSTANCE_TEST', 'value')
            ->apply();

        expect($receivedConfig)->toBe($this->config);
    });

    it('allows hooks to modify config', function () {
        $this->config->add_action('before_apply', function ($config) {
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

    it('does not bleed hooks between instances', function () {
        $hookRan = false;

        $this->config->add_action('custom_hook', function () use (&$hookRan) {
            $hookRan = true;
        });

        $other = new Config($this->rootDir);
        $other->do_action('custom_hook');

        expect($hookRan)->toBeFalse();
    });

    it('only fires before_apply once per apply call even if do_action is called manually', function () {
        $count = 0;

        $this->config->add_action('before_apply', function () use (&$count) {
            $count++;
        });

        $this->config
            ->set('DOUBLE_FIRE_TEST', true)
            ->do_action('before_apply')
            ->apply();

        expect($count)->toBe(1);
        expect(defined('DOUBLE_FIRE_TEST'))->toBeTrue();
    });

    it('fires before_apply again on subsequent apply calls', function () {
        $count = 0;

        $this->config->add_action('before_apply', function () use (&$count) {
            $count++;
        });

        $this->config->set('REAPPLY_TEST', 'value')->apply();
        expect($count)->toBe(1);

        $this->config->apply();
        expect($count)->toBe(2);
    });

    it('supports add_action chaining', function () {
        $order = [];

        $this->config
            ->add_action('before_apply', function () use (&$order) {
                $order[] = 'a';
            })
            ->add_action('before_apply', function () use (&$order) {
                $order[] = 'b';
            })
            ->set('CHAIN_TEST', true)
            ->apply();

        expect($order)->toBe(['a', 'b']);
        expect(defined('CHAIN_TEST'))->toBeTrue();
    });
});
