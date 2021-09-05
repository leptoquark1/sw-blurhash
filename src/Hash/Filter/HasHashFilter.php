<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash\Filter;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;

/**
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class HasHashFilter extends ContainsFilter
{
    public function __construct()
    {
        parent::__construct('metaData', 'blurhash');
    }
}
