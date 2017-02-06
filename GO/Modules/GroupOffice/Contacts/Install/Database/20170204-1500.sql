ALTER TABLE `contacts_contact` ADD `ownedBy` INT NOT NULL COMMENT 'The group that owns the contact and can modify permissions.' AFTER `userId`, ADD INDEX (`ownedBy`);
update contacts_contact set ownedBy = 1;
ALTER TABLE `contacts_contact` ADD FOREIGN KEY (`ownedBy`) REFERENCES `auth_group`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
