<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Hash;

use Eyecook\Blurhash\Configuration\Config;
use Eyecook\Blurhash\Hash\HashMediaService;
use Eyecook\Blurhash\Test\ConfigMockStub;
use Eyecook\Blurhash\Test\HashMediaFixtures;
use League\Flysystem\FilesystemException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @package Eyecook\Blurhash\Test
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
    protected ?EntityRepository $mediaRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->entityFixtureContext;

        $this->setSystemConfigMock(Config::PATH_COMPONENTS_X, 1);
        $this->setSystemConfigMock(Config::PATH_COMPONENTS_Y, 1);

        $this->hashMediaService = self::getContainer()->get(HashMediaService::class);
        $this->urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $this->mediaRepository = self::getContainer()->get('media.repository');
    }

    protected function tearDown(): void
    {
        $this->unsetSystemConfigMock(Config::PATH_COMPONENTS_X);
        $this->unsetSystemConfigMock(Config::PATH_COMPONENTS_Y);
    }

    /**
     * @throws FilesystemException
     */
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

        $this->getPublicFilesystem()->writeStream(
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
