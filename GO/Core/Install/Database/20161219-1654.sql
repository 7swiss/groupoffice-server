ALTER TABLE `notifications_notification_group` ADD FOREIGN KEY (`notificationId`) REFERENCES `notifications_notification`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `notifications_notification_group` ADD FOREIGN KEY (`groupId`) REFERENCES `auth_group`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `notifications_notification_appearance` ADD FOREIGN KEY (`notificationId`) REFERENCES `notifications_notification`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `notifications_notification_appearance` ADD FOREIGN KEY (`userId`) REFERENCES `auth_user`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;