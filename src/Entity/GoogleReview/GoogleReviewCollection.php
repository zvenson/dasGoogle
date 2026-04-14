<?php declare(strict_types=1);

namespace Sven\DasGoogle\Entity\GoogleReview;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                   add(GoogleReviewEntity $entity)
 * @method GoogleReviewEntity[]   getIterator()
 * @method GoogleReviewEntity|null get(string $id)
 * @method GoogleReviewEntity|null first()
 * @method GoogleReviewEntity|null last()
 */
class GoogleReviewCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return GoogleReviewEntity::class;
    }
}
