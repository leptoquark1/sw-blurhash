<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Hash;

use EyeCook\BlurHash\Configuration\Config;
use EyeCook\BlurHash\Hash\HashGenerator;
use EyeCook\BlurHash\Hash\Media\MediaHashId;
use EyeCook\BlurHash\Test\ConfigMockStub;
use EyeCook\BlurHash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @covers \EyeCook\BlurHash\Hash\HashGenerator
 * @covers \EyeCook\BlurHash\Hash\Blurhash::encode
 * @large
 *
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class HashGeneratorTest extends TestCase
{
    use ConfigMockStub,
        IntegrationTestBehaviour,
        HashMediaFixtures;

    protected HashGenerator $hashGenerator;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpSystemConfigService();
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 2);
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 1);

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->hashGenerator = $this->getContainer()->get(HashGenerator::class);
    }

    protected function tearDown(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_COMPONENTS_X);
        $this->unsetSystemConfigMock(Config::PATH_COMPONENTS_Y);
    }

    public function testHashGenerationForPng(): void
    {
        $expectedHash = '10M*,h?b';
        $fileContent = file_get_contents(__DIR__ . '/fixtures/shopware-logo.png');

        $hashId = new MediaHashId($this->getEmptyMedia());
        $this->hashGenerator->generate($hashId, $fileContent);

        static::assertEquals($expectedHash, $hashId->getHash());
    }

    public function testHashGenerationForGif(): void
    {
        $expectedHash = '17I;zW1n';
        $fileContent = file_get_contents(__DIR__ . '/fixtures/avatar.gif');

        $hashId = new MediaHashId($this->getEmptyMedia());
        $this->hashGenerator->generate($hashId, $fileContent);

        static::assertEquals($expectedHash, $hashId->getHash());
    }

    public function testHashGenerationForJpg(): void
    {
        $expectedHash = '1DMHDJyG';
        $fileContent = file_get_contents(__DIR__ . '/fixtures/shopware.jpg');

        $hashId = new MediaHashId($this->getEmptyMedia());
        $this->hashGenerator->generate($hashId, $fileContent);

        static::assertEquals($expectedHash, $hashId->getHash());
    }

    public function testHashGenerationForJpgAndDifferentComponentPaths(): void
    {
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 3);
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 3);

        $expectedHash = 'KSMHDJtm%g~p%MV@4nM{bF';
        $fileContent = file_get_contents(__DIR__ . '/fixtures/shopware.jpg');

        $hashId = new MediaHashId($this->getEmptyMedia());
        $this->hashGenerator->generate($hashId, $fileContent);

        static::assertEquals($expectedHash, $hashId->getHash());
    }

    public function testHashGenerationRespectsComponentConfig1(): void
    {
        $expectedHash = '00I;zW';
        $fileContent = file_get_contents(__DIR__ . '/fixtures/avatar.png');

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 1);
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 1);

        $hashId = new MediaHashId($this->getEmptyMedia());
        $this->hashGenerator->generate($hashId, $fileContent);

        static::assertEquals($expectedHash, $hashId->getHash());
    }

    public function testHashGenerationRespectsComponentConfig2(): void
    {
        $expectedHash = 'A0M*,h?b0IfQ';
        $fileContent = file_get_contents(__DIR__ . '/fixtures/shopware-logo.png');

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 2);
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 2);

        $hashId = new MediaHashId($this->getEmptyMedia());
        $this->hashGenerator->generate($hashId, $fileContent);

        static::assertEquals($expectedHash, $hashId->getHash());
    }
}
