<?php declare(strict_types=1);

namespace Sven\DasGoogle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class SvenDasGoogle extends Plugin
{
    public const MAIL_TEMPLATE_TYPE_TECHNICAL_NAME = 'sven_das_google_review_request';
    public const ORDER_CUSTOM_FIELD_SET_NAME = 'sven_das_google_review_mail';

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->createReviewRequestMailTemplate($installContext->getContext());
        $this->createOrderCustomFieldSet($installContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $this->createReviewRequestMailTemplate($updateContext->getContext());
        $this->createOrderCustomFieldSet($updateContext->getContext());
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeReviewRequestMailTemplate($uninstallContext->getContext());
        $this->removeOrderCustomFieldSet($uninstallContext->getContext());

        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `sven_das_google_review`');
        $connection->executeStatement(
            "DELETE FROM `migration` WHERE `class` LIKE '%Sven\\\\DasGoogle\\\\Migration%'"
        );
    }

    private function createOrderCustomFieldSet(Context $context): void
    {
        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::ORDER_CUSTOM_FIELD_SET_NAME));
        $existingId = $customFieldSetRepository->searchIds($criteria, $context)->firstId();

        if ($existingId !== null) {
            return;
        }

        $customFieldSetRepository->create([[
            'name' => self::ORDER_CUSTOM_FIELD_SET_NAME,
            'config' => [
                'label' => [
                    'de-DE' => 'Google Bewertungseinladung',
                    'en-GB' => 'Google review request',
                    Defaults::LANGUAGE_SYSTEM => 'Google review request',
                ],
                'translated' => true,
            ],
            'customFields' => [
                [
                    'name' => 'sdg_review_mail_sent_at',
                    'type' => CustomFieldTypes::DATETIME,
                    'config' => [
                        'label' => [
                            'de-DE' => 'Bewertungsmail versendet am',
                            'en-GB' => 'Review request sent at',
                            Defaults::LANGUAGE_SYSTEM => 'Review request sent at',
                        ],
                        'componentName' => 'sw-field',
                        'customFieldType' => 'date',
                        'dateType' => 'datetime',
                        'customFieldPosition' => 1,
                    ],
                ],
            ],
            'relations' => [
                ['entityName' => 'order'],
            ],
        ]], $context);
    }

    private function removeOrderCustomFieldSet(Context $context): void
    {
        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::ORDER_CUSTOM_FIELD_SET_NAME));
        $id = $customFieldSetRepository->searchIds($criteria, $context)->firstId();

        if ($id !== null) {
            $customFieldSetRepository->delete([['id' => $id]], $context);
        }
    }

    private function createReviewRequestMailTemplate(Context $context): void
    {
        /** @var EntityRepository $mailTemplateTypeRepository */
        $mailTemplateTypeRepository = $this->container->get('mail_template_type.repository');
        /** @var EntityRepository $mailTemplateRepository */
        $mailTemplateRepository = $this->container->get('mail_template.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', self::MAIL_TEMPLATE_TYPE_TECHNICAL_NAME));

        $existingType = $mailTemplateTypeRepository->search($criteria, $context)->first();

        [$subjectDe, $subjectEn, $plainDe, $plainEn, $htmlDe, $htmlEn] = $this->getReviewMailContent();

        if ($existingType !== null) {
            $this->refreshDefaultTemplateBody(
                $existingType->getId(),
                $subjectDe,
                $subjectEn,
                $plainDe,
                $plainEn,
                $htmlDe,
                $htmlEn,
                $context
            );
            return;
        }

        $mailTemplateTypeId = Uuid::randomHex();

        $mailTemplateType = [[
            'id' => $mailTemplateTypeId,
            'name' => 'Google Bewertungseinladung',
            'technicalName' => self::MAIL_TEMPLATE_TYPE_TECHNICAL_NAME,
            'availableEntities' => [
                'order' => 'order',
                'salesChannel' => 'sales_channel',
            ],
        ]];

        $mailTemplate = [[
            'id' => Uuid::randomHex(),
            'mailTemplateTypeId' => $mailTemplateTypeId,
            'systemDefault' => false,
            'senderName' => [
                'de-DE' => '{{ salesChannel.translated.name }}',
                'en-GB' => '{{ salesChannel.translated.name }}',
                Defaults::LANGUAGE_SYSTEM => '{{ salesChannel.translated.name }}',
            ],
            'subject' => [
                'de-DE' => $subjectDe,
                'en-GB' => $subjectEn,
                Defaults::LANGUAGE_SYSTEM => $subjectEn,
            ],
            'description' => [
                'de-DE' => 'Bittet den Kunden um eine Google-Bewertung. Im Flow Builder z. B. auf "Bestellung geliefert" mit zeitlicher Verzoegerung verwenden.',
                'en-GB' => 'Asks the customer for a Google review. Use in Flow Builder, e.g. on "order delivered" with a time delay.',
                Defaults::LANGUAGE_SYSTEM => 'Asks the customer for a Google review.',
            ],
            'contentPlain' => [
                'de-DE' => $plainDe,
                'en-GB' => $plainEn,
                Defaults::LANGUAGE_SYSTEM => $plainEn,
            ],
            'contentHtml' => [
                'de-DE' => $htmlDe,
                'en-GB' => $htmlEn,
                Defaults::LANGUAGE_SYSTEM => $htmlEn,
            ],
        ]];

        try {
            $mailTemplateTypeRepository->create($mailTemplateType, $context);
            $mailTemplateRepository->create($mailTemplate, $context);
        } catch (UniqueConstraintViolationException $exception) {
            // already exists - safe to ignore
        }
    }

    /**
     * Falls das Mail-Template noch unsere alte Default-Version ist (erkennbar am Marker-Text),
     * ueberschreiben wir es mit der neuen Version. Vom Admin angepasste Templates bleiben unangetastet.
     */
    private function refreshDefaultTemplateBody(
        string $mailTemplateTypeId,
        string $subjectDe,
        string $subjectEn,
        string $plainDe,
        string $plainEn,
        string $htmlDe,
        string $htmlEn,
        Context $context
    ): void {
        /** @var EntityRepository $mailTemplateRepository */
        $mailTemplateRepository = $this->container->get('mail_template.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', $mailTemplateTypeId));
        $templates = $mailTemplateRepository->search($criteria, $context);

        $oldMarker = 'Wir hoffen, alles ist gut bei Ihnen angekommen';

        $updates = [];
        foreach ($templates as $template) {
            $currentHtml = (string) $template->getContentHtml();
            $currentTranslations = $template->getTranslations();

            // Heuristik: noch alte default, wenn der alte Marker drin steckt
            // oder die HTML-Laenge unter ~1500 Zeichen ist (Default war ~700)
            $looksLikeDefault =
                str_contains($currentHtml, $oldMarker)
                || \strlen($currentHtml) < 1500;

            // Wenn Translations geladen, auch dort pruefen
            if ($currentTranslations !== null) {
                foreach ($currentTranslations as $tr) {
                    $trHtml = (string) $tr->getContentHtml();
                    if (str_contains($trHtml, $oldMarker)) {
                        $looksLikeDefault = true;
                        break;
                    }
                }
            }

            if (!$looksLikeDefault) {
                continue;
            }

            $updates[] = [
                'id' => $template->getId(),
                'subject' => [
                    'de-DE' => $subjectDe,
                    'en-GB' => $subjectEn,
                    Defaults::LANGUAGE_SYSTEM => $subjectEn,
                ],
                'contentPlain' => [
                    'de-DE' => $plainDe,
                    'en-GB' => $plainEn,
                    Defaults::LANGUAGE_SYSTEM => $plainEn,
                ],
                'contentHtml' => [
                    'de-DE' => $htmlDe,
                    'en-GB' => $htmlEn,
                    Defaults::LANGUAGE_SYSTEM => $htmlEn,
                ],
            ];
        }

        if (!empty($updates)) {
            $mailTemplateRepository->update($updates, $context);
        }
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string,5:string}
     */
    private function getReviewMailContent(): array
    {
        $subjectDe = 'Wie war Ihre Bestellung bei {{ salesChannel.translated.name }}?';
        $subjectEn = 'How was your order with {{ salesChannel.translated.name }}?';

        $plainDe = "Hallo {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},\n\n"
            . "vielen Dank fuer Ihre Bestellung (Nr. {{ order.orderNumber }}) bei {{ salesChannel.translated.name }}.\n\n"
            . "Wir hoffen, alles ist gut bei Ihnen angekommen und Sie sind zufrieden.\n\n"
            . "Wuerden Sie sich kurz Zeit nehmen und uns auf Google bewerten? "
            . "Das hilft uns sehr und anderen Kunden bei der Orientierung:\n\n"
            . "{{ sdgReviewUrl }}\n\n"
            . "Vielen Dank!\n"
            . "Ihr Team von {{ salesChannel.translated.name }}\n\n"
            . "---\n"
            . "Sie moechten keine weiteren Mails dieser Art erhalten? "
            . "Antworten Sie kurz auf diese Mail mit 'Abbestellen'.";

        $plainEn = "Hello {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},\n\n"
            . "thank you for your order ({{ order.orderNumber }}) at {{ salesChannel.translated.name }}.\n\n"
            . "We hope everything arrived well and you are happy with it.\n\n"
            . "Would you take a moment to leave us a review on Google? "
            . "It really helps us and other customers:\n\n"
            . "{{ sdgReviewUrl }}\n\n"
            . "Thank you!\n"
            . "Your team at {{ salesChannel.translated.name }}\n\n"
            . "---\n"
            . "Don't want to receive emails like this? Just reply with 'unsubscribe'.";

        $htmlDe = $this->buildHtmlBody('de');
        $htmlEn = $this->buildHtmlBody('en');

        return [$subjectDe, $subjectEn, $plainDe, $plainEn, $htmlDe, $htmlEn];
    }

    private function buildHtmlBody(string $lang): string
    {
        $isDe = $lang === 'de';

        $greeting   = $isDe ? 'Hallo' : 'Hello';
        $thanksLead = $isDe ? 'vielen Dank fuer Ihre Bestellung' : 'thank you for your order';
        $orderLbl   = $isDe ? 'Bestellnummer'  : 'Order number';
        $headline   = $isDe ? 'Wie war es bei uns?' : 'How was it?';
        $body1 = $isDe
            ? 'wir hoffen, alles ist gut bei Ihnen angekommen und Sie sind zufrieden mit Ihrer Bestellung.'
            : 'we hope everything arrived well and you are happy with your order.';
        $body2 = $isDe
            ? 'Wir wuerden uns sehr freuen, wenn Sie sich kurz Zeit nehmen und uns auf Google bewerten. Das hilft uns ungemein &mdash; und anderen Kunden bei der Orientierung.'
            : 'We would be delighted if you could take a moment to leave us a review on Google. It really helps us &mdash; and other customers, too.';
        $cta        = $isDe ? 'Jetzt auf Google bewerten' : 'Leave a Google review';
        $thanksOut  = $isDe ? 'Vielen Dank!' : 'Thank you!';
        $teamLead   = $isDe ? 'Ihr Team von' : 'Your team at';
        $footerNote = $isDe
            ? 'Sie moechten keine weiteren Mails dieser Art erhalten? Antworten Sie kurz auf diese Mail mit &bdquo;Abbestellen&ldquo;.'
            : 'Don\'t want to receive emails like this? Just reply with &bdquo;unsubscribe&ldquo;.';
        $footerSent = $isDe ? 'Diese E-Mail wurde versendet von' : 'This email was sent by';

        $html  = '<!DOCTYPE html>';
        $html .= '<html lang="' . $lang . '"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>{{ salesChannel.translated.name }}</title></head>';
        $html .= '<body style="margin:0;padding:0;background:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;color:#222;">';

        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;padding:24px 0;">';
        $html .= '<tr><td align="center">';

        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.04);">';

        // Header bar
        $html .= '<tr><td style="background:#4285F4;padding:24px 32px;text-align:center;">';
        $html .= '<div style="color:#ffffff;font-size:22px;font-weight:700;letter-spacing:0.3px;">{{ salesChannel.translated.name }}</div>';
        $html .= '</td></tr>';

        // Content
        $html .= '<tr><td style="padding:32px;">';
        $html .= '<p style="margin:0 0 16px 0;font-size:15px;line-height:1.5;">';
        $html .= $greeting . ' {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},';
        $html .= '</p>';

        $html .= '<p style="margin:0 0 16px 0;font-size:15px;line-height:1.5;">';
        $html .= $thanksLead . ' (' . $orderLbl . ': <strong>{{ order.orderNumber }}</strong>).<br>';
        $html .= $body1;
        $html .= '</p>';

        $html .= '<h2 style="margin:32px 0 8px 0;font-size:18px;color:#222;">' . $headline . '</h2>';
        $html .= '<p style="margin:0 0 24px 0;font-size:15px;line-height:1.5;color:#555;">' . $body2 . '</p>';

        // CTA
        $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:8px auto 24px;">';
        $html .= '<tr><td align="center" style="border-radius:6px;background:#4285F4;">';
        $html .= '<a href="{{ sdgReviewUrl }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:6px;background:#4285F4;">';
        $html .= '&#9733; ' . $cta;
        $html .= '</a></td></tr></table>';

        $html .= '<p style="margin:16px 0 0 0;font-size:14px;line-height:1.5;color:#666;">';
        $html .= $thanksOut . '<br>';
        $html .= $teamLead . ' <strong>{{ salesChannel.translated.name }}</strong>';
        $html .= '</p>';
        $html .= '</td></tr>';

        // Footer
        $html .= '<tr><td style="background:#fafbfc;border-top:1px solid #eef0f3;padding:20px 32px;text-align:center;">';
        $html .= '<p style="margin:0 0 8px 0;font-size:12px;color:#888;line-height:1.5;">';
        $html .= $footerSent . ' <strong>{{ salesChannel.translated.name }}</strong>';
        $html .= '</p>';
        $html .= '<p style="margin:0;font-size:11px;color:#aaa;line-height:1.5;">';
        $html .= $footerNote;
        $html .= '</p>';
        $html .= '</td></tr>';

        $html .= '</table>';
        $html .= '</td></tr></table>';
        $html .= '</body></html>';

        return $html;
    }

    private function removeReviewRequestMailTemplate(Context $context): void
    {
        /** @var EntityRepository $mailTemplateTypeRepository */
        $mailTemplateTypeRepository = $this->container->get('mail_template_type.repository');
        /** @var EntityRepository $mailTemplateRepository */
        $mailTemplateRepository = $this->container->get('mail_template.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', self::MAIL_TEMPLATE_TYPE_TECHNICAL_NAME));

        $typeId = $mailTemplateTypeRepository->searchIds($criteria, $context)->firstId();
        if ($typeId === null) {
            return;
        }

        $templateCriteria = new Criteria();
        $templateCriteria->addFilter(new EqualsFilter('mailTemplateTypeId', $typeId));
        $templateIds = $mailTemplateRepository->searchIds($templateCriteria, $context)->getIds();

        if (!empty($templateIds)) {
            $payload = array_map(static fn ($id) => ['id' => $id], $templateIds);
            $mailTemplateRepository->delete($payload, $context);
        }

        $mailTemplateTypeRepository->delete([['id' => $typeId]], $context);
    }
}
