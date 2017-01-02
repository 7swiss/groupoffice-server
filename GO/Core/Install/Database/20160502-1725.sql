--
-- Database: `go7`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `log_event`
--

CREATE TABLE `log_event` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelId` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `createdBy` int(11) NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `log_follower`
--

CREATE TABLE `log_follower` (
  `modelName` varchar(255) COLLATE ascii_general_ci NOT NULL,
  `modelId` int(11) NOT NULL,
  `userId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `log_notification`
--

CREATE TABLE `log_notification` (
  `eventId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `seenAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `log_event`
--
ALTER TABLE `log_event`
  ADD PRIMARY KEY (`id`),
  ADD KEY `createdBy` (`createdBy`);

--
-- Indexen voor tabel `log_follower`
--
ALTER TABLE `log_follower`
  ADD PRIMARY KEY (`userId`,`modelId`,`modelName`);

--
-- Indexen voor tabel `log_notification`
--
ALTER TABLE `log_notification`
  ADD PRIMARY KEY (`eventId`,`userId`),
  ADD KEY `userId` (`userId`),
  ADD KEY `seenAt` (`seenAt`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `log_event`
--
ALTER TABLE `log_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `log_event`
--
ALTER TABLE `log_event`
  ADD CONSTRAINT `log_event_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `auth_user` (`id`);

--
-- Beperkingen voor tabel `log_notification`
--
ALTER TABLE `log_notification`
  ADD CONSTRAINT `log_notification_ibfk_1` FOREIGN KEY (`eventId`) REFERENCES `log_event` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `log_notification_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `auth_user` (`id`) ON DELETE CASCADE;

