

CREATE TABLE `comments_attachment` (
  `id` int(11) NOT NULL,
  `commentId` int(11) NOT NULL,
  `blobId` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `comments_comment`
--

CREATE TABLE `comments_comment` (
  `id` int(11) NOT NULL,
  `createdBy` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `modifiedBy` int(11) NOT NULL,
  `modifiedAt` datetime NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `comments_attachment`
--
ALTER TABLE `comments_attachment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commentId` (`commentId`),
  ADD KEY `blobId` (`blobId`);

--
-- Indexen voor tabel `comments_comment`
--
ALTER TABLE `comments_comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `createdBy` (`createdBy`),
  ADD KEY `modifiedBy` (`modifiedBy`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `comments_attachment`
--
ALTER TABLE `comments_attachment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `comments_comment`
--
ALTER TABLE `comments_comment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `comments_attachment`
--
ALTER TABLE `comments_attachment`
  ADD CONSTRAINT `comments_attachment_ibfk_1` FOREIGN KEY (`commentId`) REFERENCES `comments_comment` (`id`),
  ADD CONSTRAINT `comments_attachment_ibfk_2` FOREIGN KEY (`blobId`) REFERENCES `blob_blob` (`blobId`);
