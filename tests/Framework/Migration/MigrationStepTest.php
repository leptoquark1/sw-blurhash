<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Framework\Migration;

use Doctrine\DBAL\Connection;
use Eyecook\Blurhash\Framework\Migration\MigrationStep;
use Eyecook\Blurhash\Test\MockBuilderStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Migration\MigrationStep as OriginalMigrationStep;

/**
 * @group Framework
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class MigrationStepTest extends TestCase
{
    use MockBuilderStub;

    private int $mockClassDate;
    private static string|bool|null $defaultInstallEnvValue = false;
    protected Connection $connection;
    protected MigrationStep|MockObject $migrationMock;

    public static function setUpBeforeClass(): void
    {
        self::$defaultInstallEnvValue = $_ENV[OriginalMigrationStep::INSTALL_ENVIRONMENT_VARIABLE] ?? null;
    }

    public static function tearDownAfterClass(): void
    {
        $_ENV[OriginalMigrationStep::INSTALL_ENVIRONMENT_VARIABLE] = self::$defaultInstallEnvValue;
    }

    protected function setUp(): void
    {
        $_ENV[OriginalMigrationStep::INSTALL_ENVIRONMENT_VARIABLE] = self::$defaultInstallEnvValue;
        $this->mockClassDate = time();
        $this->connection = self::getContainer()->get(Connection::class);

        $this->migrationMock = $this->getMockForAbstractClass(
            MigrationStep::class,
            [],
            'Migration' . $this->mockClassDate . 'Abc',
            false,
            true,
            true,
            ['install', 'setConnection'],
            true
        );
    }

    public function testSetConnectionIsCalledInitially(): void
    {
        $this->migrationMock->expects($this->once())->method('setConnection');
        $this->migrationMock->update($this->connection);
    }

    public function testGetCreationTimestampIsReturnTimestampFromClass(): void
    {
        self::assertEquals($this->mockClassDate, $this->migrationMock->getCreationTimestamp());
    }

    public function testUpdateDestructiveIsCallable(): void
    {
        self::assertTrue(method_exists($this->migrationMock, 'updateDestructive'));
    }

    public function testUpAndInstallMethodIsCalledLikeNative(): void
    {
        $this->migrationMock->expects($this->once())->method('up');
        $this->migrationMock->update($this->connection);
    }

    public function testInstallMethodIsCalledInContext(): void
    {
        $_ENV[OriginalMigrationStep::INSTALL_ENVIRONMENT_VARIABLE] = true;
        $this->migrationMock->expects($this->once())->method('install');
        $this->migrationMock->update($this->connection);
    }
}
