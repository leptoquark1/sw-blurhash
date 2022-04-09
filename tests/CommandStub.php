<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test;

use Eyecook\Blurhash\Command\AbstractCommand;
use Eyecook\Blurhash\Configuration\ConfigService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 *
 * @extends TestCase
 * @property-read AbstractCommand $command
 */
trait CommandStub
{
    use KernelTestBehaviour, MockBuilderStub;

    protected AbstractCommand $command;

    protected static function normalizeOutput(CommandTester $tester): string
    {
        $normalized = preg_replace('/\s+/', ' ', $tester->getDisplay(true));

        return trim(preg_replace('/\s[!?]\s/', ' ', $normalized));
    }

    private function executeCommand(
        CommandTester $tester,
        array $input = [],
        array $options = ['interactive' => false]
    ): int {
        return $tester->execute(array_merge(['command' => $this->command->getName()], $input), $options);
    }

    private function createCommandTester(?AbstractCommand $command = null): CommandTester
    {
        $application = new Application($this->getKernel());
        $application->add($command ?? $this->command);
        $application->setAutoExit(true);

        return new CommandTester($application->find($this->command->getName()));
    }

    private function createCommandTesterWithArgs($mockArgs = []): CommandTester
    {
        $command = $this->createCommandWithArgs(get_class($this->command), $mockArgs);

        return $this->createCommandTester($command);
    }

    private function createCommandWithArgs(string $class, $mockArgs = []): AbstractCommand
    {
        $command = $this->getPreparedClassInstance($class, $mockArgs);

        $command->setContainer($this->getContainer());
        $command->setConfigService($this->getContainer()->get(ConfigService::class));

        return $command;
    }
}
