<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Exception;

use EyeCook\BlurHash\Exception\InvalidClassException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Exception
 *
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class InvalidClassExceptionTest extends TestCase
{
    public function testExceptionConsistent(): void
    {
        $className = 'SomeClassName';
        $path = 'some/path';
        $exception = new InvalidClassException($className, $path);

        static::assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getStatusCode());
        static::assertEquals(InvalidClassException::$ERROR_CODE, $exception->getErrorCode());
        static::assertInstanceOf(ShopwareHttpException::class, $exception);
        static::assertArrayHasKey('class', $exception->getParameters());
        static::assertArrayHasKey('path', $exception->getParameters());
        static::assertEquals($className, $exception->getParameters()['class']);
        static::assertEquals($path, $exception->getParameters()['path']);

        $this->throwException($exception);
        $this->expectExceptionObject($exception);

        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessage($className);
        $this->expectExceptionMessage($path);

        throw $exception;
    }
}
