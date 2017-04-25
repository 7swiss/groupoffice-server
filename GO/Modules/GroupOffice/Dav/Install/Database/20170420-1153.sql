
DROP TABLE IF EXISTS dav_card;

CREATE TABLE `dav_account` (
  `id` int(11) NOT NULL,
  `url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

----------------------------------------------------

--
-- Tabelstructuur voor tabel `dav_account_card`
--

CREATE TABLE `dav_account_card` (
  `contactId` int(11) DEFAULT NULL,
  `accountId` int(11) NOT NULL,
  `modifiedAt` datetime NOT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `uri` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `etag` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `dav_account_card`
--

--
-- Tabelstructuur voor tabel `dav_account_collection`
--

CREATE TABLE `dav_account_collection` (
  `id` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `uri` varchar(190) NOT NULL,
  `ctag` varchar(190) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `dav_account_collection`
--

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `dav_account`
--
ALTER TABLE `dav_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `dav_account_card`
--
ALTER TABLE `dav_account_card`
  ADD PRIMARY KEY (`accountId`,`uri`),
  ADD KEY `etag` (`etag`),
  ADD KEY `contactId` (`contactId`);

--
-- Indexen voor tabel `dav_account_collection`
--
ALTER TABLE `dav_account_collection`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accountId` (`accountId`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `dav_account_collection`
--
ALTER TABLE `dav_account_collection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `dav_account_card`
--
ALTER TABLE `dav_account_card`
  ADD CONSTRAINT `dav_account_card_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dav_account_card_ibfk_2` FOREIGN KEY (`accountId`) REFERENCES `dav_account` (`id`) ON UPDATE CASCADE;

--
-- Beperkingen voor tabel `dav_account_collection`
--
ALTER TABLE `dav_account_collection`
  ADD CONSTRAINT `dav_account_collection_ibfk_1` FOREIGN KEY (`accountId`) REFERENCES `dav_account` (`id`) ON DELETE CASCADE;

