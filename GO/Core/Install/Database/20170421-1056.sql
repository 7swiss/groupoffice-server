CREATE TABLE `accounts_account_group` (
  `accountId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `update` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `accounts_account_group`
--

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `accounts_account_group`
--
ALTER TABLE `accounts_account_group`
  ADD PRIMARY KEY (`accountId`,`groupId`),
  ADD KEY `groupId` (`groupId`);

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `accounts_account_group`
--
ALTER TABLE `accounts_account_group`
  ADD CONSTRAINT `accounts_account_group_ibfk_1` FOREIGN KEY (`accountId`) REFERENCES `accounts_account` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accounts_account_group_ibfk_2` FOREIGN KEY (`groupId`) REFERENCES `auth_group` (`id`) ON DELETE CASCADE;


INSERT INTO accounts_account_group select id, ownedBy, '1' from accounts_account;