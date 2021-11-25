<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Message;

use Eyecook\Blurhash\Message\DelegateMessagesSubscriber;
use Eyecook\Blurhash\Message\GenerateHashMessage;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\DataAbstractionLayer\CategoryIndexingMessage;
use Shopware\Core\Content\ImportExport\Message\DeleteFileMessage;
use Shopware\Core\Content\Media\Message\GenerateThumbnailsMessage;
use Shopware\Core\Content\Media\Message\UpdateThumbnailsMessage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\MessageQueue\fixtures\TestMessage;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

/**
 * @covers \Eyecook\Blurhash\Message\DelegateMessagesSubscriber
 * @group MessageHandling
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class DelegateMessagesSubscriberTest extends TestCase
{
    use KernelTestBehaviour,
        QueueTestBehaviour;

    protected DelegateMessagesSubscriber $subscriber;
    protected Context $context;

    protected function setUp(): void
    {
        $this->subscriber = $this->getContainer()->get(DelegateMessagesSubscriber::class);
        $this->context = Context::createDefaultContext();
    }

    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = array_keys(DelegateMessagesSubscriber::getSubscribedEvents());

        self::assertCount(1, $subscribedEvents);
        self::assertContainsEquals(WorkerMessageHandledEvent::class, $subscribedEvents);
    }

    public function validMessagesProvider(): array
    {
        return [
            [new GenerateThumbnailsMessage()],
            [new UpdateThumbnailsMessage()],
        ];
    }

    /**
     * @dataProvider validMessagesProvider
     */
    public function testValidMessagesGetDelegated($message): void
    {
        $mediaIds = [Uuid::randomHex()];
        $message->setMediaIds($mediaIds);
        $message->withContext($this->context);
        $validSourceEvent = new WorkerMessageHandledEvent(new Envelope($message), 'test');

        $this->subscriber->afterMessageHandled($validSourceEvent);

        $message = $this->getMessageFromReceiver(GenerateHashMessage::class);

        static::assertInstanceOf(GenerateHashMessage::class, $message);
        static::assertCount(1, $message->getMediaIds());
        static::assertContains($mediaIds[0], $message->getMediaIds());
    }

    public function invalidMessagesProvider(): array
    {
        return [
            [new CategoryIndexingMessage([])],
            [new DeleteFileMessage()],
            [new TestMessage()]
        ];
    }

    /**
     * @dataProvider invalidMessagesProvider
     */
    public function testInvalidMessagesGetDelegated($message): void
    {
        $invalidSourceEvent = new WorkerMessageHandledEvent(new Envelope($message), 'test');
        $this->subscriber->afterMessageHandled($invalidSourceEvent);

        $message = $this->getMessageFromReceiver(GenerateHashMessage::class);
        static::assertNull($message);
    }

    private function getMessageFromReceiver(string $className): ?object
    {
        $envelopes = $this->getReceiver()->get();
        $message = null;
        foreach ($envelopes as $envelope) {
            if (get_class($envelope->getMessage()) === $className) {
                $message = $envelope->getMessage();
                break;
            }
        }

        return $message;
    }
}
