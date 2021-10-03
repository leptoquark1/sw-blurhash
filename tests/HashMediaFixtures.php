<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Test;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaType\ImageType;
use Shopware\Core\Content\Test\Media\MediaFixtures;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package EyeCook\BlurHash\Test
 * @author David Fecke (+leptoquark1)
 */
trait HashMediaFixtures
{
    use MediaFixtures;

    protected function getValidLocalMediaForHash($fileExt = 'jpg', $mimeType = 'image/jpeg'): MediaEntity
    {
        $media = new MediaEntity();

        $media->setId(Uuid::randomHex());
        $media->setMetaData(['width' => 1, 'height' => 1, 'blurhash' => '1']);
        $media->setFileName('validBlurhashMedia' . '.' . $fileExt);
        $media->setFileExtension($fileExt);
        $media->setMimeType($mimeType);
        $media->setFileSize(1024);
        $media->setMediaType(new ImageType());
        $media->setPrivate(false);

        return $media;
    }

    protected function getValidExistingMediaForHash(): MediaEntity
    {
        /** @var MediaEntity $media */
        $media = $this->createFixture(
            'validExistingBlurhashMedia',
            [
                'validExistingBlurhashMedia' => [
                    'id' => Uuid::randomHex(),
                    'mimeType' => 'image/jpeg',
                    'fileExtension' => 'jpg',
                    'fileName' => 'validExistingBlurhashMedia',
                    'fileSize' => 1024,
                    'mediaType' => new ImageType(),
                    'private' => false,
                    'metaData' => ['width' => 1, 'height' => 1, 'blurhash' => '1'],
                    'uploadedAt' => new \DateTime('2021-08-14T11:17:06.012345Z'),
                ]
            ],
            self::getFixtureRepository('media')
        );

        return $media;
    }
}
