ALTER TABLE `messages_message` ADD `accountId` INT NOT NULL AFTER `id`, ADD INDEX (`accountId`);
update `messages_message` m set accountId=(select t.accountId from messages_thread t where t.id=m.threadId);
ALTER TABLE `messages_message` ADD FOREIGN KEY (`accountId`) REFERENCES `accounts_account`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
