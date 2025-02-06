<?php

declare(strict_types=1);

namespace Roots\WPConfig;

use PHPUnit\Framework\TestCase;
use Roots\WPConfig\Exceptions\ConstantAlreadyDefinedException;
use Roots\WPConfig\Exceptions\UndefinedConfigKeyException;

/**
 * @runTestsInSeparateProcesses
 */
class ConfigTest extends TestCase
{
    public function testDefineHappy()
    {
        Config::define('WP_SCRIPT_DEBUG', true);
        $this->assertEquals(true, Config::get('WP_SCRIPT_DEBUG'));
    }

    public function testDefineSad()
    {
        $this->expectException(ConstantAlreadyDefinedException::class);

        define('WP_SCRIPT_DEBUG', false);
        Config::define('WP_SCRIPT_DEBUG', true);
    }

    public function testApplyHappy()
    {
        Config::define('WP_SCRIPT_DEBUG', true);
        Config::apply();
        $this->assertTrue(WP_SCRIPT_DEBUG);
    }

    public function testApplyRedefine()
    {
        $this->expectException(ConstantAlreadyDefinedException::class);

        Config::define('WP_SCRIPT_DEBUG', true);
        define('WP_SCRIPT_DEBUG', false);
        Config::apply();
    }

    public function testReapply()
    {
        Config::define('WP_SCRIPT_DEBUG', true);
        Config::apply();
        Config::define('AUSTIN', 'PRO');
        Config::apply();

        $this->assertTrue(WP_SCRIPT_DEBUG);
    }

    /* Below are not used in Bedrock */

    public function testGetHappy()
    {
        Config::define('WP_SCRIPT_DEBUG', true);
        Config::define('AUSTIN', 'PRO');
        Config::define('CRAIG', -1);
        Config::define('KALEN', 1);

        $this->assertEquals(true, Config::get('WP_SCRIPT_DEBUG'));
        $this->assertEquals('PRO', Config::get('AUSTIN'));
        $this->assertEquals(-1, Config::get('CRAIG'));
        $this->assertEquals(1, Config::get('KALEN'));
    }

    public function testGetSad()
    {
        $this->expectException(UndefinedConfigKeyException::class);

        Config::get('WORDPRESS_SANITY');
    }

    public function testRemove()
    {
        $this->expectException(UndefinedConfigKeyException::class);

        Config::remove('NEVER_EXISTED');

        Config::define('EXISTS', 'yep');
        Config::remove('EXISTS');
        Config::get('EXISTS');
    }
}
