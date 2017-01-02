
CREATE TABLE `dav_card` (
  `id` int(11) NOT NULL,
  `modifiedAt` datetime NOT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `uri` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `uid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uri` (`uri`),
  CONSTRAINT `dav_card_ibfk_1` FOREIGN KEY (`id`) REFERENCES `contacts_contact` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;