ALTER TABLE `oc_order` ADD `collect_date` DATE NULL AFTER `date_modified`;
ALTER TABLE `oc_order`
    ADD COLUMN `shipping_collector_id` INT(11) NULL AFTER `collect_date`;