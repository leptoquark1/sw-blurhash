<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test\Hash;

use EyeCook\BlurHash\Configuration\Config;
use EyeCook\BlurHash\Hash\HashMediaService;
use EyeCook\BlurHash\Test\ConfigMockStub;
use EyeCook\BlurHash\Test\HashMediaFixtures;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
class HashMediaServiceTest extends TestCase
{
    use IntegrationTestBehaviour,
        HashMediaFixtures,
        ConfigMockStub;

    protected Context $context;
    protected ?HashMediaService $hashMediaService;
    protected ?UrlGeneratorInterface $urlGenerator;
    protected ?EntityRepositoryInterface $mediaRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Context::createDefaultContext();
        $this->setFixtureContext($this->context);

        $this->setUpSystemConfigService();
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 1);
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 1);

        $this->hashMediaService = $this->getContainer()->get(HashMediaService::class);
        $this->urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $this->mediaRepository = $this->getContainer()->get('media.repository');
    }

    protected function tearDown(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_COMPONENTS_X);
        $this->unsetSystemConfigMock(Config::PATH_COMPONENTS_Y);
    }

    public function testGeneratedHashForMediaIsPersisted(): void
    {
        $media = $this->getPngWithFolder();
        $this->mediaRepository->update([
            [
                'id' => $media->getId(),
                'metaData' => [
                    'height' => 266,
                    'width' => 466,
                ],
            ]
        ], $this->context);

        /** @var MediaEntity $media */
        $media = $this->mediaRepository->search(new Criteria([$media->getId()]), $this->context)->get($media->getId());

        $this->getPublicFilesystem()->putStream(
            $this->urlGenerator->getRelativeMediaUrl($media),
            fopen(__DIR__ . '/fixtures/shopware-logo.png', 'rb')
        );

        $resultHash = $this->hashMediaService->processHashForMedia($media);
        static::assertNotNull($resultHash);

        $media = $this->mediaRepository->search(new Criteria([$media->getId()]), $this->context)->get($media->getId());
        ['blurhash' => $mediaHash] = $media->getMetaData();

        static::assertEquals($resultHash, $mediaHash);
        static::assertEquals('00M*,h', $mediaHash);
    }
}
