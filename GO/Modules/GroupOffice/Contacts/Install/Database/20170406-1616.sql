ALTER TABLE `contacts_contact` ADD `accountId` INT NOT NULL AFTER `deleted`, ADD INDEX (`accountId`);
INSERT INTO `accounts_account` (`id`, `name`, `modelName`, `ownedBy`) VALUES (NULL, 'GroupOffice', 'GO\\Modules\\GroupOffice\\Contacts\\Model\\Account', '1');

update contacts_contact set accountId = (select id from accounts_account where modelName="GO\\Modules\\GroupOffice\\Contacts\\Model\\Account" limit 0,1)

insert into `accounts_capability` select null, id, "GO\\Modules\\GroupOffice\\Contacts\\Model\\Contact" from accounts_account where modelName="GO\\Modules\\GroupOffice\\Contacts\\Model\\Account";

ALTER TABLE `contacts_contact` ADD FOREIGN KEY (`accountId`) REFERENCES `accounts_account`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;




CREATE TABLE `contacts_account_group` (
  `accountId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `write` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Gegevens worden geëxporteerd voor tabel `contacts_account_group`
--



--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `contacts_account_group`
--
ALTER TABLE `contacts_account_group`
  ADD PRIMARY KEY (`accountId`,`groupId`),
  ADD KEY `groupId` (`groupId`);

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `contacts_account_group`
--
ALTER TABLE `contacts_account_group`
  ADD CONSTRAINT `contacts_account_group_ibfk_1` FOREIGN KEY (`accountId`) REFERENCES `accounts_account` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_account_group_ibfk_2` FOREIGN KEY (`groupId`) REFERENCES `auth_group` (`id`) ON DELETE CASCADE;


INSERT INTO `contacts_account_group` (`accountId`, `groupId`, `write`) select id, 2, 1 from accounts_account where modelName="GO\\Modules\\GroupOffice\\Contacts\\Model\\Account" limit 0,1;


DROP TABLE contacts_contact_group;