<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Controller;

use Eyecook\Blurhash\Message\GenerateHashMessage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
class AdministrationController extends AbstractController
{
    protected MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
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

    private function delegateHashMessage(array $mediaIds, Context $context): void
    {
        $message = new GenerateHashMessage();
        $message->setMediaIds($mediaIds);
        $message->withContext($context);

        $this->messageBus->dispatch($message);
    }
}
