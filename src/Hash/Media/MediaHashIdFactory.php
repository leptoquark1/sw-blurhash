<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Media;

use Eyecook\Blurhash\Configuration\ConfigService;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Factory for MediaHashIds
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class MediaHashIdFactory {
    protected ConfigService $config;
    protected MediaValidator $mediaValidator;
    protected EntityRepositoryInterface $mediaRepository;

    public function __construct(
        ConfigService $config,
        MediaValidator $mediaValidator,
        EntityRepositoryInterface $mediaRepository
    ) {
        $this->config = $config;
        $this->mediaValidator = $mediaValidator;
        $this->mediaRepository = $mediaRepository;
    }

    public function fromMedia(MediaEntity $entity): ?MediaHashId
    {
        if ($this->mediaValidator->validate($entity) === false) {
            return null;
        }

        return new MediaHashId($entity);
    }

    public function create(?MediaEntity $entity): MediaHashId
    {
        return new MediaHashId($entity);
    }

    public function fromMediaId(string $id, ?Context $context): MediaHashId
    {
        $result = $this->mediaRepository->search(new Criteria([$id]), $context ?? Context::createDefaultContext());
        $media = $result->get($id);

        if (!$media) {
            throw new EntityNotFoundException('media', $id);
        }

        return $this->fromMedia($media);
    }
}
