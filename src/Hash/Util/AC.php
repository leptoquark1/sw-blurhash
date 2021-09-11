<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Util;

/**
 * @package kornrunner\Blurhash
 * @author kornrunner
 * @see https://github.com/kornrunner/php-blurhash
 * @see https://github.com/woltapp/blurhash
 */
final class AC
{
    public static function encode(array $value, float $max_value): float
    {
        $quant_r = self::quantise($value[0] / $max_value);
        $quant_g = self::quantise($value[1] / $max_value);
        $quant_b = self::quantise($value[2] / $max_value);

        return $quant_r * 19 * 19 + $quant_g * 19 + $quant_b;
    }

    private static function quantise(float $value): float
    {
        return floor(max(0, min(18, floor(self::signPow($value) * 9 + 9.5))));
    }

    private static function signPow(float $base): float
    {
        $sign = $base <=> 0;

        return $sign * (abs($base) ** 0.5);
    }
}
