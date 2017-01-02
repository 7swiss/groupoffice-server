CREATE TABLE `bands_band_custom_fields` (
  `id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `bands_band_custom_fields_ibfk_1` FOREIGN KEY (`id`) REFERENCES `bands_band` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
