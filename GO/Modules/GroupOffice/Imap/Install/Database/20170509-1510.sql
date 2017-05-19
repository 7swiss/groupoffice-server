delete from imap_folder;
ALTER TABLE `imap_message` ADD UNIQUE(`messageId`);