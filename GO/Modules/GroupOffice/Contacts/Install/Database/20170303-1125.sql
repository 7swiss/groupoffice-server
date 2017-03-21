CREATE TABLE `contacts_comment` (
  `commentId` int(11) NOT NULL,
  `contactId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `contacts_comment`
  ADD PRIMARY KEY (`commentId`),
  ADD KEY `contactId` (`contactId`);

ALTER TABLE `contacts_comment`
  ADD CONSTRAINT `contacts_comment_ibfk_1` FOREIGN KEY (`commentId`) REFERENCES `comments_comment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_comment_ibfk_2` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE;
