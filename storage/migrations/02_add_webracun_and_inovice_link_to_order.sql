ALTER TABLE `oc_order` ADD `webracun` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `invoice_no`;

ALTER TABLE `oc_order` ADD `webracun_link` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `invoice_prefix`;





