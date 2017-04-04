-- -----------------------------------------------------
-- Table `files_drive`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `files_mount` (
  `userId` INT NOT NULL,
  `driveId` INT NOT NULL,
  PRIMARY KEY (`userId`, `driveId`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `files_drive`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `files_drive` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(127) CHARACTER SET 'ascii' NOT NULL,
  `quota` BIGINT NULL,
  `usage` BIGINT NOT NULL DEFAULT 0,
  `ownedBy` INT NOT NULL,
  `rootId` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `files_node_access`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `files_drive_group` (
  `driveId` INT NOT NULL,
  `groupId` INT NOT NULL,
  `write` TINYINT(1) NOT NULL DEFAULT 0,
  `manage` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`driveId`, `groupId`),
  INDEX `fk_files_node_access_files_drive1_idx` (`driveId` ASC),
  INDEX `fk_files_node_access_auth_group1_idx` (`groupId` ASC),
  CONSTRAINT `fk_files_drive_access_files_drive1`
    FOREIGN KEY (`driveId`)
    REFERENCES `files_drive` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_files_drive_access_auth_group1`
    FOREIGN KEY (`groupId`)
    REFERENCES `auth_group` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `files_node`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `files_node` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `createdAt` DATETIME NOT NULL,
  `modifiedAt` DATETIME NOT NULL,
  `versionUntil` DATETIME NULL,
  `metaData` TEXT NULL,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `blobId` CHAR(40) COLLATE ascii_bin NULL,
  `isDirectory` TINYINT(1) NOT NULL DEFAULT 0,
  `ownedBy` INT NOT NULL,
  `parentId` INT NULL DEFAULT NULL,
  `driveId` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_files_node_blob_blob1_idx` (`blobId` ASC),
  INDEX `fk_files_node_auth_user1_idx` (`ownedBy` ASC),
  INDEX `fk_files_node_files_node1_idx` (`parentId` ASC),
  INDEX `fk_files_node_files_drive1_idx` (`driveId` ASC),
  UNIQUE KEY `name_parent_unique` (`parentId`,`name`),
  CONSTRAINT `fk_files_node_blob_blob1`
    FOREIGN KEY (`blobId`)
    REFERENCES `blob_blob` (`blobId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_files_node_auth_user1`
    FOREIGN KEY (`ownedBy`)
    REFERENCES `auth_user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_files_node_files_node1`
    FOREIGN KEY (`parentId`)
    REFERENCES `files_node` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_files_node_files_drive1`
    FOREIGN KEY (`driveId`)
    REFERENCES `files_drive` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `files_version`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `files_version` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `createdAt` DATETIME NULL,
  `nodeId` INT NOT NULL,
  `blobId` CHAR(40) COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_files_version_files_node1_idx` (`nodeId` ASC),
  INDEX `fk_files_version_blob_blob1_idx` (`blobId` ASC),
  CONSTRAINT `fk_files_version_files_node1`
    FOREIGN KEY (`nodeId`)
    REFERENCES `files_node` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_files_version_blob_blob1`
    FOREIGN KEY (`blobId`)
    REFERENCES `blob_blob` (`blobId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `files_node_user` (
  `nodeId` INT NOT NULL,
  `userId` INT NOT NULL,
  `starred` TINYINT(1) NOT NULL DEFAULT 0,
  `touchedAt` DATETIME NULL,
  INDEX `fk_files_node_user_files_node1_idx` (`nodeId` ASC),
  PRIMARY KEY (`userId`, `nodeId`),
  CONSTRAINT `fk_files_node_user_files_node1`
    FOREIGN KEY (`nodeId`)
    REFERENCES `files_node` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_files_node_user_auth_user1`
    FOREIGN KEY (`userId`)
    REFERENCES `auth_user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

INSERT INTO `files_node` (`id`, `name`, `createdAt`, `modifiedAt`, `versionUntil`, `metaData`, `deleted`, `blobId`, `isDirectory`, `ownedBy`, `parentId`)
VALUES ('1', '/', '0', '0', '0', '', '0', null, '1', '1', '1');
