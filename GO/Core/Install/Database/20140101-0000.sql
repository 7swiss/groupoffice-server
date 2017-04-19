CREATE TABLE `auth_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `digest` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdAt` datetime NOT NULL,
  `modifiedAt` datetime NOT NULL,
  `loginCount` int(11) NOT NULL DEFAULT '0',
  `lastLogin` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auth_browser_token` (
  `accessToken` varchar(50) CHARACTER SET ascii COLLATE ascii_general_ci,
  `XSRFToken` varchar(50) CHARACTER SET ascii COLLATE ascii_general_ci,
  `userId` int(11) NOT NULL,
  `expiresAt` datetime NOT NULL,
  PRIMARY KEY (`accessToken`),
  KEY `userId` (`userId`),
  CONSTRAINT `auth_browser_token_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `auth_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `auth_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `autoAdd` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`),
  UNIQUE KEY `name` (`name`),
  KEY `autoAdd` (`autoAdd`),
  KEY `deleted` (`deleted`),
  CONSTRAINT `auth_group_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `auth_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `auth_user_group` (
  `userId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  PRIMARY KEY (`userId`,`groupId`),
  KEY `groupId` (`groupId`),
  CONSTRAINT `auth_user_group_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `auth_user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_user_group_ibfk_2` FOREIGN KEY (`groupId`) REFERENCES `auth_group` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `core_installation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dbVersion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `modules_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(191) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `version` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `modules_module_group` (
  `moduleId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`moduleId`,`groupId`,`action`),
  KEY `groupId` (`groupId`),
  CONSTRAINT `modules_module_group_ibfk_1` FOREIGN KEY (`moduleId`) REFERENCES `modules_module` (`id`) ON DELETE CASCADE,
  CONSTRAINT `modules_module_group_ibfk_2` FOREIGN KEY (`groupId`) REFERENCES `auth_group` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




CREATE TABLE `cron_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `moduleId` int(11) DEFAULT NULL COMMENT 'Set if this cron job belongs to a module and will be deinstalled along with a module.',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name for the job',
  `cronClassName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Class name to call method in',
  `method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Method to call',
  `cronExpression` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CRON Scheduling expression. See http://en.wikipedia.org/wiki/Cron',
  `nextRun` datetime DEFAULT NULL COMMENT 'Calculated time this cron will run',
  `lastRun` datetime DEFAULT NULL COMMENT 'Last time this cron ran',
  PRIMARY KEY (`id`),
  KEY `moduleId` (`moduleId`),
  CONSTRAINT `cron_job_ibfk_1` FOREIGN KEY (`moduleId`) REFERENCES `modules_module` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `custom_fields_field_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sortOrder` int(11) NOT NULL DEFAULT '0',
  `modelName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `model` (`modelName`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `custom_fields_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fieldSetId` int(11) NOT NULL,
  `sortOrder` int(11) NOT NULL DEFAULT '0',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `databaseName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placeholder` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `defaultValue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `_data` text COLLATE utf8mb4_unicode_ci,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `filterable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `databaseName` (`databaseName`),
  KEY `fieldSetId` (`fieldSetId`),
  KEY `deleted` (`deleted`),
  KEY `sortOrder` (`sortOrder`),
  CONSTRAINT `custom_fields_field_ibfk_1` FOREIGN KEY (`fieldSetId`) REFERENCES `custom_fields_field_set` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;







CREATE TABLE `tags_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;









INSERT INTO `auth_user` (`id`, `deleted`, `enabled`, `username`, `password`, `digest`, `createdAt`, `modifiedAt`, `loginCount`, `lastLogin`) VALUES
(1, 0, 1, 'admin', 'tJJlUNVIeWo2U', 'efb6a865d83ca3d8c7671dd5b81bf3f8', '2014-07-21 14:01:17', '2014-12-22 11:00:40', 70, '2014-12-22 12:00:40');


INSERT INTO `auth_group` (`id`, `deleted`, `autoAdd`, `name`, `userId`) VALUES
(1, 0, 0, 'Admins', 1),
(2, 0, 0, 'Everyone', NULL);


INSERT INTO `auth_user_group` (`userId`, `groupId`) VALUES
(1, 1),
(1, 2);