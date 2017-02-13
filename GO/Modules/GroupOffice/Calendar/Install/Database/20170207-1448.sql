DROP TABLE `calendar_attending_resource`;
DROP TABLE `calendar_resource`;

ALTER TABLE `calendar_attendee`
DROP FOREIGN KEY `fk_calendar_attending_user_core_user1`;
ALTER TABLE `calendar_attendee`
CHANGE COLUMN `userId` `groupId` INT(11) NULL DEFAULT NULL ,
ADD INDEX `fk_calendar_attendee_core_group1_idx` (`groupId` ASC),
DROP INDEX `fk_calendar_attending_user_core_user1_idx` ;
ALTER TABLE `calendar_attendee`
ADD CONSTRAINT `fk_calendar_attendee_core_group1`
  FOREIGN KEY (`groupId`)
  REFERENCES `auth_group` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

ALTER TABLE `calendar_calendar`
DROP FOREIGN KEY `fk_calendar_calendar_core_user1`;
ALTER TABLE `calendar_calendar`
ADD INDEX `fk_calendar_calendar_core_user1_idx` (`ownedBy` ASC),
DROP INDEX `fk_calendar_calendar_core_user1_idx` ;
ALTER TABLE `calendar_calendar`
ADD CONSTRAINT `fk_calendar_calendar_core_user1`
  FOREIGN KEY (`ownedBy`)
  REFERENCES `auth_group` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION;
