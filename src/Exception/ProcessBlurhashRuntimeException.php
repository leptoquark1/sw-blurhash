<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class ProcessBlurhashRuntimeException extends ShopwareHttpException
{
    public static string $ERROR_CODE = 'EYECOOK_BLURHASH__PROCESS_RUNTIME';

    public function __construct(\Exception $parentException)
    {
        parent::__construct('An unexpected exception caught while processing Blurhash: ' . $parentException->message, [
            'parent' => $parentException,
        ]);
    }

    public function getErrorCode(): string
    {
        return self::$ERROR_CODE;
    }
}
