<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Migration;

use Doctrine\DBAL\Exception as DBALException;
use EyeCook\BlurHash\Configuration\Config;
use EyeCook\BlurHash\Framework\Migration\MigrationStep;
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
