
-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `log_block`
--

CREATE TABLE `log_block` (
  `recordTypeId` int(11) NOT NULL,
  `recordId` int(11) NOT NULL,
  `userId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Users can choose to block notifications for models';

--
-- Gegevens worden geëxporteerd voor tabel `log_block`


--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `log_block`
--
ALTER TABLE `log_block`
  ADD PRIMARY KEY (`recordTypeId`,`recordId`,`userId`),
  ADD KEY `userId` (`userId`);

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `log_block`
--
ALTER TABLE `log_block`
  ADD CONSTRAINT `log_block_ibfk_1` FOREIGN KEY (`recordTypeId`) REFERENCES `orm_record_type` (`id`),
  ADD CONSTRAINT `log_block_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `auth_user` (`id`);

