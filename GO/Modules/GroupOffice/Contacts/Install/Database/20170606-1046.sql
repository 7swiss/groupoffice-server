ALTER TABLE `contacts_contact` CHANGE `registrationNumber` `registrationNumber` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Company trade registration number';
ALTER TABLE `contacts_contact` CHANGE `IBAN` `IBAN` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `contacts_contact` CHANGE `firstName` `firstName` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `contacts_contact` CHANGE `middleName` `middleName` VARCHAR(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `contacts_contact` CHANGE `lastName` `lastName` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `contacts_contact` CHANGE `suffixes` `suffixes` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Suffixes like \'Msc.\'';
ALTER TABLE `contacts_contact` CHANGE `prefixes` `prefixes` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Prefixes like ''Sir''';

