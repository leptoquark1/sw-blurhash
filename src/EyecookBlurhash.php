<?php declare(strict_types=1);

namespace Eyecook\Blurhash;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Symfony\Component\Validator\Exception\LogicException;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class EyecookBlurhash extends Plugin
{
    public function postInstall(InstallContext $installContext): void
    {
        if (!\function_exists('imagecreatefromstring')) {
            throw new LogicException('This Plugin requires GD extension to be installed and enabled.');
        }
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
