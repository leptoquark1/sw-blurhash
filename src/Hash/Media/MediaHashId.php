<?php declare(strict_types=1);
namespace Eyecook\Blurhash\Hash\Media;

use Shopware\Core\Content\Media\MediaEntity;

/**
 * Describes a media entity in context with Blurhash
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class MediaHashId
{
    protected MediaHashMeta $data;
    protected string $mediaId;
    protected ?string $mediaVersionId = null;

    public function __construct(MediaEntity $mediaEntity)
    {
        $this->mediaId = $mediaEntity->getId();
        $this->mediaVersionId = $mediaEntity->getVersionId();

        $metaData = $mediaEntity->getMetaData() ?? [];
        $this->data = (new MediaHashMeta())->assign($metaData);
    }

    public function getHash(): ?string
    {
        return $this->data->getHash();
    }

    public function getMetaData(): MediaHashMeta
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    /**
     * @param string $mediaId
     */
    public function setMediaId(string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    /**
     * @return string|null
     */
    public function getMediaVersionId(): ?string
    {
        return $this->mediaVersionId;
    }

    /**
     * @param string|null $mediaVersionId
     */
    public function setMediaVersionId(?string $mediaVersionId): void
    {
        $this->mediaVersionId = $mediaVersionId;
    }
}
