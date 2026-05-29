<?php declare(strict_types=1);

namespace Sven\DasGoogle\Service;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sven\DasGoogle\SvenDasGoogle;

class ReviewMailService
{
    public const CUSTOM_FIELD_SENT_AT = 'sdg_review_mail_sent_at';

    private const REVIEW_URL_TEMPLATE = 'https://search.google.com/local/writereview?placeid=%s';

    public function __construct(
        private readonly AbstractMailService $mailService,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $mailTemplateRepository,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public function sendForOrder(string $orderId): void
    {
        $context = Context::createDefaultContext();

        $order = $this->loadOrder($orderId, $context);
        if ($order === null) {
            throw new \RuntimeException('Order not found.');
        }

        $template = $this->loadTemplate($context);
        if ($template === null) {
            throw new \RuntimeException('Mail template "Google Bewertungseinladung" not found.');
        }

        $recipientMail = $order->getOrderCustomer()?->getEmail();
        if (empty($recipientMail)) {
            throw new \RuntimeException('Order has no customer email.');
        }

        $salesChannel = $order->getSalesChannel();
        $salesChannelId = $order->getSalesChannelId();

        $languageId = $context->getLanguageId();
        $subject = $template->getTranslation('subject') ?: ($template->getSubject() ?? 'Bewertungseinladung');
        $contentHtml = $template->getTranslation('contentHtml') ?: ($template->getContentHtml() ?? '');
        $contentPlain = $template->getTranslation('contentPlain') ?: ($template->getContentPlain() ?? '');
        $senderName = $template->getTranslation('senderName') ?: ($template->getSenderName() ?? '{{ salesChannel.translated.name }}');

        $reviewUrl = $this->buildReviewUrl($salesChannelId);

        $recipientName = trim(($order->getOrderCustomer()?->getFirstName() ?? '') . ' ' . ($order->getOrderCustomer()?->getLastName() ?? ''));
        if ($recipientName === '') {
            $recipientName = $recipientMail;
        }

        $data = new DataBag();
        $data->set('recipients', [$recipientMail => $recipientName]);
        $data->set('senderName', $senderName);
        $data->set('salesChannelId', $salesChannelId);
        $data->set('contentHtml', $contentHtml);
        $data->set('contentPlain', $contentPlain);
        $data->set('subject', $subject);
        $data->set('mailTemplateId', $template->getId());

        $templateData = [
            'order' => $order,
            'salesChannel' => $salesChannel,
            'sdgReviewUrl' => $reviewUrl,
        ];

        $this->mailService->send($data->all(), $context, $templateData);

        $this->markOrderAsSent($orderId, $context);
    }

    public function markOrderAsSent(string $orderId, Context $context): void
    {
        $this->orderRepository->update([
            [
                'id' => $orderId,
                'customFields' => [
                    self::CUSTOM_FIELD_SENT_AT => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
            ],
        ], $context);
    }

    private function loadOrder(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('salesChannel');
        $criteria->addAssociation('salesChannel.translation');
        $criteria->addAssociation('language');

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context)->first();

        return $order;
    }

    private function loadTemplate(Context $context): ?MailTemplateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter(
            'mailTemplateType.technicalName',
            SvenDasGoogle::MAIL_TEMPLATE_TYPE_TECHNICAL_NAME
        ));
        $criteria->addAssociation('mailTemplateType');
        $criteria->setLimit(1);

        /** @var MailTemplateEntity|null $template */
        $template = $this->mailTemplateRepository->search($criteria, $context)->first();

        return $template;
    }

    private function buildReviewUrl(?string $salesChannelId): string
    {
        $override = trim((string) $this->systemConfigService->getString(
            'SvenDasGoogle.config.googleReviewUrlOverride',
            $salesChannelId
        ));

        if ($override !== '') {
            return $override;
        }

        $placeId = trim((string) $this->systemConfigService->getString(
            'SvenDasGoogle.config.googlePlaceId',
            $salesChannelId
        ));

        if ($placeId === '') {
            return '';
        }

        return sprintf(self::REVIEW_URL_TEMPLATE, rawurlencode($placeId));
    }
}
