<?php

namespace EyeCook\BlurHash\Message;

use Shopware\Core\Content\Media\Message\GenerateThumbnailsMessage;
use Shopware\Core\Content\Media\Message\UpdateThumbnailsMessage;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

class DelegateMessagesHandler extends AbstractMessageHandler
{
    protected MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getHandledMessages(): iterable
    {
        return [
            GenerateThumbnailsMessage::class => 150,
            UpdateThumbnailsMessage::class => 150,
        ];
    }

    public function handle($message): void
    {
        if ($message instanceof GenerateThumbnailsMessage === false) {
            return;
        }

        foreach (array_chunk($message->getMediaIds(), 10) as $chunk) {
            $delegateMessage = new GenerateHashMessage();
            $delegateMessage->setMediaIds($chunk);
            $delegateMessage->withContext($message->readContext());

            $this->messageBus->dispatch($delegateMessage);
        }
    }
}
