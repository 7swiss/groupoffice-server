ALTER TABLE `contacts_contact` ADD `debtorNumber` VARCHAR(50) NULL AFTER `vatNo`;
ALTER TABLE `contacts_contact` DROP INDEX `userId`, ADD UNIQUE `userId` (`userId`) USING BTREE;
