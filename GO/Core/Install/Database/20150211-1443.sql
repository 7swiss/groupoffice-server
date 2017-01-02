update `modules_module` SET name = concat(SUBSTRING_INDEX(name, '\\',3), '\\Module');
