<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Command;

use Eyecook\Blurhash\Command\RemoveCommand;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaProvider;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaUpdater;
use Eyecook\Blurhash\Test\CommandStub;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * @covers \Eyecook\Blurhash\Command\RemoveCommand
 * @group Command
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class RemoveCommandTest extends TestCase
{
    use CommandStub,
        HashMediaFixtures;

    protected function setUp(): void
    {
        $this->command = $this->getContainer()->get(RemoveCommand::class);

        $this->prepareMockConstructorArgs(RemoveCommand::class, [
            HashMediaProvider::class,
            HashMediaUpdater::class,
        ]);
    }

    public function testIsAvailableByCommand(): void
    {
        $kernel = $this->getKernel();
        $application = new Application($kernel);

        $exception = null;
        try {
            // Thanks to shopware's `IntegrationTestBehaviour` we can not presume that no assertions were made
            // So we need this workaround
            $application->find('ec:blurhash:remove');
        } catch (CommandNotFoundException $e) {
            $exception = $e;
        }

        static::assertNull($exception, 'An CommandNotFoundException is thrown while try to find the command by name.');
    }

    public function testEntityArgument(): void
    {
        $testEntityName = 'test_media';

        // Create a default folder for which entityName is used as command parameter
        $mediaDefaultFolderId = Uuid::randomHex();
        self::getFixtureRepository('media_default_folder')->create([
            [
                'id' => $mediaDefaultFolderId,
                'associationFields' => [],
                'entity' => $testEntityName,
            ]
        ], $this->entityFixtureContext);

        // Create a Test MediaEntity with folder
        $testMediaEntity = $this->getValidExistingMediaForHash(true, true);

        // That is updated with the previously created defaultFolderId
        self::getFixtureRepository('media_folder')->update([
            [
                'id' => $testMediaEntity->getMediaFolderId(),
                'defaultFolderId' => $mediaDefaultFolderId,
            ]
        ], $this->entityFixtureContext);

        $isTestMediaCb = $this->callback(function (array $mediaIds) use ($testMediaEntity) {
            return in_array($testMediaEntity->getId(), $mediaIds, true);
        });

        $mockedHashMediaUpdater = $this->createMock(HashMediaUpdater::class);
        $mockedHashMediaUpdater
            ->expects($this->once())
            ->method('removeMediaHash')
            ->with($isTestMediaCb);

        $tester = $this->createCommandTesterWithArgs([HashMediaUpdater::class => $mockedHashMediaUpdater]);
        $this->executeCommand($tester, ['entities' => [$testEntityName]]);
        $output = self::normalizeOutput($tester);

        static::assertStringContainsStringIgnoringCase($testEntityName, $output);
        static::assertStringContainsStringIgnoringCase('Blurhashes were deleted', $output);
    }

    public function testEntityArgumentWithDryRunOption(): void
    {
        $testEntityName = 'test_media';

        // Create a default folder for which entityName is used as command parameter
        $mediaDefaultFolderId = Uuid::randomHex();
        self::getFixtureRepository('media_default_folder')->create([
            [
                'id' => $mediaDefaultFolderId,
                'associationFields' => [],
                'entity' => $testEntityName,
            ]
        ], $this->entityFixtureContext);

        // Create a Test MediaEntity with folder
        $testMediaEntity = $this->getValidExistingMediaForHash(true, true);

        // That is updated with the previously created defaultFolderId
        self::getFixtureRepository('media_folder')->update([
            [
                'id' => $testMediaEntity->getMediaFolderId(),
                'defaultFolderId' => $mediaDefaultFolderId,
            ]
        ], $this->entityFixtureContext);

        $mockedHashMediaUpdater = $this->createMock(HashMediaUpdater::class);
        $mockedHashMediaUpdater
            ->expects($this->never())
            ->method('removeAllMediaHashes');
        $mockedHashMediaUpdater
            ->expects($this->never())
            ->method('removeMediaHash');

        $tester = $this->createCommandTesterWithArgs([HashMediaUpdater::class => $mockedHashMediaUpdater]);
        $this->executeCommand($tester, ['entities' => [$testEntityName], '--dryRun' => true]);
        $output = self::normalizeOutput($tester);

        static::assertStringContainsStringIgnoringCase($testEntityName, $output);
        static::assertStringContainsStringIgnoringCase('Blurhashes would be affected', $output);
    }

    public function testAllOption(): void
    {
        $mockedHashMediaUpdater = $this->createMock(HashMediaUpdater::class);
        $mockedHashMediaUpdater
            ->expects($this->once())
            ->method('removeAllMediaHashes');
        $mockedHashMediaUpdater
            ->expects($this->never())
            ->method('removeMediaHash');

        $tester = $this->createCommandTesterWithArgs([HashMediaUpdater::class => $mockedHashMediaUpdater]);
        $tester->setInputs(['y']);

        $this->executeCommand($tester, ['--all' => true], ['interactive' => true]);

        $output = self::normalizeOutput($tester);

        static::assertStringContainsStringIgnoringCase('This will delete all generated Blurhashes!', $output);
        static::assertStringContainsStringIgnoringCase('Do you really want to continue?', $output);
        static::assertStringContainsStringIgnoringCase('Blurhashes were deleted', $output);
    }

    public function testAllOptionDryRun(): void
    {
        $mockedHashMediaUpdater = $this->createMock(HashMediaUpdater::class);
        $mockedHashMediaUpdater
            ->expects($this->never())
            ->method('removeAllMediaHashes');
        $mockedHashMediaUpdater
            ->expects($this->never())
            ->method('removeMediaHash');

        $tester = $this->createCommandTesterWithArgs([HashMediaUpdater::class => $mockedHashMediaUpdater]);

        $this->executeCommand($tester, ['--all' => true, '--dryRun' => true], ['interactive' => true]);
        $output = self::normalizeOutput($tester);

        static::assertStringContainsStringIgnoringCase('Blurhashes would be affected', $output);
    }
}
