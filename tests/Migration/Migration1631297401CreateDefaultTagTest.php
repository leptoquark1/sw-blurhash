<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Migration;

use Doctrine\DBAL\Connection;
use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Migration\Migration1631297401CreateDefaultTag;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class Migration1631297401CreateDefaultTagTest extends TestCase
{
    use IntegrationTestBehaviour;

    protected ?Connection $connection;
    protected Migration1631297401CreateDefaultTag $migration;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->getContainer()->get(Connection::class);
        $this->migration = new Migration1631297401CreateDefaultTag();
        $this->migration->setConnection($this->connection);

        // Make sure that test database is in state where the migration did not run yet
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0; TRUNCATE tag; SET FOREIGN_KEY_CHECKS=1;');
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
        $resultBefore = $this->fetchDefaultTagCount();
        if ($resultBefore[0] === 0) {
            $this->migration->up();
        }

        $this->migration->down();
        $resultAfter = $this->fetchDefaultTagCount();

        self::assertEquals(0, $resultAfter[0]);
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

    private function fetchDefaultTagCount(): array
    {
        return $this->connection->createQueryBuilder()
            ->select(['count(id) as count'])
            ->from('tag')
            ->where('name = :tag_name')
            ->setParameter('tag_name', Config::DEFAULT_TAG_NAME)
            ->setMaxResults(1)
            ->execute()
            ->fetchFirstColumn();
    }
}
