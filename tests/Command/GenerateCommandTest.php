<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Command;

use Eyecook\Blurhash\Command\GenerateCommand;
use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Hash\HashMediaService;
use Eyecook\Blurhash\Hash\Media\HashMediaProvider;
use Eyecook\Blurhash\Test\ConfigMockStub;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use Eyecook\Blurhash\Test\MockBuilderStub;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Eyecook\Blurhash\Command\GenerateCommand
 * @group Command
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class GenerateCommandTest extends TestCase
{
    use IntegrationTestBehaviour, ConfigMockStub, HashMediaFixtures, MockBuilderStub;

    protected static function normalizeOutput(CommandTester $tester): string
    {
        $normalized = preg_replace('/\s+/', ' ', $tester->getDisplay(true));

        return trim(preg_replace('/\s[!?]\s/', ' ', $normalized));
    }

    protected function setUp(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_MANUAL_MODE);

        $this->prepareMockConstructorArgs(GenerateCommand::class, [
            'messenger.bus.shopware',
            'media.repository',
            'media_folder.repository',
            HashMediaService::class,
            HashMediaProvider::class,
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
            $application->find('ec:blurhash:generate');
        } catch (CommandNotFoundException $e) {
            $exception = $e;
        }

        static::assertNull($exception, 'An CommandNotFoundException is thrown while try to find the command by name.');
    }

    public function testOutputWithAllFlag(): void
    {
        $tester = $this->createCommandTester();

        $this->executeCommand($tester, ['--all' => 1]);
        $output = self::normalizeOutput($tester);
        static::assertStringContainsStringIgnoringCase('Existing hashes will be refreshed', $output);

        $this->executeCommand($tester, []);
        $output = self::normalizeOutput($tester);
        static::assertStringContainsStringIgnoringCase('Generate missing hashes', $output);
    }

    public function testOutputWithDryRunFlag(): void
    {
        $tester = $this->createCommandTester();

        $this->executeCommand($tester, ['--dryRun' => 1]);
        $output = self::normalizeOutput($tester);

        static::assertStringContainsStringIgnoringCase('media entities can be processed', $output);
        static::assertStringNotContainsStringIgnoringCase('generation will be synchronous', $output);
        static::assertStringNotContainsStringIgnoringCase('generation will be asynchronous', $output);
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testOutputWithSyncFlag(): void
    {
        $tester = $this->createCommandTester();

        $this->executeCommand($tester, ['--sync' => 1]);
        $output = self::normalizeOutput($tester);
        static::assertStringContainsStringIgnoringCase('generation will be synchronous', $output);
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());

        $this->executeCommand($tester);
        $output = self::normalizeOutput($tester);
        static::assertStringContainsStringIgnoringCase('generation will be asynchronous', $output);
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testOutputInManualMode(): void
    {
        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, true);
        $tester = $this->createCommandTester();

        $this->executeCommand($tester, ['--sync' => 1]);
        $output = self::normalizeOutput($tester);
        static::assertStringNotContainsStringIgnoringCase(
            'when plugin running in manual mode, asynchronous generation is disabled',
            $output
        );
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());

        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, false);
        $this->executeCommand($tester);
        $output = self::normalizeOutput($tester);
        static::assertStringNotContainsStringIgnoringCase(
            'when plugin running in manual mode, asynchronous generation is disabled',
            $output
        );
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());

        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, true);
        $this->executeCommand($tester);
        $output = self::normalizeOutput($tester);
        static::assertStringContainsStringIgnoringCase(
            'when plugin running in manual mode, asynchronous generation is disabled',
            $output
        );
        static::assertEquals(Command::INVALID, $tester->getStatusCode());
    }

    public function testOutputOnUnknownEntity(): void
    {
        $tester = $this->createCommandTester();

        $nonExistingEntity = Uuid::randomHex();

        $this->executeCommand($tester, ['entities' => [$nonExistingEntity]]);
        $output = self::normalizeOutput($tester);

        static::assertStringContainsStringIgnoringCase('Unknown entity "' . $nonExistingEntity . '"', $output);
        static::assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    public function testProcessWithEntityArgument(): void
    {
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_FOLDERS, []);
        $this->setSystemConfigMock(Config::PATH_EXCLUDED_TAGS, []);

        $testEntityName = 'test_media';

        // Create a default folder for which entityName is used as command parameter
        /** @var EntityRepositoryInterface $mediaDefaultFolderRepository */
        $mediaDefaultFolderId = Uuid::randomHex();
        self::getFixtureRepository('media_default_folder')->create([
            [
                'id' => $mediaDefaultFolderId,
                'associationFields' => [],
                'entity' => $testEntityName,
            ]
        ], $this->entityFixtureContext);

        // Create a Test MediaEntity with folder
        $testMediaEntity = $this->getValidExistingMediaForHash(false, true);

        // That is updated with the previously created defaultFolderId
        self::getFixtureRepository('media_folder')->update([
            [
                'id' => $testMediaEntity->getMediaFolderId(),
                'defaultFolderId' => $mediaDefaultFolderId,
            ]
        ], $this->entityFixtureContext);

        $isTestMediaCb = $this->callback(function (MediaEntity $mediaEntity) use ($testMediaEntity) {
            return $testMediaEntity->getId() === $mediaEntity->getId();
        });

        $mockedHashMediaService = $this->createMock(HashMediaService::class);
        $mockedHashMediaService
            ->expects($this->once()) // We expect exact one match in our folder
            ->method('processHashForMedia')
            ->with($isTestMediaCb);

        $command = $this->createCommandWithArgs([HashMediaService::class => $mockedHashMediaService]);

        $tester = new CommandTester($command);
        $this->executeCommand($tester, ['entities' => [$testEntityName], '--sync' => 1]);

        $output = self::normalizeOutput($tester);
        static::assertStringContainsStringIgnoringCase($testEntityName, $output);
    }

    public function testQuestionForEntities(): void
    {
        static::markTestIncomplete();
    }

    private function createCommandTester(): CommandTester
    {
        $command = $this->getContainer()->get(GenerateCommand::class);

        $application = new Application($this->getKernel());
        $application->add($command);

        return new CommandTester($application->find('ec:blurhash:generate'));
    }

    private function executeCommand(CommandTester $tester, array $inputs = [], array $options = ['interactive' => false]): int
    {
        return $tester->execute($inputs, $options);
    }

    private function createCommandWithArgs($mockArgs = []): GenerateCommand
    {
        $command = $this->getPreparedClassInstance(GenerateCommand::class, $mockArgs);

        /** @noinspection PhpParamsInspection */
        $command->setContainer($this->getContainer());
        $command->setConfigService($this->getContainer()->get(ConfigService::class));

        return $command;
    }
}
