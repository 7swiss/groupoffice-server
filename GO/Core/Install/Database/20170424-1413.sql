ALTER TABLE `smtp_account` CHANGE `encryption` `encryption` ENUM('ssl','tls') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'tls';
ALTER TABLE `smtp_account` CHANGE `port` `port` INT(11) NOT NULL DEFAULT '587';

insert into accounts_capability select null,id, "GO\\Core\\Email\\Model\\Message" from accounts_account where modelName="GO\\Core\\Smtp\\Model\\Account";

ALTER TABLE `accounts_account` ADD `deleted` BOOLEAN NOT NULL DEFAULT FALSE AFTER `ownedBy`;
