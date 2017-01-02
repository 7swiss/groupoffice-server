
-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `accounts_account`
--

CREATE TABLE IF NOT EXISTS `accounts_account` (
  `id` int(11) NOT NULL COMMENT 'Primary key',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User inputted name',
  `modelName` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'The PHP class name of the model that contains the actual account data. For example an IMAP account.',
  `createdBy` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Base account table for messages';

--
-- Gegevens worden geëxporteerd voor tabel `accounts_account`
--

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `accounts_account`
--
ALTER TABLE `accounts_account`
  ADD PRIMARY KEY (`id`),
  ADD KEY `createdBy` (`createdBy`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `accounts_account`
--
ALTER TABLE `accounts_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key',AUTO_INCREMENT=5;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `accounts_account`
--
ALTER TABLE `accounts_account`
  ADD CONSTRAINT `accounts_account_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `auth_user` (`id`);

