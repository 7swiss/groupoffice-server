ALTER TABLE `auth_user` CHANGE `password` `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'If the password hash is set to null it\'s impossible to login.';
ALTER TABLE `auth_user` CHANGE `digest` `digest` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL; 
