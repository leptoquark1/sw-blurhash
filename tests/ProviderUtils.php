<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test;

use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 * @static
 */
final class ProviderUtils
{
    public static function randomIds(?int $count = null, ?int $min = null, ?int $max = null): array
    {
        $count ??= Random::getInteger(5, 30);
        $min ??= Random::getInteger(1, 10);
        $max ??= $min + Random::getInteger(1, 20);

        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $iterations = Random::getInteger($min, $max);
            $ids = [];
            while ($iterations-- > 0) {
                $ids[] = Uuid::randomHex();
            }
            $data[] = [$ids];
        }

        return $data;
    }

    public static function provideRandomIds(): array
    {
        return self::randomIds();
    }

    public static function generator(): \Closure
    {
        return static function (...$args): \Generator {
            $items = $args[0] ?? [];
            foreach ($items as $item) {
                yield $item;
            }
        };
    }
}
