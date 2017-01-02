--
-- Tabelstructuur voor tabel `notifications_notification`
--

CREATE TABLE `notifications_notification` (
  `id` int(11) NOT NULL,
  `createdBy` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `triggerAt` datetime NOT NULL,
  `iconBlobId` char(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiresAt` datetime NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'message',
  `priority` tinyint(4) NOT NULL DEFAULT '2' COMMENT '0=min,1=low,2=default,3=high,4=max',
  `recordTypeId` int(11) NOT NULL,
  `recordId` int(11) NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `notifications_notification_appearance`
--

CREATE TABLE `notifications_notification_appearance` (
  `notificationId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `dismissedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `notifications_notification_group`
--

CREATE TABLE `notifications_notification_group` (
  `notificationId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `notifications_watch`
--

CREATE TABLE `notifications_watch` (
  `groupId` int(11) NOT NULL,
  `recordTypeId` int(11) NOT NULL,
  `recordId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `notifications_notification`
--
ALTER TABLE `notifications_notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `triggerAt` (`triggerAt`),
  ADD KEY `iconBlobId` (`iconBlobId`),
  ADD KEY `recordTypeId` (`recordTypeId`,`recordId`),
  ADD KEY `createdBy` (`createdBy`);

--
-- Indexen voor tabel `notifications_notification_appearance`
--
ALTER TABLE `notifications_notification_appearance`
  ADD PRIMARY KEY (`notificationId`,`userId`);

--
-- Indexen voor tabel `notifications_notification_group`
--
ALTER TABLE `notifications_notification_group`
  ADD PRIMARY KEY (`notificationId`,`groupId`);

--
-- Indexen voor tabel `notifications_watch`
--
ALTER TABLE `notifications_watch`
  ADD PRIMARY KEY (`groupId`,`recordTypeId`,`recordId`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `notifications_notification`
--
ALTER TABLE `notifications_notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
