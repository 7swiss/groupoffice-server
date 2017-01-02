CREATE TABLE `links_link` (
  `fromRecordTypeId` int(11) NOT NULL,
  `fromRecordId` int(11) NOT NULL,
  `toRecordTypeId` int(11) NOT NULL,
  `toRecordId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `links_link`
  ADD PRIMARY KEY (`fromRecordTypeId`,`fromRecordId`,`toRecordTypeId`,`toRecordId`),
  ADD KEY `toRecordTypeId` (`toRecordTypeId`);

