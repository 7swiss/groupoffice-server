
ALTER TABLE `contacts_contact`
  DROP `_photoFilePath`,
  DROP `_filesFolderId`;


ALTER TABLE `contacts_contact` ADD `photoBlobId` CHAR(40) CHARSET 'ascii' BINARY NULL DEFAULT NULL;
ALTER TABLE `contacts_contact` ADD INDEX(`photoBlobId`);

ALTER TABLE `contacts_contact` ADD FOREIGN KEY (`photoBlobId`) REFERENCES `blob_blob`(`blobId`) ON DELETE RESTRICT ON UPDATE RESTRICT;
