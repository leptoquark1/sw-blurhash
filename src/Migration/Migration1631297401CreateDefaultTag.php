<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Migration;

use Doctrine\DBAL\Exception as DBALException;
use Eyecook\Blurhash\Configuration\Concern\DefaultConfigPluginContext;
use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1631297401CreateDefaultTag extends MigrationStep
{
    use DefaultConfigPluginContext;

    /**
     * @throws DBALException
     */
    public function up(): void
    {
        $tagId = Uuid::randomBytes();

        $this->connection->executeStatement(
            'INSERT INTO `tag` (`id`, `name`, `created_at`) VALUES (:tag_id, :tag_name, NOW())',
            ['tag_id' => $tagId, 'tag_name' => Config::DEFAULT_TAG_NAME]
        );
    }

    /**
     * @throws DBALException
     * @throws \Throwable
     */
    public function down(): void
    {
        if (!$schema = $this->connection->getSchemaManager()) {
            return;
        }

        if (!$defaultTagId = $this->fetchDefaultExcludedTagId()) {
            return;
        }

        $defaultTagId = Uuid::fromHexToBytes($defaultTagId);
        $tables = array_filter($schema->listTableNames(), static function ($tableName) use ($schema) {
            return str_ends_with($tableName, '_tag') && array_key_exists('tag_id', $schema->listTableColumns($tableName));
        });

        foreach ($tables as $table) {
            $this->connection->delete($table, ['`tag_id`' => $defaultTagId]);
        }
        $this->connection->delete('tag', ['id' => $defaultTagId]);
    }
}
