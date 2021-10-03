<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Exception;

use EyeCook\BlurHash\Exception\IllegalManualModeLeverageException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Exception
 *
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class IllegalManualModeLeverageExceptionTest extends TestCase
{
    public function testExceptionConsistent(): void
    {
        $exception = new IllegalManualModeLeverageException();

        static::assertEquals(Response::HTTP_FAILED_DEPENDENCY, $exception->getStatusCode());
        static::assertEquals(IllegalManualModeLeverageException::$ERROR_CODE, $exception->getErrorCode());
        static::assertInstanceOf(ShopwareHttpException::class, $exception);
        static::assertArrayHasKey('manualMode', $exception->getParameters());
        static::assertArrayHasKey('adminWorkerEnabled', $exception->getParameters());
        static::assertTrue($exception->getParameters()['manualMode']);
        static::assertFalse($exception->getParameters()['adminWorkerEnabled']);

        $this->throwException($exception);
        $this->expectExceptionObject($exception);

        $this->expectException(IllegalManualModeLeverageException::class);
        $this->expectExceptionMessage('To process this request in manual mode, a worker must be defined.');

        throw $exception;
    }
}
