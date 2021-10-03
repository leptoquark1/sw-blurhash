<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Controller;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Controller\AbstractApiController;
use Eyecook\Blurhash\Exception\IllegalManualModeLeverageException;
use Eyecook\Blurhash\Test\ConfigMockStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @group Controller
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class AbstractApiControllerTest extends TestCase
{
    use ConfigMockStub;

    private TestApiController $mock;

    protected function setUp(): void
    {
        $this->setUpSystemConfigService();
        $this->resetInternalSystemConfigCache();
        $this->resetInternalConfigCache();
        $this->unsetAdminWorkerEnabledMock();

        $this->mock = new TestApiController($this->getContainer());
    }

    public function testPreventManualModeLeverageMethodThrows(): void
    {
        $this->setAdminWorkerEnabledMock(false);
        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, true);

        $this->expectException(IllegalManualModeLeverageException::class);
        $this->mock->testPreventManualModeLeverage();
    }

    public function testPreventManualModeLeverageMethodNotThrows(): void
    {
        $this->expectNotToPerformAssertions();

        $this->setAdminWorkerEnabledMock(true);
        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, false);

        $this->mock->testPreventManualModeLeverage();
    }

    public function testGetConfigMethod(): void
    {
        $this->expectNotToPerformAssertions();

        // We may test here if the container is actually called with the ConfigService class,
        // but to expect two times not to throw because the type is declared explicit
        // should be enough
        $this->mock->testGetConfig();
        $this->mock->testGetConfig();
    }
}

/**
 * @internal
 */
class TestApiController extends AbstractApiController
{
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    public function testPreventManualModeLeverage(): void
    {
        $this->preventManualModeLeverage();
    }

    public function testGetConfig(): ConfigService
    {
        return $this->getConfig();
    }
}
