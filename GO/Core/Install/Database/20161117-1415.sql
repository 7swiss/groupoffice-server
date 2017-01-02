
-- Database: `go7`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `templates_message`
--
DROP TABLE IF EXISTS template_message;
CREATE TABLE `templates_message` (
  `id` int(11) NOT NULL,
  `moduleId` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `templates_message`
--

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `templates_message`
--
ALTER TABLE `templates_message`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `moduleId` (`moduleId`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `templates_message`
--
ALTER TABLE `templates_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `templates_message`
--
ALTER TABLE `templates_message`
  ADD CONSTRAINT `templates_message_ibfk_1` FOREIGN KEY (`moduleId`) REFERENCES `modules_module` (`id`);
