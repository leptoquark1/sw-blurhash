<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class IllegalManualModeLeverageException extends ShopwareHttpException
{
    public static string $ERROR_CODE = 'ECB_ILLEGAL_MANUAL_MODE_LEVERAGE';

    public function __construct()
    {
        parent::__construct('To process this request in manual mode, a worker must be defined.', [
            'manualMode' => true,
            'adminWorkerEnabled' => false,
        ]);
    }

    public function getErrorCode(): string
    {
        return self::$ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FAILED_DEPENDENCY; // Seriously..., whatever!
    }
}
