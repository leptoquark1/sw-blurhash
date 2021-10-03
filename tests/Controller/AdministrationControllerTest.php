<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Controller;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Exception\IllegalManualModeLeverageException;
use Eyecook\Blurhash\Message\GenerateHashMessage;
use Eyecook\Blurhash\Test\ApiEndpointStub;
use Eyecook\Blurhash\Test\ConfigMockStub;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Controller
 * @covers \Eyecook\Blurhash\Controller\AdministrationController
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class AdministrationControllerTest extends TestCase
{
    use ApiEndpointStub,
        HashMediaFixtures,
        ConfigMockStub,
        QueueTestBehaviour;

    protected const VALIDATE_MEDIA_ID_URL = '/api/_action/eyecook/blurhash/validator/media/';
    protected const VALIDATE_FOLDER_ID_URL = '/api/_action/eyecook/blurhash/validator/folder/';
    protected const GENERATE_BY_MEDIA_URL = '/api/_action/eyecook/blurhash/generate/media';

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeMediaFixtures();
        $this->setUpSystemConfigService();
        $this->resetInternalSystemConfigCache();
        $this->unsetAdminWorkerEnabledMock();
        $this->unsetSystemConfigMock(Config::PATH_MANUAL_MODE);
    }

    public function validMediaByEntityProvider(): array
    {
        return [
            [null, null],
            [false, false],
            [true, true],
            [false, true],
        ];
    }

    /**
     * @dataProvider validMediaByEntityProvider
     */
    public function testGenerateByMediaEntityValid(?bool $manualMode, ?bool $enableAdminWorker): void
    {
        if ($manualMode !== null) {
            $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, $manualMode);
        }
        if ($enableAdminWorker !== null) {
            $this->setAdminWorkerEnabledMock($enableAdminWorker);
        }

        $mediaId = Uuid::randomHex();
        ['response' => $response, 'content' => $content] = $this->fetch('GET', static::GENERATE_BY_MEDIA_URL . '/' . $mediaId);
        $message = $this->getMessageFromReceiver(GenerateHashMessage::class);

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);
        static::assertInstanceOf(GenerateHashMessage::class, $message);
        static::assertCount(1, $message->getMediaIds());
        static::assertContains($mediaId, $message->getMediaIds());
    }

    /**
     * @dataProvider validMediaByEntityProvider
     */
    public function testGenerateByMediaEntitiesValid(?bool $manualMode, ?bool $enableAdminWorker): void
    {
        if ($manualMode !== null) {
            $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, $manualMode);
        }
        if ($enableAdminWorker !== null) {
            $this->setAdminWorkerEnabledMock($enableAdminWorker);
        }

        $mediaIds = [Uuid::randomHex(), Uuid::randomHex(), Uuid::randomHex()];
        ['response' => $response, 'content' => $content] = $this->fetch(
            'POST',
            static::GENERATE_BY_MEDIA_URL,
            ['mediaIds' => $mediaIds]
        );
        $message = $this->getMessageFromReceiver(GenerateHashMessage::class);

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);
        static::assertInstanceOf(GenerateHashMessage::class, $message);
        static::assertCount(3, $message->getMediaIds());
        static::assertContains($mediaIds[0], $message->getMediaIds());
        static::assertContains($mediaIds[1], $message->getMediaIds());
        static::assertContains($mediaIds[2], $message->getMediaIds());
    }

    public function invalidMediaByEntityProvider(): array
    {
        return [['GET'], ['POST']];
    }

    /**
     * @dataProvider invalidMediaByEntityProvider
     */
    public function testGenerateByMediaEntityInvalid(string $method): void
    {
        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, true);
        $this->setAdminWorkerEnabledMock(false);

        ['response' => $response, 'content' => $content] = $method === 'POST'
            ? $this->fetch('POST', static::GENERATE_BY_MEDIA_URL, ['mediaIds' => [Uuid::randomHex()]])
            : $this->fetch('GET', static::GENERATE_BY_MEDIA_URL . '/' . Uuid::randomHex());

        static::assertEquals(Response::HTTP_FAILED_DEPENDENCY, $response->getStatusCode(), 'Dependent fail code expected');
        static::assertIsArray($content, 'Error array in response body was expected');
        static::assertArrayHasKey('errors', $content, 'Error array in response body was expected');
        static::assertCount(1, $content['errors'], 'Exactly one error expected');
        static::assertIsArray($content['errors'][0], 'Invalid error array in response errors');
        static::assertArrayHasKey('status', $content['errors'][0], '"status" key is missing in error');
        static::assertArrayHasKey('code', $content['errors'][0], '"code" key is missing in error');
        static::assertEquals(Response::HTTP_FAILED_DEPENDENCY, $content['errors'][0]['status'], 'Invalid status code in error');
        static::assertEquals(IllegalManualModeLeverageException::$ERROR_CODE, $content['errors'][0]['code'], 'Invalid error code in error');
    }

    public function testValidateMediaResponseWithNonExistentEntity(): void
    {
        ['response' => $response] = $this->fetch(
            'GET',
            static::VALIDATE_MEDIA_ID_URL . Uuid::randomHex(),
        );

        static::assertEquals(404, $response->getStatusCode(), 'Non existing Media should return 404');
    }

    public function testValidateMediaResponseWithInvalidEntity(): void
    {
        $invalidMedia = $this->getPdf();
        ['response' => $response, 'content' => $content] = $this->fetch(
            'GET',
            static::VALIDATE_MEDIA_ID_URL . $invalidMedia->getId(),
        );

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
        ['response' => $response, 'content' => $content] = $this->fetch(
            'GET',
            static::VALIDATE_MEDIA_ID_URL . $validMedia->getId(),
        );

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
        ['response' => $response, 'content' => $content] = $this->fetch(
            'GET',
            static::VALIDATE_FOLDER_ID_URL . $nonExistingFolderId,
        );

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

        ['response' => $response, 'content' => $content] = $this->fetch(
            'GET',
            static::VALIDATE_FOLDER_ID_URL . $excluded
        );

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

    private function getMessageFromReceiver(string $className): object
    {
        $envelopes = $this->getReceiver()->get();
        $message = null;
        foreach ($envelopes as $envelope) {
            if (get_class($envelope->getMessage()) === $className) {
                $message = $envelope->getMessage();
                break;
            }
        }

        return $message;
    }
}
