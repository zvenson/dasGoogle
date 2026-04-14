<?php declare(strict_types=1);

namespace Sven\DasGoogle\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GooglePlacesService
{
    private const CACHE_KEY_PREFIX = 'sven_das_google_place_';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly CacheInterface $cache,
        private readonly EntityRepository $reviewRepository,
    ) {
    }

    /**
     * Holt Place-Daten (Name, Rating, URL) aus dem Cache/API
     * und Reviews aus der lokalen Datenbank.
     */
    public function getPlaceData(?string $salesChannelId = null): ?array
    {
        $apiKey = $this->systemConfigService->getString('SvenDasGoogle.config.googleApiKey', $salesChannelId);
        $placeId = $this->systemConfigService->getString('SvenDasGoogle.config.googlePlaceId', $salesChannelId);

        if (empty($apiKey) || empty($placeId)) {
            return null;
        }

        $cacheTtl = $this->systemConfigService->getInt('SvenDasGoogle.config.cacheTtl', $salesChannelId);
        if ($cacheTtl < 1) {
            $cacheTtl = 6;
        }

        $minRating = $this->systemConfigService->getInt('SvenDasGoogle.config.minRating', $salesChannelId);
        if ($minRating < 1 || $minRating > 5) {
            $minRating = 4;
        }

        // Place-Infos + neue Reviews aus API holen (gecacht)
        $cacheKey = self::CACHE_KEY_PREFIX . md5($placeId);
        $placeInfo = $this->cache->get($cacheKey, function (ItemInterface $item) use ($apiKey, $placeId, $cacheTtl): ?array {
            $result = $this->fetchAndStoreReviews($apiKey, $placeId);

            if ($result === null) {
                $item->expiresAfter(300);
            } else {
                $item->expiresAfter($cacheTtl * 3600);
            }

            return $result;
        });

        if ($placeInfo === null) {
            return null;
        }

        // Reviews aus lokaler DB lesen (gefiltert + randomisiert)
        $reviews = $this->getReviewsFromDb($placeId, $minRating);

        $placeInfo['reviews'] = $reviews;

        return $placeInfo;
    }

    /**
     * Testet ob API Key und Place ID funktionieren.
     */
    public function testConnection(?string $salesChannelId = null): array
    {
        $apiKey = $this->systemConfigService->getString('SvenDasGoogle.config.googleApiKey', $salesChannelId);
        $placeId = $this->systemConfigService->getString('SvenDasGoogle.config.googlePlaceId', $salesChannelId);

        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'Kein API Key eingetragen.'];
        }

        if (empty($placeId)) {
            return ['success' => false, 'message' => 'Keine Place ID eingetragen.'];
        }

        $url = sprintf(
            'https://maps.googleapis.com/maps/api/place/details/json?place_id=%s&fields=name,rating,user_ratings_total&language=de&key=%s',
            urlencode($placeId),
            urlencode($apiKey)
        );

        $response = $this->httpGet($url);

        if ($response === null) {
            return ['success' => false, 'message' => 'Google API nicht erreichbar. Bitte Internetverbindung pruefen.'];
        }

        $data = json_decode($response, true);
        $status = $data['status'] ?? 'UNKNOWN';

        return match ($status) {
            'OK' => [
                'success' => true,
                'message' => sprintf(
                    'Verbindung erfolgreich! "%s" - Bewertung: %s/5 (%d Bewertungen)',
                    $data['result']['name'] ?? '?',
                    number_format((float) ($data['result']['rating'] ?? 0), 1, ',', '.'),
                    (int) ($data['result']['user_ratings_total'] ?? 0)
                ),
            ],
            'REQUEST_DENIED' => ['success' => false, 'message' => 'API Key ungueltig oder Places API nicht aktiviert. Bitte in der Google Cloud Console pruefen.'],
            'INVALID_REQUEST' => ['success' => false, 'message' => 'Place ID ungueltig. Bitte mit dem Place ID Finder pruefen.'],
            'NOT_FOUND' => ['success' => false, 'message' => 'Kein Eintrag mit dieser Place ID gefunden.'],
            'OVER_QUERY_LIMIT' => ['success' => false, 'message' => 'API-Kontingent erschoepft. Bitte Abrechnung in der Google Cloud Console pruefen.'],
            default => ['success' => false, 'message' => 'Unbekannter Fehler: ' . $status . ' - ' . ($data['error_message'] ?? '')],
        };
    }

    /**
     * Holt Reviews von Google (2 Sortierungen) und speichert neue in die DB.
     * Gibt nur die Place-Infos zurueck (Name, Rating, URL).
     */
    private function fetchAndStoreReviews(string $apiKey, string $placeId): ?array
    {
        $fields = 'name,rating,user_ratings_total,url,reviews';

        // Abruf 1: relevanteste
        $url1 = sprintf(
            'https://maps.googleapis.com/maps/api/place/details/json?place_id=%s&fields=%s&reviews_sort=most_relevant&language=de&key=%s',
            urlencode($placeId),
            urlencode($fields),
            urlencode($apiKey)
        );

        $response1 = $this->httpGet($url1);
        if ($response1 === null) {
            return null;
        }

        $data1 = json_decode($response1, true);
        if (!isset($data1['result'])) {
            return null;
        }

        // Abruf 2: neueste
        $url2 = sprintf(
            'https://maps.googleapis.com/maps/api/place/details/json?place_id=%s&fields=reviews&reviews_sort=newest&language=de&key=%s',
            urlencode($placeId),
            urlencode($apiKey)
        );

        $response2 = $this->httpGet($url2);
        $data2 = $response2 !== null ? json_decode($response2, true) : null;

        $result = $data1['result'];

        // Alle Reviews sammeln und in DB speichern
        $allRawReviews = array_merge(
            $result['reviews'] ?? [],
            $data2['result']['reviews'] ?? []
        );

        $this->storeReviews($allRawReviews, $placeId);

        return [
            'name' => $result['name'] ?? '',
            'rating' => (float) ($result['rating'] ?? 0),
            'userRatingsTotal' => (int) ($result['user_ratings_total'] ?? 0),
            'url' => $result['url'] ?? '',
        ];
    }

    /**
     * Speichert neue Reviews in die Datenbank (upsert nach Autorenname + Place ID).
     */
    private function storeReviews(array $rawReviews, string $placeId): void
    {
        $context = Context::createDefaultContext();

        // Bestehende Reviews laden
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('placeId', $placeId));
        $existing = $this->reviewRepository->search($criteria, $context);

        $existingByAuthor = [];
        foreach ($existing as $entity) {
            $existingByAuthor[$entity->getAuthorName()] = $entity->getId();
        }

        $upserts = [];
        $seen = [];

        foreach ($rawReviews as $review) {
            $authorName = $review['author_name'] ?? '';
            if (empty($authorName) || isset($seen[$authorName])) {
                continue;
            }
            $seen[$authorName] = true;

            $data = [
                'authorName' => $authorName,
                'rating' => (int) ($review['rating'] ?? 0),
                'text' => $review['text'] ?? '',
                'reviewTime' => (int) ($review['time'] ?? 0),
                'relativeTimeDescription' => $review['relative_time_description'] ?? '',
                'profilePhotoUrl' => $review['profile_photo_url'] ?? '',
                'placeId' => $placeId,
            ];

            if (isset($existingByAuthor[$authorName])) {
                $data['id'] = $existingByAuthor[$authorName];
            } else {
                $data['id'] = Uuid::randomHex();
            }

            $upserts[] = $data;
        }

        if (!empty($upserts)) {
            $this->reviewRepository->upsert($upserts, $context);
        }
    }

    /**
     * Liest Reviews aus der lokalen DB, gefiltert und randomisiert.
     */
    private function getReviewsFromDb(string $placeId, int $minRating): array
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('placeId', $placeId));
        $criteria->addFilter(new RangeFilter('rating', [
            RangeFilter::GTE => $minRating,
        ]));
        $criteria->setLimit(50);

        $results = $this->reviewRepository->search($criteria, $context);

        $reviews = [];
        foreach ($results as $entity) {
            $reviews[] = [
                'authorName' => $entity->getAuthorName(),
                'rating' => $entity->getRating(),
                'text' => $entity->getText() ?? '',
                'relativeTimeDescription' => $entity->getRelativeTimeDescription() ?? '',
                'time' => $entity->getReviewTime(),
                'profilePhotoUrl' => $entity->getProfilePhotoUrl() ?? '',
            ];
        }

        // Randomisieren
        shuffle($reviews);

        return $reviews;
    }

    private function httpGet(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        return $response !== false ? $response : null;
    }
}
