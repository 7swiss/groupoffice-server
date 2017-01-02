

--
-- Database: `go7`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_address`
--

DROP TABLE IF EXISTS `contacts_address`;
CREATE TABLE `contacts_address` (
  `id` int(11) NOT NULL,
  `contactId` int(11) NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `street` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zipCode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `state` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `country` char(2) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_contact`
--

DROP TABLE IF EXISTS `contacts_contact`;
CREATE TABLE `contacts_contact` (
  `id` int(11) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `userId` int(11) DEFAULT NULL COMMENT 'Set to user ID if this contact is a profile for that user',
  `createdBy` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `modifiedAt` datetime NOT NULL,
  `prefixes` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Prefixes like ''Sir''',
  `firstName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `middleName` varchar(55) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `suffixes` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Suffixes like ''Msc.''',
  `gender` enum('M','F') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'M for Male, F for Female or null for unknown',
  `_photoFilePath` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Relative path where photo is stored',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `isOrganization` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'name field for companies and contacts. It should be the display name of first, middle and last name',
  `IBAN` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `registrationNumber` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Company trade registration number',
  `organizationContactId` int(11) DEFAULT NULL,
  `_filesFolderId` int(11) DEFAULT NULL COMMENT 'Links to the folder with files'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_contact_group`
--

DROP TABLE IF EXISTS `contacts_contact_group`;
CREATE TABLE `contacts_contact_group` (
  `contactId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT '1',
  `write` tinyint(1) NOT NULL DEFAULT '1',
  `delete` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Link table for contact permissions';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_contact_organization`
--

DROP TABLE IF EXISTS `contacts_contact_organization`;
CREATE TABLE `contacts_contact_organization` (
  `contactId` int(11) NOT NULL,
  `organizationContactId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_contact_tag`
--

DROP TABLE IF EXISTS `contacts_contact_tag`;
CREATE TABLE `contacts_contact_tag` (
  `contactId` int(11) NOT NULL,
  `tagId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_custom_fields`
--

DROP TABLE IF EXISTS `contacts_custom_fields`;
CREATE TABLE `contacts_custom_fields` (
  `id` int(11) NOT NULL,
  `textfield1` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `number1` double DEFAULT NULL,
  `select` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'test',
  `select1` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Option 1',
  `textfield2` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `textfield3` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `textfield 1` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `t1` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `t12` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `t5` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_date`
--

DROP TABLE IF EXISTS `contacts_date`;
CREATE TABLE `contacts_date` (
  `id` int(11) NOT NULL,
  `contactId` int(11) NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'birthday',
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_email_address`
--

DROP TABLE IF EXISTS `contacts_email_address`;
CREATE TABLE `contacts_email_address` (
  `id` int(11) NOT NULL,
  `contactId` int(11) NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'work',
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contacts_phone`
--

DROP TABLE IF EXISTS `contacts_phone`;
CREATE TABLE `contacts_phone` (
  `id` int(11) NOT NULL,
  `contactId` int(11) NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'work,voice',
  `number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `contacts_address`
--
ALTER TABLE `contacts_address`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contactId` (`contactId`);

--
-- Indexen voor tabel `contacts_contact`
--
ALTER TABLE `contacts_contact`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deleted` (`deleted`),
  ADD KEY `userId` (`userId`),
  ADD KEY `companyContactId` (`organizationContactId`),
  ADD KEY `_filesFolderId` (`_filesFolderId`),
  ADD KEY `owner` (`createdBy`);

--
-- Indexen voor tabel `contacts_contact_group`
--
ALTER TABLE `contacts_contact_group`
  ADD PRIMARY KEY (`contactId`,`groupId`),
  ADD KEY `groupId` (`groupId`);

--
-- Indexen voor tabel `contacts_contact_organization`
--
ALTER TABLE `contacts_contact_organization`
  ADD PRIMARY KEY (`contactId`,`organizationContactId`),
  ADD KEY `organizationContactId` (`organizationContactId`);

--
-- Indexen voor tabel `contacts_contact_tag`
--
ALTER TABLE `contacts_contact_tag`
  ADD PRIMARY KEY (`contactId`,`tagId`),
  ADD KEY `tagId` (`tagId`);

--
-- Indexen voor tabel `contacts_custom_fields`
--
ALTER TABLE `contacts_custom_fields`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `contacts_date`
--
ALTER TABLE `contacts_date`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contactId` (`contactId`);

--
-- Indexen voor tabel `contacts_email_address`
--
ALTER TABLE `contacts_email_address`
  ADD PRIMARY KEY (`id`,`contactId`);

--
-- Indexen voor tabel `contacts_phone`
--
ALTER TABLE `contacts_phone`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contactId` (`contactId`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `contacts_address`
--
ALTER TABLE `contacts_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT voor een tabel `contacts_contact`
--
ALTER TABLE `contacts_contact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=677;
--
-- AUTO_INCREMENT voor een tabel `contacts_date`
--
ALTER TABLE `contacts_date`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT voor een tabel `contacts_email_address`
--
ALTER TABLE `contacts_email_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=475;
--
-- AUTO_INCREMENT voor een tabel `contacts_phone`
--
ALTER TABLE `contacts_phone`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `contacts_address`
--
ALTER TABLE `contacts_address`
  ADD CONSTRAINT `contacts_address_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `contacts_contact`
--
ALTER TABLE `contacts_contact`
  ADD CONSTRAINT `contacts_contact_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `auth_user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contacts_contact_ibfk_3` FOREIGN KEY (`organizationContactId`) REFERENCES `contacts_contact` (`id`) ON DELETE SET NULL;

--
-- Beperkingen voor tabel `contacts_contact_group`
--
ALTER TABLE `contacts_contact_group`
  ADD CONSTRAINT `contacts_contact_group_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_contact_group_ibfk_2` FOREIGN KEY (`groupId`) REFERENCES `auth_group` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `contacts_contact_organization`
--
ALTER TABLE `contacts_contact_organization`
  ADD CONSTRAINT `contacts_contact_organization_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`),
  ADD CONSTRAINT `contacts_contact_organization_ibfk_2` FOREIGN KEY (`organizationContactId`) REFERENCES `contacts_contact` (`id`);

--
-- Beperkingen voor tabel `contacts_contact_tag`
--
ALTER TABLE `contacts_contact_tag`
  ADD CONSTRAINT `contacts_contact_tag_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_contact_tag_ibfk_2` FOREIGN KEY (`tagId`) REFERENCES `tags_tag` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `contacts_custom_fields`
--
ALTER TABLE `contacts_custom_fields`
  ADD CONSTRAINT `contacts_custom_fields_ibfk_1` FOREIGN KEY (`id`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `contacts_date`
--
ALTER TABLE `contacts_date`
  ADD CONSTRAINT `contacts_date_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `contacts_phone`
--
ALTER TABLE `contacts_phone`
  ADD CONSTRAINT `contacts_phone_ibfk_1` FOREIGN KEY (`contactId`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE;

