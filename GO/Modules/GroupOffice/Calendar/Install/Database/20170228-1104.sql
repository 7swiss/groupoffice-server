
DROP TABLE IF EXISTS `calendar_recurrence_exception`;
DROP TABLE IF EXISTS `calendar_recurrence_rule`;
DROP TABLE IF EXISTS `calendar_default_alarm`;
DROP TABLE IF EXISTS `calendar_alarm`;
DROP TABLE IF EXISTS `calendar_attendee`;
DROP TABLE IF EXISTS `calendar_event_attachment`;
DROP TABLE IF EXISTS `calendar_event`;
DROP TABLE IF EXISTS `calendar_calendar`;

--
-- Table structure for table `calendar_event`
--
CREATE TABLE `calendar_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recurrenceId` datetime DEFAULT NULL,
  `sequence` int(11) NOT NULL DEFAULT '0',
  `allDay` tinyint(1) DEFAULT '0',
  `startAt` datetime DEFAULT NULL,
  `endAt` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `modifiedAt` datetime NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(144) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(4) DEFAULT '1',
  `busy` tinyint(1) DEFAULT '1',
  `tag` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visibility` tinyint(4) DEFAULT '1',
  `organizerEmail` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_calendar_event_calendar_attending_individual1_idx` (`id`,`organizerEmail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `calendar_event_attachment`
--
CREATE TABLE `calendar_event_attachment` (
  `eventId` int(11) NOT NULL,
  `blobId` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recurrenceId` datetime DEFAULT NULL,
  PRIMARY KEY (`eventId`,`blobId`),
  KEY `fk_calendar_event_has_blob_blob1_idx` (`blobId`),
  KEY `fk_calendar_event_attachment_calendar_event1_idx` (`eventId`),
  CONSTRAINT `fk_calendar_event_attachment_calendar_event1` FOREIGN KEY (`eventId`) REFERENCES `calendar_event` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_calendar_event_has_file_file_file_file1` FOREIGN KEY (`blobId`) REFERENCES `blob_blob` (`blobId`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `calendar_recurrence_rule`
--
CREATE TABLE `calendar_recurrence_rule` (
  `eventId` int(11) NOT NULL,
  `frequency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `until` datetime DEFAULT NULL,
  `occurrences` int(11) DEFAULT NULL,
  `interval` int(11) NOT NULL DEFAULT '0',
  `byDay` int(11) DEFAULT NULL,
  `byMonth` int(10) unsigned DEFAULT NULL,
  `byYearday` bigint(20) DEFAULT NULL,
  `byMonthday` bigint(20) unsigned DEFAULT NULL,
  `byHour` bigint(20) DEFAULT NULL,
  `byMinute` bigint(20) DEFAULT NULL,
  `bySecond` bigint(20) DEFAULT NULL,
  `bySetPos` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`eventId`),
  KEY `fk_calendar_recurrence_rule_calendar_event1_idx` (`eventId`),
  CONSTRAINT `fk_calendar_recurrence_rule_calendar_event1` FOREIGN KEY (`eventId`) REFERENCES `calendar_event` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `calendar_event_instance`
--
CREATE TABLE `calendar_event_instance` (
  `eventId` int(11) NOT NULL,
  `recurrenceId` datetime NOT NULL,
  `patchEventId` int(11) DEFAULT NULL,
  PRIMARY KEY (`eventId`,`recurrenceId`),
  KEY `fk_calendar_exception_calendar_recurrence_rule1_idx` (`eventId`),
  KEY `fk_calendar_event_patch_1_idx` (`patchEventId`),
  CONSTRAINT `fk_calendar_event_patch_event` FOREIGN KEY (`patchEventId`) REFERENCES `calendar_event` (`id`)  ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_calendar_exception_calendar_recurrence_rule1` FOREIGN KEY (`eventId`) REFERENCES `calendar_recurrence_rule` (`eventId`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB;


--
-- Table structure for table `calendar_calendar`
--
CREATE TABLE `calendar_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `ownedBy` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_calendar_calendar_core_user1_idx` (`ownedBy`),
  CONSTRAINT `fk_calendar_calendar_core_user1` FOREIGN KEY (`ownedBy`) REFERENCES `auth_group` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `calendar_default_alarm`
--
CREATE TABLE `calendar_default_alarm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trigger` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relativeTo` int(11) NOT NULL DEFAULT '0',
  `calendarId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_calendar_default_alarm_calendar_calendar1_idx` (`calendarId`),
  CONSTRAINT `fk_calendar_default_alarm_calendar_calendar1` FOREIGN KEY (`calendarId`) REFERENCES `calendar_calendar` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `calendar_attendee`
--
CREATE TABLE `calendar_attendee` (
  `eventId` int(11) NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` tinyint(4) NOT NULL DEFAULT '1',
  `responseStatus` tinyint(4) NOT NULL DEFAULT '1',
  `calendarId` int(11) DEFAULT NULL,
  `groupId` int(11) DEFAULT NULL,
  PRIMARY KEY (`eventId`,`email`),
  KEY `fk_calendar_attendee_calendar_event1_idx` (`eventId`),
  KEY `fk_calendar_attendee_user_calendar_calendar1_idx` (`calendarId`),
  KEY `fk_calendar_attendee_core_group1_idx` (`groupId`),
  CONSTRAINT `fk_calendar_attendee_calendar_event1` FOREIGN KEY (`eventId`) REFERENCES `calendar_event` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_calendar_attendee_core_group1` FOREIGN KEY (`groupId`) REFERENCES `auth_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_calendar_attendee_user_calendar_calendar1` FOREIGN KEY (`calendarId`) REFERENCES `calendar_calendar` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  UNIQUE INDEX `fk_calendar_event_unique` (`calendarId` ASC, `eventId` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `calendar_alarm`
--
CREATE TABLE `calendar_alarm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trigger` varchar(28) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `triggerAt` datetime NOT NULL,
  `relativeTo` int(11) NOT NULL DEFAULT '1',
  `eventId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_calendar_alarm_calendar_attending_individual1_idx` (`eventId`),
  KEY `fk_calendar_alarm_auth_user_idx` (`groupId`),
  CONSTRAINT `fk_calendar_alarm_auth_group` FOREIGN KEY (`groupId`) REFERENCES `auth_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_calendar_alarm_calendar_attending_individual1` FOREIGN KEY (`eventId`) REFERENCES `calendar_attendee` (`eventId`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `calendar_calendar_group`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `calendar_calendar_group` (
  `calendarId` INT NOT NULL,
  `groupId` INT NOT NULL,
  `read` TINYINT(1) NOT NULL DEFAULT 1,
  `write` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`calendarId`, `groupId`),
  INDEX `fk_calendar_calendar_group_calendar_calendar_idx` (`calendarId` ASC),
  INDEX `fk_calendar_calendar_group_auth_group1_idx` (`groupId` ASC),
  CONSTRAINT `fk_calendar_calendar_group_calendar_calendar`
    FOREIGN KEY (`calendarId`)
    REFERENCES `calendar_calendar` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_calendar_calendar_group_auth_group1`
    FOREIGN KEY (`groupId`)
    REFERENCES `auth_group` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;