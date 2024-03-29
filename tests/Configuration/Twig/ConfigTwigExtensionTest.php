<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Configuration\Twig;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Configuration\Twig\ConfigTwigExtension;
use Eyecook\Blurhash\Test\ConfigMockStub;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class ConfigTwigExtensionTest extends TestCase
{
    use ConfigMockStub;

    protected ?ConfigTwigExtension $twigExtension = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twigExtension = self::getContainer()->get(ConfigTwigExtension::class);
    }

    protected function tearDown(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_INTEGRATION_MODE);
        $this->unsetSystemConfigMock(Config::PATH_INCLUDE_PRIVATE);
    }

    public function getTestsProvider(): array
    {
        return [
            self::getContainer()->get(ConfigTwigExtension::class)->getTests(),
        ];
    }

    public function getFunctionsProvider(): array
    {
        return [
            self::getContainer()->get(ConfigTwigExtension::class)->getFunctions(),
        ];
    }

    /**
     * @dataProvider getTestsProvider
     */
    public function testGetTestsMethodReturnsArrayOfTwigTests($class): void
    {
        static::assertInstanceOf(TwigTest::class, $class);
    }

    /**
     * @dataProvider getFunctionsProvider
     */
    public function testGetFunctionsMethodReturnsArrayOfTwigFunctions($class): void
    {
        static::assertInstanceOf(TwigFunction::class, $class);
    }

    public function testConfigMethod(): void
    {
        $this->setSystemConfigMock(Config::PATH_INTEGRATION_MODE, Config::VALUE_INTEGRATION_MODE_CUSTOM);
        $this->setSystemConfigMock(Config::PATH_INCLUDE_PRIVATE, false);

        static::assertEquals(Config::VALUE_INTEGRATION_MODE_CUSTOM, $this->twigExtension->config('integrationMode'));
        static::assertEquals(Config::VALUE_INTEGRATION_MODE_CUSTOM, $this->twigExtension->config('getIntegrationMode'));
        static::assertNull($this->twigExtension->config('isIntegrationMode'));

        static::assertFalse($this->twigExtension->config('includedPrivate'));
        static::assertFalse($this->twigExtension->config('isIncludedPrivate'));
        static::assertNull($this->twigExtension->config('getIncludedPrivate'));
    }

    public function testIsConfigConstMethod(): void
    {
        $this->setSystemConfigMock(Config::PATH_INTEGRATION_MODE, Config::VALUE_INTEGRATION_MODE_EMULATED);
        $this->setSystemConfigMock(Config::PATH_INCLUDE_PRIVATE, false);

        static::assertTrue($this->twigExtension->isConfigConst('integrationMode', 'VALUE_INTEGRATION_MODE_EMULATED'));
        static::assertFalse($this->twigExtension->isConfigConst('integrationMode', 'INTEGRATION_MODE_EMULATED'));
    }
}
