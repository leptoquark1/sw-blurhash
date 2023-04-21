<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash;

use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaUpdater;
use Eyecook\Blurhash\Hash\Media\MediaHashId;
use Eyecook\Blurhash\Hash\Media\MediaHashIdFactory;
use League\Flysystem\FilesystemOperator;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class HashMediaService
{
    public function __construct(
        protected readonly ConfigService $config,
        protected readonly MediaHashIdFactory $hashFactory,
        protected readonly HashGeneratorInterface $hashGenerator,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly FilesystemOperator $filesystemPublic,
        protected readonly FilesystemOperator $filesystemPrivate,
        protected readonly HashMediaUpdater $hashMediaUpdater
    ) {
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \Doctrine\DBAL\Exception
     */
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

        $this->hashMediaUpdater->upsertMediaHash($mediaHashId);

        return $mediaHashId->getHash();
    }

    /**
     * @throws \League\Flysystem\FilesystemException
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

    private function getFileSystem(MediaEntity $media): FilesystemOperator
    {
        return $media->isPrivate()
            ? $this->filesystemPrivate
            : $this->filesystemPublic;
    }
}
