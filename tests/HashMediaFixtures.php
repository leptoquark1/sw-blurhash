<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test;

use DateTime;
use Eyecook\Blurhash\Hash\Media\MediaHashMeta;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaType\ImageType;
use Shopware\Core\Content\Test\Media\MediaFixtures;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
trait HashMediaFixtures
{
    use KernelTestBehaviour,
        DatabaseTransactionBehaviour,
        MediaFixtures;

    protected function getValidLocalMediaForHash(?string $fileExt = 'jpg', ?string $mimeType = 'image/jpeg', array $unset = []): MediaEntity
    {
        $media = new MediaEntity();

        if (in_array('id', $unset, true) === false) {
            $media->setId(Uuid::randomHex());
        }

        if (in_array('metaData', $unset, true) === false) {
            $media->setMetaData(['width' => 1, 'height' => 1, 'blurhash' => '1']);
        }

        if (in_array('fileName', $unset, true) === false) {
            $media->setFileName('validBlurhashMedia' . '.' . $fileExt);
        }

        if ($fileExt !== null) {
            $media->setFileExtension($fileExt);
        }

        if ($mimeType !== null) {
            $media->setMimeType($mimeType);
        }

        if (in_array('fileSize', $unset, true) === false) {
            $media->setFileSize(1024);
        }

        if (in_array('mediaType', $unset, true) === false) {
            $media->setMediaType(new ImageType());
        }

        $media->setPrivate(false);

        return $media;
    }

    protected function getValidExistingMediaForHash(bool $withBlurhash = true, bool $withFolder = false): MediaEntity
    {
        $id = Uuid::randomHex();
        $data = [
            'id' => $id,
            'mimeType' => 'image/jpeg',
            'fileExtension' => 'jpg',
            'fileName' => 'validExistingBlurhashMedia_' . $id,
            'fileSize' => 1024,
            'mediaType' => new ImageType(),
            'private' => false,
            'metaData' => ['width' => 1, 'height' => 1],
            'uploadedAt' => new DateTime('2021-08-14T11:17:06.012345Z'),
        ];

        if ($withBlurhash) {
            $data['metaData'][MediaHashMeta::$PROP_HASH] = '1';
            $data['metaData'][MediaHashMeta::$PROP_HEIGHT] = '1';
            $data['metaData'][MediaHashMeta::$PROP_WIDTH] = '1';
        }

        if ($withFolder) {
            $data['mediaFolder'] = [
                'name' => 'test folder ' . $id,
                'useParentConfiguration' => false,
                'configuration' => [
                    'createThumbnails' => false,
                ],
            ];
        }

        /** @var MediaEntity $media */
        $media = $this->createFixture(
            'validExistingBlurhashMedia',
            [
                'validExistingBlurhashMedia' => $data,
            ],
            self::getFixtureRepository('media')
        );

        return $media;
    }
}
