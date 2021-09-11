<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Hash\Adapter;
use Eyecook\Blurhash\Hash\Adapter\GdHashImageAdapter;
use Eyecook\Blurhash\Hash\Adapter\HashImageAdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class GdHashImageAdapterTest extends TestCase
{
    private static ?GdHashImageAdapter $adapter = null;
    private static ?string $resourceJpg = null;
    private static ?string $resourceGif = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$adapter = new GdHashImageAdapter();
        static::$resourceJpg = file_get_contents(__DIR__ . '/../fixtures/shopware.jpg');
        static::$resourceGif = file_get_contents(__DIR__ . '/../fixtures/avatar.gif');
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        static::$adapter = null;
        static::$resourceJpg = null;
        static::$resourceGif = null;
    }

    public function testClassImplementsAdapterInterface(): void
    {
        self::assertInstanceOf(HashImageAdapterInterface::class, static::$adapter);
    }

    public function testCreateImage(): void
    {
        $image = static::$adapter->createImage(static::$resourceJpg);

        static::assertIsResource($image);

        if ((float) PHP_MAJOR_VERSION >= 8) {
            static::assertInstanceOf(\GdImage::class, $image);
        }
    }

    public function testGetImageColorAt(): void
    {
        $image = static::$adapter->createImage(static::$resourceJpg);
        $image2 = static::$adapter->createImage(static::$resourceGif);

        // Fine True color
        $color = static::$adapter->getImageColorAt($image, 50, 50);
        static::assertEquals(120, $color[2], 'Wrong blue value');
        static::assertEquals(133, $color[1], 'Wrong green value');
        static::assertEquals(135, $color[0], 'Wrong red value');

        // Wrong True color
        $color2 = static::$adapter->getImageColorAt($image2, 50, 50, true);
        static::assertEquals(243, $color2[2], 'Wrong blue value');
        static::assertEquals(0, $color2[1], 'Wrong green value');
        static::assertEquals(0, $color2[0], 'Wrong red value');

        // Non True Color
        $color3 = static::$adapter->getImageColorAt($image2, 50, 50, false);
        static::assertEquals(145, $color3[2], 'Wrong blue value');
        static::assertEquals(241, $color3[1], 'Wrong green value');
        static::assertEquals(243, $color3[0], 'Wrong red value');

        // False True Color explicit
        $color4 = static::$adapter->getImageTrueColorAt($image2, 50, 50);
        static::assertEquals(243, $color4[2], 'Wrong blue value');
        static::assertEquals(0, $color4[1], 'Wrong green value');
        static::assertEquals(0, $color4[0], 'Wrong red value');

        $color5 = static::$adapter->getImageTrueColorAt($image, 50, 50);
        static::assertEquals(120, $color5[2], 'Wrong blue value');
        static::assertEquals(133, $color5[1], 'Wrong green value');
        static::assertEquals(135, $color5[0], 'Wrong red value');
    }

    public function testGetImageTrueColorAt(): void
    {
        $image = static::$adapter->createImage(static::$resourceJpg);
        $color = static::$adapter->getImageColorAt($image, 50, 50);

        static::assertEquals(120, $color[2], 'Wrong blue value');
        static::assertEquals(133, $color[1], 'Wrong green value');
        static::assertEquals(135, $color[0], 'Wrong red value');
    }

    public function testGetImageWidth(): void
    {
        $image = static::$adapter->createImage(static::$resourceJpg);
        static::assertEquals(1530, static::$adapter->getImageWidth($image));
    }

    public function testGetImageHeight(): void
    {
        $image = static::$adapter->createImage(static::$resourceJpg);
        static::assertEquals(1021, static::$adapter->getImageHeight($image));
    }

    public function testIsLinear(): void
    {
        $image = static::$adapter->createImage(static::$resourceGif);
        $image2 = static::$adapter->createImage(static::$resourceJpg);

        static::assertFalse(static::$adapter->isLinear($image));
        static::assertFalse(static::$adapter->isLinear($image2));
    }

    public function testIsTrueColor(): void
    {
        $image = static::$adapter->createImage(static::$resourceJpg);
        static::assertTrue(static::$adapter->isTrueColor($image));

        $image = static::$adapter->createImage(static::$resourceGif);
        static::assertFalse(static::$adapter->isTrueColor($image));
    }
}
