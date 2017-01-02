ALTER TABLE `log_entry` DROP `type`;
ALTER TABLE `log_entry` CHANGE `action` `type` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

