<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Configuration\Concern;

use Doctrine\DBAL\Connection;
use Eyecook\Blurhash\Configuration\Config;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 * @internal
 *
 * @see \Symfony\Component\DependencyInjection\ContainerAwareTrait
 */
trait DefaultConfigPluginContext
{
    protected static string $FULL_PATH_EXCLUDED_TAGS = Config::PLUGIN_CONFIG_DOMAIN . '.' . Config::PATH_EXCLUDED_TAGS;

    protected function addDefaultExcludedTag(): void
    {
        $existingConfigValue = $this->fetchCurrentExcludedTagConfig();
        // Only add when config value is empty (initial), otherwise it may be removed on purpose
        if (is_array($existingConfigValue) === true) {
            return;
        }

        $id = $this->fetchDefaultExcludedTagId();
        // Skip when id is invalid
        if ($id === null || Uuid::isValid($id) === false) {
            return;
        }

        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->container->get(SystemConfigService::class);

        $systemConfigService->set(
            static::$FULL_PATH_EXCLUDED_TAGS,
            [$id]
        );
    }

    private function fetchCurrentExcludedTagConfig()
    {
        $configValue = $this->_getConnection()->createQueryBuilder()
            ->select(["JSON_UNQUOTE(JSON_EXTRACT(`configuration_value`, '$._value'))"])
            ->from('system_config')
            ->where('configuration_key = :config_key')
            ->setParameter('config_key', static::$FULL_PATH_EXCLUDED_TAGS)
            ->execute()
            ->fetchOne();

        if ($configValue === false) {
            return null;
        }

        try {
            return json_decode($configValue, true, 3, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function fetchDefaultExcludedTagId(): ?string
    {
        $result = $this->_getConnection()->createQueryBuilder()
            ->select(['LOWER(HEX(id)) as id'])
            ->from('tag')
            ->where('name = :tag_name')
            ->setParameter('tag_name', Config::DEFAULT_TAG_NAME)
            ->execute()
            ->fetchOne();

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @throws \RuntimeException
     */
    private function _getConnection(): Connection
    {
        if (property_exists($this, 'connection')) {
            return $this->connection;
        }

        if (property_exists($this, 'container')) {
            return $this->container->get(Connection::class);
        }

        if (method_exists($this, 'getContainer')) {
            return $this->getContainer()->get(Connection::class);
        }

        throw new \RuntimeException('No Connection found in ' . get_class($this));
    }
}
