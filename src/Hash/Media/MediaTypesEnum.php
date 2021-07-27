<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash\Media;

/**
 * Specification of valid media entities
 *
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class MediaTypesEnum
{
    public const FILE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'svg'
    ];

    public static function getValues(): array
    {
        return [...static::FILE_EXTENSIONS];
    }
}
