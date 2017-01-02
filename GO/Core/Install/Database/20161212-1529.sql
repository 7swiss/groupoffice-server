ALTER TABLE `cron_job` ADD `runUserId` INT NOT NULL DEFAULT '1' AFTER `cronExpression`, ADD INDEX (`runUserId`);
ALTER TABLE `cron_job` ADD FOREIGN KEY (`runUserId`) REFERENCES `auth_user`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

DELETE FROM orm_record_type where moduleId IS NULL;

update `modules_module` set name = replace(name, 'GO\\Modules\\', 'GO\\Modules\\GroupOffice\\');
update `orm_record_type` set name = replace(name, 'GO\\Modules\\', 'GO\\Modules\\GroupOffice\\');
update `custom_fields_field_set` set name = replace(name, 'GO\\Modules\\', 'GO\\Modules\\GroupOffice\\');
update `accounts_account` set modelName = replace(modelName, 'GO\\Modules\\', 'GO\\Modules\\GroupOffice\\');
update `cron_job` set cronClassName = replace(cronClassName, 'GO\\Modules\\', 'GO\\Modules\\GroupOffice\\');
update `log_entry` set moduleName = replace(moduleName, 'GO\\Modules\\', 'GO\\Modules\\GroupOffice\\');
update `log_entry` set recordClassName = replace(recordClassName, 'GO\\Modules\\', 'GO\\Modules\\GroupOffice\\');