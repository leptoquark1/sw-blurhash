<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Hash\Media;

use EyeCook\BlurHash\Hash\Media\MediaHashId;
use EyeCook\BlurHash\Test\TestCaseBase\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class MediaHashIdTest extends TestCase
{
    use IntegrationTestBehaviour;
    use HashMediaFixtures;

    protected MediaEntity $media;
    protected array $metaDataFixture;

    protected function setUp(): void
    {
        $this->media = $this->getJpg();
        $this->media->setVersionId(Uuid::randomHex());

        $this->metaDataFixture = [
            'width' => 2400,
            'height' => 1800,
            'blurhash' => '12344556778890',
        ];
        $this->media->setMetaData($this->metaDataFixture);
    }

    public function testMediaIdProperty(): void
    {
        $instance = new MediaHashId($this->media);

        static::assertEquals($instance->getMediaId(), $this->media->getId());
    }

    public function testMediaVersionIdProperty(): void
    {
        $instance = new MediaHashId($this->media);

        static::assertEquals($instance->getMediaVersionId(), $this->media->getVersionId());
    }

    public function testMetaDataProperties(): void
    {
        $instance = new MediaHashId($this->media);

        static::assertEquals($this->metaDataFixture['width'], $instance->getMetaData()->getWidth());
        static::assertEquals($this->metaDataFixture['height'], $instance->getMetaData()->getHeight());
        static::assertEquals($this->metaDataFixture['blurhash'], $instance->getMetaData()->getHash());

        $this->media->setMetaData(array_merge($this->metaDataFixture, [
            'hashOriginWidth' => 800,
            'hashOriginHeight' => 600,
        ]));
        $instance = new MediaHashId($this->media);

        static::assertEquals(800, $instance->getMetaData()->getWidth());
        static::assertEquals(600, $instance->getMetaData()->getHeight());
    }

    public function testMetaDataSerialization(): void
    {
        $this->media->setMetaData(array_merge($this->metaDataFixture, [
            'hashOriginWidth' => 300,
            'hashOriginHeight' => 200,
            'foo' => 'bar',
        ]));
        $instance = new MediaHashId($this->media);

        $serialized = $instance->getMetaData()->jsonSerialize();

        static::assertArrayHasKey('hashOriginWidth', $serialized);
        static::assertArrayHasKey('hashOriginHeight', $serialized);
        static::assertArrayHasKey('blurhash', $serialized);
        static::assertArrayNotHasKey('width', $serialized);
        static::assertArrayNotHasKey('height', $serialized);
        static::assertArrayNotHasKey('foo', $serialized);
        static::assertEquals(300, $serialized['hashOriginWidth']);
        static::assertEquals(200, $serialized['hashOriginHeight']);
        static::assertEquals($this->metaDataFixture['blurhash'], $serialized['blurhash']);
    }
}
