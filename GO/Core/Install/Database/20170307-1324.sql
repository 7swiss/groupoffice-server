ALTER TABLE `cron_job` CHANGE `cronExpression` `cronExpression` VARCHAR(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'CRON Scheduling expression. See http://en.wikipedia.org/wiki/Cron';
ALTER TABLE `cron_job` ADD `params` VARCHAR(190) NULL COMMENT 'JSON encoded parameters that are passed to the cron method' AFTER `method`;
