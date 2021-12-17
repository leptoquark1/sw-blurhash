<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Hash\Media\DataAbstractionLayer;

use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaUpdater;
use Eyecook\Blurhash\Hash\Media\MediaHashId;
use Eyecook\Blurhash\Hash\Media\MediaHashIdCollection;
use Eyecook\Blurhash\Hash\Media\MediaHashMeta;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class HashMediaUpdaterTest extends TestCase
{
    use IntegrationTestBehaviour,
        HashMediaFixtures;

    protected Context $context;
    protected ?HashMediaUpdater $updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Context::createDefaultContext();
        $this->setFixtureContext($this->context);

        $this->updater = $this->getContainer()->get(HashMediaUpdater::class);
        $this->mediaRepository = self::getFixtureRepository('media');
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUpsertMediaHash(): void
    {
        $media = $this->getValidExistingMediaForHash(false, false);

        $hashId = new MediaHashId($media);
        $hashId->getMetaData()->setHash('abc');
        $hashId->getMetaData()->setHeight(800);
        $hashId->getMetaData()->setWidth(1200);

        $this->updater->upsertMediaHash($hashId);

        $media = $this->mediaRepository->search(new Criteria([$media->getId()]), $this->context)->get($media->getId());

        static::assertEquals('abc', $media->getMetaData()[MediaHashMeta::$PROP_HASH]);
        static::assertEquals(800, $media->getMetaData()[MediaHashMeta::$PROP_HEIGHT]);
        static::assertEquals(1200, $media->getMetaData()[MediaHashMeta::$PROP_WIDTH]);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testRemoveMediaHashById(): void
    {
        $mediaBefore = $this->getValidExistingMediaForHash();

        $this->updater->removeMediaHash($mediaBefore->getId());

        $mediaAfter = $this->mediaRepository
            ->search(new Criteria([$mediaBefore->getId()]), $this->context)
            ->get($mediaBefore->getId());

        self::assertCompareMediaHasHash($mediaBefore, $mediaAfter);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testRemoveMediaHashByHashMediaId(): void
    {
        $mediaBefore = $this->getValidExistingMediaForHash();
        $hashId = new MediaHashId($mediaBefore);

        $this->updater->removeMediaHash($hashId);

        $mediaAfter = $this->mediaRepository
            ->search(new Criteria([$mediaBefore->getId()]), $this->context)
            ->get($mediaBefore->getId());

        self::assertCompareMediaHasHash($mediaBefore, $mediaAfter);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testRemoveMediaHashByArray(): void
    {
        $media1Before = $this->getValidExistingMediaForHash();
        $media2Before = $this->getValidExistingMediaForHash();
        $media3Before = $this->getValidExistingMediaForHash();
        $media4Before = $this->getValidExistingMediaForHash();

        $arr = [new MediaHashId($media1Before), $media2Before, $media3Before, new MediaHashId($media4Before)];

        $this->updater->removeMediaHash($arr);

        $searchResult = $this->mediaRepository
            ->search(
                new Criteria([$media1Before->getId(), $media2Before->getId(), $media3Before->getId(), $media4Before->getId()]),
                $this->context
            );

        self::assertCompareMediaHasHash($media1Before, $searchResult->get($media1Before->getId()));
        self::assertCompareMediaHasHash($media2Before, $searchResult->get($media2Before->getId()));
        self::assertCompareMediaHasHash($media3Before, $searchResult->get($media3Before->getId()));
        self::assertCompareMediaHasHash($media4Before, $searchResult->get($media4Before->getId()));
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testRemoveMediaHashByHashMediaIdCollection(): void
    {
        $media1Before = $this->getValidExistingMediaForHash();
        $media2Before = $this->getValidExistingMediaForHash();
        $media3Before = $this->getValidExistingMediaForHash();

        $collection = MediaHashIdCollection::createFromMedia([$media1Before, $media2Before, $media3Before]);

        $this->updater->removeMediaHash($collection);

        $searchResult = $this->mediaRepository
            ->search(new Criteria([$media1Before->getId(), $media2Before->getId(), $media3Before->getId()]), $this->context);

        self::assertCompareMediaHasHash($media1Before, $searchResult->get($media1Before->getId()));
        self::assertCompareMediaHasHash($media2Before, $searchResult->get($media2Before->getId()));
        self::assertCompareMediaHasHash($media3Before, $searchResult->get($media3Before->getId()));
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testRemoveAllMediaHashes(): void
    {
        $media1 = $this->getValidExistingMediaForHash();
        $media2 = $this->getValidExistingMediaForHash();
        $media3 = $this->getValidExistingMediaForHash();

        $this->updater->removeAllMediaHashes();

        $dbResults = $this->mediaRepository
            ->search(new Criteria([$media1->getId(), $media2->getId(), $media3->getId()]), $this->context);

        self::assertCompareMediaHasHash($media1, $dbResults->get($media1->getId()));
        self::assertCompareMediaHasHash($media2, $dbResults->get($media2->getId()));
        self::assertCompareMediaHasHash($media3, $dbResults->get($media3->getId()));
    }

    private static function assertCompareMediaHasHash(MediaEntity $before, MediaEntity $after): void
    {
        self::assertArrayHasKey(MediaHashMeta::$PROP_HASH, $before->getMetaData());
        self::assertArrayHasKey(MediaHashMeta::$PROP_WIDTH, $before->getMetaData());
        self::assertArrayHasKey(MediaHashMeta::$PROP_HEIGHT, $before->getMetaData());

        self::assertArrayNotHasKey(MediaHashMeta::$PROP_HASH, $after->getMetaData());
        self::assertArrayNotHasKey(MediaHashMeta::$PROP_WIDTH, $after->getMetaData());
        self::assertArrayNotHasKey(MediaHashMeta::$PROP_HEIGHT, $after->getMetaData());

        self::assertEquals($before->getId(), $after->getId());
        self::assertCount(3, array_diff_assoc($before->getMetaData(), $after->getMetaData()));
    }
}
