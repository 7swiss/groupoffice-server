-- removed for creating account for elearning participants
ALTER TABLE `auth_user` 
CHANGE COLUMN `email` `email` VARCHAR(191) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NULL ,
DROP INDEX `email_2`;