<?php declare(strict_types=1);

namespace Sven\DasGoogle\Cms;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Sven\DasGoogle\Service\GooglePlacesService;

class GoogleReviewsCmsElementResolver extends AbstractCmsElementResolver
{
    public function __construct(
        private readonly GooglePlacesService $googlePlacesService,
    ) {
    }

    public function getType(): string
    {
        return 'google-reviews';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $salesChannelId = $resolverContext->getSalesChannelContext()->getSalesChannelId();
        $placeData = $this->googlePlacesService->getPlaceData($salesChannelId);

        if ($placeData === null) {
            return;
        }

        $config = $slot->getFieldConfig();
        $maxReviewsConfig = $config->get('maxReviews');
        $maxReviews = $maxReviewsConfig !== null ? (int) $maxReviewsConfig->getValue() : 5;

        if ($maxReviews > 0 && count($placeData['reviews']) > $maxReviews) {
            $placeData['reviews'] = array_slice($placeData['reviews'], 0, $maxReviews);
        }

        $slot->setData(new ArrayStruct($placeData));
    }
}
