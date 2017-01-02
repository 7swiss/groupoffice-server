DROP TABLE `log_notification`;
DROP TABLE `log_event`;
DROP TABLE `log_follower`;


-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `log_entry`
--

CREATE TABLE `log_entry` (
  `id` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `createdBy` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moduleName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recordId` int(11) DEFAULT NULL,
  `recordClassName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remoteIpAddress` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userAgent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('info','warning','critical','debug') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `log_entry`
--

-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `log_entry`
--
ALTER TABLE `log_entry`
  ADD PRIMARY KEY (`id`),
  ADD KEY `createdBy` (`createdBy`),
  ADD KEY `moduleId` (`moduleName`),
  ADD KEY `recordId` (`recordId`),
  ADD KEY `recordTypeId` (`recordClassName`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `log_entry`
--
ALTER TABLE `log_entry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
