DELETE FROM log_event;

ALTER TABLE `log_event` CHANGE `modelName` `recordTypeId` INT NOT NULL; 

ALTER TABLE `log_event` ADD INDEX(`recordTypeId`); 
ALTER TABLE `log_event` ADD INDEX(`modelId`); 

ALTER TABLE `log_event` CHANGE `modelId` `recordId` INT(11) NOT NULL; 



ALTER TABLE `log_notification` ADD `clickedAt` DATETIME NULL AFTER `seenAt`;