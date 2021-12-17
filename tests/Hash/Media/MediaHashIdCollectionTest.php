<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Hash\Media;

use Eyecook\Blurhash\Hash\Media\MediaHashId;
use Eyecook\Blurhash\Hash\Media\MediaHashIdCollection;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class MediaHashIdCollectionTest extends TestCase
{
    use IntegrationTestBehaviour, HashMediaFixtures;

    protected array $metaDataFixture = ['height' => 1, 'width' => 1, 'blurhash' => '1'];

    public function testFilterForItemsWithoutHash(): void
    {
        $collection = new MediaHashIdCollection([
            ...$this->generateHashIds(5),
            ...$this->generateHashIdWithoutHash(8),
        ]);

        $withoutHash = $collection->filterWithoutHash();

        static::assertEquals(8, $withoutHash->count());
    }

    public function testFilterForItemsWithHash(): void
    {
        $collection = new MediaHashIdCollection([
            ...$this->generateHashIds(12),
            ...$this->generateHashIdWithoutHash(2),
        ]);

        $withHash = $collection->filterWithHash();

        static::assertEquals(12, $withHash->count());
    }

    public function testGetMediaIds(): void
    {
        $mediaHashId1 = new MediaHashId($this->getValidLocalMediaForHash());
        $mediaHashId2 = new MediaHashId($this->getValidLocalMediaForHash());

        $collection = new MediaHashIdCollection([$mediaHashId1, $mediaHashId2]);
        $mediaIds = $collection->getMediaIds();

        self::assertContains($mediaHashId1->getMediaId(), $mediaIds);
        self::assertContains($mediaHashId2->getMediaId(), $mediaIds);
    }

    public function testCreateFromMedia(): void
    {
        $media1 = $this->getValidLocalMediaForHash();
        $media2 = $this->getValidLocalMediaForHash();

        $byArray = MediaHashIdCollection::createFromMedia([$media1, $media2]);
        $byCollection = MediaHashIdCollection::createFromMedia(new MediaCollection([$media1, $media2]));

        self::assertInstanceOf(MediaHashIdCollection::class, $byArray);
        self::assertInstanceOf(MediaHashIdCollection::class, $byCollection);
        self::assertEquals(2, $byArray->count());
        self::assertEquals(2, $byCollection->count());
        self::assertEquals($media1->getId(), $byArray->first()->getMediaId());
        self::assertEquals($media2->getId(), $byArray->last()->getMediaId());
        self::assertEquals($media1->getId(), $byCollection->first()->getMediaId());
        self::assertEquals($media2->getId(), $byCollection->last()->getMediaId());
    }

    private function generateHashIds(int $count, array $options = [], ?MediaEntity $media = null): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $this->makeHashId($options);
        }

        return $result;
    }

    private function makeHashId(array $options = [], ?MediaEntity $media = null): MediaHashId
    {
        if (!$media) {
            $media = new MediaEntity();
        }
        $media->setId(Uuid::randomHex());

        if ($media->getMetaData() === null) {
            $media->setMetaData($this->metaDataFixture);
        }
        $media->assign($options);

        return new MediaHashId($media);
    }

    private function generateHashIdWithoutHash(int $count, array $options = []): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $this->makeHashIdWithoutHash($options);
        }

        return $result;
    }

    private function makeHashIdWithoutHash(array $options = []): MediaHashId
    {
        $media = new MediaEntity();
        $metaData = $this->metaDataFixture;

        unset($metaData['blurhash']);
        $media->setMetaData($metaData);

        return $this->makeHashId($options, $media);
    }
}
