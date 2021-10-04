<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Hash\Media;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Hash\Media\MediaValidator;
use Eyecook\Blurhash\Test\ConfigMockStub;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaType\AudioType;
use Shopware\Core\Content\Media\MediaType\BinaryType;
use Shopware\Core\Content\Media\MediaType\DocumentType;
use Shopware\Core\Content\Media\MediaType\VideoType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tag\TagCollection;
use Shopware\Core\System\Tag\TagEntity;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class MediaValidatorTest extends TestCase
{
    use IntegrationTestBehaviour,
        ConfigMockStub,
        HashMediaFixtures;

    protected Context $context;
    protected MediaValidator $mediaValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSystemConfigService();
        $this->resetInternalSystemConfigCache();
        $this->resetInternalConfigCache();

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
        $mediaWithoutMimeType = $this->getValidLocalMediaForHash('jpg', null);
        static::assertFalse($this->mediaValidator->validate($mediaWithoutMimeType));

        $mediaWithoutExtension = $this->getValidLocalMediaForHash(null);
        static::assertFalse($this->mediaValidator->validate($mediaWithoutExtension));

        $mediaWithoutFilename = $this->getValidLocalMediaForHash('jpg', 'image/jpeg', ['fileName']);
        static::assertFalse($this->mediaValidator->validate($mediaWithoutFilename));

        $mediaAllInvalid = $this->getValidLocalMediaForHash(null, null, ['fileName']);
        static::assertFalse($this->mediaValidator->validate($mediaAllInvalid));
    }

    public function testWrongMediaType(): void
    {
        $mediaWithInvalidMediaTypeVideo = $this->getValidLocalMediaForHash();
        $mediaWithInvalidMediaTypeVideo->setMediaType(new VideoType);

        static::assertFalse($this->mediaValidator->validate($mediaWithInvalidMediaTypeVideo));

        $mediaWithInvalidMediaTypeDocument = $this->getValidLocalMediaForHash();
        $mediaWithInvalidMediaTypeDocument->setMediaType(new DocumentType);

        static::assertFalse($this->mediaValidator->validate($mediaWithInvalidMediaTypeDocument));

        $mediaWithInvalidMediaTypeBinary = $this->getValidLocalMediaForHash();
        $mediaWithInvalidMediaTypeBinary->setMediaType(new BinaryType);

        static::assertFalse($this->mediaValidator->validate($mediaWithInvalidMediaTypeBinary));

        $mediaWithInvalidMediaTypeAudio = $this->getValidLocalMediaForHash();
        $mediaWithInvalidMediaTypeAudio->setMediaType(new AudioType);

        static::assertFalse($this->mediaValidator->validate($mediaWithInvalidMediaTypeAudio));
    }

    public function testPrivateMediaNotAllowed(): void
    {
        $this->setSystemConfigMock(Config::PATH_INCLUDE_PRIVATE, false);

        $mediaPrivateTrue = $this->getValidLocalMediaForHash();
        $mediaPrivateTrue->setPrivate(true);
        static::assertFalse($this->mediaValidator->validate($mediaPrivateTrue));

        $mediaPrivateFalse = $this->getValidLocalMediaForHash();
        $mediaPrivateFalse->setPrivate(false);
        static::assertTrue($this->mediaValidator->validate($mediaPrivateFalse));
    }

    public function testPrivateMediaAllowed(): void
    {
        $this->setSystemConfigMock(Config::PATH_INCLUDE_PRIVATE, true);

        $mediaPrivateTrue = $this->getValidLocalMediaForHash();
        $mediaPrivateTrue->setPrivate(true);
        static::assertTrue($this->mediaValidator->validate($mediaPrivateTrue));

        $mediaPrivateFalse = $this->getValidLocalMediaForHash();
        $mediaPrivateFalse->setPrivate(false);
        static::assertTrue($this->mediaValidator->validate($mediaPrivateFalse));
    }

    public function testMediaExcludedByFolder(): void
    {
        $excludedFolderId = Uuid::randomHex();
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS, [$excludedFolderId]);

        $mediaExcluded = $this->getValidLocalMediaForHash();
        $mediaExcluded->setMediaFolderId($excludedFolderId);

        static::assertFalse($this->mediaValidator->validate($mediaExcluded));
    }

    public function testMediaNotExcludedByFolder(): void
    {
        $excludedFolderId = Uuid::randomHex();
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS, [$excludedFolderId]);

        $mediaExcluded = $this->getValidLocalMediaForHash();
        $mediaExcluded->setMediaFolderId(Uuid::randomHex());

        static::assertTrue($this->mediaValidator->validate($mediaExcluded));
    }

    public function testMediaExcludedByTag(): void
    {
        $excludedTagId = Uuid::randomHex();
        $tag = new TagEntity();
        $tag->setId($excludedTagId);

        $this->setSystemConfigMock(Config::PATH_EXCLUDED_TAGS, [$excludedTagId]);

        $mediaExcluded = $this->getValidLocalMediaForHash();
        $mediaExcluded->setTags(new TagCollection([$tag]));

        static::assertFalse($this->mediaValidator->validate($mediaExcluded));
    }

    public function testMediaNotExcludedByTag(): void
    {
        $tag = new TagEntity();
        $tag->setId(Uuid::randomHex());

        $excludedTagId = Uuid::randomHex();
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_TAGS, [$excludedTagId]);

        $mediaExcluded = $this->getValidLocalMediaForHash();
        $mediaExcluded->setTags(new TagCollection([$tag]));

        static::assertTrue($this->mediaValidator->validate($mediaExcluded));
    }

    public function testMediaFileExtensions(): void
    {
        static::assertTrue($this->mediaValidator->validate($this->getValidLocalMediaForHash())); // Jpg
        static::assertTrue($this->mediaValidator->validate($this->getValidLocalMediaForHash('jpeg')));
        static::assertTrue($this->mediaValidator->validate($this->getValidLocalMediaForHash('png', 'image/png')));
        static::assertTrue($this->mediaValidator->validate($this->getValidLocalMediaForHash('gif', 'image/gif')));

        static::assertFalse($this->mediaValidator->validate($this->getValidLocalMediaForHash('svg', 'image/svg')));
    }

    private function assertValidateExpectThrow($input, string $expectedExceptionClass = \TypeError::class): void
    {
        $this->expectException($expectedExceptionClass);

        static::assertIsNotBool(
            $this->mediaValidator->validate($input),
            'The value should throw "' . $expectedExceptionClass . '"'
        );
    }
}
