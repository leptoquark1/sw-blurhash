<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash\Adapter;

/**
 * Adapter Interface
 *
 * Use this interface for customization of the image processing
 *
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
interface HashImageAdapterInterface
{
    /**
     * @param string $data A string containing the image data
     * @return resource|false Image resource for provided data or false on error
     */
    public function createImage(string &$data);

    /**
     * @param resource $resource An image resource, returned by the `createImage` function
     * @param int $x x-coordinate of the point
     * @param int $y y-coordinate of the point
     * @param bool|null $isTrueColor Indicator if the resource image is of true color RGB or palette color
     * @return array|false An array with red, green, blue values (in that specific order) that contain the appropriate values for the
     *     specified color at that coordinates
     */
    public function getImageColorAt(&$resource, int $x, int $y, ?bool $isTrueColor): array;

    /**
     * @param resource $resource An image resource, returned by the `createImage` function
     * @param int $x x-coordinate of the point
     * @param int $y y-coordinate of the point
     * @return array|false An array with red, green, blue values (in that specific order) that contain the appropriate values for the
     *     specified color at that coordinates
     * @see getImageColorAt Performant version, which treats all resources as truecolor type
     * @see isTrueColor
     */
    public function getImageTrueColorAt(&$resource, int $x, int $y): array;

    /**
     * @param resource $resource An image resource, returned by the image creation function
     * @return int The width of the image
     */
    public function getImageWidth(&$resource): int;

    /**
     * @param resource $resource An image resource, returned by the image creation function
     * @return int The height of the image
     */
    public function getImageHeight(&$resource): int;

    /**
     * @param resource $resource An image resource, returned by the image creation function
     * @return bool Indication if getImageColorAt returns linear colors or not
     */
    public function isLinear(&$resource): bool;

    /**
     * @param resource $resource An image resource, returned by the image creation function
     * @return bool The resource image is of true color type. Can be relevant if an RGB or a palette index is determined for further color
     *     resolving
     * @see getImageColorAt Used here as param to have a cache for iterative function calls
     */
    public function isTrueColor(&$resource): bool;
}
