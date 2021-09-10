<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Migration;

use Doctrine\DBAL\Exception as DBALException;
use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1631297401CreateDefaultTag extends MigrationStep
{
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
     */
    public function down(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM `tag` WHERE name = :tag_name LIMIT 1',
            ['tag_name' => Config::DEFAULT_TAG_NAME]
        );
    }
}
