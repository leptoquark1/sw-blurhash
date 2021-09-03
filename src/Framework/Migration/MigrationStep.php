<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Framework\Migration;

use Doctrine\DBAL\Connection;
use EyeCook\BlurHash\Exception\InvalidClassException;
use Shopware\Core\Framework\Migration\MigrationStep as OriginalMigrationStep;

/**
 * Shopware `MigrationStep` Wrapper
 *
 * @see \Shopware\Core\Framework\Migration\MigrationStep
 *
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
abstract class MigrationStep extends OriginalMigrationStep
{
    protected ?Connection $connection = null;

    /**
     * Run migrations.
     */
    abstract public function up(): void;

    /**
     * Reverse migrations.
     *
     * @see \EyeCook\BlurHash\Framework\PluginHelper::rollbackAllMigrations
     */
    abstract public function down(): void;

    /**
     * Run migrations only when called in plugin installation context
     * This will be run before the common migration process
     */
    public function install(): void {
        //
    }

    final public function update(Connection $connection): void
    {
        $this->setConnection($connection);
        if ($this->isInstallation()) {
            $this->install();
        }
        $this->up();
    }

    /**
     * Default implementation that return the timestamp using class name
     *
     * @throws InvalidClassException
     */
    public function getCreationTimestamp(): int
    {
        $className = get_class($this);
        preg_match('/[\d]{10}/', $className, $matches, PREG_UNMATCHED_AS_NULL, 1);

        if (count($matches) === 0 || (($timestamp = (int) $matches[0]) === 0)) {
            throw new InvalidClassException(self::class, $className);
        }
        return $timestamp;
    }

    /**
     * Force implementation of an uncommon method
     */
    public function updateDestructive(Connection $connection): void
    {
        //
    }

    /**
     * @internal
     */
    public function setConnection(Connection $connection): void
    {
        if ($this->connection instanceof Connection === true) {
            return;
        }
        $this->connection = $connection;
    }
}
