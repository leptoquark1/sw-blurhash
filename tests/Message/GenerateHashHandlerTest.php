<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Message;

use EyeCook\BlurHash\Message\GenerateHashHandler;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class GenerateHashHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;

    protected GenerateHashHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->getContainer()->get(GenerateHashHandler::class);
    }

    public function testGenerateHashHandler(): void
    {
        static::markTestIncomplete();
    }
}
