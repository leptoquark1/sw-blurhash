<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Hash\Media;

use Eyecook\Blurhash\Hash\Media\MediaHashMeta;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class MediaHashMetaTest extends TestCase
{
    use IntegrationTestBehaviour;

    protected array $metaDataFixture;

    protected function setUp(): void
    {
        $this->metaDataFixture = [
            'width' => 1,
            'height' => 1,
            'blurhash' => '1',
        ];
    }

    public function testSerializedStructDataWhenAssigned(): void
    {
        $instance = new MediaHashMeta();
        $instance->assign($this->metaDataFixture);

        $serialized = $instance->jsonSerialize();

        self::assertArrayHasKey(MediaHashMeta::$PROP_WIDTH, $serialized);
        self::assertArrayHasKey(MediaHashMeta::$PROP_HEIGHT, $serialized);
        self::assertArrayHasKey(MediaHashMeta::$PROP_HASH, $serialized);

        static::assertEquals($this->metaDataFixture['width'], $serialized[MediaHashMeta::$PROP_WIDTH]);
        static::assertEquals($this->metaDataFixture['height'], $serialized[MediaHashMeta::$PROP_HEIGHT]);
        static::assertEquals($this->metaDataFixture['blurhash'], $serialized[MediaHashMeta::$PROP_HASH]);
    }

    public function testGetterWhenAssigned(): void
    {
        $instance = new MediaHashMeta();
        $instance->assign($this->metaDataFixture);

        static::assertEquals($this->metaDataFixture['width'], $instance->getWidth());
        static::assertEquals($this->metaDataFixture['height'], $instance->getHeight());
        static::assertEquals($this->metaDataFixture['blurhash'], $instance->getHash());
    }

    public function testDataWhenUsingSetter(): void
    {
        $instance = new MediaHashMeta();
        $instance->assign($this->metaDataFixture);

        $instance->setWidth($this->metaDataFixture['width']);
        $instance->setHeight($this->metaDataFixture['height']);
        $instance->setHash($this->metaDataFixture['blurhash']);

        static::assertEquals($this->metaDataFixture['width'], $instance->getWidth());
        static::assertEquals($this->metaDataFixture['height'], $instance->getHeight());
        static::assertEquals($this->metaDataFixture['blurhash'], $instance->getHash());
    }
}
