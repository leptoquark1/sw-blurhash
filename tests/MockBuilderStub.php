<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test;

use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
trait MockBuilderStub
{
    use KernelTestBehaviour;

    private array $_mockConstructorArgs = [];

    private function prepareMockConstructorArgs(string $className, array $args): void
    {
        $this->_mockConstructorArgs[$className] = $args;
    }

    private function getMockConstructorArgs(string $className, array $mockArgs = []): array
    {
        return array_map(static function ($arg) use ($mockArgs) {
            if (is_string($arg)) {
                return $mockArgs[$arg] ?? self::getContainer()->get($arg);
            }

            return $arg;
        }, $this->_mockConstructorArgs[$className] ?? []);
    }

    /**
     * @psalm-template RealInstanceType of object
     * @psalm-param class-string<RealInstanceType> $className
     * @psalm-return MockBuilder<RealInstanceType>
     */
    private function getPreparedMockBuilder(string $className, $mockArgs = []): MockBuilder
    {
        $args = $this->getMockConstructorArgs($className, $mockArgs);

        return $this->getMockBuilder($className)
            ->setConstructorArgs($args);
    }

    /**
     * @psalm-template RealInstanceType of object
     * @psalm-param class-string<RealInstanceType> $className
     * @psalm-return MockObject&RealInstanceType
     */
    private function getPreparedMock(string $className, ?array $onlyMethods = null, $mockArgs = []): MockObject
    {
        $builder = $this->getPreparedMockBuilder($className, $mockArgs);

        if ($onlyMethods !== null) {
            $builder->onlyMethods($onlyMethods);
        }

        return $builder->getMock();
    }

    /**
     * @psalm-template RealInstanceType of object
     * @psalm-param class-string<RealInstanceType> $className
     * @psalm-return RealInstanceType
     */
    private function getPreparedClassInstance(string $className, $mockArgs = []): object
    {
        $args = $this->getMockConstructorArgs($className, $mockArgs);

        return new $className(...$args);
    }
}
