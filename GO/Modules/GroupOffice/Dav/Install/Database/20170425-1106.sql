CREATE TABLE `dav_event` (
  `id` int(11) unsigned NOT NULL,
  `data` mediumblob,
  `uri` varchar(200) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `modifiedAt` datetime NOT NULL,
  `size` int(11) unsigned NOT NULL,
  `uid` varchar(200) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `calendarId` int(11) NOT NULL,
  `eventId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_calendar_UNIQUE` (`calendarId`,`uid`),
  UNIQUE KEY `fk_dav_calendar_event_idx` (`calendarId`,`eventId`),
  CONSTRAINT `fk_dav_calendar_event` FOREIGN KEY (`calendarId`, `eventId`) REFERENCES `calendar_attendee` (`calendarId`, `eventId`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB;
