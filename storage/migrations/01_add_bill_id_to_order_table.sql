ALTER TABLE `oc_order` ADD `bill_id` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `payment_code`;

ALTER TABLE `oc_order` ADD `refunded` VARCHAR(96) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `payment_code`;