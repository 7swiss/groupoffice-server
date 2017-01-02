
CREATE TABLE IF NOT EXISTS `tasks_task_comment` (
  `taskId` int(11) NOT NULL,
  `commentId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `tasks_task_comment`
  ADD PRIMARY KEY (`taskId`,`commentId`);

-- CREATE TRIGGER `delete_comment` AFTER DELETE ON `tasks_task_comment`
--  FOR EACH ROW BEGIN
-- DELETE FROM comments_comment
--     WHERE comments_comment.id = old.commentId;
-- END



ALTER TABLE `tasks_task_comment` ADD FOREIGN KEY (`taskId`) REFERENCES `tasks_task`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; ALTER TABLE `tasks_task_comment` ADD FOREIGN KEY (`commentId`) REFERENCES `comments_comment`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
