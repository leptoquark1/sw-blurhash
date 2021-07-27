<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash\Adapter;

/**
 * Adapter to process Images with GD extension
 *
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class GdHashImageAdapter implements HashImageAdapterInterface
{
    public function createImage(&$data)
    {
        return imagecreatefromstring($data);
    }

    public function getImageColorAt(&$resource, int $x, int $y): array
    {
        $index = imagecolorat($resource, $x, $y);

        return imagecolorsforindex($resource, $index);
    }

    public function getImageWidth(&$resource): int
    {
        return imagesx($resource);
    }

    public function getImageHeight(&$resource): int
    {
        return imagesy($resource);
    }
}
