ALTER TABLE tasks_task DROP FOREIGN KEY tasks_task_ibfk_1;
ALTER TABLE `tasks_task` CHANGE `assignedTo` `assignedToBak` INT(11) NULL DEFAULT NULL;

ALTER TABLE `tasks_task` ADD `assignedTo` INT NULL DEFAULT NULL AFTER `createdBy`, ADD INDEX (`assignedTo`);
update tasks_task set assignedTo = (select id from auth_group where userId=assignedToBak);
ALTER TABLE `tasks_task` ADD FOREIGN KEY (`assignedTo`) REFERENCES `auth_group`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `tasks_task` DROP `assignedToBak`;


ALTER TABLE `tasks_task` ADD FOREIGN KEY (`accountId`) REFERENCES `accounts_account`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
