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
    private static ?string $resource = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$adapter = new GdHashImageAdapter();
        static::$resource = file_get_contents(__DIR__ . '/../fixtures/shopware.jpg');
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        static::$adapter = null;
        static::$resource = null;
    }

    public function testClassImplementsAdapterInterface(): void
    {
        self::assertInstanceOf(HashImageAdapterInterface::class, static::$adapter);
    }

    public function testCreateImage(): void
    {
        $image = static::$adapter->createImage(static::$resource);

        static::assertIsResource($image);

        if ((float) PHP_MAJOR_VERSION >= 8) {
            static::assertInstanceOf(\GdImage::class, $image);
        }
    }

    public function testGetImageColorAt(): void
    {
        $image = static::$adapter->createImage(static::$resource);
        $color = static::$adapter->getImageColorAt($image, 50, 50);

        static::assertArrayHasKey('alpha', $color);
        static::assertArrayHasKey('blue', $color);
        static::assertArrayHasKey('green', $color);
        static::assertArrayHasKey('red', $color);

        static::assertEquals(0, $color['alpha'], 'Wrong alpha value');
        static::assertEquals(120, $color['blue'], 'Wrong blue value');
        static::assertEquals(133, $color['green'], 'Wrong green value');
        static::assertEquals(135, $color['red'], 'Wrong red value');
    }

    public function testGetImageWidth(): void
    {
        $image = static::$adapter->createImage(static::$resource);
        static::assertEquals(1530, static::$adapter->getImageWidth($image));
    }

    public function testGetImageHeight(): void
    {
        $image = static::$adapter->createImage(static::$resource);
        static::assertEquals(1021, static::$adapter->getImageHeight($image));
    }
}
