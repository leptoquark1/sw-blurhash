<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash;

use Doctrine\DBAL\Connection;
use EyeCook\BlurHash\Hash\Media\MediaHashId;
use EyeCook\BlurHash\Hash\Media\MediaHashIdFactory;
use EyeCook\BlurHash\Hash\Media\MediaHashMeta;
use EyeCook\BlurHash\Configuration\ConfigService;
use League\Flysystem\FilesystemInterface;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailCollection;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * @package EyeCook\BlurHash
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
     * @throws \Doctrine\DBAL\Exception
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
            $path = $this->getPhysicalFilePath($media, $mediaHashId);
            $fileContent = $this->getFileSystem($media)->read($path);
        } catch (FileNotFoundException $e) {
            return null;
        }

        $this->hashGenerator->generate($mediaHashId, $fileContent);
        $fileContent = null;

        $this->persistMediaHash($mediaHashId);

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
    protected function getPhysicalFilePath(MediaEntity $media, MediaHashId $mediaHashId): string
    {
        $thresholdThumbnail = $this->getThresholdThumbnail($media);
        $path = $thresholdThumbnail
            ? $this->urlGenerator->getRelativeThumbnailUrl($media, $thresholdThumbnail)
            : $this->urlGenerator->getRelativeMediaUrl($media);

        if ($this->getFileSystem($media)->has($path) === false) {
            throw new FileNotFoundException('Filepath cannot be resolved by the filesystem', 0, null, $path);
        }

        return $path;
    }

    protected function getThresholdThumbnail(MediaEntity $media): ?MediaThumbnailEntity
    {
        if (!$this->isBetterToUseThumbnail($media)) {
            return null;
        }

        $filtered = $media->getThumbnails()->filter(function(MediaThumbnailEntity $thumb) {
            return $thumb->getWidth() <= $this->config->getThumbnailThresholdWidth()
                ||  $thumb->getHeight() <= $this->config->getThumbnailThresholdHeight();
        });

        $filtered->sort(static function(MediaThumbnailEntity $a, MediaThumbnailEntity $b) {
            $ag = $a->getHeight() + $a->getWidth();
            $bg = $b->getHeight() + $b->getWidth();

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
     * @throws \Doctrine\DBAL\Exception
     */
    private function initQuery(): void
    {
        $statement = 'UPDATE media SET meta_data = JSON_SET(meta_data, '
            .'\'$.'.MediaHashMeta::$PROP_HASH.'\', :'.MediaHashMeta::$PROP_HASH.','
            .'\'$.'.MediaHashMeta::$PROP_WIDTH.'\', :'.MediaHashMeta::$PROP_WIDTH.','
            .'\'$.'.MediaHashMeta::$PROP_HEIGHT.'\', :'.MediaHashMeta::$PROP_HEIGHT
            .') WHERE id = :id';

        $query = $this->connection->prepare($statement);

        $this->query = new RetryableQuery($query);
    }
}
