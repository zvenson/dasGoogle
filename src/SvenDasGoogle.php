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
        if ($existingType !== null) {
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

        $subjectDe = 'Wie war Ihre Bestellung bei {{ salesChannel.translated.name }}?';
        $subjectEn = 'How was your order with {{ salesChannel.translated.name }}?';

        $plainDe = "Hallo {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},\n\n"
            . "vielen Dank fuer Ihre Bestellung bei {{ salesChannel.translated.name }}.\n\n"
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
            . "thank you for your order at {{ salesChannel.translated.name }}.\n\n"
            . "We hope everything arrived well and you are happy with it.\n\n"
            . "Would you take a moment to leave us a review on Google? "
            . "It really helps us and other customers:\n\n"
            . "{{ sdgReviewUrl }}\n\n"
            . "Thank you!\n"
            . "Your team at {{ salesChannel.translated.name }}\n\n"
            . "---\n"
            . "Don't want to receive emails like this? Just reply with 'unsubscribe'.";

        $htmlDe = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;">'
            . '<p>Hallo {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},</p>'
            . '<p>vielen Dank fuer Ihre Bestellung bei <strong>{{ salesChannel.translated.name }}</strong>.</p>'
            . '<p>Wir hoffen, alles ist gut bei Ihnen angekommen und Sie sind zufrieden.</p>'
            . '<p>Wuerden Sie sich kurz Zeit nehmen und uns auf Google bewerten? Das hilft uns sehr und anderen Kunden bei der Orientierung:</p>'
            . '<p style="text-align:center;margin:24px 0;">'
            . '<a href="{{ sdgReviewUrl }}" style="background:#4285F4;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">Jetzt auf Google bewerten</a>'
            . '</p>'
            . '<p>Vielen Dank!<br>Ihr Team von {{ salesChannel.translated.name }}</p>'
            . '<hr style="border:none;border-top:1px solid #ccc;margin:24px 0;">'
            . '<p style="font-size:12px;color:#666;">Sie moechten keine weiteren Mails dieser Art erhalten? Antworten Sie kurz auf diese Mail mit &quot;Abbestellen&quot;.</p>'
            . '</div>';

        $htmlEn = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;">'
            . '<p>Hello {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},</p>'
            . '<p>thank you for your order at <strong>{{ salesChannel.translated.name }}</strong>.</p>'
            . '<p>We hope everything arrived well and you are happy with it.</p>'
            . '<p>Would you take a moment to leave us a review on Google? It really helps us and other customers:</p>'
            . '<p style="text-align:center;margin:24px 0;">'
            . '<a href="{{ sdgReviewUrl }}" style="background:#4285F4;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">Leave a Google review</a>'
            . '</p>'
            . '<p>Thank you!<br>Your team at {{ salesChannel.translated.name }}</p>'
            . '<hr style="border:none;border-top:1px solid #ccc;margin:24px 0;">'
            . '<p style="font-size:12px;color:#666;">Don\'t want to receive emails like this? Just reply with &quot;unsubscribe&quot;.</p>'
            . '</div>';

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
