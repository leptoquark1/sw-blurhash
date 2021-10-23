<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Configuration;

use Eyecook\Blurhash\Configuration\Config;
use PHPUnit\Framework\TestCase;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class ConfigTest extends TestCase
{
    public function testMethodValidateComponentValue(): void
    {
        $default = 1;
        $validator = Config::validateComponentValue($default);

        static::assertInstanceOf(\Closure::class, $validator);

        // Valid values
        static::assertEquals(1, $validator(1));
        static::assertEquals(2, $validator(2));
        static::assertEquals(3, $validator(3));
        static::assertEquals(4, $validator(4));
        static::assertEquals(5, $validator(5));
        static::assertEquals(6, $validator(6));
        static::assertEquals(7, $validator(7));
        static::assertEquals(8, $validator(8));
        static::assertEquals(9, $validator(9));

        // Invalid Values
        static::assertEquals($default, $validator(null));
        static::assertEquals($default, $validator(0));
        static::assertEquals($default, $validator(true));
        static::assertEquals($default, $validator('-'));
        static::assertEquals($default, $validator(10));
        static::assertEquals($default, $validator(423));
        static::assertEquals($default, $validator(-45));
        static::assertEquals($default, $validator(new \stdClass()));
    }

    public function testMethodValidateIntegrationModeValue(): void
    {
        $validator = Config::validateIntegrationModeValue();
        $fallback = Config::VALUE_INTEGRATION_MODE_EMULATED;

        static::assertInstanceOf(\Closure::class, $validator);

        // Valid values
        static::assertEquals(Config::VALUE_INTEGRATION_MODE_NONE, $validator(Config::VALUE_INTEGRATION_MODE_NONE));
        static::assertEquals(Config::VALUE_INTEGRATION_MODE_CUSTOM, $validator(Config::VALUE_INTEGRATION_MODE_CUSTOM));
        static::assertEquals(Config::VALUE_INTEGRATION_MODE_EMULATED, $validator(Config::VALUE_INTEGRATION_MODE_EMULATED));

        // Invalid Values
        static::assertEquals($fallback, $validator(null));
        static::assertEquals($fallback, $validator(1));
        static::assertEquals($fallback, $validator('-'));
        static::assertEquals($fallback, $validator(new \stdClass()));
        static::assertEquals($fallback, $validator(true));
        static::assertEquals($fallback, $validator(false));
    }
}
