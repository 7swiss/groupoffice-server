
-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `webclient_settings`
--

CREATE TABLE `webclient_settings` (
  `id` int(11) NOT NULL,
  `css` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `webclient_settings`
--


-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `webclient_settings`
--
ALTER TABLE `webclient_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `webclient_settings`
--
ALTER TABLE `webclient_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
