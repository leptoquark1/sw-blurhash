<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Controller;

use Eyecook\Blurhash\Exception\IllegalManualModeLeverageException;
use Eyecook\Blurhash\Hash\Media\HashMediaProvider;
use Eyecook\Blurhash\Hash\Media\MediaValidator;
use Eyecook\Blurhash\Message\GenerateHashMessage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Administrative Api endpoint
 *
 * @RouteScope(scopes={"api"})
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class AdministrationController extends AbstractApiController
{
    protected MessageBusInterface $messageBus;
    protected MediaValidator $mediaValidator;

    public function __construct(MessageBusInterface $messageBus, MediaValidator $mediaValidator)
    {
        $this->messageBus = $messageBus;
        $this->mediaValidator = $mediaValidator;
    }

    /**
     * @Route(
     *     "/api/_action/eyecook/blurhash/generate/media/{mediaId}",
     *     name="api.ec.blurhash.generate.media-id",
     *     defaults={"auth_required"=true},
     *     methods={"GET"},
     * )
     */
    public function generateByMediaEntity(?string $mediaId, Request $request, Context $context): JsonResponse
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
    public function generateByMediaEntities(Request $request, Context $context): JsonResponse
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
    public function mediaFolderIsValid(string $folderId, Request $request, Context $context): JsonResponse
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

    /**
     * @throws IllegalManualModeLeverageException
     */
    private function delegateHashMessage(array $mediaIds, Context $context): void
    {
        $this->preventManualModeLeverage();

        $message = new GenerateHashMessage();
        $message->setMediaIds($mediaIds);
        $message->setIgnoreManualMode(true);
        $message->withContext($context);

        $this->messageBus->dispatch($message);
    }
}
