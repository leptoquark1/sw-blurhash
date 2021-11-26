<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash;

use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Hash\Media\MediaHashId;
use RuntimeException;

/**
 * Generates a Blurhash for given image data
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class HashGenerator implements HashGeneratorInterface
{
    protected ConfigService $config;
    protected Adapter\HashImageAdapterInterface $imageAdapter;

    public function __construct(ConfigService $config, Adapter\HashImageAdapterInterface $imageAdapter)
    {
        $this->config = $config;
        $this->imageAdapter = $imageAdapter;
    }

    public function generate(MediaHashId $hashId, string $filename): void
    {
        $image = $this->imageAdapter->createImage($filename);

        if (is_resource($image) === false) {
            throw new RuntimeException('Image resource stream cannot be bind from filename ' . $filename, ['hashId' => $hashId]);
        }

        $height = $this->imageAdapter->getImageHeight($image);
        $width = $this->imageAdapter->getImageWidth($image);

        $hash = Blurhash::encode(
            $image,
            $this->imageAdapter,
            $this->config->getComponentsX(),
            $this->config->getComponentsY(),
        );
        $image = null;

        $hashId->getMetaData()->setHash($hash);
        $hashId->getMetaData()->setWidth($width);
        $hashId->getMetaData()->setHeight($height);
    }
}
