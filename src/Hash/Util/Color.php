<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash\Util;

/**
 * @package EyeCook\BlurHash
 * @author kornrunner & David Fecke (+leptoquark1)
 * @see https://github.com/kornrunner/php-blurhash
 * @see https://github.com/woltapp/blurhash
 */
final class Color
{
    private static array $linearMap = [];

    public static function toLinear($v): float
    {
        return self::$linearMap[$v] ?? (self::$linearMap[$v] = ($v <= 10)
                ? $v / 255 / 12.92
                : ((($v / 255) + 0.055) / 1.055) ** 2.4);
    }

    public static function tosRGB(float $value): int
    {
        $normalized = max(0, min(1, $value));
        $result = ($normalized <= 0.0031308)
            ? (int)round($normalized * 12.92 * 255 + 0.5)
            : (int)round((1.055 * ($normalized ** (1 / 2.4)) - 0.055) * 255 + 0.5);

        return max(0, min($result, 255));
    }
}
