<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Media;

/**
 * Specification of valid media entities
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class MediaTypesEnum
{
    public const FILE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
    ];

    public static function getValues(): array
    {
        return [...static::FILE_EXTENSIONS];
    }
}
