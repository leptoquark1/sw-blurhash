<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Media\DataAbstractionLayer;

use Doctrine\DBAL\Connection;
use Eyecook\Blurhash\Hash\Media\MediaHashId;
use Eyecook\Blurhash\Hash\Media\MediaHashIdCollection;
use Eyecook\Blurhash\Hash\Media\MediaHashMeta;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class HashMediaUpdater
{
    public static function getRemoveStatement(): string
    {
        return sprintf(
            "UPDATE `media` SET `meta_data` = JSON_REMOVE(`meta_data`, '$.%s','$.%s','$.%s')",
            MediaHashMeta::$PROP_HASH, MediaHashMeta::$PROP_WIDTH, MediaHashMeta::$PROP_HEIGHT
        );
    }

    public static function getUpsertStatement(): string
    {
        return sprintf(
            "UPDATE `media` SET `meta_data` = JSON_SET(`meta_data`, '\$.%s', :%s,'\$.%s', :%s,'\$.%s', :%s) WHERE `id` in (:id)",
            MediaHashMeta::$PROP_HASH, MediaHashMeta::$PROP_HASH,
            MediaHashMeta::$PROP_WIDTH, MediaHashMeta::$PROP_WIDTH,
            MediaHashMeta::$PROP_HEIGHT, MediaHashMeta::$PROP_HEIGHT
        );
    }

    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function upsertMediaHash(MediaHashId $hashId): void
    {
        $updateQuery = new RetryableQuery(
            $this->connection,
            $this->connection->prepare(self::getUpsertStatement()),
        );

        $updateQuery->execute(array_merge($hashId->getMetaData()->jsonSerialize(), [
            'id' => Uuid::fromHexToBytes($hashId->getMediaId()),
        ]));
    }

    /**
     * @param MediaHashId|MediaHashId[]|MediaHashIdCollection|string|string[]|MediaEntity|MediaEntity[] $input
     */
    public function removeMediaHash($input): void
    {
        if ($input instanceof MediaHashIdCollection) {
            $ids = $input->getMediaIds();
        } else {
            if (is_iterable($input) === false) {
                $input = [$input];
            }

            $ids = array_map(static function ($entry) {
                if ($entry instanceof MediaHashId) {
                    return $entry->getMediaId();
                }

                if ($entry instanceof MediaEntity) {
                    return $entry->getId();
                }

                return $entry;
            }, $input);
        }

        $statement = self::getRemoveStatement() . ' WHERE `id` in (:ids)';
        RetryableQuery::retryable($this->connection, function () use ($statement, $ids) {
            $this->connection->executeStatement(
                $statement,
                ['ids' => Uuid::fromHexToBytesList($ids)],
                ['ids' => Connection::PARAM_STR_ARRAY]
            );
        });
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function removeAllMediaHashes(): void
    {
        $removeAllQuery = new RetryableQuery(
            $this->connection,
            $this->connection->prepare(self::getRemoveStatement()),
        );

        $removeAllQuery->execute();
    }
}
