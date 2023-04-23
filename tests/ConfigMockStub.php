<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Configuration\ConfigService;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
trait ConfigMockStub
{
    use KernelTestBehaviour;

    private static ?bool $initialAdminWorkerEnabled = null;
    private static ?bool $initialProdModeValue = null;

    /**
     * @return SystemConfigService|null
     */
    public function getSystemConfigService(): ?SystemConfigService
    {
        return self::getContainer()->get(SystemConfigService::class);
    }

    public function getConfigService(): ?ConfigService
    {
        return self::getContainer()->get(ConfigService::class);
    }

    /**
     * @before
     */
    public function resetInternalConfigCacheBefore(?string $path = null): void
    {
        $reflection = new \ReflectionClass($this->getConfigService());

        $property = $reflection->getProperty('config');
        $property->setAccessible(true);

        if ($path) {
            $config = $property->getValue($this->getConfigService());
            unset($config[$path]);
            $property->setValue($this->getConfigService(), $config);
        } else {
            $property->setValue($this->getConfigService(), []);
        }
    }

    protected function setProductionModeMock(bool $value): void
    {
        $reflection = new \ReflectionClass($this->getConfigService());

        $property = $reflection->getProperty('isProductionMode');
        $property->setAccessible(true);

        if (self::$initialProdModeValue === null) {
            self::$initialProdModeValue = $property->getValue($this->getConfigService());
        }

        $property->setValue($this->getConfigService(), $value);
    }

    protected function unsetProductionModeMock(): void
    {
        if (self::$initialProdModeValue !== null) {
            $this->setProductionModeMock(self::$initialProdModeValue);
        }
    }

    protected function setAdminWorkerEnabledMock(bool $value): void
    {
        $reflection = new \ReflectionClass($this->getConfigService());

        $property = $reflection->getProperty('isAdminWorkerEnabled');
        $property->setAccessible(true);

        if (self::$initialAdminWorkerEnabled === null) {
            self::$initialAdminWorkerEnabled = $property->getValue($this->getConfigService());
        }

        $property->setValue($this->getConfigService(), $value);
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
        $this->getSystemConfigService()->set($configKey, $value);
        $this->resetInternalConfigCacheBefore($path);
    }

    protected function unsetSystemConfigMock(string $path): void
    {
        $this->setSystemConfigMock($path, null);
    }
}
