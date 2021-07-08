<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1625574165BoxalinoDi extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1625574165;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `boxalino_di_timesheet` (
      `account`           VARCHAR(128)                             NOT NULL,
      `mode`              VARCHAR(128)                             NOT NULL,
      `type`              VARCHAR(128)                             NOT NULL,
      `run_at`            DATETIME(3),
      `updated_at`        DATETIME(3),
      PRIMARY KEY (`account`, `mode`, `type`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `boxalino_di_flagged_id_product` (
    `id`                  BINARY(16)                              NOT NULL,
    `entity_id`           VARCHAR(128)                            NOT NULL,
    `sales_channel_id`    VARCHAR(128),
    `created_at`          DATETIME(3)                             NOT NULL,
  INDEX `idx.boxalino_di_flagged_id_product.entity_id_created_at` (`entity_id`, `created_at` DESC),
  INDEX `idx.boxalino_di_flagged_id_product.id_created_at` (`id`, `created_at` DESC),
  CONSTRAINT `fk.boxalino_di_flagged_id_product.id` FOREIGN KEY (`id`)
    REFERENCES `product` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `boxalino_di_flagged_id_customer` (
    `id`                  BINARY(16)                              NOT NULL,
    `entity_id`           VARCHAR(128)                            NOT NULL,
    `sales_channel_id`    VARCHAR(128),
    `created_at`          DATETIME(3)                             NOT NULL,
  INDEX `idx.boxalino_di_flagged_id_customer.entity_id_created_at` (`entity_id`, `created_at` DESC),
  INDEX `idx.boxalino_di_flagged_id_customer.id_created_at` (`id`, `created_at` DESC),
  CONSTRAINT `fk.boxalino_di_flagged_id_customer.id` FOREIGN KEY (`id`)
    REFERENCES `customer` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `boxalino_di_flagged_id_order` (
    `id`                  BINARY(16)                              NOT NULL,
    `entity_id`           VARCHAR(128)                            NOT NULL,
    `sales_channel_id`    VARCHAR(128),
    `created_at`          DATETIME(3)                             NOT NULL,
  INDEX `idx.boxalino_di_flagged_id_order.entity_id_created_at` (`entity_id`, `created_at` DESC),
  INDEX `idx.boxalino_di_flagged_id_order.id_created_at` (`id`, `created_at` DESC),
  CONSTRAINT `fk.boxalino_di_flagged_id_order.id` FOREIGN KEY (`id`)
    REFERENCES `order` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeQuery($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
