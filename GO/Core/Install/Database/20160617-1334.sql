CREATE TABLE `core_settings` (
  `id` int(11) NOT NULL,
  `smtpAccountId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `core_settings`
--
ALTER TABLE `core_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `smtpAccountId` (`smtpAccountId`);

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `core_settings`
--
ALTER TABLE `core_settings`
  ADD CONSTRAINT `core_settings_ibfk_1` FOREIGN KEY (`smtpAccountId`) REFERENCES `smtp_account` (`id`);

