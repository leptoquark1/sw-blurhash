<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Framework;

use Doctrine\DBAL\Connection;
use Eyecook\Blurhash\EyecookBlurhash;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class PluginHelperTest extends TestCase
{
    use IntegrationTestBehaviour;

    protected Connection $connection;
    protected EyecookBlurhash $plugin;

    protected function setUp(): void
    {
        $this->connection = self::getContainer()->get(Connection::class);
        $this->plugin = self::getContainer()->get(EyecookBlurhash::class);
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
