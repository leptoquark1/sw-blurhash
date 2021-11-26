<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Hash\Media\MediaHashId;
use Eyecook\Blurhash\Hash\Media\MediaHashIdFactory;
use Eyecook\Blurhash\Hash\Media\MediaHashMeta;
use League\Flysystem\FilesystemInterface;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class HashMediaService
{
    protected ConfigService $config;
    protected MediaHashIdFactory $hashFactory;
    protected HashGeneratorInterface $hashGenerator;
    protected UrlGeneratorInterface $urlGenerator;
    protected FilesystemInterface $filesystemPublic;
    protected FilesystemInterface $filesystemPrivate;
    protected Connection $connection;
    protected RetryableQuery $query;

    /**
     * @throws Exception
     */
    public function __construct(
        ConfigService $config,
        MediaHashIdFactory $hashFactory,
        HashGeneratorInterface $hashGenerator,
        UrlGeneratorInterface $urlGenerator,
        FilesystemInterface $filesystemPublic,
        FilesystemInterface $filesystemPrivate,
        Connection $connection
    ) {
        $this->config = $config;
        $this->hashFactory = $hashFactory;
        $this->hashGenerator = $hashGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->filesystemPublic = $filesystemPublic;
        $this->filesystemPrivate = $filesystemPrivate;
        $this->connection = $connection;

        $this->initQuery();
    }

    public function processHashForMedia(MediaEntity $media): ?string
    {
        $mediaHashId = $this->hashFactory->fromMedia($media);
        if ($mediaHashId === null) {
            return null;
        }

        try {
            $filename = $this->getPhysicalFilePath($media);
        } catch (FileNotFoundException $e) {
            return null;
        }

        $this->hashGenerator->generate($mediaHashId, $filename);

        if (empty($mediaHashId->getHash()) === false) {
            $this->persistMediaHash($mediaHashId);
        }

        return $mediaHashId->getHash();
    }

    public function persistMediaHash(MediaHashId $hashId): void
    {
        $this->query->execute(array_merge($hashId->getMetaData()->jsonSerialize(), [
            'id' => Uuid::fromHexToBytes($hashId->getMediaId()),
        ]));
    }

    /**
     * @throws FileNotFoundException
     */
    protected function getPhysicalFilePath(MediaEntity $media): string
    {
        $thresholdThumbnail = $this->getThresholdThumbnail($media);
        $fileSystem = $this->getFileSystem($media);
        $path = $thresholdThumbnail
            ? $this->urlGenerator->getRelativeThumbnailUrl($media, $thresholdThumbnail)
            : $this->urlGenerator->getRelativeMediaUrl($media);

        if ($fileSystem->has($path) === false) {
            throw new FileNotFoundException('Filepath cannot be resolved by the filesystem', 0, null, $path);
        }

        return $fileSystem->getAdapter()->applyPathPrefix($path);
    }

    protected function getThresholdThumbnail(MediaEntity $media): ?MediaThumbnailEntity
    {
        if (!$this->isBetterToUseThumbnail($media)) {
            return null;
        }

        $isLandscape = $media->getMetaData()['width'] > $media->getMetaData()['height'];
        $threshold = $isLandscape ? $this->config->getThumbnailThresholdWidth() : $this->config->getThumbnailThresholdHeight();

        $filtered = $media->getThumbnails()->filter(function (MediaThumbnailEntity $thumb) use ($isLandscape, $threshold) {
            return $isLandscape ? $thumb->getWidth() < $threshold : $thumb->getHeight() < $threshold;
        });

        $filtered->sort(static function (MediaThumbnailEntity $a, MediaThumbnailEntity $b) use ($isLandscape) {
            $ag = $isLandscape ? $a->getWidth() : $a->getHeight();
            $bg = $isLandscape ? $b->getWidth() : $b->getHeight();

            if ($ag === $bg) {
                return 0;
            }

            return $ag > $bg ? -1 : 1;
        });

        return $filtered->first();
    }

    private function isBetterToUseThumbnail(MediaEntity $media): bool
    {
        $thumbnails = $media->getThumbnails();

        if (!$thumbnails || $thumbnails->count() <= 0) {
            return false;
        }

        ['height' => $height, 'width' => $width] = $media->getMetaData();

        return $height > $this->config->getThumbnailThresholdHeight()
            || $width > $this->config->getThumbnailThresholdWidth();
    }

    private function getFileSystem(MediaEntity $media): FilesystemInterface
    {
        return $media->isPrivate() ? $this->filesystemPrivate : $this->filesystemPublic;
    }

    /**
     * @throws Exception
     */
    private function initQuery(): void
    {
        $statement = 'UPDATE media SET meta_data = JSON_SET(meta_data, '
            . '\'$.' . MediaHashMeta::$PROP_HASH . '\', :' . MediaHashMeta::$PROP_HASH . ','
            . '\'$.' . MediaHashMeta::$PROP_WIDTH . '\', :' . MediaHashMeta::$PROP_WIDTH . ','
            . '\'$.' . MediaHashMeta::$PROP_HEIGHT . '\', :' . MediaHashMeta::$PROP_HEIGHT
            . ') WHERE id = :id';

        $query = $this->connection->prepare($statement);

        $this->query = new RetryableQuery($query);
    }
}
