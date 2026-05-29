<?php declare(strict_types=1);

namespace Sven\DasGoogle\Controller\Api;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sven\DasGoogle\Service\GooglePlacesService;
use Sven\DasGoogle\Service\ReviewMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class GoogleApiTestController extends AbstractController
{
    public function __construct(
        private readonly GooglePlacesService $googlePlacesService,
        private readonly SystemConfigService $systemConfigService,
        private readonly ReviewMailService $reviewMailService,
    ) {
    }

    #[Route(path: '/api/sven-das-google/test-connection', name: 'api.sven_das_google.test_connection', methods: ['POST'])]
    public function testConnection(Request $request): JsonResponse
    {
        $salesChannelId = $request->request->get('salesChannelId');

        $result = $this->googlePlacesService->testConnection(
            $salesChannelId ? (string) $salesChannelId : null
        );

        return new JsonResponse($result);
    }

    #[Route(path: '/api/sven-das-google/refresh-reviews', name: 'api.sven_das_google.refresh_reviews', methods: ['POST'])]
    public function refreshReviews(Request $request): JsonResponse
    {
        $salesChannelId = $request->request->get('salesChannelId');

        $result = $this->googlePlacesService->forceRefresh(
            $salesChannelId ? (string) $salesChannelId : null
        );

        return new JsonResponse($result);
    }

    #[Route(path: '/api/sven-das-google/stats', name: 'api.sven_das_google.stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $salesChannelId = $request->query->get('salesChannelId');
        $placeId = $this->systemConfigService->getString(
            'SvenDasGoogle.config.googlePlaceId',
            $salesChannelId ? (string) $salesChannelId : null
        );

        if (empty($placeId)) {
            return new JsonResponse(['configured' => false, 'total' => 0]);
        }

        return new JsonResponse([
            'configured' => true,
            'placeId' => $placeId,
            'total' => $this->googlePlacesService->countReviewsForPlace($placeId),
        ]);
    }

    #[Route(
        path: '/api/_action/sven-das-google/send-review-mail/{orderId}',
        name: 'api.action.sven_das_google.send_review_mail',
        methods: ['POST']
    )]
    public function sendReviewMail(string $orderId): JsonResponse
    {
        try {
            $this->reviewMailService->sendForOrder($orderId);
            return new JsonResponse(['success' => true, 'message' => 'Bewertungsmail wurde versendet.']);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Versand fehlgeschlagen: ' . $e->getMessage(),
            ], 500);
        }
    }
}
