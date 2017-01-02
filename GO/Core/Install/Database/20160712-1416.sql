ALTER TABLE `auth_user` ADD `email` varchar(191) NOT NULL AFTER `lastLogin`, ADD `emailSecondary` varchar(191) NULL AFTER `email`, ADD INDEX (`email`), ADD INDEX (`emailSecondary`);

update `auth_user` SET email=concat(username, '@intermesh.dev');

ALTER TABLE `auth_user` ADD UNIQUE(`email`);
ALTER TABLE `auth_user` ADD INDEX(`emailSecondary`);


ALTER TABLE `auth_user` ADD `photo` varchar(191) NULL DEFAULT NULL;

