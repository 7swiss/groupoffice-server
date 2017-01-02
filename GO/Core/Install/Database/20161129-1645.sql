ALTER TABLE `auth_user` DROP `photo`;

ALTER TABLE `auth_user` ADD `photoBlobId` CHAR(40) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER `emailSecondary`;