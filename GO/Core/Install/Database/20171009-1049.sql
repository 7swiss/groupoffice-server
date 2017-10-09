ALTER TABLE `auth_user` ADD COLUMN `displayName` VARCHAR(200) NOT NULL DEFAULT '' AFTER `lastLogin`;
