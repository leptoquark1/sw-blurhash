<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Message;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Hash\HashMediaService;
use Eyecook\Blurhash\Message\GenerateHashHandler;
use Eyecook\Blurhash\Message\GenerateHashMessage;
use Eyecook\Blurhash\Test\ConfigMockStub;
use Eyecook\Blurhash\Test\MockBuilderStub;
use Eyecook\Blurhash\Test\ProviderUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use stdClass;

/**
 * @covers \Eyecook\Blurhash\Message\GenerateHashHandler
 * @group MessageHandling
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class GenerateHashHandlerTest extends TestCase
{
    use KernelTestBehaviour, ConfigMockStub, MockBuilderStub;

    protected GenerateHashHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::getContainer()->get(GenerateHashHandler::class);

        $this->prepareMockConstructorArgs(GenerateHashHandler::class, [
            ConfigService::class,
            HashMediaService::class,
            'media.repository',
            'monolog.logger',
        ]);
    }

    public function throwableMessageProvider(): array
    {
        return [
            [null],
            [true],
            [true],
            ['abc'],
            [1],
            [[]],
            [new stdClass()],
        ];
    }

    /**
     * @dataProvider throwableMessageProvider
     */
    public function testIsMessageValidThrows($message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->__invoke($message);
    }

    public function invalidMessageProvider(): array
    {
        return [
            [$this->createMessage([], true), true],
            [$this->createMessage([], true), false],
            [$this->createMessage([], false), false],
            [$this->createMessage([Uuid::randomHex()], false), true],
        ];
    }

    /**
     * @dataProvider invalidMessageProvider
     */
    public function testIsMessageValidSkipsHandling($message, bool $ignoreManualMode): void
    {
        $mock = $this->getMockBuilder(GenerateHashHandler::class)
            ->setConstructorArgs($this->getMockConstructorArgs(GenerateHashHandler::class))
            ->onlyMethods(['handle'])->getMock();

        $mock->expects($this->exactly(0))->method('handler');

        $this->setSystemConfigMock(Config::PATH_MANUAL_MODE, $ignoreManualMode);
        $mock->__invoke($message);
    }

    public function testHandleMethodNotCallsHandleMessageWhenMessageInvalid(): void
    {
        $mock = $this->getPreparedMock(GenerateHashHandler::class, ['handleMessage', 'isMessageValid']);

        // Expect not call to handleMessage when isValidMessage returns false
        $mock->method('isMessageValid')->willReturn(false);
        $mock->expects($this->never())->method('handler');

        $mock->__invoke($this->createMessage([], false));
    }

    public function testHandleMethodCallsHandleMessageWhenMessageValid(): void
    {
        $mock = $this->getPreparedMock(GenerateHashHandler::class, ['handleMessage', 'isMessageValid']);

        // Expect a call to handleMessage with respective arguments from message when isValidMessage returns true
        $message = $this->createMessage([Uuid::randomHex(), Uuid::randomHex()], false);

        $mock->method('isMessageValid')->willReturn(true);
        $mock->expects($this->atLeastOnce())
            ->method('handler')
            ->with($message->getMediaIds(), $message->readContext())
            ->willReturnCallback(ProviderUtils::generator());

        $mock->__invoke($message);
    }

    /**
     * @dataProvider \Eyecook\Blurhash\Test\ProviderUtils::provideRandomIds()
     */
    public function testRepositoryCalledWithCorrectMediaIdsInFilterCriteria(array $mediaIds): void
    {
        $criteria = new Criteria(); // The expected Criteria
        $criteria->addFilter(new EqualsAnyFilter('media.id', $mediaIds));

        $searchResultMock = $this->createStub(EntitySearchResult::class);
        $searchResultMock->method('getEntities')->willReturn(new EntityCollection([])); // We don't need to actually process

        $mockMediaRepository = $this->createStub(EntityRepository::class);
        // Expect one call to search method
        $mockMediaRepository
            ->expects($this->once())
            ->method('search')
            ->with($criteria, $this->isInstanceOf(Context::class)) // With this Args
            ->willReturn($searchResultMock);

        $mockHandler = $this->getPreparedMock(
            GenerateHashHandler::class,
            ['isMessageValid'],
            ['media.repository' => $mockMediaRepository]
        );

        $mockHandler->method('isMessageValid')->willReturn(true); // Make sure validation is not breaking this test

        $mockHandler->__invoke($this->createMessage($mediaIds, true));
    }

    /**
     * @dataProvider \Eyecook\Blurhash\Test\ProviderUtils::provideRandomIds()
     */
    public function testHandleMessage(array $mediaIds): void
    {
        $mediaObjects = [];
        $mediaObjectsParams = [];
        $failedIndex = Random::getInteger(0, count($mediaIds) - 1);

        foreach ($mediaIds as $key => $mediaId) {
            $entity = new MediaEntity();
            $entity->setId($mediaId);

            if ($failedIndex !== $key) {
                // The one without versionId will be the failure
                $entity->setVersionId($mediaId);
            }

            $mediaObjects[] = $entity;
            $mediaObjectsParams[] = [$entity];
        }

        $collection = new MediaCollection($mediaObjects);

        $mockHashMediaService = $this->createStub(HashMediaService::class);
        $mockHashMediaService
            ->expects($this->exactly(count($mediaIds)))
            ->method('processHashForMedia')
            ->withConsecutive(...$mediaObjectsParams)
            ->willReturnCallback(static function (MediaEntity $media) {
                return $media->getVersionId();
            });

        $mockLogger = $this->getMockForAbstractClass(LoggerInterface::class);
        $mockLogger
            ->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Blurhash generation has been failed'),
                ['mediaIds' => [$mediaIds[$failedIndex]]]
            );

        $handlerMock = $this->getPreparedMock(
            GenerateHashHandler::class,
            ['getMediaByIds', 'isMessageValid'],
            [HashMediaService::class => $mockHashMediaService, 'monolog.logger' => $mockLogger],
        );

        $handlerMock->expects($this->once())->method('isMessageValid')->willReturn(true);
        $handlerMock->expects($this->once())->method('getMediaByIds')->willReturn($collection);

        $handlerMock->__invoke($this->createMessage($mediaIds, true));
    }

    private function createMessage(array $mediaIds, bool $ignoreManualMode): GenerateHashMessage
    {
        $message = new GenerateHashMessage();
        $message->setMediaIds($mediaIds);
        $message->setIgnoreManualMode($ignoreManualMode);
        $message->withContext(Context::createDefaultContext());

        return $message;
    }
}
