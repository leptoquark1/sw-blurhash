<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Controller;

use EyeCook\BlurHash\Configuration\ConfigService;
use EyeCook\BlurHash\Exception\IllegalManualModeLeverageException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @RouteScope(scopes={"api"})
 *
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
abstract class AbstractApiController extends AbstractController
{
    private ?ConfigService $config = null;

    /**
     * @throws IllegalManualModeLeverageException
     */
    protected function preventManualModeLeverage(): void
    {
        if ($this->getConfig()->isAdminWorkerEnabled() === false && $this->getConfig()->isPluginManualMode()) {
            throw new IllegalManualModeLeverageException();
        }
    }

    protected function getConfig(): ConfigService
    {
        if ($this->config === null) {
            $this->config = $this->container->get(ConfigService::class);
        }

        return $this->config;
    }
}
