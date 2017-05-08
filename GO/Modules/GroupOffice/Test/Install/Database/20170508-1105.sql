---------------------------------------

--
-- Tabelstructuur voor tabel `test_main`
--

CREATE TABLE `test_main` (
  `id` int(11) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `createdBy` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `modifiedBy` int(11) DEFAULT NULL,
  `modifiedAt` datetime NOT NULL,
  `ownedBy` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `test_relation_record`
--

CREATE TABLE `test_relation_record` (
  `id` int(11) NOT NULL,
  `mainId` int(11) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `test_main`
--
ALTER TABLE `test_main`
  ADD PRIMARY KEY (`id`),
  ADD KEY `createdBy` (`createdBy`),
  ADD KEY `modifiedBy` (`modifiedBy`),
  ADD KEY `ownedBy` (`ownedBy`);

--
-- Indexen voor tabel `test_relation_record`
--
ALTER TABLE `test_relation_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mainId` (`mainId`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `test_main`
--
ALTER TABLE `test_main`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `test_relation_record`
--
ALTER TABLE `test_relation_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `test_main`
--
ALTER TABLE `test_main`
  ADD CONSTRAINT `test_main_ibfk_1` FOREIGN KEY (`ownedBy`) REFERENCES `auth_user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_main_ibfk_2` FOREIGN KEY (`modifiedBy`) REFERENCES `auth_user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `test_main_ibfk_3` FOREIGN KEY (`ownedBy`) REFERENCES `auth_user` (`id`) ON DELETE CASCADE;



ALTER TABLE `test_relation_record` ADD `description` TEXT NULL AFTER `name`;


ALTER TABLE `test_main` ADD `description` TEXT NULL AFTER `name`;