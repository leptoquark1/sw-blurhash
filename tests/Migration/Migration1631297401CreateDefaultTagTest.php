<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Migration;

use Doctrine\DBAL\Connection;
use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Migration\Migration1631297401CreateDefaultTag;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @group Migration
 * @covers \Eyecook\Blurhash\Migration\Migration1631297401CreateDefaultTag
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class Migration1631297401CreateDefaultTagTest extends TestCase
{
    use KernelTestBehaviour, DatabaseTransactionBehaviour;

    protected ?Connection $connection;
    protected Migration1631297401CreateDefaultTag $migration;

    public static function setUpBeforeClass(): void
    {
        // Make sure that test database is in state where the migration did not run yet
        KernelLifecycleManager::getKernel()
            ->getContainer()
            ->get(Connection::class)
            ->executeStatement('SET FOREIGN_KEY_CHECKS=0; TRUNCATE tag; SET FOREIGN_KEY_CHECKS=1;');
    }

    protected function setUp(): void
    {
        $this->connection = self::getContainer()->get(Connection::class);
        $this->migration = new Migration1631297401CreateDefaultTag();
        $this->migration->setConnection($this->connection);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUp(): void
    {
        $before = $this->fetchDefaultTag();

        $ignoreIds = $before ? array_map(static function ($row) {
            return $row['id'];
        }, $before) : [];

        $now = new \DateTime();
        $this->migration->up();

        $result = $this->fetchDefaultTag($ignoreIds);

        static::assertCount(1, $result);
        static::assertEquals(Config::DEFAULT_TAG_NAME, $result[0]['name']);
        static::assertEqualsWithDelta($now->getTimestamp(), (new \DateTime($result[0]['created_at']))->getTimestamp(), 72000);
    }

    /**
     * @depends testUp
     * @throws \Doctrine\DBAL\Exception
     */
    public function testDown(): void
    {
        [$mediaId] = $this->connection->executeQuery('SELECT id FROM media LIMIT 1')->fetchFirstColumn();

        $tagsResultBefore = $this->fetchDefaultTagCount();
        if ($tagsResultBefore === 0) {
            $this->migration->up();
        }

        $defaultTagId = $this->fetchDefaultTag()[0]['id'] ?? null;
        $mediaTagsCreated = $this->connection->executeStatement(
            'INSERT INTO `media_tag` (`media_id`, `tag_id`) VALUES (:mid, :tid)',
            ['mid' => $mediaId, 'tid' => $defaultTagId]
        );

        self::assertEquals(1, $mediaTagsCreated);

        $this->migration->down();

        $tagsResultAfter = $this->fetchDefaultTagCount();
        $mediaTagsAfter = $this->connection
            ->executeQuery('SELECT `media_id` FROM media_tag WHERE `tag_id` = :tid', ['tid' => $defaultTagId])
            ->rowCount();

        self::assertEquals(0, $mediaTagsAfter);
        self::assertEquals(0, $tagsResultAfter);
    }

    private function fetchDefaultTag($ignoreIds = []): array
    {
        $builder = $this->connection->createQueryBuilder()
            ->select(['id', 'name', 'created_at'])
            ->from('tag')
            ->where('name = :tag_name')
            ->setParameter('tag_name', Config::DEFAULT_TAG_NAME);

        if (count($ignoreIds)) {
            $builder->andWhere('id NOT IN (:ignore_ids)')
                ->setParameter('ignore_ids', implode(',', $ignoreIds));
        }

        return $builder->execute()->fetchAllAssociative();
    }

    private function fetchDefaultTagCount(): int
    {
        return $this->connection->createQueryBuilder()
            ->select(['id'])
            ->from('tag')
            ->where('name = :tag_name')
            ->setParameter('tag_name', Config::DEFAULT_TAG_NAME)
            ->setMaxResults(1)
            ->execute()
            ->rowCount();
    }
}
