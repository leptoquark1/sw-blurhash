<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test;

use EyeCook\BlurHash\Configuration\Config;
use EyeCook\BlurHash\Configuration\ConfigService;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SystemConfigTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
trait ConfigMockStub
{
    use SystemConfigTestBehaviour, KernelTestBehaviour;

    private static ?bool $initialAdminWorkerEnabled = null;
    private static ?bool $initialProdModeValue = null;
    protected SystemConfigService $systemConfigService;
    protected ConfigService $configService;

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

    protected function setAdminWorkerEnabledMock(bool $value): void
    {
        $reflection = new \ReflectionClass($this->configService);

        $property = $reflection->getProperty('isAdminWorkerEnabled');
        $property->setAccessible(true);

        if (self::$initialAdminWorkerEnabled === null) {
            self::$initialAdminWorkerEnabled = $property->getValue($this->configService);
        }

        $property->setValue($this->configService, $value);
    }

    protected function unsetAdminWorkerEnabledMock(): void
    {
        if (self::$initialAdminWorkerEnabled !== null) {
            $this->setProductionModeMock(self::$initialAdminWorkerEnabled);
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
