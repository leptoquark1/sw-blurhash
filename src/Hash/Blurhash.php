<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash;

use EyeCook\BlurHash\Util as Util;
use InvalidArgumentException;

/**
 * A more performant implementation of kornrunner\Blurhash package.
 *
 * @package EyeCook\BlurHash
 * @author kornrunner & David Fecke (+leptoquark1)
 * @see https://github.com/kornrunner/php-blurhash
 * @see https://github.com/woltapp/blurhash
 */
final class Blurhash
{
    /**
     * @throws InvalidArgumentException
     */
    public static function encode(
        &$resource,
        Adapter\HashImageAdapterInterface $adapter,
        int $componentsX = 4,
        int $componentsY = 4,
        bool $linear = false
    ): string {
        if (($componentsX < 1 || $componentsX > 9) || ($componentsY < 1 || $componentsY > 9)) {
            throw new InvalidArgumentException("Component counts must be between 1 and 9 inclusive.");
        }

        if (is_resource($resource) === false) {
            $resource = $adapter->createImage($resource);
        }

        $height = $adapter->getImageHeight($resource);
        $width = $adapter->getImageWidth($resource);

        $components = [];
        $scale = 1 / ($width * $height);
        for ($y = 0; $y < $componentsY; $y++) {
            for ($x = 0; $x < $componentsX; $x++) {
                $normalisation = $x === 0 && $y === 0 ? 1 : 2;
                $r = $g = $b = 0;

                for ($i = 0; $i < $width; $i++) {
                    for ($j = 0; $j < $height; $j++) {
                        ['red' => $red, 'green' => $green, 'blue' => $blue] = $adapter->getImageColorAt($resource, $i, $j);
                        $color = $linear
                            ? [$red, $green, $blue]
                            : [Util\Color::toLinear($red), Util\Color::toLinear($green), Util\Color::toLinear($blue)];
                        $basis = $normalisation
                            * cos(M_PI * $i * $x / $width)
                            * cos(M_PI * $j * $y / $height);

                        $r += $basis * $color[0];
                        $g += $basis * $color[1];
                        $b += $basis * $color[2];
                    }
                }

                $components[] = [$r * $scale, $g * $scale, $b * $scale];
            }
        }

        $dc_value = Util\DC::encode(array_shift($components) ?: []);

        $maxAcComponent = 0;
        foreach ($components as $component) {
            $component[] = $maxAcComponent;
            $maxAcComponent = max($component);
        }

        $quantMaxAcComponent = (int) max(0, min(82, floor($maxAcComponent * 166 - 0.5)));
        $acComponentNormFactor = ($quantMaxAcComponent + 1) / 166;

        $acValues = [];
        foreach ($components as $component) {
            $acValues[] = Util\AC::encode($component, $acComponentNormFactor);
        }

        $blurhash = Util\Base83::encode($componentsX - 1 + ($componentsY - 1) * 9, 1);
        $blurhash .= Util\Base83::encode($quantMaxAcComponent, 1);
        $blurhash .= Util\Base83::encode($dc_value, 4);
        foreach ($acValues as $acValue) {
            $blurhash .= Util\Base83::encode((int) $acValue, 2);
        }

        return $blurhash;
    }
}
