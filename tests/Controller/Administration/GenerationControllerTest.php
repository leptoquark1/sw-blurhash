<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Controller\Administration;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Exception\IllegalManualModeLeverageException;
use Eyecook\Blurhash\Message\GenerateHashMessage;
use Eyecook\Blurhash\Test\ApiEndpointStub;
use Eyecook\Blurhash\Test\ConfigMockStub;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;

/**
 * @group Controller
 * @covers \Eyecook\Blurhash\Controller\Administration\GenerationController
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class GenerationControllerTest extends TestCase
{
    use ApiEndpointStub,
        HashMediaFixtures,
        ConfigMockStub,
        QueueTestBehaviour;

    protected const GENERATE_BY_MEDIA_URL = '/api/_action/eyecook/blurhash/generate/media';
    protected const GENERATE_BY_FOLDER_URL = '/api/_action/eyecook/blurhash/generate/folder';

    protected function setUp(): void
    {
        parent::setUp();

        $this->unsetAdminWorkerEnabledMock();
        $this->unsetSystemConfigMock(Config::PATH_MANUAL_MODE);
        $this->unsetSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS);
        $this->unsetSystemConfigMock(Config::PATH_EXCLUDED_TAGS);
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
    public function testGenerateByFolderValid(?bool $manualMode, ?bool $enableAdminWorker): void
    {
        if ($manualMode !== null) {
            $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, $manualMode);
        }
        if ($enableAdminWorker !== null) {
            $this->setAdminWorkerEnabledMock($enableAdminWorker);
        }
        $mediaWithoutHash = $this->getValidExistingMediaForHash(false, true);
        $mediaWithHash = $this->getValidExistingMediaForHash(true, true);

        $folderId = $mediaWithoutHash->getMediaFolderId();
        ['response' => $response, 'content' => $content] = $this->fetch('GET', static::GENERATE_BY_FOLDER_URL . '/' . $folderId);
        $message = $this->getMessageFromReceiver(GenerateHashMessage::class);

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);
        static::assertInstanceOf(GenerateHashMessage::class, $message);
        static::assertCount(1, $message->getMediaIds());
        static::assertContains($mediaWithoutHash->getId(), $message->getMediaIds());

        $folderId = $mediaWithHash->getMediaFolderId();
        ['response' => $response, 'content' => $content] = $this->fetch(
            'GET',
            static::GENERATE_BY_FOLDER_URL . '/' . $folderId,
            ['all' => true]
        );
        $message = $this->getMessageFromReceiver(GenerateHashMessage::class);

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);
        static::assertInstanceOf(GenerateHashMessage::class, $message);
        static::assertCount(1, $message->getMediaIds());
        static::assertContains($mediaWithHash->getId(), $message->getMediaIds());
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

    /**
     * @dataProvider validMediaByEntityProvider
     */
    public function testGenerateByFoldersValid(?bool $manualMode, ?bool $enableAdminWorker): void
    {
        if ($manualMode !== null) {
            $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, $manualMode);
        }
        if ($enableAdminWorker !== null) {
            $this->setAdminWorkerEnabledMock($enableAdminWorker);
        }

        $mediaWithoutHash1 = $this->getValidExistingMediaForHash(false, true);
        $mediaWithoutHash2 = $this->getValidExistingMediaForHash(false, true);
        $mediaWithHash = $this->getValidExistingMediaForHash(true, true);

        $folderIds = array_unique([
            $mediaWithoutHash1->getMediaFolderId(),
            $mediaWithoutHash2->getMediaFolderId(),
            $mediaWithHash->getMediaFolderId()
        ]);

        ['response' => $response, 'content' => $content] = $this->fetch(
            'POST',
            static::GENERATE_BY_FOLDER_URL,
            ['folderIds' => $folderIds],
        );
        $message = $this->getMessageFromReceiver(GenerateHashMessage::class);

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);
        static::assertInstanceOf(GenerateHashMessage::class, $message);
        static::assertCount(2, $message->getMediaIds());
        static::assertContains($mediaWithoutHash1->getId(), $message->getMediaIds());
        static::assertContains($mediaWithoutHash2->getId(), $message->getMediaIds());

        ['response' => $response, 'content' => $content] = $this->fetch(
            'POST',
            static::GENERATE_BY_FOLDER_URL,
            ['folderIds' => $folderIds, 'all' => true],
        );
        $message = $this->getMessageFromReceiver(GenerateHashMessage::class);

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);
        static::assertInstanceOf(GenerateHashMessage::class, $message);
        static::assertCount(3, $message->getMediaIds());
        static::assertContains($mediaWithoutHash1->getId(), $message->getMediaIds());
        static::assertContains($mediaWithoutHash2->getId(), $message->getMediaIds());
        static::assertContains($mediaWithHash->getId(), $message->getMediaIds());
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

    /**
     * @dataProvider invalidMediaByEntityProvider
     */
    public function testGenerateByFolderInvalid(string $method): void
    {
        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, true);
        $this->setAdminWorkerEnabledMock(false);

        ['response' => $response, 'content' => $content] = $method === 'POST'
            ? $this->fetch('POST', static::GENERATE_BY_FOLDER_URL, ['folderIds' => [Uuid::randomHex()]])
            : $this->fetch('GET', static::GENERATE_BY_FOLDER_URL . '/' . Uuid::randomHex());

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

    private function getMessageFromReceiver(string $className): ?object
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

    private function getReceiver(): ReceiverInterface
    {
        /** @var ServiceLocator $locator */
        $locator = self::getContainer()->get('messenger.test_receiver_locator');

        return $locator->get('async');
    }
}
