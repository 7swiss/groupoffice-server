-- -----------------------------------------------------
-- Table `files_storage`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `files_storage` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `quota` BIGINT NULL,
  `usage` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `files_node`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `files_node` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `createdAt` DATETIME NOT NULL,
  `modifiedAt` DATETIME NOT NULL,
  `versionUntil` DATETIME NULL,
  `metaData` TEXT NULL,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `storageId` INT NOT NULL,
  `blobId` CHAR(40) COLLATE ascii_bin NOT NULL,
  `isDirectory` TINYINT NOT NULL DEFAULT 0,
  `ownedBy` INT NOT NULL,
  `parentId` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_files_node_files_storage1_idx` (`storageId` ASC),
  INDEX `fk_files_node_blob_blob1_idx` (`blobId` ASC),
  INDEX `fk_files_node_auth_user1_idx` (`ownedBy` ASC),
  INDEX `fk_files_node_files_node1_idx` (`parentId` ASC),
  CONSTRAINT `fk_files_node_files_storage1`
    FOREIGN KEY (`storageId`)
    REFERENCES `files_storage` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
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
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `files_node_access`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `files_node_group` (
  `nodeId` INT NOT NULL,
  `groupId` INT NOT NULL,
  `read` TINYINT(1) NOT NULL DEFAULT 1,
  `write` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`nodeId`, `groupId`),
  INDEX `fk_fiiles_node_access_files_node1_idx` (`nodeId` ASC),
  INDEX `fk_fiiles_node_access_auth_group1_idx` (`groupId` ASC),
  CONSTRAINT `fk_fiiles_node_access_files_node1`
    FOREIGN KEY (`nodeId`)
    REFERENCES `files_node` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_fiiles_node_access_auth_group1`
    FOREIGN KEY (`groupId`)
    REFERENCES `auth_group` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
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

INSERT INTO `files_storage` (`name`, `quota`, `usage`) VALUES ('Files', null, '0');

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