<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Util;

use InvalidArgumentException;

/**
 * @package kornrunner\Blurhash
 * @author kornrunner
 * @see https://github.com/kornrunner/php-blurhash
 * @see https://github.com/woltapp/blurhash
 */
final class Base83
{
    private const ALPHABET = [
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D',
        'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
        'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f',
        'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
        'u', 'v', 'w', 'x', 'y', 'z', '#', '$', '%', '*', '+', ',', '-', '.',
        ':', ';', '=', '?', '@', '[', ']', '^', '_', '{', '|', '}', '~'
    ];
    private const BASE = 83;

    public static function encode(int $value, int $length): string
    {
        if (floor($value / (self::BASE ** $length)) != 0) {
            throw new InvalidArgumentException('Specified length is too short to encode given value.');
        }

        $result = '';
        for ($i = 1; $i <= $length; $i++) {
            $digit = floor($value / (self::BASE ** ($length - $i))) % self::BASE;
            $result .= self::ALPHABET[$digit];
        }

        return $result;
    }
}
