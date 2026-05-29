<?php declare(strict_types=1);

namespace Sven\DasGoogle\Controller\Api;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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
        private readonly EntityRepository $reviewRepository,
        private readonly EntityRepository $salesChannelRepository,
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
        $context = Context::createDefaultContext();
        $salesChannelId = $request->request->get('salesChannelId');
        $scope = $salesChannelId ? (string) $salesChannelId : null;

        // Falls global nicht konfiguriert, finde irgendeinen Sales Channel mit Config.
        if ($scope === null) {
            $globalPlace = trim((string) $this->systemConfigService->getString(
                'SvenDasGoogle.config.googlePlaceId',
                null
            ));
            if ($globalPlace === '') {
                $scope = $this->findFirstSalesChannelWithPlaceId($context);
            }
        }

        $result = $this->googlePlacesService->forceRefresh($scope);

        return new JsonResponse($result);
    }

    private function findFirstSalesChannelWithPlaceId(Context $context): ?string
    {
        $salesChannelIds = $this->salesChannelRepository->searchIds(new Criteria(), $context)->getIds();

        foreach ($salesChannelIds as $id) {
            $value = trim((string) $this->systemConfigService->getString(
                'SvenDasGoogle.config.googlePlaceId',
                (string) $id
            ));
            if ($value !== '') {
                return (string) $id;
            }
        }

        return null;
    }

    #[Route(path: '/api/sven-das-google/stats', name: 'api.sven_das_google.stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $context = Context::createDefaultContext();

        $placeId = trim((string) $this->systemConfigService->getString(
            'SvenDasGoogle.config.googlePlaceId',
            null
        ));

        if ($placeId === '') {
            $placeId = $this->findFirstConfiguredPlaceId($context);
        }

        $totalCriteria = new Criteria();
        $totalCriteria->setLimit(1);
        $totalCriteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
        $totalAll = $this->reviewRepository->searchIds($totalCriteria, $context)->getTotal();

        return new JsonResponse([
            'configured' => $placeId !== '' || $totalAll > 0,
            'placeId' => $placeId,
            'total' => $totalAll,
        ]);
    }

    private function findFirstConfiguredPlaceId(Context $context): string
    {
        $scId = $this->findFirstSalesChannelWithPlaceId($context);
        if ($scId === null) {
            return '';
        }

        return trim((string) $this->systemConfigService->getString(
            'SvenDasGoogle.config.googlePlaceId',
            $scId
        ));
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
