CREATE TABLE `event_store` (
    `aggregate_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
    `aggregate_version` INT(10) UNSIGNED NOT NULL,
    `event_class` VARCHAR(255) NOT NULL COLLATE utf8mb4_general_ci,
    `version` INT(10) UNSIGNED NOT NULL,
    `payload` LONGTEXT NOT NULL COLLATE utf8mb4_general_ci,
    `metadata` LONGTEXT NOT NULL COLLATE utf8mb4_general_ci,
    `created_at` DATETIME(6) NOT NULL,
    PRIMARY KEY (`aggregate_id`, `aggregate_version`) USING BTREE,
    INDEX `event_created_at_idx` (`created_at`) USING BTREE
)
COLLATE=utf8mb4_general_ci
ENGINE=InnoDB;

CREATE TABLE `key_store` (
    `aggregate_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
    `secret_key` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME(6) NOT NULL,
    PRIMARY KEY (`aggregate_id`) USING BTREE,
    INDEX `key_created_at_idx` (`created_at`) USING BTREE
)
COLLATE=utf8mb4_general_ci
ENGINE=InnoDB;

CREATE TABLE `snapshot_store` (
    `aggregate_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
    `aggregate_version` INT(10) UNSIGNED NOT NULL,
    `aggregate_class_name` VARCHAR(255) NOT NULL COLLATE utf8mb4_general_ci,
    `data` LONGTEXT NOT NULL COLLATE utf8mb4_general_ci,
    `created_at` DATETIME(6) NOT NULL,
    INDEX `aggregate_id_idx` (`aggregate_id`) USING BTREE,
    INDEX `aggregate_version_idx` (`aggregate_version`) USING BTREE
)
COLLATE=utf8mb4_general_ci
ENGINE=InnoDB;

CREATE TABLE `private_data` (
    `aggregate_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
    `value_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
    `value` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`aggregate_id`, `value_id`) USING BTREE
)
COLLATE=utf8mb4_general_ci
ENGINE=InnoDB;
