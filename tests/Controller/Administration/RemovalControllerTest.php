<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Controller\Administration;

use Eyecook\Blurhash\Hash\Filter\HasHashFilter;
use Eyecook\Blurhash\Test\ApiEndpointStub;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * @group Controller
 * @covers \Eyecook\Blurhash\Controller\Administration\RemovalController
 * @req
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class RemovalControllerTest extends TestCase
{
    use ApiEndpointStub, HashMediaFixtures;

    protected const REMOVE_BY_MEDIA_URL = '/api/_action/eyecook/blurhash/remove/media';
    protected const REMOVE_BY_FOLDER_URL = '/api/_action/eyecook/blurhash/remove/folder';
    protected EntityRepository $mediaRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeMediaFixtures();
        $this->mediaRepository = self::getFixtureRepository('media');
    }

    public function testRemoveByMediaId(): void
    {
        $media1 = $this->getValidExistingMediaForHash(true);

        ['response' => $response, 'content' => $content] = $this->fetch('GET', static::REMOVE_BY_MEDIA_URL . '/' . $media1->getId());

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);

        $existingIds = $this->searchExistingHashByIds([$media1->getId()]);

        static::assertNotContains($media1->getId(), $existingIds);
    }

    public function testRemoveByMediaIds(): void
    {
        $media1 = $this->getValidExistingMediaForHash(true);
        $media2 = $this->getValidExistingMediaForHash(true);
        $media3 = $this->getValidExistingMediaForHash(true);

        $mediaIds = [$media1->getId(), $media2->getId(), $media3->getId()];
        ['response' => $response, 'content' => $content] = $this->fetch(
            'POST',
            static::REMOVE_BY_MEDIA_URL,
            ['mediaIds' => $mediaIds]
        );

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);

        $existingIds = $this->searchExistingHashByIds($mediaIds);

        static::assertNotContains($media1->getId(), $existingIds);
        static::assertNotContains($media2->getId(), $existingIds);
        static::assertNotContains($media3->getId(), $existingIds);
    }

    public function testRemoveByMediaFolderId(): void
    {
        $media1 = $this->getValidExistingMediaForHash(true, true);

        ['response' => $response, 'content' => $content] = $this->fetch(
            'GET',
            static::REMOVE_BY_FOLDER_URL . '/' . $media1->getMediaFolderId()
        );

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);

        $existingIds = $this->searchExistingHashByIds([$media1->getId()]);

        static::assertNotContains($media1->getId(), $existingIds);
    }

    public function testRemoveByMediaFolderIds(): void
    {
        $media1 = $this->getValidExistingMediaForHash(true, true);
        $media2 = $this->getValidExistingMediaForHash(true, true);
        $media3 = $this->getValidExistingMediaForHash(true, true);

        $existingIds = $this->searchExistingHashByIds([$media1->getId(), $media2->getId(), $media3->getId()]);

        static::assertContains($media1->getId(), $existingIds);
        static::assertContains($media2->getId(), $existingIds);
        static::assertContains($media3->getId(), $existingIds);

        $folderIds = [$media1->getMediaFolderId(), $media2->getMediaFolderId(), $media3->getMediaFolderId()];
        ['response' => $response, 'content' => $content] = $this->fetch(
            'POST',
            static::REMOVE_BY_FOLDER_URL,
            ['folderIds' => $folderIds]
        );

        static::assertEquals(204, $response->getStatusCode());
        static::assertEmpty($content);

        $existingIds = $this->searchExistingHashByIds([$media1->getId(), $media2->getId(), $media3->getId()]);

        static::assertNotContains($media1->getId(), $existingIds);
        static::assertNotContains($media2->getId(), $existingIds);
        static::assertNotContains($media3->getId(), $existingIds);
    }

    private function searchExistingHashByIds(array $ids): array
    {
        $criteria = new Criteria($ids);
        $criteria->addFilter(new HasHashFilter());

        $result = $this->mediaRepository->searchIds($criteria, $this->entityFixtureContext);

        return $result->getIds();
    }
}
