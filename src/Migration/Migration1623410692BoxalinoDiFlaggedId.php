<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1623410692BoxalinoDiFlaggedId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1623410692;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `boxalino_di_flagged_id` (
    `id`                  BINARY(16)                              NOT NULL,
    `entity_name`         VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `entity_id`           VARCHAR(128)                            NOT NULL,
    `created_at`          DATETIME(3)                             NOT NULL,
    `updated_at`          DATETIME(3)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeQuery($query);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
