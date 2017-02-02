ALTER TABLE `calendar_event` 
DROP FOREIGN KEY `fk_calendar_event_calendar_exception1`;
ALTER TABLE `calendar_event` 
DROP COLUMN `exceptionId`,
DROP INDEX `fk_calendar_event_calendar_exception1_idx` ;


DROP TABLE `calendar_event_exception`;

CREATE TABLE IF NOT EXISTS `calendar_recurrence_exception` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `eventId` INT NOT NULL,
  `recurrenceId` DATETIME NOT NULL,
  `isDeleted` TINYINT(1) NOT NULL DEFAULT 1,
  `title` VARCHAR(255) NULL,
  `startAt` DATETIME NULL,
  `endAt` DATETIME NULL,
  `description` TEXT NULL,
  `location` VARCHAR(144) NULL,
  `status` TINYINT(1) NULL,
  `classification` TINYINT NULL,
  `busy` TINYINT(1) NULL,
  PRIMARY KEY (`id`, `eventId`),
  INDEX `fk_calendar_exception_calendar_recurence1_idx` (`eventId` ASC),
  CONSTRAINT `fk_calendar_exception_calendar_recurence1`
    FOREIGN KEY (`eventId`)
    REFERENCES `calendar_recurrence_rule` (`eventId`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB


ALTER TABLE `calendar_event`
ADD COLUMN `sequence` INT NOT NULL DEFAULT 0 AFTER `uuid`;

ALTER TABLE `calendar_attending_individual` RENAME TO  `calendar_attendee` ;

ALTER TABLE `calendar_event`
CHANGE COLUMN `allDay` `allDay` TINYINT(1) NULL DEFAULT '0' ,
CHANGE COLUMN `startAt` `startAt` DATETIME NULL ,
CHANGE COLUMN `endAt` `endAt` DATETIME NULL ,
CHANGE COLUMN `title` `title` VARCHAR(191) COLLATE 'utf8mb4_unicode_ci' NULL ,
CHANGE COLUMN `status` `status` TINYINT(4) NULL DEFAULT '1' ,
CHANGE COLUMN `visibility` `visibility` TINYINT(4) NULL DEFAULT '1' ,
ADD COLUMN `busy` TINYINT(4) NULL DEFAULT 1 AFTER `status`;

ALTER TABLE `calendar_event`
CHANGE COLUMN `deleted` `deleted` TINYINT(1) NULL DEFAULT '0' ;
UPDATE go7.calendar_event SET deleted = null;
ALTER TABLE `calendar_event`
CHANGE COLUMN `deleted` `deletedAt` DATETIME NULL DEFAULT NULL ;

ALTER TABLE `calendar_event`
DROP FOREIGN KEY `fk_calendar_event_calendar_exception1`;
ALTER TABLE `calendar_event`
ADD CONSTRAINT `fk_calendar_event_calendar_exception1`
  FOREIGN KEY (`exceptionId`)
  REFERENCES `calendar_recurrence_exception` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

ALTER TABLE `calendar_recurrence_rule`
DROP FOREIGN KEY `fk_calendar_recurrence_rule_calendar_event1`;
ALTER TABLE `calendar_recurrence_rule`
ADD CONSTRAINT `fk_calendar_recurrence_rule_calendar_event1`
  FOREIGN KEY (`eventId`)
  REFERENCES `calendar_event` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION;