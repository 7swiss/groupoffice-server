CREATE TABLE `tasks_contact_task` (
  `contactId` int(11) NOT NULL,
  `taskId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `tasks_contact_task`
  ADD PRIMARY KEY (`taskId`),
  ADD KEY `contactId` (`contactId`);

ALTER TABLE `tasks_contact_task`
  ADD CONSTRAINT `tasks_contact_task_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_contact_task_ibfk_2` FOREIGN KEY (`taskId`) REFERENCES `tasks_task` (`id`) ON DELETE CASCADE;

