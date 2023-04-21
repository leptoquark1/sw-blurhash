<?php

namespace Eyecook\Blurhash\Message;

use Shopware\Core\Content\Media\Message\GenerateThumbnailsMessage;
use Shopware\Core\Framework\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

class DelegateMessagesSubscriber implements EventSubscriberInterface
{
    public function __construct(protected readonly MessageBusInterface $messageBus)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageHandledEvent::class => 'afterMessageHandled',
        ];
    }

    public function afterMessageHandled(WorkerMessageHandledEvent $message): void
    {
        $handledMessage = $message->getEnvelope()->getMessage();

        if ($handledMessage instanceof GenerateThumbnailsMessage === false) {
            return;
        }

        Context::createDefaultContext()->scope(Context::SYSTEM_SCOPE, function ($scopedContext) use ($handledMessage): void {
            foreach (array_chunk($handledMessage->getMediaIds(), 10) as $chunk) {
                $delegateMessage = new GenerateHashMessage();
                $delegateMessage->setMediaIds($chunk);
                $delegateMessage->withContext($scopedContext);

                $this->messageBus->dispatch($delegateMessage, [new DispatchAfterCurrentBusStamp()]);
            }
        });
    }
}
