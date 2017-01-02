CREATE TABLE `bands_band` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdBy` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `modifiedAt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  CONSTRAINT `bands_band_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `auth_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `bands_album` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bandId` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,	
  `genre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdBy` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `modifiedAt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bandId` (`bandId`),
  KEY `createdBy` (`createdBy`),
  CONSTRAINT `bands_album_ibfk_1` FOREIGN KEY (`bandId`) REFERENCES `bands_band` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bands_album_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `auth_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
