<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Controller\Administration;

use Eyecook\Blurhash\Controller\AbstractApiController;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaProvider;
use Eyecook\Blurhash\Hash\Media\MediaValidator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 * @
 */
class ValidationController extends AbstractApiController
{
    protected MediaValidator $mediaValidator;

    public function __construct(MediaValidator $mediaValidator)
    {
        $this->mediaValidator = $mediaValidator;
    }

    /**
     * @Route(
     *     "/api/_action/eyecook/blurhash/validator/media/{mediaId}",
     *     name="api.action.eyecook.blurhash.validator.media-id",
     *     defaults={"auth_required"=true},
     *     methods={"GET"}
     * )
     */
    public function mediaIsValid(string $mediaId, Request $request, Context $context): JsonResponse
    {
        if (!$mediaId) {
            throw new MissingRequestParameterException('mediaId');
        }

        $mediaRepository = $this->container->get('media.repository');
        $criteria = HashMediaProvider::buildCriteria([$mediaId]);
        $media = $mediaRepository->search($criteria, $context)->get($mediaId);

        if (!$media) {
            throw new EntityNotFoundException('media', $mediaId);
        }

        $error = $this->mediaValidator->getValidationError($media);

        if ($error !== null) {
            return $this->json([
                'mediaId' => $mediaId,
                'valid' => false,
                'message' => $error,
            ], Response::HTTP_OK);
        }

        return $this->json([
            'mediaId' => $mediaId,
            'valid' => true,
        ], Response::HTTP_OK);
    }

    /**
     * @Route(
     *     "/api/_action/eyecook/blurhash/validator/folder/{folderId}",
     *     name="api.action.eyecook.blurhash.validator.folder-id",
     *     defaults={"auth_required"=true},
     *     methods={"GET"}
     * )
     */
    public function folderIsValid(string $folderId, Request $request, Context $context): JsonResponse
    {
        if (!$folderId) {
            throw new MissingRequestParameterException('folderId');
        }

        $isExcluded = $this->mediaValidator->isExcludedFolderId($folderId);

        if ($isExcluded) {
            return $this->json([
                'folderId' => $folderId,
                'valid' => false,
                'message' => 'Folder is excluded by configuration',
            ], Response::HTTP_OK);
        }

        return $this->json([
            'folderId' => $folderId,
            'valid' => true,
        ], Response::HTTP_OK);
    }
}
