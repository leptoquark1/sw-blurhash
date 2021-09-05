<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Hash\Media;

use EyeCook\BlurHash\Configuration\ConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NorFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;

/**
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class HashMediaProvider
{
    protected ConfigService $config;
    protected EntityRepositoryInterface $mediaRepository;

    public function __construct(ConfigService $config, EntityRepositoryInterface $mediaRepository)
    {
        $this->config = $config;
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * @param array|Criteria|null $paramsOrCriteria
     */
    public static function buildCriteria($paramsOrCriteria = []): Criteria
    {
        $criteria = $paramsOrCriteria instanceof Criteria
            ? $paramsOrCriteria
            : new Criteria($paramsOrCriteria ?? []);

        self::addAssociations($criteria);

        return $criteria;
    }

    public static function addAssociations(Criteria $criteria): void
    {
        $criteria->addAssociation('mediaFolder');
        $criteria->addAssociation('thumbnails');
        $criteria->addAssociation('tags');
    }

    public function searchValidMedia(Context $context, ?Criteria $criteria = null): EntitySearchResult
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

        return $this->mediaRepository->search($criteria, $context);
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
}
