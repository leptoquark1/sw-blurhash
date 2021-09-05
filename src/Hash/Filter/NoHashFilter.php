<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Filter;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class NoHashFilter extends NotFilter
{
    public function __construct()
    {
        parent::__construct(static::CONNECTION_AND, [new HasHashFilter()]);
    }
}
