<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Configuration;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Test\ConfigMockStub;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @coversDefaultClass \Eyecook\Blurhash\Configuration\ConfigService
 * @covers \Eyecook\Blurhash\Configuration\Config
 * @covers \Eyecook\Blurhash\Configuration\ConfigService
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class ConfigServiceTest extends TestCase
{
    use ConfigMockStub;

    protected const COMPONENTS_X_DEFAULT = 5;
    protected const COMPONENTS_Y_DEFAULT = 4;
    protected const THUMBNAIL_THRESHOLD_WIDTH_DEFAULT = 1400;
    protected const THUMBNAIL_THRESHOLD_HEIGHT_DEFAULT = 1080;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSystemConfigService();
    }

    public function testDefaultConfigurationValueTypes(): void
    {
        $this->resetInternalConfigCache();

        self::assertIsBool($this->configService->isPluginManualMode());
        self::assertIsBool($this->configService->isIncludedPrivate());
        self::assertIsArray($this->configService->getExcludedTags());
        self::assertIsArray($this->configService->getExcludedFolders());
        self::assertIsInt($this->configService->getComponentsX());
        self::assertIsint($this->configService->getComponentsY());
        self::assertIsInt($this->configService->getThumbnailThresholdWidth());
        self::assertIsInt($this->configService->getThumbnailThresholdHeight());
        self::assertIsString($this->configService->getIntegrationMode());
    }

    public function testIsPluginManualModeDefault(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_MANUAL_MODE);

        self::assertFalse($this->configService->isPluginManualMode());
    }

    public function testIsPluginManualModeUsingSystemConfig(): void
    {
        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, true);

        self::assertTrue($this->configService->isPluginManualMode());
    }

    public function testIsIncludedPrivateDefault(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_INCLUDE_PRIVATE);

        self::assertTrue($this->configService->isIncludedPrivate());
    }

    public function testIsIncludedPrivateUsingSystemConfig(): void
    {
        $this->setSystemConfigMock(Config::PATH_INCLUDE_PRIVATE, false);

        self::assertFalse($this->configService->isIncludedPrivate());
    }

    public function testGetExcludedFoldersDefault(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS);
        $default = $this->configService->getExcludedFolders();

        self::assertIsArray($default);
        self::assertCount(0, $default);
    }

    public function testIntegrationModeDefault(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_INTEGRATION_MODE);

        self::assertEquals(Config::VALUE_INTEGRATION_MODE_EMULATED, $this->configService->getIntegrationMode());
    }

    public function testIntegrationModeFallback(): void
    {
        $this->setSystemConfigMock(Config::PATH_INTEGRATION_MODE, PHP_INT_MAX);

        self::assertEquals(Config::VALUE_INTEGRATION_MODE_EMULATED, $this->configService->getIntegrationMode());
    }

    public function testGetExcludedFoldersUsingSystemConfig(): void
    {
        $mock = [Uuid::randomHex(), Uuid::randomHex(), Uuid::randomHex(), Uuid::randomHex()];
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS, $mock);

        $actualResult = $this->configService->getExcludedFolders();

        self::assertIsArray($actualResult);
        self::assertCount(count($mock), $actualResult);

        foreach ($mock as $value) {
            self::assertContains($value, $actualResult);
        }
    }

    public function testGetExcludedTagsDefault(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_EXCLUDED_TAGS);
        $default = $this->configService->getExcludedTags();

        self::assertIsArray($default);
        self::assertCount(0, $default);
    }

    public function testGetExcludedTagsUsingSystemConfig(): void
    {
        $mock = [Uuid::randomHex(), Uuid::randomHex(), Uuid::randomHex()];
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_TAGS, $mock);

        $actualResult = $this->configService->getExcludedTags();

        self::assertIsArray($actualResult);
        self::assertCount(count($mock), $actualResult);

        foreach ($mock as $value) {
            self::assertContains($value, $actualResult);
        }
    }

    public function testGetComponentsXDefault(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_COMPONENTS_X);

        self::assertEquals(self::COMPONENTS_X_DEFAULT, $this->configService->getComponentsX());
    }

    public function testGetComponentsXUsingSystemConfig(): void
    {
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 8);

        self::assertEquals(8, $this->configService->getComponentsX());
    }

    public function testGetComponentsXInvalidValueFallbackDefault(): void
    {
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 12);
        self::assertEquals(self::COMPONENTS_X_DEFAULT, $this->configService->getComponentsX());

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 0);
        self::assertEquals(self::COMPONENTS_X_DEFAULT, $this->configService->getComponentsX());

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, -1);
        self::assertEquals(self::COMPONENTS_X_DEFAULT, $this->configService->getComponentsX());

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, true);
        self::assertEquals(self::COMPONENTS_X_DEFAULT, $this->configService->getComponentsX());

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 'abc');
        self::assertEquals(self::COMPONENTS_X_DEFAULT, $this->configService->getComponentsX());
    }

    public function testGetComponentsYDefault(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_COMPONENTS_Y);

        self::assertEquals(self::COMPONENTS_Y_DEFAULT, $this->configService->getComponentsY());
    }

    public function testGetComponentsYUsingSystemConfig(): void
    {
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 1);

        self::assertEquals(1, $this->configService->getComponentsY());
    }

    public function testGetComponentsYInvalidValueFallbackDefault(): void
    {
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 12);
        self::assertEquals(self::COMPONENTS_Y_DEFAULT, $this->configService->getComponentsY());

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 0);
        self::assertEquals(self::COMPONENTS_Y_DEFAULT, $this->configService->getComponentsY());

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, -1);
        self::assertEquals(self::COMPONENTS_Y_DEFAULT, $this->configService->getComponentsY());

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, true);
        self::assertEquals(self::COMPONENTS_Y_DEFAULT, $this->configService->getComponentsY());

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 'abc');
        self::assertEquals(self::COMPONENTS_Y_DEFAULT, $this->configService->getComponentsY());
    }

    public function testGetThumbnailThresholdWidthDefault(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_THUMB_THRESHOLD_WIDTH);

        self::assertEquals(self::THUMBNAIL_THRESHOLD_WIDTH_DEFAULT, $this->configService->getThumbnailThresholdWidth());
    }

    public function testGetThumbnailThresholdWidthUsingSystemConfig(): void
    {
        $this->setSystemConfigMock(Config::PATH_THUMB_THRESHOLD_WIDTH, 480);

        self::assertEquals(480, $this->configService->getThumbnailThresholdWidth());
    }

    public function testGetThumbnailThresholdHeightDefault(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_THUMB_THRESHOLD_HEIGHT);

        self::assertEquals(self::THUMBNAIL_THRESHOLD_HEIGHT_DEFAULT, $this->configService->getThumbnailThresholdHeight());
    }

    public function testGetThumbnailThresholdHeightUsingSystemConfig(): void
    {
        $this->setSystemConfigMock(Config::PATH_THUMB_THRESHOLD_HEIGHT, 220);

        self::assertEquals(220, $this->configService->getThumbnailThresholdHeight());
    }

    public function testIsProductionModeUsingProperty(): void
    {
        $this->setProductionModeMock(true);
        self::assertTrue($this->configService->isProductionMode());

        $this->setProductionModeMock(false);
        self::assertFalse($this->configService->isProductionMode());
    }

    public function testIsProductionModeIsSetByConstructor(): void
    {
        $configService = new ConfigService('prod', true, $this->systemConfigService);
        self::assertTrue($configService->isProductionMode());

        $configService = new ConfigService('dev', true, $this->systemConfigService);
        self::assertFalse($configService->isProductionMode());

        $configService = new ConfigService('someother', true, $this->systemConfigService);
        self::assertFalse($configService->isProductionMode());
    }

    public function testIsAdminWorkerEnabledUsingProperty(): void
    {
        $this->setAdminWorkerEnabledMock(true);
        self::assertTrue($this->configService->isAdminWorkerEnabled());

        $this->setAdminWorkerEnabledMock(false);
        self::assertFalse($this->configService->isAdminWorkerEnabled());
    }

    /**
     * @covers ::getRaw
     */
    public function testGetRaw(): void
    {
        self::assertEquals(
            $this->configService->getThumbnailThresholdWidth(),
            $this->configService->getRaw(Config::PATH_THUMB_THRESHOLD_WIDTH),
        );
        self::assertSame(
            $this->configService->getExcludedTags(),
            $this->configService->getRaw(Config::PATH_EXCLUDED_TAGS),
            'Should both grab from cache, but object are not same'
        );
        self::assertSame(
            $this->configService->getExcludedFolders(),
            $this->configService->getRaw(Config::PATH_EXCLUDED_FOLDERS),
            'Should both grab from cache, but object are not same'
        );

        $this->setSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS, [Uuid::randomHex()]);
        $expected = $this->configService->getExcludedFolders();
        $this->resetInternalConfigCache(Config::PATH_EXCLUDED_FOLDERS);
        $result = $this->configService->getRaw(Config::PATH_EXCLUDED_FOLDERS);

        self::assertEqualsCanonicalizing($expected, $result);
    }
}
