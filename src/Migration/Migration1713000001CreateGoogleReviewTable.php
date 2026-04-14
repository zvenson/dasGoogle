<?php declare(strict_types=1);

namespace Sven\DasGoogle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1713000001CreateGoogleReviewTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1713000001;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `sven_das_google_review` (
                `id` BINARY(16) NOT NULL,
                `author_name` VARCHAR(255) NOT NULL,
                `rating` INT NOT NULL,
                `text` LONGTEXT NULL,
                `review_time` INT NOT NULL,
                `relative_time_description` VARCHAR(255) NULL,
                `profile_photo_url` VARCHAR(500) NULL,
                `place_id` VARCHAR(255) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_author_place` (`author_name`, `place_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
