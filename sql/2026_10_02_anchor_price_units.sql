-- OPG Ruzic: nadogradnja jediničnih cijena i naziva datoteka.
-- Pokrenuti u odabranoj OpenCart bazi (prefiks oc_) PRIJE objave novog koda.
-- Ne prepisuje iznose ni postojeće arhivske datoteke.
-- Vlasnik je 2026-10-02 potvrdio da je svih šest dolje navedenih iznosa
-- bilo jednako na 2025-05-02. Samo ti početni potvrđeni zapisi dobivaju
-- ispravljen referentni datum uz revizijski trag; kasnije ručne izmjene ostaju.

SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price' AND column_name = 'unit') = 0,
  "ALTER TABLE `oc_anchor_price` ADD `unit` VARCHAR(16) NOT NULL DEFAULT '' AFTER `gross_price`", "SELECT 1");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price' AND column_name = 'package_quantity') = 0,
  "ALTER TABLE `oc_anchor_price` ADD `package_quantity` DECIMAL(15,6) NOT NULL DEFAULT 0 AFTER `unit`", "SELECT 1");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TEMPORARY TABLE `anchor_measure_seed` (
  `product_id` INT UNSIGNED PRIMARY KEY,
  `unit` VARCHAR(16) NOT NULL,
  `package_quantity` DECIMAL(15,6) NOT NULL,
  `confirmed_gross_price` DECIMAL(15,4) NOT NULL
);
INSERT INTO `anchor_measure_seed` VALUES
  (771, 'l', 3, 10), (773, 'kg', 5, 12), (775, 'kg', 5, 11),
  (803, 'kg', 5, 12), (804, 'l', 1, 4.5), (821, 'kg', 1, 2.5);

START TRANSACTION;
INSERT INTO `oc_anchor_price_audit`
  (`anchor_price_id`, `product_id`, `store_id`, `user_id`, `action`, `old_data`, `new_data`, `reason`, `date_added`)
SELECT ap.`anchor_price_id`, ap.`product_id`, ap.`store_id`, 0, 'measure_update',
  JSON_OBJECT('price', ap.`price`, 'gross_price', ap.`gross_price`, 'unit', ap.`unit`, 'package_quantity', ap.`package_quantity`, 'reference_date', ap.`reference_date`, 'verification_status', ap.`verification_status`),
  JSON_OBJECT('price', ap.`price`, 'gross_price', ap.`gross_price`, 'unit', seed.`unit`, 'package_quantity', seed.`package_quantity`, 'reference_date', ap.`reference_date`, 'verification_status', ap.`verification_status`),
  'Kolicina prodajnog pakiranja prema nazivu proizvoda; cijena i referentni datum nisu promijenjeni.', NOW()
FROM `oc_anchor_price` ap
INNER JOIN `anchor_measure_seed` seed ON seed.`product_id` = ap.`product_id`
WHERE ap.`store_id` = 0 AND ap.`unit` = '' AND ap.`package_quantity` = 0;

INSERT INTO `oc_anchor_price_audit`
  (`anchor_price_id`, `product_id`, `store_id`, `user_id`, `action`, `old_data`, `new_data`, `reason`, `date_added`)
SELECT ap.`anchor_price_id`, ap.`product_id`, ap.`store_id`, 0, 'baseline_correction',
  JSON_OBJECT('price', ap.`price`, 'gross_price', ap.`gross_price`, 'unit', ap.`unit`, 'package_quantity', ap.`package_quantity`, 'reference_date', ap.`reference_date`, 'rule_code', ap.`rule_code`, 'source', ap.`source`, 'verification_status', ap.`verification_status`),
  JSON_OBJECT('price', ap.`price`, 'gross_price', ap.`gross_price`, 'unit', ap.`unit`, 'package_quantity', ap.`package_quantity`, 'reference_date', '2025-05-02', 'rule_code', 'baseline_configured', 'source', 'verified_baseline_correction', 'verification_status', ap.`verification_status`),
  'Vlasnik je 2026-10-02 potvrdio iste redovne cijene na 2025-05-02; ispravak pocetnog referentnog datuma.', NOW()
FROM `oc_anchor_price` ap
INNER JOIN `anchor_measure_seed` seed ON seed.`product_id` = ap.`product_id`
WHERE ap.`store_id` = 0 AND ap.`reference_date` = '2026-09-30'
  AND ap.`verification_status` = 'confirmed' AND ap.`gross_price` = seed.`confirmed_gross_price`;

UPDATE `oc_anchor_price` ap
INNER JOIN `anchor_measure_seed` seed ON seed.`product_id` = ap.`product_id`
SET ap.`reference_date` = '2025-05-02', ap.`rule_code` = 'baseline_configured',
  ap.`source` = 'verified_baseline_correction', ap.`date_modified` = NOW()
WHERE ap.`store_id` = 0 AND ap.`reference_date` = '2026-09-30'
  AND ap.`verification_status` = 'confirmed' AND ap.`gross_price` = seed.`confirmed_gross_price`;

UPDATE `oc_anchor_price` ap
INNER JOIN `anchor_measure_seed` seed ON seed.`product_id` = ap.`product_id`
SET ap.`unit` = seed.`unit`, ap.`package_quantity` = seed.`package_quantity`, ap.`date_modified` = NOW()
WHERE ap.`store_id` = 0 AND ap.`unit` = '' AND ap.`package_quantity` = 0;

UPDATE `oc_setting` SET `value` = '2025-05-02', `serialized` = 0
WHERE `store_id` = 0 AND `code` = 'module_anchor_price' AND `key` = 'module_anchor_price_reference_date';
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'module_anchor_price', 'module_anchor_price_reference_date', '2025-05-02', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'module_anchor_price_reference_date');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'module_anchor_price', 'module_anchor_price_publication_type', 'webshop', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'module_anchor_price_publication_type');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'module_anchor_price', 'module_anchor_price_publication_code', 'WEB', 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'module_anchor_price_publication_code');
-- Adresa se čita iz config_address dok je ne unesete izričito u modulu.
COMMIT;
DROP TEMPORARY TABLE `anchor_measure_seed`;

SELECT ap.`product_id`, pd.`name`, ap.`unit`, ap.`package_quantity`,
  ROUND(ap.`gross_price` / NULLIF(ap.`package_quantity`, 0), 2) AS `anchor_unit_price`,
  ap.`reference_date`, ap.`verification_status`
FROM `oc_anchor_price` ap
LEFT JOIN `oc_product_description` pd ON pd.`product_id` = ap.`product_id`
WHERE ap.`store_id` = 0 ORDER BY ap.`product_id`, pd.`language_id`;
