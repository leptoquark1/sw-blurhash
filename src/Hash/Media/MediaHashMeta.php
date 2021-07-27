<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash\Media;

use Shopware\Core\Framework\Struct\Struct;

/**
 * Consolidates the metadata of a media entity to the relevant for Blurhash
 *
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class MediaHashMeta extends Struct
{
    public static string $PROP_HASH = 'blurhash';
    public static string $PROP_WIDTH = 'hashOriginWidth';
    public static string $PROP_HEIGHT = 'hashOriginHeight';

    protected ?string $blurhash = null;
    protected int $width;
    protected int $height;

    public function assign(array $options): MediaHashMeta
    {
        return parent::assign([
            'width' => $options[self::$PROP_WIDTH] ?? $options['width'] ?? 0,
            'height' => $options[self::$PROP_HEIGHT] ?? $options['height'] ?? 0,
            'blurhash' => $options[self::$PROP_HASH] ?? null,
        ]);
    }

    public function getHash(): ?string
    {
        return $this->blurhash;
    }

    public function setHash(string $blurhash): MediaHashMeta
    {
        $this->blurhash = $blurhash;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): MediaHashMeta
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): MediaHashMeta
    {
        $this->height = $height;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            self::$PROP_HASH => $this->getHash(),
            self::$PROP_WIDTH => $this->getWidth(),
            self::$PROP_HEIGHT => $this->getHeight(),
        ];
    }
}
