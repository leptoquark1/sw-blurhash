<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash;

use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Hash\Media\MediaHashId;

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

    public function generate(MediaHashId $hashId, string &$imageData): void
    {
        $image = $this->imageAdapter->createImage($imageData);
        $height = $this->imageAdapter->getImageHeight($image);
        $width = $this->imageAdapter->getImageWidth($image);

        $hash = Blurhash::encode(
            $image,
            $this->imageAdapter,
            $this->config->getComponentsX(),
            $this->config->getComponentsY(),
            false
        );
        $image = null;

        $hashId->getMetaData()->setHash($hash);
        $hashId->getMetaData()->setWidth($width);
        $hashId->getMetaData()->setHeight($height);
    }
}
