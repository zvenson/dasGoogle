<?php declare(strict_types=1);

namespace Sven\DasGoogle\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Sven\DasGoogle\Service\GooglePlacesService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorefrontSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly GooglePlacesService $googlePlacesService,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onStorefrontRender',
        ];
    }

    public function onStorefrontRender(StorefrontRenderEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        $widgetActive = $this->systemConfigService->get('SvenDasGoogle.config.widgetActive', $salesChannelId);

        // Default ist true - nur deaktivieren wenn explizit auf false gesetzt
        if ($widgetActive === false) {
            return;
        }

        $placeData = $this->googlePlacesService->getPlaceData($salesChannelId);

        if ($placeData === null) {
            return;
        }

        $widgetPosition = $this->systemConfigService->getString('SvenDasGoogle.config.widgetPosition', $salesChannelId) ?: 'right';
        $widgetVerticalPosition = $this->systemConfigService->getString('SvenDasGoogle.config.widgetVerticalPosition', $salesChannelId) ?: 'center';
        $widgetBgColor = $this->systemConfigService->getString('SvenDasGoogle.config.widgetBackgroundColor', $salesChannelId) ?: '#ffffff';

        $event->setParameter('svenDasGoogleWidget', [
            'active' => true,
            'position' => $widgetPosition,
            'verticalPosition' => $widgetVerticalPosition,
            'backgroundColor' => $widgetBgColor,
            'placeData' => $placeData,
        ]);
    }
}
