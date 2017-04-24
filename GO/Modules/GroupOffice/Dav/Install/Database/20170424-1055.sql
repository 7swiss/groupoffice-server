ALTER TABLE `dav_account` CHANGE `password` `password` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `dav_account` ADD `ctag` VARCHAR(190) NOT NULL AFTER `password`;
DROP TABLE dav_account_collection;
