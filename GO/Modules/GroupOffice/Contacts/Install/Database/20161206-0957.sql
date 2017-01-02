ALTER TABLE `contacts_email_address` CHANGE `type` `type` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `contacts_phone` CHANGE `type` `type` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `contacts_address` CHANGE `type` `type` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;



-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_url`
--

CREATE TABLE `contacts_url` (
  `id` int(11) NOT NULL,
  `contactId` int(11) NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `contacts_url`
--
ALTER TABLE `contacts_url`
  ADD PRIMARY KEY (`id`,`contactId`),
  ADD KEY `contactId` (`contactId`);

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `contacts_url`
--
ALTER TABLE `contacts_url`
  ADD CONSTRAINT `contacts_url_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE;

ALTER TABLE `contacts_url` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contacts_address` CHANGE `country` `country` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;


ALTER TABLE `contacts_contact_organization` DROP FOREIGN KEY `contacts_contact_organization_ibfk_1`; ALTER TABLE `contacts_contact_organization` ADD CONSTRAINT `contacts_contact_organization_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; ALTER TABLE `contacts_contact_organization` DROP FOREIGN KEY `contacts_contact_organization_ibfk_2`; ALTER TABLE `contacts_contact_organization` ADD CONSTRAINT `contacts_contact_organization_ibfk_2` FOREIGN KEY (`organizationContactId`) REFERENCES `contacts_contact`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;