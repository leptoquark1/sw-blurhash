<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Command;

use Eyecook\Blurhash\Command\GenerateCommand;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class ProcessCommandTest extends TestCase
{
    use IntegrationTestBehaviour;

    protected ?GenerateCommand $command = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = $this->getContainer()->get(GenerateCommand::class);
    }

    public function testProcessCommand(): void
    {
        static::markTestIncomplete();
    }
}
