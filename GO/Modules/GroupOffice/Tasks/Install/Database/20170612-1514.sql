ALTER TABLE `tasks_task` ADD FOREIGN KEY (`assignedTo`) REFERENCES `auth_user`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `tasks_task` ADD FOREIGN KEY (`createdBy`) REFERENCES `auth_user`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `tasks_task` ADD `accountId` INT NULL AFTER `assignedTo`, ADD INDEX (`accountId`);
INSERT INTO `accounts_account` (`id`, `name`, `modelName`, `ownedBy`) VALUES (NULL, 'Public tasks', 'GO\\Modules\\GroupOffice\\Tasks\\Model\\Account', '1');

update tasks_task set accountId = (select id from accounts_account where modelName="GO\\Modules\\GroupOffice\\Tasks\\Model\\Account" limit 0,1);

ALTER TABLE `tasks_task` CHANGE `accountId` `accountId` INT(11) NOT NULL;


insert into `accounts_capability` select null, id, "GO\\Modules\\GroupOffice\\Tasks\\Model\\Task" from accounts_account where modelName="GO\\Modules\\GroupOffice\\Tasks\\Model\\Account";

insert into `accounts_account_group` select id, "2", "1" from accounts_account where modelName="GO\\Modules\\GroupOffice\\Tasks\\Model\\Account";

