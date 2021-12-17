<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Media;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Struct\Collection;

/**
 * A collection of MediaHashIds
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class MediaHashIdCollection extends Collection
{
    /**
     * @param array|EntityCollection $mediaEntities
     * @return void
     */
    public static function createFromMedia($mediaEntities): self
    {
        if ($mediaEntities instanceof EntityCollection) {
            $mediaEntities = $mediaEntities->getElements();
        }

        $instance = new self();

        foreach ($mediaEntities as $mediaEntity) {
            $instance->add(new MediaHashId($mediaEntity));
        }

        return $instance;
    }

    public function filterWithoutHash(): MediaHashIdCollection
    {
        return $this->filter(function (MediaHashId $item) {
            return $item->getHash() === null;
        });
    }

    public function filterWithHash(): MediaHashIdCollection
    {
        return $this->filter(function (MediaHashId $item) {
            return $item->getHash() !== null;
        });
    }

    public function getMediaIds(): array
    {
        return $this->fmap(function (MediaHashId $item) {
            return $item->getMediaId();
        });
    }
}
