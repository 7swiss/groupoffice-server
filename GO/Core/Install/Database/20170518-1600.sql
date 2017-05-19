ALTER TABLE `auth_token` ADD `userAgent` VARCHAR(190) NULL AFTER `expiresAt`, ADD `remoteIpAddress` VARCHAR(50) NULL AFTER `userAgent`;


INSERT INTO `cron_job` (`deleted`, `moduleId`, `name`, `cronClassName`, `method`, `params`, `cronExpression`, `enabled`, `runUserId`, `nextRun`, `lastRun`, `runningSince`, `timezone`, `lastError`) VALUES
(0, NULL, 'Garbage Collector', 'GO\\Core\\GarbageCollection\\Collector', 'collect', NULL, '0 0 * * *', 1, 1, '2017-05-20 00:00:00', NULL, NULL, 'UTC', NULL);

ALTER TABLE `notifications_watch` ADD FOREIGN KEY (`groupId`) REFERENCES `auth_group`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; ALTER TABLE `notifications_watch` ADD FOREIGN KEY (`recordTypeId`) REFERENCES `orm_record_type`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `orm_record_type` ADD CONSTRAINT `orm_record_type_ibfk_1` FOREIGN KEY (`moduleId`) REFERENCES `modules_module`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;


ALTER TABLE `blob_blob` CHANGE `expireAt` `expiresAt` DATETIME NULL DEFAULT NULL;


ALTER TABLE `blob_blob` ADD `used` BOOLEAN NOT NULL DEFAULT FALSE AFTER `size`;

ALTER TABLE `blob_blob` ADD `deleted` BOOLEAN NOT NULL DEFAULT FALSE AFTER `used`;

drop table `blob_blob_user`;
