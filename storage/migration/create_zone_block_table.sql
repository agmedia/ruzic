CREATE TABLE `ruzic`.`oc_zone_block` (
                                         `zone_block_id` INT(11) NOT NULL AUTO_INCREMENT,
                                         `zone_id` INT(11) NOT NULL,
                                         `title` VARCHAR(191) NOT NULL,
                                         `zip` VARCHAR(45) NOT NULL,
                                         `code` VARCHAR(45) NULL,
                                         `status` TINYINT(1) NOT NULL,
                                         PRIMARY KEY (`zone_block_id`) );