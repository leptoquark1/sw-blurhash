<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Controller;

use EyeCook\BlurHash\Configuration\Config;
use EyeCook\BlurHash\Configuration\ConfigService;
use EyeCook\BlurHash\Controller\AbstractApiController;
use EyeCook\BlurHash\Exception\IllegalManualModeLeverageException;
use EyeCook\BlurHash\Test\ConfigMockStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @group Controller
 *
 * @package EyeCook\BlurHash\Test
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
