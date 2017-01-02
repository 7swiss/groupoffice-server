------------------------------------

--
-- Tabelstructuur voor tabel `tasks_task`
--

CREATE TABLE `tasks_task` (
  `id` int(11) NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `dueAt` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `completedAt` datetime DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `createdAt` datetime NOT NULL,
  `modifiedAt` datetime NOT NULL,
  `createdBy` int(11) NOT NULL,
  `assignedTo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `tasks_task`
-------------------------------------------------

--
-- Tabelstructuur voor tabel `tasks_task_tag`
--

CREATE TABLE `tasks_task_tag` (
  `taskId` int(11) NOT NULL,
  `tagId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `tasks_task_tag`

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `tasks_task`
--
ALTER TABLE `tasks_task`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `tasks_task_tag`
--
ALTER TABLE `tasks_task_tag`
  ADD PRIMARY KEY (`taskId`,`tagId`),
  ADD KEY `tagId` (`tagId`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `tasks_task`
--
ALTER TABLE `tasks_task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `tasks_task_tag`
--
ALTER TABLE `tasks_task_tag`
  ADD CONSTRAINT `tasks_task_tag_ibfk_1` FOREIGN KEY (`taskId`) REFERENCES `tasks_task` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_task_tag_ibfk_2` FOREIGN KEY (`tagId`) REFERENCES `tags_tag` (`id`) ON DELETE CASCADE;

