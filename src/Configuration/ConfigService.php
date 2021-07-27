<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Configuration;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class ConfigService
{
    protected bool $isProductionMode = false;
    protected SystemConfigService $systemConfig;
    protected array $config = [];

    public function __construct(string $appEnv, SystemConfigService $systemConfig)
    {
        $this->isProductionMode = $appEnv === 'prod';
        $this->systemConfig = $systemConfig;
    }

    public function isPluginManualMode(): bool
    {
        return (bool) $this->loadConfig(Config::PATH_MANUAL_MODE);
    }

    public function isIncludedPrivate(): bool
    {
        return (bool) $this->loadConfig(Config::PATH_INCLUDE_PRIVATE, true);
    }

    public function getExcludedFolders(): array
    {
        return $this->loadConfig(Config::PATH_EXCLUDED_FOLDERS, []);
    }

    public function getExcludedTags(): array
    {
        return $this->loadConfig(Config::PATH_EXCLUDED_TAGS, []);
    }

    public function getComponentsX(): int
    {
        return $this->loadConfig(
            Config::PATH_COMPONENTS_X,
            Config::validateComponentValue(4)
        );
    }

    public function getComponentsY(): int
    {
        return $this->loadConfig(
            Config::PATH_COMPONENTS_Y,
            Config::validateComponentValue(3)
        );
    }

    public function getThumbnailThresholdWidth()
    {
        return $this->loadConfig(Config::PATH_THUMB_THRESHOLD_WIDTH, 1920);
    }

    public function getThumbnailThresholdHeight()
    {
        return $this->loadConfig(Config::PATH_THUMB_THRESHOLD_HEIGHT, 1080);
    }

    public function getIntegrationMode(): string
    {
        return $this->loadConfig(
            Config::PATH_INTEGRATION_MODE,
            Config::validateIntegrationModeValue()
        );
    }

    public function isProductionMode(): bool
    {
        return $this->isProductionMode;
    }

    public function getRaw(string $key) {
        return $this->config[$key] ?? $this->systemConfig->get(Config::PLUGIN_CONFIG_DOMAIN . '.' . $key);
    }

    private function loadConfig(string $key, $default = null)
    {
        if (!isset($this->config[$key])) {
            $value = $this->getByConfigKey($key);
            $this->config[$key] = is_callable($default) ? $default($value) : ($value ?? $default);
        }
        return $this->config[$key] ?? null;
    }

    /**
     * @return array|bool|float|int|string|null
     */
    private function getByConfigKey(string $key) {
        return $this->systemConfig->get(Config::PLUGIN_CONFIG_DOMAIN . '.' . $key);
    }
}
