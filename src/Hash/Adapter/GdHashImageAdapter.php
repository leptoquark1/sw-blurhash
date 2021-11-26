<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Adapter;

/**
 * Adapter to process Images with GD extension
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class GdHashImageAdapter implements HashImageAdapterInterface
{
    public function createImage(string $filename)
    {
        $type = function_exists('exif_imagetype')
            ? exif_imagetype($filename)
            : getImageSize($filename)[2] ?? null;

        if ($type === null) {
            return false;
        }

        switch ($type) {
            case IMAGETYPE_GIF:
                $resource = @imageCreateFromGif($filename);
                break;
            case IMAGETYPE_JPEG:
            case IMAGETYPE_JPEG2000:
                $resource = @imageCreateFromJpeg($filename);
                break;
            case IMAGETYPE_PNG:
                $resource = @imagecreatefrompng($filename);
                break;
            default:
                return false;
        }

        return is_resource($resource) === true ? $resource : false;
    }

    public function getImageColorAt(&$resource, int $x, int $y, ?bool $isTrueColor = null): array
    {
        $rgb = imagecolorat($resource, $x, $y);

        return ($isTrueColor ?? imageistruecolor($resource))
            ? [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF]
            : array_values(imagecolorsforindex($resource, $rgb));
    }

    public function getImageTrueColorAt(&$resource, int $x, int $y): array
    {
        $rgb = imagecolorat($resource, $x, $y);

        return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
    }

    public function getImageWidth(&$resource): int
    {
        return imagesx($resource);
    }

    public function getImageHeight(&$resource): int
    {
        return imagesy($resource);
    }

    public function isLinear(&$resource): bool
    {
        return false;
    }

    public function isTrueColor(&$resource): bool
    {
        return imageistruecolor($resource);
    }
}
