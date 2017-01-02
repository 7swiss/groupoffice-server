
--
-- Database: `go7`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `messages_address`
--

CREATE TABLE `messages_address` (
  `id` int(11) NOT NULL,
  `messageId` int(11) NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0=from\n1=to\n2=cc\n3=bcc\n4=reply to'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `messages_attachment`
--

CREATE TABLE `messages_attachment` (
  `id` int(11) NOT NULL,
  `messageId` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contentId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'If this set then this file (image) appears inline in the message body. When it''s an attachment this is set to null. ',
  `size` bigint(20) DEFAULT NULL,
  `blobId` CHAR(40) CHARACTER SET 'ascii' BINARY DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `messages_message`
--

CREATE TABLE `messages_message` (
  `id` int(11) NOT NULL,
  `threadId` int(11) DEFAULT NULL COMMENT 'Reference to the thread model.',
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `forwarded` tinyint(1) NOT NULL DEFAULT '0',
  `flagged` tinyint(1) NOT NULL DEFAULT '0',
  `actioned` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Actioned means that no action is required anymore on this message.',
  `type` tinyint(6) NOT NULL DEFAULT '2' COMMENT '0=incoming,1=sent,2=draft,3=junk,4=trash,5=outbox,6=actioned',
  `modifiedAt` datetime NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `uuid` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inReplyToId` int(11) DEFAULT NULL,
  `sentAt` datetime NOT NULL,
  `subject` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `photoBlobId` CHAR(40) CHARACTER SET 'ascii' BINARY DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `messages_thread`
--

CREATE TABLE `messages_thread` (
  `id` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `answered` tinyint(1) NOT NULL DEFAULT '0',
  `forwarded` tinyint(1) NOT NULL DEFAULT '0',
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `flagged` tinyint(1) NOT NULL DEFAULT '0',
  `hasAttachments` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'If any of the thread messages has this flag, The thread will have this set.',
  `lastMessageSentAt` datetime DEFAULT NULL,
  `excerpt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Excerpt of the latest message in the thread',
  `messageCount` int(11) DEFAULT NULL,
  `photoBlobId` CHAR(40) CHARACTER SET 'ascii' BINARY DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Threads';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `messages_thread_tag`
--

CREATE TABLE `messages_thread_tag` (
  `threadId` int(11) NOT NULL,
  `tagId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `messages_address`
--
ALTER TABLE `messages_address`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_messages_address_1_idx` (`messageId`);

--
-- Indexen voor tabel `messages_attachment`
--
ALTER TABLE `messages_attachment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_message_idx` (`messageId`),
  ADD KEY `blobId` (`blobId`);

--
-- Indexen voor tabel `messages_message`
--
ALTER TABLE `messages_message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `threadId` (`threadId`),
  ADD KEY `type` (`type`),
  ADD KEY `deleted` (`deleted`),
  ADD KEY `inReplyToId` (`inReplyToId`);

--
-- Indexen voor tabel `messages_thread`
--
ALTER TABLE `messages_thread`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_messages_threads_1_idx` (`accountId`),
  ADD KEY `lastMessageSentAt` (`lastMessageSentAt`),
  ADD KEY `seen` (`seen`),
  ADD KEY `flagged` (`flagged`),
  ADD KEY `photoBlobId` (`photoBlobId`);

--
-- Indexen voor tabel `messages_thread_tag`
--
ALTER TABLE `messages_thread_tag`
  ADD PRIMARY KEY (`threadId`,`tagId`),
  ADD KEY `messages_thread_tag_ibfk_2` (`tagId`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `messages_address`
--
ALTER TABLE `messages_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `messages_attachment`
--
ALTER TABLE `messages_attachment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `messages_message`
--
ALTER TABLE `messages_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `messages_thread`
--
ALTER TABLE `messages_thread`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `messages_address`
--
ALTER TABLE `messages_address`
  ADD CONSTRAINT `messages_address_ibfk_1` FOREIGN KEY (`messageId`) REFERENCES `messages_message` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `messages_attachment`
--
ALTER TABLE `messages_attachment`
  ADD CONSTRAINT `messages_attachment_ibfk_1` FOREIGN KEY (`blobId`) REFERENCES `blob_blob` (`blobId`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_message` FOREIGN KEY (`messageId`) REFERENCES `messages_message` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Beperkingen voor tabel `messages_message`
--
ALTER TABLE `messages_message`
  ADD CONSTRAINT `messages_message_ibfk_1` FOREIGN KEY (`threadId`) REFERENCES `messages_thread` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_message_ibfk_2` FOREIGN KEY (`inReplyToId`) REFERENCES `messages_message` (`id`) ON DELETE SET NULL;

--
-- Beperkingen voor tabel `messages_thread`
--
ALTER TABLE `messages_thread`
  ADD CONSTRAINT `messages_thread_ibfk_1` FOREIGN KEY (`accountId`) REFERENCES `accounts_account` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_thread_ibfk_2` FOREIGN KEY (`photoBlobId`) REFERENCES `blob_blob` (`blobId`);

--
-- Beperkingen voor tabel `messages_thread_tag`
--
ALTER TABLE `messages_thread_tag`
  ADD CONSTRAINT `messages_thread_tag_ibfk_1` FOREIGN KEY (`threadId`) REFERENCES `messages_thread` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_thread_tag_ibfk_2` FOREIGN KEY (`tagId`) REFERENCES `tags_tag` (`id`) ON DELETE CASCADE;



