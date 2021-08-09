<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Controller;

use Enqueue\Client\TraceableProducer;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class AdministrationControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    protected Context $context;
    protected TraceableProducer $traceableProducer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traceableProducer = $this->getContainer()->get('enqueue.client.default.traceable_producer');
        $this->context = Context::createDefaultContext();
    }

    public function testGenerateByMediaEntityReturnCode(): void
    {
        $this->getBrowser()->request('GET', '/api/_action/eyecook/blurhash/generate/media/' . Uuid::randomHex());
        $response = $this->getBrowser()->getResponse();

        static::assertEquals(204, $response->getStatusCode());
    }

    public function testGenerateByMediaEntityMessageDispatched(): void
    {
        $this->markTestSkipped();
    }

    public function testGenerateByMediaEntitiesReturnCode(): void
    {
        $this->getBrowser()->request('POST', '/api/_action/eyecook/blurhash/generate/media', [
            'mediaIds' => [Uuid::randomHex()]
        ]);
        $response = $this->getBrowser()->getResponse();

        static::assertEquals(204, $response->getStatusCode());
    }

    public function testGenerateByMediaEntitiesMessageDispatch(): void
    {
        $this->markTestSkipped();
    }
}
