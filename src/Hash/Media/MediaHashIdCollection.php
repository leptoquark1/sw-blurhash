<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash\Media;

use Shopware\Core\Framework\Struct\Collection;

/**
 * A collection of MediaHashIds
 *
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class MediaHashIdCollection extends Collection
{
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
}
