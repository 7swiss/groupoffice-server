CREATE TABLE `orm_record_type` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE ascii_general_ci NOT NULL COMMENT 'The full PHP class name of the model without leading slash'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `orm_record_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `className` (`name`) USING BTREE;


--
ALTER TABLE `orm_record_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `log_event` ADD FOREIGN KEY (`recordTypeId`) REFERENCES `orm_record_type`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; 

