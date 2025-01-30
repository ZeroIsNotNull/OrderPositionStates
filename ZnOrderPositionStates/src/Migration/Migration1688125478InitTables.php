<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1688125478InitTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1688125478;
    }

    public function update(Connection $connection): void
    {
        $this->createInitTable($connection);
        $this->createTranslationTable($connection);
        $this->createMappingTable($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function createInitTable(Connection $connection)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `order_position_states` (
    `id` BINARY(16) NOT NULL,
    `technical_name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeQuery($sql);
    }

    private function createTranslationTable(Connection $connection)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `order_position_states_translation` (
    `name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `order_position_states_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`order_position_states_id`,`language_id`),
    KEY `fk.order_position_states_translation.order_position_states_id` (`order_position_states_id`),
    KEY `fk.order_position_states_translation.language_id` (`language_id`),
    CONSTRAINT `fk.order_position_states_translation.order_position_states_id` FOREIGN KEY (`order_position_states_id`) REFERENCES `order_position_states` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.order_position_states_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeQuery($sql);
    }

    private function createMappingTable(Connection $connection)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `order_line_item_states` (
    `order_position_states_id` BINARY(16) NOT NULL,
    `order_line_item_id` BINARY(16) NOT NULL,
    `order_line_item_version_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`order_position_states_id`,`order_line_item_id`),
    KEY `fk.order_line_item_states.order_position_states_id` (`order_position_states_id`),
    KEY `fk.order_line_item_states.order_line_item_id` (`order_line_item_id`,`order_line_item_version_id`),
    CONSTRAINT `fk.order_line_item_states.order_position_states_id` FOREIGN KEY (`order_position_states_id`) REFERENCES `order_position_states` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.order_line_item_states.order_line_item_id` FOREIGN KEY (`order_line_item_id`,`order_line_item_version_id`) REFERENCES `order_line_item` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeQuery($sql);
    }
}
