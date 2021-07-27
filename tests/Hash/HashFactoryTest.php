<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Hash;

use EyeCook\BlurHash\Hash\Media\MediaHashIdFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Test\Media\MediaFixtures;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class HashFactoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MediaFixtures;

    protected MediaHashIdFactory $factoryInstance;

    protected function setUp(): void
    {
        parent::setUp();

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->factoryInstance = $this->getContainer()->get(MediaHashIdFactory::class);
    }

    public function testCreateWithEmptyMedia(): void
    {
        $media = $this->getEmptyMedia();
        $hashId = $this->factoryInstance->create($media);

        static::assertNull($hashId->getHash());
        static::assertEquals($hashId->getMediaId(), $media->getId());
        static::assertEquals($hashId->getMediaVersionId(), $media->getVersionId());
    }

    public function testThrowsWhenCreateWithoutMediaId(): void
    {
        $this->expectException(\TypeError::class);

        $media = new MediaEntity();
        $hashId = $this->factoryInstance->create($media);

        static::assertNull($hashId->getMediaId());
    }

    public function testFromMediaWithValidMedia(): void
    {
        $media = $this->getMediaFixture('NamedMimeJpgEtxJpg');

        $hashId = $this->factoryInstance->create($media);

        static::assertEquals($media->getId(), $hashId->getMediaId());
        static::assertNull($hashId->getHash());
        static::assertEquals($hashId->getMediaId(), $media->getId());
        static::assertEquals($hashId->getMediaVersionId(), $media->getVersionId());
    }
}
