ALTER TABLE `templates_message` ADD COLUMN `type` INT NULL AFTER `moduleId`;
ALTER TABLE `custom_fields_field` ADD COLUMN `hintText` VARCHAR(255) NOT NULL DEFAULT '' AFTER `type`;
