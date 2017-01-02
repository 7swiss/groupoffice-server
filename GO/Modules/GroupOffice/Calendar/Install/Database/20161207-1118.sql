ALTER TABLE `calendar_recurrence_rule` DROP `rrule`;

ALTER TABLE `calendar_exception` RENAME TO  `calendar_event_exception`;
