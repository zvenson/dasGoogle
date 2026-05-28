<?php declare(strict_types=1);

namespace Sven\DasGoogle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1779000002FixReviewUniqueKey extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779000002;
    }

    public function update(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['sven_das_google_review'])) {
            return;
        }

        $indexes = $schemaManager->listTableIndexes('sven_das_google_review');

        if (isset($indexes['uniq_author_place'])) {
            $connection->executeStatement(
                'ALTER TABLE `sven_das_google_review` DROP INDEX `uniq_author_place`'
            );
        }

        if (!isset($indexes['uniq_author_place_time'])) {
            $connection->executeStatement(
                'ALTER TABLE `sven_das_google_review`
                 ADD UNIQUE KEY `uniq_author_place_time` (`place_id`, `author_name`, `review_time`)'
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
