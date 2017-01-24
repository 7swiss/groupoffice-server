ALTER TABLE `cron_job` ADD `runningSince` DATETIME NULL DEFAULT NULL AFTER `lastRun`, ADD INDEX (`runningSince`);
