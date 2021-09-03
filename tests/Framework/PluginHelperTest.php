<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Framework;

use Doctrine\DBAL\Connection;
use EyeCook\BlurHash\EcBlurHash;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class PluginHelperTest extends TestCase
{
    use IntegrationTestBehaviour;

    protected Connection $connection;
    protected EcBlurHash $plugin;

    protected function setUp(): void
    {
        $this->connection = $this->getContainer()->get(Connection::class);
        $this->plugin = $this->getContainer()->get(EcBlurHash::class);
    }

    public function testRollbackAllMigrations(): void
    {
        static::markTestIncomplete();
    }

    public function testFindPluginMigrationsMethod(): void
    {
        static::markTestIncomplete();
    }

    public function testGetFromNamespaceMethod(): void
    {
        static::markTestIncomplete();
    }
}
