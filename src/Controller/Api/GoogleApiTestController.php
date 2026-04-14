<?php declare(strict_types=1);

namespace Sven\DasGoogle\Controller\Api;

use Sven\DasGoogle\Service\GooglePlacesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class GoogleApiTestController extends AbstractController
{
    public function __construct(
        private readonly GooglePlacesService $googlePlacesService,
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
}
