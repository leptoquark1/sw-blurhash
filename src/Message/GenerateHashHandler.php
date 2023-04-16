<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Message;

use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Hash\HashMediaService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
#[AsMessageHandler(handles: GenerateHashMessage::class)]
class GenerateHashHandler
{
    protected ConfigService $config;
    protected HashMediaService $hashMediaService;
    protected EntityRepositoryInterface $mediaRepository;
    protected LoggerInterface $logger;

    public function __construct(
        ConfigService $config,
        HashMediaService $hashMediaService,
        EntityRepositoryInterface $mediaRepository,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->hashMediaService = $hashMediaService;
        $this->mediaRepository = $mediaRepository;
        $this->logger = $logger;
    }

    public function __invoke(GenerateHashMessage $message): void
    {
        $this->handle($message);
    }

    public function handle($message): void
    {
        if ($this->isMessageValid($message) === false) {
            return;
        }

        $generator = $this->handleMessage($message->getMediaIds(), $message->readContext());
        while ($generator->valid()) {
            $generator->next();
        }
    }

    protected function handleMessage(array $givenMediaIds, Context $context): \Generator
    {
        $mediaEntities = $this->getMediaByIds($givenMediaIds, $context);

        $failedIds = [];
        foreach ($mediaEntities as $media) {
            $hash = $this->hashMediaService->processHashForMedia($media);
            $mediaId = $media->getId();

            if (!$hash) {
                $failedIds[] = $mediaId;
            }

            $mediaEntities->remove($mediaId);
            gc_collect_cycles();

            yield ['id' => $mediaId];
        }

        if (count($failedIds)) {
            $this->logger->warning('Blurhash generation has been failed for ' . count($failedIds) . ' media entities!', [
                'mediaIds' => $failedIds
            ]);
        }
    }

    protected function getMediaByIds(array $mediaIds, Context $context): EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('media.id', $mediaIds));

        return $this->mediaRepository->search($criteria, $context)->getEntities();
    }

    protected function isMessageValid($message): bool
    {
        if (
            !$message
            || !is_object($message)
            || method_exists($message, 'getMediaIds') === false
            || method_exists($message, 'isIgnoreManualMode') === false
        ) {
            throw new \InvalidArgumentException('Invalid Message invoked, unable to handle given message.');
        }

        /** @var GenerateHashMessage $message */
        return count($message->getMediaIds()) > 0 && ($this->config->isPluginManualMode() === false || $message->isIgnoreManualMode());
    }
}
