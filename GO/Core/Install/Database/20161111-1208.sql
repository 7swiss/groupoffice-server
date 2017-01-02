ALTER TABLE `core_settings` ADD `defaultLanguage` VARCHAR(20) NOT NULL DEFAULT 'en' AFTER `smtpAccountId`;



update `tags_tag` set color=null;

ALTER TABLE `tags_tag` CHANGE `color` `color` CHAR(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Color hex value without hash sign';