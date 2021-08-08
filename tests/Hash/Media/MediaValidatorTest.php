<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Hash\Media;

use Eyecook\Blurhash\Hash\Media\MediaValidator;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaType\ImageType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class MediaValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;

    protected Context $context;
    protected MediaValidator $mediaValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Context::createDefaultContext();
        $this->mediaValidator = $this->getContainer()->get(MediaValidator::class);
    }

    public function testValidateWithInvalidInputs(): void
    {
        $this->assertValidateExpectThrow(null);
        $this->assertValidateExpectThrow('null');
        $this->assertValidateExpectThrow(42);
        $this->assertValidateExpectThrow([]);
        $this->assertValidateExpectThrow((object)[]);
        $this->assertValidateExpectThrow(new \Exception());
        $this->assertValidateExpectThrow(new MediaEntity());
    }

    public function testMediaWithoutFile(): void
    {
        static::markTestIncomplete();
    }

    public function testWrongMediaType(): void
    {
        static::markTestIncomplete();
    }

    public function testPrivateMedia(): void
    {
        static::markTestIncomplete();
    }

    public function testMediaExcludedByFolder(): void
    {
        static::markTestIncomplete();
    }

    public function testMediaExcludedByTag(): void
    {
        static::markTestIncomplete();
    }

    public function testMediaFileExtensions(): void
    {
        static::assertTrue($this->mediaValidator->validate($this->getValidMedia())); // Jpg
        static::assertTrue($this->mediaValidator->validate($this->getValidMedia('jpeg')));
        static::assertTrue($this->mediaValidator->validate($this->getValidMedia('png', 'image/png')));
        static::assertTrue($this->mediaValidator->validate($this->getValidMedia('gif', 'image/gif')));

        static::assertFalse($this->mediaValidator->validate($this->getValidMedia('svg', 'image/svg')));
    }

    private function assertValidateExpectThrow($input, string $expectedExceptionClass = \TypeError::class): void
    {
        $this->expectException($expectedExceptionClass);

        static::assertIsNotBool(
            $this->mediaValidator->validate($input),
            'The value should throw "' . $expectedExceptionClass . '"'
        );
    }

    private function getValidMedia($fileExt = 'jpg', $mimeType = 'image/jpeg'): MediaEntity
    {
        $media = new MediaEntity();

        $media->setId(Uuid::randomHex());
        $media->setMetaData(['width' => 1, 'height' => 1, 'blurhash' => '1']);
        $media->setFileName('validBlurhashMedia' . '.' . $fileExt);
        $media->setFileExtension($fileExt);
        $media->setMimeType($mimeType);
        $media->setFileSize(1024);
        $media->setMediaType(new ImageType());
        $media->setPrivate(false);

        return $media;
    }
}
