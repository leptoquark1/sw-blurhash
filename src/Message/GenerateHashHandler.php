<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Message;

use Eyecook\Blurhash\Hash\HashMediaService;
use Eyecook\Blurhash\Configuration\ConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\Message\GenerateThumbnailsMessage;
use Shopware\Core\Content\Media\Message\UpdateThumbnailsMessage;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\Framework\App\Exception\InvalidArgumentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class GenerateHashHandler extends AbstractMessageHandler
{
    protected ConfigService $config;
    protected HashMediaService $hashMediaService;
    protected EntityRepositoryInterface $mediaRepository;
    protected CacheClearer $cacheClearer;
    protected LoggerInterface $logger;

    public function __construct(
        ConfigService $config,
        HashMediaService $hashMediaService,
        EntityRepositoryInterface $mediaRepository,
        CacheClearer $cacheClearer,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->hashMediaService = $hashMediaService;
        $this->mediaRepository = $mediaRepository;
        $this->cacheClearer = $cacheClearer;
        $this->logger = $logger;
    }

    public static function getHandledMessages(): iterable
    {
        return [
            GenerateHashMessage::class,
        ];
    }

    public function handle($message): void
    {
        if (!$this->config->isPluginManualMode() || !$this->isMessageValid($message)) {
            return;
        }

        $this->handleMessage($message->getMediaIds(), $message->readContext());
    }

    public function handleIterative($message): ?\Generator
    {
        if (!$this->isMessageValid($message)) {
            return null;
        }
        return $this->handleMessage($message->getMediaIds(), $message->readContext(), true);
    }

    protected function handleMessage(array $givenMediaIds, Context $context, bool $isIterator = false): ?\Generator
    {
        $mediaEntities = $this->getMediaByIds($givenMediaIds, $context);

        $failedIds = [];
        foreach ($mediaEntities as $media) {
            $hash = $this->hashMediaService->processHashForMedia($media);

            $mediaId = $media->getId();
            $name = $media->getTitle() ?? $media->getFileName();

            if (!$hash) {
                $failedIds[] = $mediaId;
            }

            $mediaEntities->remove($mediaId);
            gc_collect_cycles();

            if ($isIterator) {
                yield ['id' => $mediaId, 'hash' => $hash, 'name' => $name];
            }
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
        if (!$message || !is_object($message) || method_exists($message, 'getMediaIds') === false) {
            throw new InvalidArgumentException('Invalid Message invoked, unable to handle given message.');
        }

        return count($message->getMediaIds()) > 0;
    }
}
