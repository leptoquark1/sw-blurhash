<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
final class EncodeContext
{
    private $resource;
    private Adapter\HashImageAdapterInterface $adapter;
    private bool $isLinear;
    private bool $isTrueColor;
    private int $height;
    private int $width;
    private int $componentsX;
    private int $componentsY;

    public function __construct($resource, Adapter\HashImageAdapterInterface $adapter, int $componentsX, int $componentsY)
    {
        $this->resource = $resource;
        $this->adapter = $adapter;
        $this->componentsX = $componentsX;
        $this->componentsY = $componentsY;

        $this->isLinear = $adapter->isLinear($resource);
        $this->isTrueColor = $adapter->isTrueColor($resource);
        $this->height = $adapter->getImageHeight($resource);
        $this->width = $adapter->getImageWidth($resource);
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getAdapter(): Adapter\HashImageAdapterInterface
    {
        return $this->adapter;
    }

    public function isLinear(): bool
    {
        return $this->isLinear;
    }

    public function isTrueColor(): bool
    {
        return $this->isTrueColor;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getComponentsX(): int
    {
        return $this->componentsX;
    }

    public function getComponentsY(): int
    {
        return $this->componentsY;
    }
}
