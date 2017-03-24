ALTER TABLE `messages_thread` ADD `modifiedAt` DATETIME NULL DEFAULT null AFTER `id`;
update messages_thread set modifiedAt = lastMessageSentAt;
ALTER TABLE `messages_thread` CHANGE `modifiedAt` `modifiedAt` DATETIME NOT NULL;

ALTER TABLE `messages_message` ADD INDEX(`modifiedAt`);
ALTER TABLE `messages_thread` ADD INDEX(`modifiedAt`);
