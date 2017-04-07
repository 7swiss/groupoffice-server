ALTER TABLE `accounts_account` ADD `ownedBy` INT NOT NULL AFTER `createdBy`;
update accounts_account a set a.ownedBy = (select id from auth_group where auth_group.userId = a.createdBy);
ALTER TABLE `accounts_account` ADD INDEX(`ownedBy`);

ALTER TABLE accounts_account DROP FOREIGN KEY accounts_account_ibfk_1;
ALTER TABLE `accounts_account` ADD FOREIGN KEY (`ownedBy`) REFERENCES `auth_group`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `accounts_account` DROP `createdBy`;


CREATE TABLE `accounts_capability` (
  `id` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `modelName` varchar(190) NOT NULL
) ENGINE=InnoDB DEFAULT;


ALTER TABLE `accounts_capability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accountId` (`accountId`);


ALTER TABLE `accounts_capability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `accounts_capability`
  ADD CONSTRAINT `accounts_capability_ibfk_1` FOREIGN KEY (`accountId`) REFERENCES `accounts_account` (`id`) ON DELETE CASCADE;

ALTER TABLE `accounts_capability` ADD UNIQUE( `accountId`, `modelName`);
ALTER TABLE accounts_capability DROP INDEX accountId;

insert into `accounts_capability` select null, id, "GO\\Modules\\GroupOffice\\Messages\\Model\\Thread" from accounts_account where modelName="GO\\Modules\\GroupOffice\\Imap\\Model\\Account";