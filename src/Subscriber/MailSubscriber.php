<?php declare(strict_types=1);

namespace Sven\DasGoogle\Subscriber;

use Shopware\Core\Content\Mail\Service\MailBeforeValidateEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sven\DasGoogle\Service\ReviewMailService;
use Sven\DasGoogle\SvenDasGoogle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailSubscriber implements EventSubscriberInterface
{
    private const REVIEW_URL_TEMPLATE = 'https://search.google.com/local/writereview?placeid=%s';

    private ?string $cachedOurTemplateId = null;

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $mailTemplateRepository,
        private readonly ReviewMailService $reviewMailService,
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
                    $reviewUrl = null;
                } else {
                    $reviewUrl = sprintf(self::REVIEW_URL_TEMPLATE, rawurlencode($placeId));
                }
            }

            if ($reviewUrl !== null) {
                $templateData['sdgReviewUrl'] = $reviewUrl;
                $event->setTemplateData($templateData);
            }

            // If THIS mail is our review request template AND there is an order in scope,
            // mark the order as "review mail sent".
            if ($this->isOurTemplate($event, $event->getContext())) {
                $orderId = $this->extractOrderId($templateData);
                if ($orderId !== null) {
                    $this->reviewMailService->markOrderAsSent($orderId, $event->getContext());
                }
            }
        } catch (\Throwable $e) {
            // Never break mail delivery because of this subscriber.
        }
    }

    private function isOurTemplate(MailBeforeValidateEvent $event, Context $context): bool
    {
        $ourId = $this->getOurTemplateId($context);
        if ($ourId === null) {
            return false;
        }

        $data = $event->getData();
        $templateId = null;

        if (\is_object($data) && method_exists($data, 'get')) {
            $templateId = $data->get('mailTemplateId') ?? $data->get('templateId');
        } elseif (\is_array($data)) {
            $templateId = $data['mailTemplateId'] ?? $data['templateId'] ?? null;
        }

        return $templateId !== null && (string) $templateId === $ourId;
    }

    private function getOurTemplateId(Context $context): ?string
    {
        if ($this->cachedOurTemplateId !== null) {
            return $this->cachedOurTemplateId ?: null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter(
            'mailTemplateType.technicalName',
            SvenDasGoogle::MAIL_TEMPLATE_TYPE_TECHNICAL_NAME
        ));
        $criteria->setLimit(1);

        $id = $this->mailTemplateRepository->searchIds($criteria, $context)->firstId();
        $this->cachedOurTemplateId = $id ?? '';

        return $id;
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

    private function extractOrderId(array $templateData): ?string
    {
        if (isset($templateData['order']) && \is_object($templateData['order'])
            && method_exists($templateData['order'], 'getId')) {
            return $templateData['order']->getId();
        }

        if (isset($templateData['orderId'])) {
            return (string) $templateData['orderId'];
        }

        return null;
    }
}
