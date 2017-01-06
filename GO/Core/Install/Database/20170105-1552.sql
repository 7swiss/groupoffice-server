ALTER TABLE `templates_pdf` ADD `language` VARCHAR(20) NOT NULL DEFAULT 'en' AFTER `moduleId`;
ALTER TABLE `custom_fields_field` CHANGE `_data` `data` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

