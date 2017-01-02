
--
-- Tabelstructuur voor tabel `blob_blob`
--

CREATE TABLE `blob_blob` (
  `blobId` char(40) CHARACTER SET 'ascii' BINARY NOT NULL,
  `createdAt` datetime NOT NULL,
  `createdBy` int(11) NOT NULL,
  `expireAt` datetime DEFAULT NULL,
  `mime` varchar(129) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `blob_blob_user`
--

CREATE TABLE `blob_blob_user` (
  `blobId` char(40) CHARACTER SET 'ascii' BINARY NOT NULL,
  `modelTypeId` int(11) NOT NULL,
  `modelPk` varchar(45) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `blob_blob`
--
ALTER TABLE `blob_blob`
  ADD PRIMARY KEY (`blobId`);

--
-- Indexen voor tabel `blob_blob_user`
--
ALTER TABLE `blob_blob_user`
  ADD PRIMARY KEY (`blobId`,`modelTypeId`,`modelPk`);

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `blob_blob_user`
--
ALTER TABLE `blob_blob_user`
  ADD CONSTRAINT `blob_blob_user_ibfk_1` FOREIGN KEY (`blobId`) REFERENCES `blob_blob` (`blobId`) ON DELETE CASCADE;