- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `smtp_account`
--

CREATE TABLE IF NOT EXISTS `smtp_account` (
  `id` int(11) NOT NULL,
  `createdBy` int(11) NOT NULL,
  `hostname` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int(11) NOT NULL DEFAULT '25',
  `encryption` enum('ssl','tls') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fromName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fromEmail` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SMTP Accounts used by core and messages';

--
-- Gegevens worden geëxporteerd voor tabel `smtp_account`
--


--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `smtp_account`
--
ALTER TABLE `smtp_account`
  ADD PRIMARY KEY (`id`);

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `smtp_account`
--
ALTER TABLE `smtp_account`
  ADD CONSTRAINT `smtp_account_ibfk_1` FOREIGN KEY (`id`) REFERENCES `accounts_account` (`id`) ON DELETE CASCADE;
