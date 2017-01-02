DELETE FROM `log_event`;
DELETE FROM `orm_record_type`;
ALTER TABLE `orm_record_type` ADD `moduleId` INT NULL AFTER `id`, ADD INDEX (`moduleId`) ; 
ALTER TABLE `orm_record_type` ADD FOREIGN KEY (`moduleId`) REFERENCES `modules_module`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;