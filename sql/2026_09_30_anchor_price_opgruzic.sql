-- OPG Ruzic: sidrene cijene i digitalni CSV/XML cjenik (OpenCart 3)
-- Jedinstveni idempotentni phpMyAdmin paket. Produkcijski prefiks je `oc_`.
-- Preferirani postupak je ipak Extensions > Extensions > Modules >
-- "OPG Ruzic - Sidrene cijene" > Install jer aplikacijska instalacija moze
-- pravilno izracunati porez. Ovaj paket ne brise povijesne podatke.

CREATE TABLE IF NOT EXISTS `oc_anchor_price` (
  `anchor_price_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `store_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `gross_price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `currency_code` CHAR(3) NOT NULL DEFAULT 'EUR',
  `tax_class_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `tax_context` TEXT NOT NULL,
  `reference_date` DATE NOT NULL,
  `rule_code` VARCHAR(32) NOT NULL,
  `source` VARCHAR(32) NOT NULL,
  `verification_status` VARCHAR(20) NOT NULL DEFAULT 'confirmed',
  `created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `date_added` DATETIME NOT NULL,
  `date_modified` DATETIME NOT NULL,
  PRIMARY KEY (`anchor_price_id`),
  UNIQUE KEY `product_store` (`product_id`, `store_id`),
  KEY `reference_date` (`reference_date`),
  KEY `verification_status` (`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `oc_anchor_price_audit` (
  `audit_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `anchor_price_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `store_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `action` VARCHAR(32) NOT NULL,
  `old_data` MEDIUMTEXT NOT NULL,
  `new_data` MEDIUMTEXT NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `date_added` DATETIME NOT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `anchor_price_id` (`anchor_price_id`),
  KEY `product_store` (`product_id`, `store_id`),
  KEY `date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `oc_anchor_price_publication` (
  `publication_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `batch_key` CHAR(32) NOT NULL DEFAULT '',
  `location_code` VARCHAR(16) NOT NULL,
  `sequence_no` INT(11) UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `relative_path` VARCHAR(255) NOT NULL,
  `xml_filename` VARCHAR(255) NOT NULL DEFAULT '',
  `xml_relative_path` VARCHAR(255) NOT NULL DEFAULT '',
  `status` VARCHAR(20) NOT NULL,
  `product_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `checksum_sha256` CHAR(64) NOT NULL DEFAULT '',
  `xml_checksum_sha256` CHAR(64) NOT NULL DEFAULT '',
  `error_message` TEXT NULL,
  `created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `published_at` DATETIME NULL,
  `date_added` DATETIME NOT NULL,
  PRIMARY KEY (`publication_id`),
  UNIQUE KEY `store_location_sequence` (`store_id`, `location_code`, `sequence_no`),
  KEY `batch_key` (`batch_key`),
  KEY `status_published` (`status`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Upgrade ranijih instalacija koje imaju samo CSV stupce.
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND column_name = 'batch_key') = 0,
  "ALTER TABLE `oc_anchor_price_publication` ADD `batch_key` CHAR(32) NOT NULL DEFAULT '' AFTER `store_id`", "SELECT 1");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND column_name = 'xml_filename') = 0,
  "ALTER TABLE `oc_anchor_price_publication` ADD `xml_filename` VARCHAR(255) NOT NULL DEFAULT '' AFTER `relative_path`", "SELECT 1");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND column_name = 'xml_relative_path') = 0,
  "ALTER TABLE `oc_anchor_price_publication` ADD `xml_relative_path` VARCHAR(255) NOT NULL DEFAULT '' AFTER `xml_filename`", "SELECT 1");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND column_name = 'xml_checksum_sha256') = 0,
  "ALTER TABLE `oc_anchor_price_publication` ADD `xml_checksum_sha256` CHAR(64) NOT NULL DEFAULT '' AFTER `checksum_sha256`", "SELECT 1");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- Normalizacija legacy sheme koju aplikacijski installer također provodi.
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND column_name = 'batch_key' AND column_type = 'char(32)' AND is_nullable = 'NO' AND COALESCE(column_default, '') = '') = 1,
  "SELECT 1", "ALTER TABLE `oc_anchor_price_publication` MODIFY `batch_key` CHAR(32) NOT NULL DEFAULT ''");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @valid_index := (SELECT IF(COUNT(*) = 1 AND SUM(column_name = 'batch_key' AND seq_in_index = 1 AND non_unique = 1) = 1, 1, 0) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND index_name = 'batch_key');
SET @q := IF(@valid_index = 1, "SELECT 1", IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND index_name = 'batch_key') > 0, "ALTER TABLE `oc_anchor_price_publication` DROP INDEX `batch_key`", "SELECT 1"));
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q := IF(@valid_index = 1, "SELECT 1", "ALTER TABLE `oc_anchor_price_publication` ADD KEY `batch_key` (`batch_key`)");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @valid_index := (SELECT IF(COUNT(*) = 3 AND SUM(column_name = 'store_id' AND seq_in_index = 1) = 1 AND SUM(column_name = 'location_code' AND seq_in_index = 2) = 1 AND SUM(column_name = 'sequence_no' AND seq_in_index = 3) = 1 AND SUM(non_unique = 0) = 3, 1, 0) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND index_name = 'store_location_sequence');
SET @q := IF(@valid_index = 1, "SELECT 1", IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND index_name = 'store_location_sequence') > 0, "ALTER TABLE `oc_anchor_price_publication` DROP INDEX `store_location_sequence`", "SELECT 1"));
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q := IF(@valid_index = 1, "SELECT 1", "ALTER TABLE `oc_anchor_price_publication` ADD UNIQUE KEY `store_location_sequence` (`store_id`, `location_code`, `sequence_no`)");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @valid_index := (SELECT IF(COUNT(*) = 2 AND SUM(column_name = 'status' AND seq_in_index = 1) = 1 AND SUM(column_name = 'published_at' AND seq_in_index = 2) = 1 AND SUM(non_unique = 1) = 2, 1, 0) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND index_name = 'status_published');
SET @q := IF(@valid_index = 1, "SELECT 1", IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND index_name = 'status_published') > 0, "ALTER TABLE `oc_anchor_price_publication` DROP INDEX `status_published`", "SELECT 1"));
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q := IF(@valid_index = 1, "SELECT 1", "ALTER TABLE `oc_anchor_price_publication` ADD KEY `status_published` (`status`, `published_at`)");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q := IF((SELECT UPPER(engine) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price') = 'INNODB', "SELECT 1", "ALTER TABLE `oc_anchor_price` ENGINE=InnoDB");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q := IF((SELECT UPPER(engine) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_audit') = 'INNODB', "SELECT 1", "ALTER TABLE `oc_anchor_price_audit` ENGINE=InnoDB");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q := IF((SELECT UPPER(engine) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication') = 'INNODB', "SELECT 1", "ALTER TABLE `oc_anchor_price_publication` ENGINE=InnoDB");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

START TRANSACTION;

INSERT INTO `oc_extension` (`type`, `code`)
SELECT 'module', 'anchor_price' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'module' AND `code` = 'anchor_price');

INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'module_anchor_price', 'module_anchor_price_status', '1', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'module_anchor_price' AND `key` = 'module_anchor_price_status');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'module_anchor_price', 'module_anchor_price_reference_date', DATE_FORMAT(CURDATE(), '%Y-%m-%d'), 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'module_anchor_price' AND `key` = 'module_anchor_price_reference_date');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'module_anchor_price', 'module_anchor_price_default_unit', 'kom', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'module_anchor_price' AND `key` = 'module_anchor_price_default_unit');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'module_anchor_price', 'module_anchor_price_cron_key', SHA2(CONCAT(UUID(), UUID(), RAND(), NOW()), 256), 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'module_anchor_price' AND `key` = 'module_anchor_price_cron_key');

UPDATE `oc_setting` SET `value` = '1', `serialized` = 0
WHERE `store_id` = 0 AND `code` = 'module_anchor_price' AND `key` = 'module_anchor_price_status';

-- phpMyAdmin deployment bypasses ControllerExtensionModuleAnchorPrice::install().
-- Grant this route only to administrator-capable groups and append it to the
-- existing JSON arrays so no current permissions are replaced or removed.
UPDATE `oc_user_group`
SET `permission` = JSON_SET(
  `permission`,
  '$.access',
  CASE
    WHEN JSON_TYPE(JSON_EXTRACT(`permission`, '$.access')) = 'ARRAY'
      THEN JSON_ARRAY_APPEND(JSON_EXTRACT(`permission`, '$.access'), '$', 'extension/module/anchor_price')
    ELSE JSON_ARRAY('extension/module/anchor_price')
  END
)
WHERE JSON_VALID(`permission`)
  AND JSON_SEARCH(JSON_EXTRACT(`permission`, '$.access'), 'one', 'extension/module/anchor_price') IS NULL
  AND (
    `name` = 'Administrator'
    OR JSON_SEARCH(JSON_EXTRACT(`permission`, '$.access'), 'one', 'user/user_permission') IS NOT NULL
  );

UPDATE `oc_user_group`
SET `permission` = JSON_SET(
  `permission`,
  '$.modify',
  CASE
    WHEN JSON_TYPE(JSON_EXTRACT(`permission`, '$.modify')) = 'ARRAY'
      THEN JSON_ARRAY_APPEND(JSON_EXTRACT(`permission`, '$.modify'), '$', 'extension/module/anchor_price')
    ELSE JSON_ARRAY('extension/module/anchor_price')
  END
)
WHERE JSON_VALID(`permission`)
  AND JSON_SEARCH(JSON_EXTRACT(`permission`, '$.modify'), 'one', 'extension/module/anchor_price') IS NULL
  AND (
    `name` = 'Administrator'
    OR JSON_SEARCH(JSON_EXTRACT(`permission`, '$.access'), 'one', 'user/user_permission') IS NOT NULL
  );

DELETE FROM `oc_event` WHERE `code` = 'anchor_price';
INSERT INTO `oc_event` (`code`, `trigger`, `action`, `status`, `sort_order`) VALUES
('anchor_price', 'admin/model/catalog/product/addProduct/after', 'extension/module/anchor_price/captureProduct', 1, 0),
('anchor_price', 'admin/model/catalog/product/editProduct/after', 'extension/module/anchor_price/captureProduct', 1, 0),
('anchor_price', 'admin/model/extension/module/product_quick_edit/quickEditProduct/after', 'extension/module/anchor_price/captureQuickEdit', 1, 0),
('anchor_price', 'catalog/model/module/oc_model/addProduct/after', 'extension/module/anchor_price/captureProduct', 1, 0),
('anchor_price', 'catalog/model/module/oc_model/editProduct/after', 'extension/module/anchor_price/captureProduct', 1, 0),
('anchor_price', 'catalog/view/*/before', 'extension/module/anchor_price/beforeView', 1, 0);

COMMIT;

-- Sigurni fallback backfill: svi automatski SQL snapshoti ostaju PENDING jer
-- phpMyAdmin ne koristi OpenCart porezni kalkulator. Potvrdite ih kroz admin
-- ili ih zamijenite provjerenim masovnim CSV uvozom prije prve objave cjenika.
SET @anchor_reference_date := COALESCE((SELECT `value` FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'module_anchor_price' AND `key` = 'module_anchor_price_reference_date' LIMIT 1), CURDATE());

INSERT INTO `oc_anchor_price` (`product_id`, `store_id`, `price`, `gross_price`, `currency_code`, `tax_class_id`, `tax_context`, `reference_date`, `rule_code`, `source`, `verification_status`, `created_by`, `date_added`, `date_modified`)
SELECT p.`product_id`, 0, p.`price`, p.`price`, 'EUR', p.`tax_class_id`, '{"source":"phpmyadmin_fallback","requires_review":true}',
  CASE WHEN DATE(p.`date_added`) > @anchor_reference_date THEN LEAST(DATE(p.`date_added`), CURDATE()) ELSE @anchor_reference_date END,
  CASE WHEN DATE(p.`date_added`) > @anchor_reference_date THEN 'first_listing' ELSE 'baseline_configured' END,
  'phpmyadmin_fallback', 'pending', 0, NOW(), NOW()
FROM `oc_product` p
INNER JOIN `oc_product_to_store` p2s ON p2s.`product_id` = p.`product_id` AND p2s.`store_id` = 0
LEFT JOIN `oc_anchor_price` ap ON ap.`product_id` = p.`product_id` AND ap.`store_id` = 0
WHERE ap.`anchor_price_id` IS NULL AND p.`status` = 1 AND p.`date_available` <= CURDATE();

INSERT INTO `oc_anchor_price_audit` (`anchor_price_id`, `product_id`, `store_id`, `user_id`, `action`, `old_data`, `new_data`, `reason`, `date_added`)
SELECT ap.`anchor_price_id`, ap.`product_id`, ap.`store_id`, 0, 'create', '{}',
  CONCAT('{"price":"', ap.`price`, '","gross_price":"', ap.`gross_price`, '","reference_date":"', ap.`reference_date`, '","source":"phpmyadmin_fallback","verification_status":"pending"}'),
  'Idempotent phpMyAdmin fallback; requires tax and date review', NOW()
FROM `oc_anchor_price` ap
LEFT JOIN `oc_anchor_price_audit` aa ON aa.`anchor_price_id` = ap.`anchor_price_id` AND aa.`action` = 'create'
WHERE ap.`source` = 'phpmyadmin_fallback' AND aa.`audit_id` IS NULL;

-- Brza provjera nakon uvoza.
SELECT
  (SELECT COUNT(*) FROM `oc_anchor_price`) AS `anchor_rows`,
  (SELECT COUNT(*) FROM `oc_anchor_price` WHERE `verification_status` = 'pending') AS `pending_review`,
  (SELECT COUNT(*) FROM `oc_event` WHERE `code` = 'anchor_price' AND `status` = 1) AS `active_events`,
  (SELECT `value` FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'module_anchor_price' AND `key` = 'module_anchor_price_reference_date' LIMIT 1) AS `reference_date`;
