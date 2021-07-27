<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace Eyecook\Blurhash\Test\TestCaseBase;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Configuration\ConfigService;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SystemConfigTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
trait ConfigServiceTestBehaviour
{
    use IntegrationTestBehaviour;
    use SystemConfigTestBehaviour;

    private static ?bool $initialProdModeValue = null;
    protected SystemConfigService $systemConfigService;
    protected ConfigService $configService;

    /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
    public function setUpSystemConfigService(): void
    {
        $this->systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->configService = $this->getContainer()->get(ConfigService::class);
    }

    protected function setProductionModeMock(bool $value): void
    {
        $reflection = new \ReflectionClass($this->configService);

        $property = $reflection->getProperty('isProductionMode');
        $property->setAccessible(true);

        if (self::$initialProdModeValue === null) {
            self::$initialProdModeValue = $property->getValue($this->configService);
        }

        $property->setValue($this->configService, $value);
    }

    protected function unsetProductionModeMock(): void
    {
        if (self::$initialProdModeValue !== null) {
            $this->setProductionModeMock(self::$initialProdModeValue);
        }
    }

    protected function setSystemConfigMock(string $path, $value): void
    {
        $configKey = Config::PLUGIN_CONFIG_DOMAIN . '.' . $path;
        $this->systemConfigService->set($configKey, $value);
        $this->resetInternalConfigCache($path);
    }

    protected function unsetSystemConfigMock(string $path): void
    {
        $this->setSystemConfigMock($path, null);
    }

    private function resetInternalConfigCache(?string $path = null): void
    {
        $reflection = new \ReflectionClass($this->configService);

        $property = $reflection->getProperty('config');
        $property->setAccessible(true);

        if ($path) {
            $config = $property->getValue($this->configService);
            unset($config[$path]);
            $property->setValue($this->configService, $config);
        } else {
            $property->setValue($this->configService, []);
        }
    }
}
