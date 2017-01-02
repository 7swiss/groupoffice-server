
-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `templates_pdf`
--

CREATE TABLE `templates_pdf` (
  `id` int(11) NOT NULL,
  `moduleId` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stationaryPdfBlobId` char(40) COLLATE ascii_bin DEFAULT NULL,
  `marginLeft` double NOT NULL DEFAULT '10',
  `marginRight` double NOT NULL DEFAULT '10',
  `marginTop` double NOT NULL DEFAULT '10',
  `marginBottom` double NOT NULL DEFAULT '10',
  `landscape` tinyint(1) NOT NULL DEFAULT '0',
  `pageSize` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A4',
  `measureUnit` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `templates_pdf_block`
--

CREATE TABLE `templates_pdf_block` (
  `id` int(11) NOT NULL,
  `pdfTemplateId` int(11) NOT NULL,
  `sortOrder` int(11) NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `x` double DEFAULT NULL COMMENT 'If x is null then the left margin will be used',
  `y` double DEFAULT NULL COMMENT 'If y is null then it will continue on where last block had the highest y',
  `width` double DEFAULT NULL COMMENT 'If null then the full page width will be used',
  `height` double DEFAULT NULL COMMENT 'If null then the height will be automatic depending on the content.',
  `align` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L',
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON content'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `templates_pdf`
--
ALTER TABLE `templates_pdf`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `stationaryPdfBlobId` (`stationaryPdfBlobId`),
  ADD KEY `moduleId` (`moduleId`);

--
-- Indexen voor tabel `templates_pdf_block`
--
ALTER TABLE `templates_pdf_block`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pdfTemplateId` (`pdfTemplateId`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `templates_pdf`
--
ALTER TABLE `templates_pdf`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT voor een tabel `templates_pdf_block`
--
ALTER TABLE `templates_pdf_block`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `templates_pdf`
--
ALTER TABLE `templates_pdf`
  ADD CONSTRAINT `templates_pdf_ibfk_1` FOREIGN KEY (`moduleId`) REFERENCES `modules_module` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `templates_pdf_ibfk_2` FOREIGN KEY (`stationaryPdfBlobId`) REFERENCES `blob_blob` (`blobId`);

--
-- Beperkingen voor tabel `templates_pdf_block`
--
ALTER TABLE `templates_pdf_block`
  ADD CONSTRAINT `templates_pdf_block_ibfk_1` FOREIGN KEY (`pdfTemplateId`) REFERENCES `templates_pdf` (`id`) ON DELETE CASCADE;









ALTER TABLE `blob_blob` CHANGE `mime` `contentType` VARCHAR(129) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
