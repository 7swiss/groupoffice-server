ALTER TABLE `messages_message` ADD `accountId` INT NOT NULL AFTER `id`, ADD INDEX (`accountId`);
update `messages_message` m set accountId=(select t.accountId from messages_thread t where t.id=m.threadId);
ALTER TABLE `messages_message` ADD FOREIGN KEY (`accountId`) REFERENCES `accounts_account`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `messages_message` DROP FOREIGN KEY `messages_message_ibfk_1`; ALTER TABLE `messages_message` ADD CONSTRAINT `messages_message_ibfk_1` FOREIGN KEY (`threadId`) REFERENCES `messages_thread`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
