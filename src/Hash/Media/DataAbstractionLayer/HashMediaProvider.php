<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash\Media\DataAbstractionLayer;

use Eyecook\Blurhash\Configuration\ConfigService;
use Eyecook\Blurhash\Hash\Filter\HasHashFilter;
use Eyecook\Blurhash\Hash\Filter\NoHashFilter;
use Eyecook\Blurhash\Hash\Media\MediaTypesEnum;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NorFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class HashMediaProvider
{
    public function __construct(
        protected readonly ConfigService $config,
        protected readonly EntityRepository $mediaRepository
    ) {
    }

    public static function buildCriteria(array|Criteria|null $paramsOrCriteria = []): Criteria
    {
        $criteria = $paramsOrCriteria instanceof Criteria
            ? $paramsOrCriteria
            : new Criteria($paramsOrCriteria ?? null);

        self::addAssociations($criteria);

        return $criteria;
    }

    public static function addAssociations(Criteria $criteria): void
    {
        $criteria->addAssociation('mediaFolder');
        $criteria->addAssociation('thumbnails');
        $criteria->addAssociation('tags');
    }

    public function searchMediaWithHash(Context $context, ?Criteria $criteria = null): EntitySearchResult
    {
        $criteria = self::buildCriteria($criteria)->addFilter(new HasHashFilter());

        return $this->mediaRepository->search($criteria, $context);
    }

    public function searchMediaIdsWithHash(Context $context, ?Criteria $criteria = null): IdSearchResult
    {
        $criteria = self::buildCriteria($criteria)->addFilter(new HasHashFilter());

        return $this->mediaRepository->searchIds($criteria, $context);
    }

    public function searchMediaWithoutHash(Context $context, ?Criteria $criteria = null): EntitySearchResult
    {
        $criteria = self::buildCriteria($criteria)->addFilter(new NoHashFilter());

        return $this->mediaRepository->search($criteria, $context);
    }

    public function searchMediaIdsWithoutHash(Context $context, ?Criteria $criteria = null): IdSearchResult
    {
        $criteria = self::buildCriteria($criteria)->addFilter(new NoHashFilter());

        return $this->mediaRepository->searchIds($criteria, $context);
    }

    public function searchValidMedia(Context $context, ?Criteria $criteria = null): EntitySearchResult
    {
        return $this->mediaRepository->search($this->buildValidCriteria($criteria), $context);
    }

    public function searchValidMediaIds(Context $context, ?Criteria $criteria = null): IdSearchResult
    {
        return $this->mediaRepository->searchIds($this->buildValidCriteria($criteria), $context);
    }

    public function searchInvalidMedia(Context $context, ?Criteria $criteria = null): EntitySearchResult
    {
        $criteria = self::buildCriteria($criteria);

        $orFilters = [
            new EqualsAnyFilter('mediaFolderId', $this->config->getExcludedFolders()),
            new EqualsAnyFilter('tags.id', $this->config->getExcludedTags()),
            new NorFilter([
                new EqualsAnyFilter('fileExtension', MediaTypesEnum::FILE_EXTENSIONS),
            ]),
        ];

        if ($this->config->isIncludedPrivate() === false) {
            $orFilters[] = new EqualsFilter('private', true);
        }
        $criteria->addFilter(new OrFilter($orFilters));

        return $this->mediaRepository->search($criteria, $context);
    }

    protected function buildValidCriteria(?Criteria $criteria = null): Criteria
    {
        $criteria = self::buildCriteria($criteria);
        $norConditions = [];

        $criteria->addFilter(new EqualsAnyFilter('fileExtension', MediaTypesEnum::FILE_EXTENSIONS));

        if (count($excludedFolders = $this->config->getExcludedFolders())) {
            $norConditions[] = new EqualsAnyFilter('mediaFolderId', $excludedFolders);
        }

        if (count($excludedTags = $this->config->getExcludedTags())) {
            $norConditions[] = new EqualsAnyFilter('tags.id', $excludedTags);
        }

        if ($this->config->isIncludedPrivate() === false) {
            $criteria->addFilter(new EqualsFilter('private', false));
        }

        if (count($norConditions)) {
            $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, $norConditions));
        }

        return $criteria;
    }
}
