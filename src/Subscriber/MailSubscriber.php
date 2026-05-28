<?php declare(strict_types=1);

namespace Sven\DasGoogle\Subscriber;

use Shopware\Core\Content\Mail\Service\MailBeforeValidateEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailSubscriber implements EventSubscriberInterface
{
    private const REVIEW_URL_TEMPLATE = 'https://search.google.com/local/writereview?placeid=%s';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MailBeforeValidateEvent::class => 'onMailBeforeValidate',
        ];
    }

    public function onMailBeforeValidate(MailBeforeValidateEvent $event): void
    {
        try {
            $templateData = $event->getTemplateData();

            $salesChannelId = $this->extractSalesChannelId($templateData);

            $override = trim((string) $this->systemConfigService->getString(
                'SvenDasGoogle.config.googleReviewUrlOverride',
                $salesChannelId
            ));

            if ($override !== '') {
                $reviewUrl = $override;
            } else {
                $placeId = trim((string) $this->systemConfigService->getString(
                    'SvenDasGoogle.config.googlePlaceId',
                    $salesChannelId
                ));

                if ($placeId === '') {
                    return;
                }

                $reviewUrl = sprintf(self::REVIEW_URL_TEMPLATE, rawurlencode($placeId));
            }

            $templateData['sdgReviewUrl'] = $reviewUrl;
            $event->setTemplateData($templateData);
        } catch (\Throwable $e) {
            // Never break mail delivery because of this subscriber.
        }
    }

    private function extractSalesChannelId(array $templateData): ?string
    {
        if (isset($templateData['salesChannel']) && \is_object($templateData['salesChannel'])
            && \method_exists($templateData['salesChannel'], 'getId')) {
            return $templateData['salesChannel']->getId();
        }

        if (isset($templateData['order']) && \is_object($templateData['order'])
            && \method_exists($templateData['order'], 'getSalesChannelId')) {
            return $templateData['order']->getSalesChannelId();
        }

        return null;
    }
}
