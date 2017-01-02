--
-- Table structure for table `actions_action`
--

CREATE TABLE IF NOT EXISTS `actions_action` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `actions_follower`
--

CREATE TABLE IF NOT EXISTS `actions_follower` (
  `userId` int(11) NOT NULL,
  `actionId` int(11) NOT NULL,
  `seenAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actions_action`
--
ALTER TABLE `actions_action`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `actions_follower`
--
ALTER TABLE `actions_follower`
  ADD PRIMARY KEY (`userId`,`actionId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `actions_action`
--
ALTER TABLE `actions_action`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;