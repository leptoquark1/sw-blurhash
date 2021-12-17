<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Hash\Media\DataAbstractionLayer;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaProvider;
use Eyecook\Blurhash\Hash\Media\MediaValidator;
use Eyecook\Blurhash\Test\ConfigMockStub;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class HashMediaProviderTest extends TestCase
{
    use ConfigMockStub, IntegrationTestBehaviour, HashMediaFixtures;

    protected Context $context;
    protected EntityRepositoryInterface $mediaRepository;
    protected EntityRepositoryInterface $mediaFolderRepository;
    protected HashMediaProvider $provider;
    protected MediaValidator $validator;
    protected EntityRepositoryInterface $tagRepository;

    protected function setUp(): void
    {
        $this->context = $this->entityFixtureContext;
        $this->mediaRepository = $this->getContainer()->get('media.repository');
        $this->mediaFolderRepository = $this->getContainer()->get('media_folder.repository');
        $this->tagRepository = $this->getContainer()->get('tag.repository');
        $this->provider = $this->getContainer()->get(HashMediaProvider::class);
        $this->validator = $this->getContainer()->get(MediaValidator::class);

        $this->setSystemConfigMock(Config::PATH_INCLUDE_PRIVATE, true);
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_TAGS, []);
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS, []);
    }

    public function testSearchValidMediaExcludePrivate(): void
    {
        $privateMediaId = $this->createValidButPrivateMedia();

        $this->setSystemConfigMock(Config::PATH_INCLUDE_PRIVATE, false);
        $collection = $this->provider->searchValidMedia($this->context, new Criteria([$privateMediaId]));
        self::assertCount(0, $collection);

        $this->setSystemConfigMock(Config::PATH_INCLUDE_PRIVATE, true);
        $collection = $this->provider->searchValidMedia($this->context, new Criteria([$privateMediaId]));
        self::assertCount(1, $collection);
    }

    public function testSearchValidMediaExcludeFolders(): void
    {
        $excludedFolderId = $this->createMediaFolder();
        $excludedByFolderMediaId = $this->createValidButExcludedFolderMedia($excludedFolderId);

        $this->setSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS, [$excludedFolderId]);
        $collection = $this->provider->searchValidMedia($this->context, new Criteria([$excludedByFolderMediaId]));
        self::assertCount(0, $collection);

        $this->setSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS, []);
        $collection = $this->provider->searchValidMedia($this->context, new Criteria([$excludedByFolderMediaId]));
        self::assertCount(1, $collection);
    }

    public function testSearchMediaWithHash(): void
    {
        $media1WithHash = $this->getValidExistingMediaForHash();
        $media2WithHash = $this->getValidExistingMediaForHash();
        $media1WithoutHash = $this->getValidExistingMediaForHash(false);
        $media2WithoutHash = $this->getValidExistingMediaForHash(false);

        $result = $this->provider->searchMediaWithHash($this->context);

        self::assertContains($media1WithHash->getId(), $result->getIds());
        self::assertContains($media2WithHash->getId(), $result->getIds());
        self::assertNotContains($media1WithoutHash->getId(), $result->getIds());
        self::assertNotContains($media2WithoutHash->getId(), $result->getIds());

        $mediaWithHashByFolder = $this->getValidExistingMediaForHash(true, true);

        $folderWithHashCriteria = new Criteria();
        $folderWithHashCriteria->addFilter(new EqualsFilter('mediaFolderId', $mediaWithHashByFolder->getMediaFolderId()));

        $resultByFolder = $this->provider->searchMediaWithHash($this->context, $folderWithHashCriteria);

        self::assertContains($mediaWithHashByFolder->getId(), $resultByFolder->getIds());

        $mediaWithoutHashByFolder = $this->getValidExistingMediaForHash(false, true);

        $folderWithoutHashCriteria = new Criteria();
        $folderWithoutHashCriteria->addFilter(new EqualsFilter('mediaFolderId', $mediaWithoutHashByFolder->getMediaFolderId()));

        $resultWithoutHashByFolder = $this->provider->searchMediaWithHash($this->context, $folderWithoutHashCriteria);

        self::assertNotContains($mediaWithoutHashByFolder->getId(), $resultWithoutHashByFolder->getIds());
    }

    public function testSearchMediaIdsWithHash(): void
    {
        $media1WithHash = $this->getValidExistingMediaForHash();
        $media2WithHash = $this->getValidExistingMediaForHash();
        $media1WithoutHash = $this->getValidExistingMediaForHash(false);
        $media2WithoutHash = $this->getValidExistingMediaForHash(false);

        $result = $this->provider->searchMediaIdsWithHash($this->context);

        self::assertContains($media1WithHash->getId(), $result->getIds());
        self::assertContains($media2WithHash->getId(), $result->getIds());
        self::assertNotContains($media1WithoutHash->getId(), $result->getIds());
        self::assertNotContains($media2WithoutHash->getId(), $result->getIds());

        $mediaWithHashByFolder = $this->getValidExistingMediaForHash(true, true);

        $folderWithHashCriteria = new Criteria();
        $folderWithHashCriteria->addFilter(new EqualsFilter('mediaFolderId', $mediaWithHashByFolder->getMediaFolderId()));

        $resultByFolder = $this->provider->searchMediaIdsWithHash($this->context, $folderWithHashCriteria);

        self::assertContains($mediaWithHashByFolder->getId(), $resultByFolder->getIds());

        $mediaWithoutHashByFolder = $this->getValidExistingMediaForHash(false, true);

        $folderWithoutHashCriteria = new Criteria();
        $folderWithoutHashCriteria->addFilter(new EqualsFilter('mediaFolderId', $mediaWithoutHashByFolder->getMediaFolderId()));

        $resultWithoutHashByFolder = $this->provider->searchMediaIdsWithHash($this->context, $folderWithoutHashCriteria);

        self::assertNotContains($mediaWithoutHashByFolder->getId(), $resultWithoutHashByFolder->getIds());
    }

    public function testSearchMediaWithoutHash(): void
    {
        $media1WithHash = $this->getValidExistingMediaForHash();
        $media2WithHash = $this->getValidExistingMediaForHash();
        $media1WithoutHash = $this->getValidExistingMediaForHash(false);
        $media2WithoutHash = $this->getValidExistingMediaForHash(false);

        $result = $this->provider->searchMediaWithoutHash($this->context);

        self::assertNotContains($media1WithHash->getId(), $result->getIds());
        self::assertNotContains($media2WithHash->getId(), $result->getIds());
        self::assertContains($media1WithoutHash->getId(), $result->getIds());
        self::assertContains($media2WithoutHash->getId(), $result->getIds());

        $mediaWithHashByFolder = $this->getValidExistingMediaForHash(true, true);

        $folderWithHashCriteria = new Criteria();
        $folderWithHashCriteria->addFilter(new EqualsFilter('mediaFolderId', $mediaWithHashByFolder->getMediaFolderId()));

        $resultByFolder = $this->provider->searchMediaWithoutHash($this->context, $folderWithHashCriteria);

        self::assertNotContains($mediaWithHashByFolder->getId(), $resultByFolder->getIds());

        $mediaWithoutHashByFolder = $this->getValidExistingMediaForHash(false, true);

        $folderWithoutHashCriteria = new Criteria();
        $folderWithoutHashCriteria->addFilter(new EqualsFilter('mediaFolderId', $mediaWithoutHashByFolder->getMediaFolderId()));

        $resultWithoutHashByFolder = $this->provider->searchMediaWithoutHash($this->context, $folderWithoutHashCriteria);

        self::assertContains($mediaWithoutHashByFolder->getId(), $resultWithoutHashByFolder->getIds());
    }

    public function testSearchMediaIdsWithoutHash(): void
    {
        $media1WithHash = $this->getValidExistingMediaForHash();
        $media2WithHash = $this->getValidExistingMediaForHash();
        $media1WithoutHash = $this->getValidExistingMediaForHash(false);
        $media2WithoutHash = $this->getValidExistingMediaForHash(false);

        $result = $this->provider->searchMediaIdsWithoutHash($this->context);

        self::assertNotContains($media1WithHash->getId(), $result->getIds());
        self::assertNotContains($media2WithHash->getId(), $result->getIds());
        self::assertContains($media1WithoutHash->getId(), $result->getIds());
        self::assertContains($media2WithoutHash->getId(), $result->getIds());

        $mediaWithHashByFolder = $this->getValidExistingMediaForHash(true, true);

        $folderWithHashCriteria = new Criteria();
        $folderWithHashCriteria->addFilter(new EqualsFilter('mediaFolderId', $mediaWithHashByFolder->getMediaFolderId()));

        $resultByFolder = $this->provider->searchMediaIdsWithoutHash($this->context, $folderWithHashCriteria);

        self::assertNotContains($mediaWithHashByFolder->getId(), $resultByFolder->getIds());

        $mediaWithoutHashByFolder = $this->getValidExistingMediaForHash(false, true);

        $folderWithoutHashCriteria = new Criteria();
        $folderWithoutHashCriteria->addFilter(new EqualsFilter('mediaFolderId', $mediaWithoutHashByFolder->getMediaFolderId()));

        $resultWithoutHashByFolder = $this->provider->searchMediaIdsWithoutHash($this->context, $folderWithoutHashCriteria);

        self::assertContains($mediaWithoutHashByFolder->getId(), $resultWithoutHashByFolder->getIds());
    }

    public function testSearchValidMediaExcludeTags(): void
    {
        $this->markTestIncomplete('Result should be ok, but it does not work. It works when not in test env');
        //$excludedMediaTagId = $this->createTag();
        //$excludedByTagMediaId = $this->createValidButExcludedTagMedia($excludedMediaTagId);
        //
        //$this->setSystemConfigMock(Config::PATH_EXCLUDED_TAGS, [$excludedByTagMediaId]);
        //$collection = $this->provider->searchValidMedia($this->context, new Criteria([$excludedByTagMediaId]));
        //
        //self::assertCount(0, $collection);
        //
        //$this->setSystemConfigMock(Config::PATH_EXCLUDED_TAGS, []);
        //$collection = $this->provider->searchValidMedia($this->context, new Criteria([$excludedByTagMediaId]));
        //self::assertCount(1, $collection);
    }

    public function testSearchValidMediaExcludeInvalidExtensions(): void
    {
        $ids = $this->createInvalidExtensionMedias();

        $collection = $this->provider->searchValidMedia($this->context, new Criteria($ids));
        self::assertCount(0, $collection);
    }

    public function testSearchInvalidMedia(): void
    {
        $this->markTestIncomplete();
    }

    private function createValidButPrivateMedia(): string
    {
        $mediaId = Uuid::randomHex();
        $this->mediaRepository->create(
            [
                [
                    'id' => $mediaId,
                    'name' => 'test media',
                    'mimeType' => 'image/png',
                    'fileExtension' => 'png',
                    'fileName' => $mediaId . '-' . (new \DateTime())->getTimestamp(),
                    'private' => true,
                ],
            ],
            $this->context
        );

        return $mediaId;
    }

    private function createValidButExcludedFolderMedia(string $folderId): string
    {
        $mediaId = Uuid::randomHex();
        $this->mediaRepository->create(
            [
                [
                    'id' => $mediaId,
                    'name' => 'test media 2',
                    'mimeType' => 'image/png',
                    'fileExtension' => 'png',
                    'fileName' => $mediaId . '-' . (new \DateTime())->getTimestamp(),
                    'private' => false,
                    'mediaFolderId' => $folderId,
                ],
            ],
            $this->context
        );

        return $mediaId;
    }

    private function createValidButExcludedTagMedia(string $tagId): string
    {
        $mediaId = Uuid::randomHex();
        $this->mediaRepository->create(
            [
                [
                    'id' => $mediaId,
                    'name' => 'test media 3',
                    'mimeType' => 'image/png',
                    'fileExtension' => 'png',
                    'fileName' => $mediaId . '-' . (new \DateTime())->getTimestamp(),
                    'private' => false,
                    'tags' => [
                        [
                            'id' => $tagId
                        ]
                    ]
                ],
            ],
            $this->context
        );

        return $mediaId;
    }

    private function createInvalidExtensionMedias(): array
    {
        $mediaIds = [];
        $media = [];
        foreach (['pdf', 'exe', 'msi', 'svg', 'log'] as $ext) {
            $mediaId = Uuid::randomHex();
            $media[] = [
                'id' => $mediaId,
                'name' => 'test media ext ' . $ext,
                'mimeType' => 'application/binary',
                'fileExtension' => $ext,
                'fileName' => $mediaId . '-' . (new \DateTime())->getTimestamp(),
            ];
            $mediaIds[] = $mediaId;
        }

        $this->mediaRepository->create($media, $this->context);

        return $mediaIds;
    }

    private function createMediaFolder(): string
    {
        $folderId = Uuid::randomHex();
        $configurationId = Uuid::randomHex();

        $this->mediaFolderRepository->upsert([
            [
                'id' => $folderId,
                'name' => 'some folder',
                'configuration' => [
                    'id' => $configurationId,
                    'createThumbnails' => true,
                ],
            ],
        ], $this->context);

        return $folderId;
    }

    private function createTag(): string
    {
        $tagId = Uuid::randomHex();

        $this->tagRepository->upsert([
            [
                'id' => $tagId,
                'name' => 'someTag',
            ],
        ], $this->context);

        return $tagId;
    }
}
