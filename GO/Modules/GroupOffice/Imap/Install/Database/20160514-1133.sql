--
-- Database: `go7`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `imap_account`
--

CREATE TABLE `imap_account` (
  `id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hostname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int(11) NOT NULL DEFAULT '143',
  `encryption` enum('ssl','tls') COLLATE utf8mb4_unicode_ci DEFAULT 'tls',
  `smtpAccountId` int(11) DEFAULT NULL,
  `createdBy` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IMAP account connection';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `imap_attachment`
--

CREATE TABLE `imap_attachment` (
  `attachmentId` int(11) NOT NULL,
  `messageId` int(11) NOT NULL,
  `partNo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The IMAP part number',
  `encoding` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `imap_folder`
--

CREATE TABLE `imap_folder` (
  `id` int(11) NOT NULL,
  `parentFolderId` int(11) DEFAULT NULL,
  `accountId` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delimiter` char(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mailbox path delimiter. Typically a . or /. Eg. INBOX.Folder',
  `uidValidity` int(11) DEFAULT NULL COMMENT 'When changed the uid''s must be resynchronized with IMAP\n\nhttps://tools.ietf.org/html/rfc3501#section-2.3.1.1',
  `highestModSeq` int(11) DEFAULT NULL COMMENT 'See: https://tools.ietf.org/html/rfc4551#section-3.1.1',
  `priority` tinyint(4) NOT NULL DEFAULT '10' COMMENT 'Sort column to make it possible to sort special folders like inbox, sent, spam and trash on top,'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IMAP folder table';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `imap_message`
--

CREATE TABLE `imap_message` (
  `messageId` int(11) NOT NULL,
  `folderId` int(11) NOT NULL,
  `imapUid` int(11) NOT NULL COMMENT 'UID of the IMAP server',
  `syncedAt` datetime NOT NULL,
  `inReplyToUuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Extra IMAP message data';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `imap_message_reference`
--

CREATE TABLE `imap_message_reference` (
  `messageId` int(11) NOT NULL,
  `uuid` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `imap_signature`
--

CREATE TABLE `imap_signature` (
  `id` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Composer signatures';

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `imap_account`
--
ALTER TABLE `imap_account`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_messages_imap_account_1_idx` (`smtpAccountId`);

--
-- Indexen voor tabel `imap_attachment`
--
ALTER TABLE `imap_attachment`
  ADD PRIMARY KEY (`attachmentId`,`messageId`),
  ADD KEY `messageId` (`messageId`);

--
-- Indexen voor tabel `imap_folder`
--
ALTER TABLE `imap_folder`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accountId` (`accountId`),
  ADD KEY `parentFolderId` (`parentFolderId`);

--
-- Indexen voor tabel `imap_message`
--
ALTER TABLE `imap_message`
  ADD PRIMARY KEY (`folderId`,`imapUid`),
  ADD KEY `messageId` (`messageId`);

--
-- Indexen voor tabel `imap_message_reference`
--
ALTER TABLE `imap_message_reference`
  ADD PRIMARY KEY (`messageId`,`uuid`);

--
-- Indexen voor tabel `imap_signature`
--
ALTER TABLE `imap_signature`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_signature_ibfk_1_idx` (`accountId`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `imap_folder`
--
ALTER TABLE `imap_folder`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `imap_signature`
--
ALTER TABLE `imap_signature`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `imap_account`
--
ALTER TABLE `imap_account`
  ADD CONSTRAINT `imap_account_ibfk_1` FOREIGN KEY (`id`) REFERENCES `accounts_account` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `imap_account_ibfk_2` FOREIGN KEY (`smtpAccountId`) REFERENCES `smtp_account` (`id`) ON DELETE SET NULL;

--
-- Beperkingen voor tabel `imap_attachment`
--
ALTER TABLE `imap_attachment`
  ADD CONSTRAINT `imap_attachment_ibfk_1` FOREIGN KEY (`attachmentId`) REFERENCES `messages_attachment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `imap_attachment_ibfk_2` FOREIGN KEY (`messageId`) REFERENCES `imap_message` (`messageId`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `imap_folder`
--
ALTER TABLE `imap_folder`
  ADD CONSTRAINT `imap_folder_ibfk_2` FOREIGN KEY (`accountId`) REFERENCES `imap_account` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `imap_folder_ibfk_3` FOREIGN KEY (`parentFolderId`) REFERENCES `imap_folder` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `imap_message`
--
ALTER TABLE `imap_message`
  ADD CONSTRAINT `imap_message_ibfk_1` FOREIGN KEY (`folderId`) REFERENCES `imap_folder` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_imap_message_data_ibfk_1` FOREIGN KEY (`messageId`) REFERENCES `messages_message` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `imap_message_reference`
--
ALTER TABLE `imap_message_reference`
  ADD CONSTRAINT `imap_message_reference_ibfk_1` FOREIGN KEY (`messageId`) REFERENCES `imap_message` (`messageId`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `imap_signature`
--
ALTER TABLE `imap_signature`
  ADD CONSTRAINT `imap_signature_ibfk_1` FOREIGN KEY (`accountId`) REFERENCES `imap_account` (`id`) ON DELETE CASCADE;
