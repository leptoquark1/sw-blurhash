<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

/**
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class InvalidClassException extends ShopwareHttpException
{
    public static string $ERROR_CODE = 'ECB_INVALID_CLASS';

    public function __construct($className, $path)
    {
        parent::__construct(
            'Unable to load class {{ class }} at path {{ path }}',
            ['class' => $className, 'path' => $path]
        );
    }

    public function getErrorCode(): string
    {
        return self::$ERROR_CODE;
    }
}
