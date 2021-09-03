<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Framework;

use Doctrine\DBAL\Connection;
use EyeCook\BlurHash\Exception\InvalidClassException;
use EyeCook\BlurHash\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Plugin;

/**
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 * @static
 */
class PluginHelper
{
    /**
     * Calls the `down` method of all migrations of this plugin in respective order
     *  This is meant to be called in a Plugins Baseclass uninstall method.
     */
    public static function rollbackAllMigrations(Plugin $pluginBaseClass, Connection $connection): void
    {
        $migrations = self::findPluginMigrations($pluginBaseClass);

        // Filter for valid Migrations
        $rollbacks = array_filter($migrations, static function ($migration) {
            return $migration instanceof MigrationStep
                || (method_exists($migration, 'down') && method_exists($migration, 'setConnection'));
        });

        // We roll back in reversed order
        usort($rollbacks, static function ($a, $b) {
            return $b->getCreationTimestamp() - $a->getCreationTimestamp();
        });

        // Run the rollback
        foreach ($rollbacks as $migration) {
            /** @var MigrationStep $migration */
            $migration->setConnection($connection);
            $migration->down();
        }
    }

    /**
     * Find all migrations provided by a plugin using filesystem
     *
     * @throws InvalidClassException
     */
    private static function findPluginMigrations(Plugin $pluginBaseClass): array
    {
        $migrationPath = str_replace(
            '\\',
            '/',
            $pluginBaseClass->getPath() . str_replace(
                $pluginBaseClass->getNamespace(),
                '',
                $pluginBaseClass->getMigrationNamespace()
            )
        );

        return self::getFromNamespace($migrationPath, $pluginBaseClass->getMigrationNamespace());
    }

    /**
     * Get all classes from a specific path and namespace
     *
     * @throws InvalidClassException
     */
    private static function getFromNamespace(string $directory, string $namespace, string $expectedClass = null): array
    {
        if (!is_readable($directory) || !is_dir($directory)) {
            return [];
        }

        $classes = [];
        foreach (scandir($directory, SCANDIR_SORT_ASCENDING) as $classFileName) {
            $path = $directory . '/' . $classFileName;
            $className = $namespace . '\\' . pathinfo($classFileName, PATHINFO_FILENAME);
            if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            if (!class_exists($className)) {
                throw new InvalidClassException($className, $path);
            }

            if ($expectedClass !== null && !is_a($className, $expectedClass, true)) {
                continue;
            }
            try {
                $instance = new $className();
                $classes[$className] = $instance;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $classes;
    }
}
