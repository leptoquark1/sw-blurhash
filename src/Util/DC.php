<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Util;

/**
 * @package kornrunner\Blurhash
 * @author kornrunner
 * @see https://github.com/kornrunner/php-blurhash
 * @see https://github.com/woltapp/blurhash
 */
final class DC
{
    public static function encode(array $value): int
    {
        $rounded_r = Color::tosRGB($value[0]);
        $rounded_g = Color::tosRGB($value[1]);
        $rounded_b = Color::tosRGB($value[2]);
        return ($rounded_r << 16) + ($rounded_g << 8) + $rounded_b;
    }
}
