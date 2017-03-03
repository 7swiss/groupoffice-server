ALTER TABLE `tasks_task_comment` ADD INDEX(`taskId`);
ALTER TABLE `tasks_task_comment` DROP PRIMARY KEY, ADD PRIMARY KEY( `commentId`);