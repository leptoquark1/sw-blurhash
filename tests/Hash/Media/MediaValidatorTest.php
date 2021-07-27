<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Hash\Media;

use EyeCook\BlurHash\Hash\Media\MediaValidator;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Test\Media\MediaFixtures;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class MediaValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MediaFixtures;

    protected Context $context;
    protected MediaValidator $mediaValidator;

    protected function setUp(): void
    {
        parent::setUp();

        // TODO: Make sure the configuration of everything that is used to generate the hash is mocked by fixtures
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
        static::markTestIncomplete();
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
