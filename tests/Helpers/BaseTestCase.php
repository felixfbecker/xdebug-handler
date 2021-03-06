<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Helpers;

use Composer\XdebugHandler\Mocks\CoreMock;
use PHPUnit\Framework\TestCase;

/**
 * BaseTestCase provides the framework for mock tests by ensuring that core
 * environment variables are unset before each test. It also provides two helper
 * methods to check the state of restarted and non-restarted processes.
 */
abstract class BaseTestCase extends TestCase
{
    private static $env = array();
    private static $argv = array();

    private static $names = array(
        CoreMock::ALLOW_XDEBUG,
        CoreMock::ORIGINAL_INIS,
        'PHP_INI_SCAN_DIR',
    );

    /**
     * Saves the current environment and argv state
     */
    public static function setUpBeforeClass()
    {
        foreach (self::$names as $name) {
            self::$env[$name] = getenv($name);
        }

        self::$argv = $_SERVER['argv'];
    }

    /**
     * Restores the original environment and argv state
     */
    public static function tearDownAfterClass()
    {
        foreach (self::$env as $name => $value) {
            if (false !== $value) {
                putenv($name.'='.$value);
            } else {
                putenv($name);
            }
        }

        $_SERVER['argv'] = self::$argv;
    }

    /**
     * Unsets environment variables for each test and restores argv
     *
     */
    protected function setUp()
    {
        foreach (self::$names as $name) {
            putenv($name);
        }

        $_SERVER['argv'] = self::$argv;
    }

    /**
     * Provides basic assertions for a restarted process
     *
     * @param mixed $xdebug
     */
    protected function checkRestart($xdebug)
    {
        // We must have been restarted
        $this->assertTrue($xdebug->restarted);

        // Env ALLOW_XDEBUG must be unset
        $this->assertSame(false, getenv(CoreMock::ALLOW_XDEBUG));

        // Env ORIGINAL_INIS must be set and be a string
        $this->assertInternalType('string', getenv(CoreMock::ORIGINAL_INIS));

        // Skipped version must match xdebug version, or '' if restart fails
        $class = get_class($xdebug);
        $version = !strpos($class, 'Fail') ? CoreMock::TEST_VERSION : '';
        $this->assertSame($version, $class::getSkippedVersion());
    }

    /**
     * Provides basic assertions for a non-restarted process
     *
     * @param mixed $xdebug
     */
    protected function checkNoRestart($xdebug)
    {
        // We must not have been restarted
        $this->assertFalse($xdebug->restarted);

        // Env ORIGINAL_INIS must not be set
        $this->assertSame(false, getenv(CoreMock::ORIGINAL_INIS));

        // Skipped version must be an empty string
        $class = get_class($xdebug);
        $this->assertSame('', $class::getSkippedVersion());
    }
}
