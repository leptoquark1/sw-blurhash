<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Controller\Administration;

use Eyecook\Blurhash\Controller\AbstractApiController;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaProvider;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaUpdater;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class RemovalController extends AbstractApiController
{
    public function __construct(
        protected readonly HashMediaProvider $hashMediaProvider,
        protected readonly HashMediaUpdater $hashMediaUpdater
    ) {
    }

    #[Route(path: '/api/_action/eyecook/blurhash/remove/media/{mediaId}', name: 'api.action.eyecook.blurhash.remove.media-id', defaults: ['auth_required' => true], methods: ['GET'])]
    public function removeByMediaId(?string $mediaId, Request $request, Context $context): JsonResponse
    {
        if (!$mediaId) {
            throw new MissingRequestParameterException('mediaId');
        }

        $this->hashMediaUpdater->removeMediaHash($mediaId);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/_action/eyecook/blurhash/remove/media', name: 'api.action.eyecook.blurhash.remove.media', defaults: ['auth_required' => true], methods: ['POST'])]
    public function removeByMediaIds(Request $request, Context $context): JsonResponse
    {
        if ($request->request->has('mediaIds') === false) {
            throw new MissingRequestParameterException('mediaIds');
        }

        $mediaIds = $request->request->all('mediaIds');
        if (count($mediaIds) < 1) {
            throw new InvalidRequestParameterException('mediaIds');
        }

        $this->hashMediaUpdater->removeMediaHash($mediaIds);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/_action/eyecook/blurhash/remove/folder/{folderId}', name: 'api.action.eyecook.blurhash.remove.folder-id', defaults: ['auth_required' => true], methods: ['GET'])]
    public function removeByFolderId(?string $folderId, Request $request, Context $context): JsonResponse
    {
        if (!$folderId) {
            throw new MissingRequestParameterException('folderId');
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mediaFolderId', $folderId));

        $idResult = $this->hashMediaProvider->searchMediaIdsWithHash($context, $criteria);

        $this->hashMediaUpdater->removeMediaHash($idResult->getIds());

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/_action/eyecook/blurhash/remove/folder', name: 'api.action.eyecook.blurhash.remove.folder', defaults: ['auth_required' => true], methods: ['POST'])]
    public function removeByFolderIds(Request $request, Context $context): JsonResponse
    {
        if ($request->request->has('folderIds') === false) {
            throw new MissingRequestParameterException('folderIds');
        }

        /** @var array $mediaFolderIds */
        $mediaFolderIds = $request->request->all('folderIds');
        if (count($mediaFolderIds) < 1) {
            throw new InvalidRequestParameterException('folderIds');
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaFolderId', $mediaFolderIds));

        $idResult = $this->hashMediaProvider->searchMediaIdsWithHash($context, $criteria);

        $this->hashMediaUpdater->removeMediaHash($idResult->getIds());

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
