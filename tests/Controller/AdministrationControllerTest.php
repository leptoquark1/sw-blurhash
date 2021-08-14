<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Controller;

use Enqueue\Client\TraceableProducer;
use EyeCook\BlurHash\Configuration\Config;
use EyeCook\BlurHash\Test\TestCaseBase\ConfigServiceTestBehaviour;
use EyeCook\BlurHash\Test\TestCaseBase\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class AdministrationControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour,
        HashMediaFixtures,
        ConfigServiceTestBehaviour;

    protected const VALIDATE_MEDIA_ID_URL = '/api/_action/eyecook/blurhash/validator/media/';
    protected const VALIDATE_FOLDER_ID_URL = '/api/_action/eyecook/blurhash/validator/folder/';

    protected Context $context;
    protected TraceableProducer $traceableProducer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traceableProducer = $this->getContainer()->get('enqueue.client.default.traceable_producer');
        $this->context = Context::createDefaultContext();
        $this->initializeMediaFixtures();
        $this->setUpSystemConfigService();
        $this->resetInternalSystemConfigCache();
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

    public function testValidateMediaResponseWithNonExistentEntity(): void
    {
        ['response' => $response] = $this->getResponseResult('GET', static::VALIDATE_MEDIA_ID_URL . Uuid::randomHex());

        static::assertEquals(404, $response->getStatusCode(), 'Non existing Media should return 404');
    }

    public function testValidateMediaResponseWithInvalidEntity(): void
    {
        $invalidMedia = $this->getPdf();
        ['response' => $response, 'content' => $content] = $this->getResponseResult('GET', static::VALIDATE_MEDIA_ID_URL . $invalidMedia->getId());

        static::assertSame(200, $response->getStatusCode(), 'Invalid but existing Media should return 200');
        static::assertInstanceOf(JsonResponse::class, $response);
        static::assertIsArray($content);
        static::assertArrayHasKey('mediaId', $content);
        static::assertArrayHasKey('valid', $content);
        static::assertArrayHasKey('message', $content);
        static::assertSame($invalidMedia->getId(), $content['mediaId']);
        static::assertFalse($content['valid']);
        static::assertIsString($content['message']);
    }

    public function testValidateMediaResponseWithValidEntity(): void
    {
        $validMedia = $this->getValidExistingMediaForHash();
        ['response' => $response, 'content' => $content] = $this->getResponseResult('GET', static::VALIDATE_MEDIA_ID_URL . $validMedia->getId());

        static::assertSame(200, $response->getStatusCode(), 'Valid Media should return 200');
        static::assertInstanceOf(JsonResponse::class, $response);
        static::assertIsArray($content);
        static::assertArrayHasKey('mediaId', $content);
        static::assertArrayHasKey('valid', $content);
        static::assertSame($validMedia->getId(), $content['mediaId']);
        static::assertTrue($content['valid']);
    }

    public function testValidateFolderResponseWithNonExistingFolder(): void
    {
        $nonExistingFolderId = Uuid::randomHex();
        ['response' => $response, 'content' => $content] = $this->getResponseResult('GET', static::VALIDATE_FOLDER_ID_URL . $nonExistingFolderId);

        static::assertEquals(200, $response->getStatusCode(), 'Even non existing folders should return 200');
        static::assertIsArray($content);
        static::assertArrayHasKey('folderId', $content);
        static::assertArrayHasKey('valid', $content);
        static::assertSame($nonExistingFolderId, $content['folderId']);
        static::assertTrue($content['valid']);
    }

    public function testValidateFolderResponseWithExcludedFolder(): void
    {
        $excluded = Uuid::randomHex();
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS, [$excluded]);

        ['response' => $response, 'content' => $content] = $this->getResponseResult('GET', static::VALIDATE_FOLDER_ID_URL . $excluded);

        static::assertSame(200, $response->getStatusCode(), 'Valid folders should return status 200');
        static::assertInstanceOf(JsonResponse::class, $response);
        static::assertIsArray($content);
        static::assertArrayHasKey('folderId', $content);
        static::assertArrayHasKey('valid', $content);
        static::assertArrayHasKey('message', $content);
        static::assertSame($excluded, $content['folderId']);
        static::assertFalse($content['valid']);
        static::assertIsString($content['message']);
    }

    private function getResponseResult(string $method, string $url, $parameters = []): array
    {
        $this->getBrowser()->request($method, $url, $parameters);
        $response = $this->getBrowser()->getResponse();

        return [
            'response' => $response,
            'content' => (array) json_decode($response->getContent()),
        ];
    }
}
