<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Filter;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class HasHashFilter extends ContainsFilter
{
    public function __construct()
    {
        parent::__construct('metaData', 'blurhash');
    }
}
