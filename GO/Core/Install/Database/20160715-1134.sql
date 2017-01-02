delete from auth_browser_token;
ALTER TABLE auth_browser_token DROP PRIMARY KEY;
ALTER TABLE `auth_browser_token` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
ALTER TABLE `auth_browser_token` ADD INDEX(`accessToken`);