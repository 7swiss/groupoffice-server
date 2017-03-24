ALTER TABLE `messages_thread` ADD `modifiedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id`;
ALTER TABLE `messages_thread` CHANGE `modifiedAt` `modifiedAt` DATETIME NOT NULL;
update messages_thread set modifiedAt = lastMessageSentAt;

ALTER TABLE `messages_message` ADD INDEX(`modifiedAt`);
ALTER TABLE `messages_thread` ADD INDEX(`modifiedAt`);