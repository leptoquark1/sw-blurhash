<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Controller;

use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Exception\IllegalManualModeLeverageException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @RouteScope(scopes={"api"})
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class AbstractApiController extends AbstractController
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
