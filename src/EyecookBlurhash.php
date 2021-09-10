<?php declare(strict_types=1);

namespace Eyecook\Blurhash;

use Doctrine\DBAL\Connection;
use Eyecook\Blurhash\Configuration\Concern\DefaultConfigPluginContext;
use Eyecook\Blurhash\Framework\PluginHelper;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\Validator\Exception\LogicException;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class EyecookBlurhash extends Plugin
{
    use DefaultConfigPluginContext;

    public function postInstall(InstallContext $installContext): void
    {
        if ($installContext->isAutoMigrate()) {
            $this->addDefaultExcludedTag();
        }

        if (!\function_exists('imagecreatefromstring')) {
            throw new LogicException('This Plugin requires GD extension to be installed and enabled.');
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        PluginHelper::rollbackAllMigrations($this, $connection);
    }

    public function executeComposerCommands(): bool
    {
        return true;
    }

    public function rebuildContainer(): bool
    {
        return false;
    }
}
