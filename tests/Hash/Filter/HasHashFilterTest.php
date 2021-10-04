<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Hash\Filter;

use EyeCook\BlurHash\Hash\Filter\HasHashFilter;
use EyeCook\BlurHash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class HasHashFilterTest extends TestCase
{
    use IntegrationTestBehaviour, HashMediaFixtures;

    private EntityRepositoryInterface $mediaRepository;
    private Context $context;

    protected function setUp(): void
    {
        $this->mediaRepository = $this->getContainer()->get('media.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testCriteriaIntegration(): void
    {
        $media = $this->getValidExistingMediaForHash();

        $criteria = new Criteria();
        $criteria->addFilter(new HasHashFilter());

        $foundMedia = $this->mediaRepository->searchIds($criteria, $this->context)->has($media->getId());

        static::assertTrue($foundMedia);
    }
}
