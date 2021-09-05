<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Media;

use Eyecook\Blurhash\Configuration\ConfigService;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaType\ImageType;

/**
 * Validates media entities for Blurhash generation
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class MediaValidator
{
    protected const MESSAGE_GENERAL = 'Blurhash generation has been failed due to an unexpected error.';
    protected const MESSAGE_PRIVATE = 'This file is private, and therefore excluded by config';
    protected const MESSAGE_EXCLUDED_FOLDER = 'The folder is excluded by config';
    protected const MESSAGE_EXCLUDED_TAG = 'One of the tags is excluded by config';
    protected const MESSAGE_MEDIA_TYPE = 'Wrong media type';
    protected const MESSAGE_NO_FILE = 'No physical file present';
    protected const MESSAGE_INVALID_META = 'Invalid Metadata';
    protected const MESSAGE_INVALID_FILE_EXTENSION = 'Invalid File Extension';

    protected LoggerInterface $logger;
    protected ConfigService $config;
    protected string $logLevel;

    public function __construct(
        LoggerInterface $logger,
        ConfigService $configService
    ) {
        $this->logger = $logger;
        $this->config = $configService;

        $this->logLevel = $this->config->isProductionMode() ? LogLevel::INFO : LogLevel::DEBUG;
    }

    public function validate(MediaEntity $media): bool
    {
        try {
            $error = $this->getValidationError($media);

            if ($error !== null) {
                $this->logger->log($this->logLevel, $error, ['mediaId' => $media->getId()]);

                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error(self::MESSAGE_GENERAL, [
                'mediaId' => $media->getId(),
                'errorMessage' => $e->getMessage()
            ]);

            return false;
        }

        return true;
    }

    public function getValidationError(MediaEntity $media): ?string
    {
        if ($this->hasValidFileExtension($media) === false) {
            return self::MESSAGE_INVALID_FILE_EXTENSION;
        }

        if ($this->hasValidMetaData($media) === false) {
            return self::MESSAGE_INVALID_META;
        }

        if ($this->hasValidFile($media) === false) {
            return self::MESSAGE_NO_FILE;
        }

        if ($this->hasValidMediaType($media) === false) {
            return self::MESSAGE_MEDIA_TYPE;
        }

        if ($this->hasValidVisibility($media) === false) {
            return self::MESSAGE_PRIVATE;
        }

        if ($this->isExcludedFolderId($media->getMediaFolderId())) {
            return self::MESSAGE_EXCLUDED_FOLDER;
        }

        if ($this->hasExcludedTags($media)) {
            return self::MESSAGE_EXCLUDED_TAG;
        }

        return null;
    }

    public function hasValidFileExtension(MediaEntity $media): bool
    {
        return in_array($media->getFileExtension(), MediaTypesEnum::FILE_EXTENSIONS, true);
    }

    public function hasValidMetaData(MediaEntity $media): bool
    {
        $metaData = $media->getMetaData();

        return is_array($metaData)
            && isset($metaData['height'], $metaData['width'])
            && $metaData['height'] > 0
            && $metaData['width'] > 0;
    }

    public function hasValidFile(MediaEntity $media): bool
    {
        return $media->hasFile();
    }

    public function hasValidMediaType(MediaEntity $media): bool
    {
        return $media->getMediaType() instanceof ImageType;
    }

    public function hasValidVisibility(MediaEntity $media): bool
    {
        return ($media->isPrivate() && $this->config->isIncludedPrivate() === false) === false;
    }

    public function hasExcludedTags(MediaEntity $media): bool
    {
        $tags = $media->getTags();
        if (!$tags || $tags->count() === 0) {
            return false;
        }

        return (bool)$tags->getList($this->config->getExcludedTags())->count();
    }

    public function isExcludedFolderId(?string $folderId): bool
    {
        return $folderId && in_array($folderId, $this->config->getExcludedFolders(), false);
    }
}
