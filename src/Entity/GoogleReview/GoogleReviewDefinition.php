<?php declare(strict_types=1);

namespace Sven\DasGoogle\Entity\GoogleReview;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class GoogleReviewDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'sven_das_google_review';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return GoogleReviewEntity::class;
    }

    public function getCollectionClass(): string
    {
        return GoogleReviewCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('author_name', 'authorName'))->addFlags(new Required()),
            (new IntField('rating', 'rating'))->addFlags(new Required()),
            new LongTextField('text', 'text'),
            (new IntField('review_time', 'reviewTime'))->addFlags(new Required()),
            new StringField('relative_time_description', 'relativeTimeDescription'),
            new StringField('profile_photo_url', 'profilePhotoUrl'),
            (new StringField('place_id', 'placeId'))->addFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
