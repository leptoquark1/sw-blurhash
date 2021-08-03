<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Message;

use EyeCook\BlurHash\Hash\HashMediaService;
use EyeCook\BlurHash\Configuration\ConfigService;
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
 * @package EyeCook\BlurHash
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
        return [GenerateHashMessage::class];
    }

    public function handle($message): void
    {
        if ($this->config->isPluginManualMode() || $this->isMessageValid($message) === false) {
            return;
        }

        $generator = $this->handleMessage($message->getMediaIds(), $message->readContext());
        while ($generator->valid()) {
            $generator->next();
        }
    }

    public function handleIterative($message): ?\Generator
    {
        if (!$this->isMessageValid($message)) {
            return null;
        }

        return $this->handleMessage($message->getMediaIds(), $message->readContext());
    }

    protected function handleMessage(array $givenMediaIds, Context $context): \Generator
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

            yield ['id' => $mediaId, 'hash' => $hash, 'name' => $name];
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
