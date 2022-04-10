<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Controller\Administration;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Test\ApiEndpointStub;
use Eyecook\Blurhash\Test\ConfigMockStub;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @group Controller
 * @covers \Eyecook\Blurhash\Controller\Administration\ValidationController
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class ValidationControllerTest extends TestCase
{
    use ApiEndpointStub,
        HashMediaFixtures,
        ConfigMockStub;

    protected const VALIDATE_MEDIA_ID_URL = '/api/_action/eyecook/blurhash/validator/media/';
    protected const VALIDATE_FOLDER_ID_URL = '/api/_action/eyecook/blurhash/validator/folder/';

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
}
