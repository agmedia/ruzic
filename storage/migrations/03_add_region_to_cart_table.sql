ALTER TABLE `oc_cart`
    ADD COLUMN `region` TINYINT(1) NULL DEFAULT 0 AFTER `quantity`;

ALTER TABLE `oc_cart`
    CHANGE COLUMN `region` `region` VARCHAR(32) NULL DEFAULT '0' ;