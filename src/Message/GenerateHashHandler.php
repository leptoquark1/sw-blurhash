<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Message;

use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Exception\ProcessBlurhashRuntimeException;
use Eyecook\Blurhash\Hash\HashMediaService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
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
    public function __construct(
        protected readonly ConfigService $config,
        protected readonly HashMediaService $hashMediaService,
        protected readonly EntityRepository $mediaRepository,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws ProcessBlurhashRuntimeException
     */
    public function __invoke(GenerateHashMessage $message): void
    {
        if ($this->isMessageValid($message) === false) {
            return;
        }

        $generator = $this->handler($message->getMediaIds(), $message->readContext());
        while ($generator->valid()) {
            $generator->next();
        }
    }

    /**
     * @throws ProcessBlurhashRuntimeException
     */
    protected function handler(array $givenMediaIds, Context $context): \Generator
    {
        $mediaEntities = $this->getMediaByIds($givenMediaIds, $context);

        $missingIds = array_diff($mediaEntities->getIds(), $givenMediaIds);
        if (count($missingIds)) {
            $this->logger->warning(
                'Blurhash generation cannot process missing media-entities.',
                ['missingIds' => $missingIds]
            );
        }

        $failedIds = [];
        foreach ($mediaEntities as $media) {
            // Exceptions during processing are to be expected and are forwarded
            // to the exception-handler of the bus
            $hash = $this->hashMediaService->processHashForMedia($media);

            if (!$hash) {
                $failedIds[] = $media->getId();
            }

            $mediaEntities->remove($media->getId());
            gc_collect_cycles();

            yield ['id' => $media->getId()];
        }

        if (count($failedIds)) {
            $this->logger->warning(
                'Some Blurhashes cannot be processed. Make sure to exclude unprocessable media-entities.',
                ['mediaIds' => $failedIds]
            );
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
