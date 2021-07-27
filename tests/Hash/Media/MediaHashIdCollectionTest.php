<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Hash\Media;

use EyeCook\BlurHash\Hash\Media\MediaHashId;
use EyeCook\BlurHash\Hash\Media\MediaHashIdCollection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class MediaHashIdCollectionTest extends TestCase
{
    use IntegrationTestBehaviour;

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
