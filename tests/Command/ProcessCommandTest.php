<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Command;

use Eyecook\Blurhash\Command\GenerateCommand;
use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Test\ConfigMockStub;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class ProcessCommandTest extends TestCase
{
    use IntegrationTestBehaviour, ConfigMockStub;

    protected ?GenerateCommand $command = null;

    protected function setUp(): void
    {
        $this->setUpSystemConfigService();
        $this->resetInternalSystemConfigCache();
        $this->resetInternalConfigCache();

        $this->unsetSystemConfigMock(Config::PATH_MANUAL_MODE);

        $this->command = $this->getContainer()->get(GenerateCommand::class);
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
        $tester = new CommandTester($this->command);

        $tester->execute(['--all' => 1]);
        $output = $tester->getDisplay(true);
        static::assertStringContainsStringIgnoringCase('Existing hashes will be refreshed', $output);

        $tester->execute([]);
        $output = $tester->getDisplay(true);
        static::assertStringContainsStringIgnoringCase('Generate missing hashes', $output);
    }

    public function testOutputWithDryRunFlag(): void
    {
        $tester = new CommandTester($this->command);

        $tester->execute(['--dryRun' => 1]);
        $output = $tester->getDisplay(true);

        static::assertStringContainsStringIgnoringCase('media entities can be processed', $output);
        static::assertStringNotContainsStringIgnoringCase('generation will be synchronous', $output);
        static::assertStringNotContainsStringIgnoringCase('generation will be asynchronous', $output);
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testOutputWithSyncFlag(): void
    {
        $tester = new CommandTester($this->command);

        $tester->execute(['--sync' => 1]);
        $output = $tester->getDisplay(true);
        static::assertStringContainsStringIgnoringCase('generation will be synchronous', $output);
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());

        $tester->execute([]);
        $output = $tester->getDisplay(true);
        static::assertStringContainsStringIgnoringCase('generation will be asynchronous', $output);
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testOutputInManualMode(): void
    {
        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, true);
        $tester = new CommandTester($this->command);

        $tester->execute(['--sync' => 1]);
        $output = $tester->getDisplay(true);
        static::assertStringNotContainsStringIgnoringCase(
            'when plugin running in manual mode, asynchronous generation is disabled',
            $output
        );
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());

        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, false);
        $tester->execute([]);
        $output = $tester->getDisplay(true);
        static::assertStringNotContainsStringIgnoringCase(
            'when plugin running in manual mode, asynchronous generation is disabled',
            $output
        );
        static::assertEquals(Command::SUCCESS, $tester->getStatusCode());

        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, true);
        $tester->execute([]);
        $output = $tester->getDisplay(true);
        static::assertStringContainsStringIgnoringCase(
            'when plugin running in manual mode, asynchronous generation is disabled',
            $output
        );
        static::assertEquals(Command::INVALID, $tester->getStatusCode());
    }

    public function testOutputOnUnknownEntity(): void
    {
        $tester = new CommandTester($this->command);

        $nonExistingEntity = Uuid::randomHex();

        $tester->execute(['entities' => [$nonExistingEntity]]);
        $output = $tester->getDisplay(true);

        static::assertStringContainsStringIgnoringCase('Unknown entity "' . $nonExistingEntity . '"', $output);
        static::assertEquals(Command::FAILURE, $tester->getStatusCode());
    }

    public function testProcessCommandIntegration(): void
    {
        static::markTestIncomplete();
    }
}
