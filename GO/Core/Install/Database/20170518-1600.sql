ALTER TABLE `auth_token` ADD `userAgent` VARCHAR(190) NULL AFTER `expiresAt`, ADD `remoteIpAddress` VARCHAR(50) NULL AFTER `userAgent`;


INSERT INTO `cron_job` (`id`, `deleted`, `moduleId`, `name`, `cronClassName`, `method`, `params`, `cronExpression`, `enabled`, `runUserId`, `nextRun`, `lastRun`, `runningSince`, `timezone`, `lastError`) VALUES
(4, 0, NULL, 'Garbage Collector', 'GO\\Core\\GarbageCollection\\Collector', 'collect', NULL, '0 0 * * *', 1, 1, '2017-05-20 00:00:00', NULL, NULL, 'UTC', NULL);

--
