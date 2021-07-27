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
class MediaValidator {
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
    protected ?array $excludedFolders = null;
    protected ?array $excludedTags = null;

    public function __construct(
        LoggerInterface $logger,
        ConfigService $configService
    ) {
        $this->logger = $logger;
        $this->config = $configService;

        $this->logLevel = $this->config->isProductionMode() ? LogLevel::INFO : LogLevel::DEBUG;
        $this->excludedFolders = $this->config->getExcludedFolders();
        $this->excludedTags = $this->config->getExcludedFolders();
    }

    /**
     * @throws \Exception
     */
    public function validate(MediaEntity $media): bool
    {
        try {
            $result = $this->validateMedia($media);

            if ($result !== true) {
                $this->logger->log($this->logLevel, $result, ['mediaId' => $media->getId()]);
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

    /**
     * @return true|string
     */
    protected function validateMedia(MediaEntity $media)
    {
        if (in_array($media->getFileExtension(), MediaTypesEnum::FILE_EXTENSIONS, true) === false) {
            return self::MESSAGE_INVALID_FILE_EXTENSION;
        }

        $metaData = $media->getMetaData();
        if (!is_array($metaData) || !$metaData['height'] || !$metaData['width']) {
            return self::MESSAGE_INVALID_META;
        }

        if ($media->hasFile() === false) {
            return self::MESSAGE_NO_FILE;
        }

        if ($media->getMediaType() instanceof ImageType === false) {
            return self::MESSAGE_MEDIA_TYPE;
        }

        if ($media->isPrivate() && $this->config->isIncludedPrivate() === false) {
            return self::MESSAGE_PRIVATE;
        }

        if ($this->hasExcludedFolder($media)) {
            return self::MESSAGE_EXCLUDED_FOLDER;
        }

        if ($this->hasExcludedTags($media)) {
            return self::MESSAGE_EXCLUDED_TAG;
        }

        return true;
    }

    private function hasExcludedTags(MediaEntity $media): bool
    {
        $tags = $media->getTags();
        if (!$tags || $tags->count()) {
            return false;
        }

        return (bool) $tags->getList($this->excludedTags)->count();
    }

    private function hasExcludedFolder(MediaEntity $media): bool
    {
        return in_array($media->getMediaFolderId(), $this->excludedFolders, false);
    }
}
