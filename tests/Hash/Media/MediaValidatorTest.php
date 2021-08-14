<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Hash\Media;

use EyeCook\BlurHash\Hash\Media\MediaValidator;
use EyeCook\BlurHash\Test\TestCaseBase\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class MediaValidatorTest extends TestCase
{
    use IntegrationTestBehaviour,
        HashMediaFixtures;

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
