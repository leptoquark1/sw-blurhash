<?php declare(strict_types=1);

namespace EyeCook\BlurHash;

use Doctrine\DBAL\Connection;
use EyeCook\BlurHash\Configuration\Concern\DefaultConfigPluginContext;
use EyeCook\BlurHash\Framework\PluginHelper;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\Validator\Exception\LogicException;

/**
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class EcBlurHash extends Plugin
{
    use DefaultConfigPluginContext;

    public function postInstall(InstallContext $installContext): void
    {
        if ($installContext->isAutoMigrate()) {
            $this->addDefaultExcludedTag();
        }

        if (!\function_exists('imagecreatefromstring')) {
            //This may only indicate as a warning, since the adapter can be overwritten
            throw new LogicException('This Plugin requires GD extension to work without a custom adapter! You may run into errors.');
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
