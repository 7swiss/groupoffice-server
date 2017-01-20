ALTER TABLE `cron_job` ADD `enabled` BOOLEAN NOT NULL DEFAULT TRUE AFTER `cronExpression`;
