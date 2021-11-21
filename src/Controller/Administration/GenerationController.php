<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Controller\Administration;

use Eyecook\Blurhash\Controller\AbstractApiController;
use Eyecook\Blurhash\Exception\IllegalManualModeLeverageException;
use Eyecook\Blurhash\Hash\Filter\NoHashFilter;
use Eyecook\Blurhash\Hash\Media\HashMediaProvider;
use Eyecook\Blurhash\Hash\Media\MediaValidator;
use Eyecook\Blurhash\Message\GenerateHashMessage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class GenerationController extends AbstractApiController
{
    protected MessageBusInterface $messageBus;
    protected MediaValidator $mediaValidator;
    protected HashMediaProvider $hashMediaProvider;

    public function __construct(MessageBusInterface $messageBus, MediaValidator $mediaValidator, HashMediaProvider $hashMediaProvider)
    {
        $this->messageBus = $messageBus;
        $this->mediaValidator = $mediaValidator;
        $this->hashMediaProvider = $hashMediaProvider;
    }

    /**
     * @Route(
     *     "/api/_action/eyecook/blurhash/generate/media/{mediaId}",
     *     name="api.action.eyecook.blurhash.generate.media-id",
     *     defaults={"auth_required"=true},
     *     methods={"GET"},
     * )
     */
    public function generateByMediaId(?string $mediaId, Request $request, Context $context): JsonResponse
    {
        if (!$mediaId) {
            throw new MissingRequestParameterException('mediaId');
        }

        $this->delegateHashMessage([$mediaId], $context);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/_action/eyecook/blurhash/generate/media",
     *     name="api.action.eyecook.blurhash.generate.media",
     *     defaults={"auth_required"=true},
     *     methods={"POST"}
     * )
     */
    public function generateByMediaIds(Request $request, Context $context): JsonResponse
    {
        if ($request->request->has('mediaIds') === false) {
            throw new MissingRequestParameterException('mediaIds');
        }

        $mediaIds = $request->request->get('mediaIds');
        if (is_array($mediaIds) === false) {
            throw new InvalidRequestParameterException('mediaIds');
        }

        $this->delegateHashMessage($mediaIds, $context);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/_action/eyecook/blurhash/generate/folder/{folderId}",
     *     name="api.action.eyecook.blurhash.generate.folder-id",
     *     defaults={"auth_required"=true},
     *     methods={"GET"},
     * )
     */
    public function generateByFolderId(?string $folderId, Request $request, Context $context): JsonResponse
    {
        if (!$folderId) {
            throw new MissingRequestParameterException('folderId');
        }

        if ($this->mediaValidator->isExcludedFolderId($folderId)) {
            throw new UnprocessableEntityHttpException('Folder is excluded by configuration');
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mediaFolderId', $folderId));

        if ($request->query->getBoolean('all') === false) {
            $criteria->addFilter(new NoHashFilter());
        }

        $idResult = $this->hashMediaProvider->searchValidMediaIds($context, $criteria);

        $this->delegateHashMessage($idResult->getIds(), $context);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/_action/eyecook/blurhash/generate/folder",
     *     name="api.action.eyecook.blurhash.generate.folder",
     *     defaults={"auth_required"=true},
     *     methods={"POST"}
     * )
     */
    public function generateByFolderIds(Request $request, Context $context): JsonResponse
    {
        if ($request->request->has('folderIds') === false) {
            throw new MissingRequestParameterException('folderIds');
        }

        $mediaFolderIds = $request->request->get('folderIds');
        if (is_array($mediaFolderIds) === false) {
            throw new InvalidRequestParameterException('folderIds');
        }

        foreach ($mediaFolderIds as $folderId) {
            if ($this->mediaValidator->isExcludedFolderId($folderId)) {
                throw new UnprocessableEntityHttpException('Some folders are excluded by configuration');
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaFolderId', $mediaFolderIds));

        if ($request->request->getBoolean('all') === false) {
            $criteria->addFilter(new NoHashFilter());
        }

        $idResult = $this->hashMediaProvider->searchValidMediaIds($context, $criteria);

        $this->delegateHashMessage($idResult->getIds(), $context);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws IllegalManualModeLeverageException
     */
    private function delegateHashMessage(array $mediaIds, Context $context): void
    {
        $this->preventManualModeLeverage();

        if (count($mediaIds) === 0) {
            return;
        }

        foreach (array_chunk($mediaIds, 10) as $chunk) {
            $message = new GenerateHashMessage();
            $message->setMediaIds($chunk);
            $message->setIgnoreManualMode(true);
            $message->withContext($context);

            $this->messageBus->dispatch($message);
        }
    }
}
