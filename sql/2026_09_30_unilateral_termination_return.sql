-- OPG Ružić: jednostrani raskid ugovora i povrat (OpenCart 3.0.3.8)
-- Idempotentan paket za phpMyAdmin. Paket pretpostavlja produkcijski prefiks "oc_".
-- Prije izvršavanja napravite sigurnosnu kopiju baze.

SET @database_name := DATABASE();
SET @previous_sql_mode := @@SESSION.sql_mode;
-- Legacy OpenCart tablice često sadrže 0000-00-00 datume. Privremeno
-- olabavite mode samo za ovu vezu kako bi MySQL 8 dopustio ALTER TABLE.
SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET @statement := IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'oc_return' AND COLUMN_NAME = 'invoice_number'),
  'SELECT ''oc_return.invoice_number already exists''',
  'ALTER TABLE `oc_return` ADD `invoice_number` VARCHAR(64) NOT NULL DEFAULT '''' AFTER `order_id`'
);
PREPARE opg_statement FROM @statement;
EXECUTE opg_statement;
DEALLOCATE PREPARE opg_statement;

SET @statement := IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'oc_return' AND COLUMN_NAME = 'invoice_date'),
  'SELECT ''oc_return.invoice_date already exists''',
  'ALTER TABLE `oc_return` ADD `invoice_date` DATE NULL AFTER `invoice_number`'
);
PREPARE opg_statement FROM @statement;
EXECUTE opg_statement;
DEALLOCATE PREPARE opg_statement;

SET @statement := IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'oc_return' AND COLUMN_NAME = 'refund_iban'),
  'SELECT ''oc_return.refund_iban already exists''',
  'ALTER TABLE `oc_return` ADD `refund_iban` VARCHAR(64) NOT NULL DEFAULT '''' AFTER `telephone`'
);
PREPARE opg_statement FROM @statement;
EXECUTE opg_statement;
DEALLOCATE PREPARE opg_statement;

SET @statement := IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'oc_return' AND COLUMN_NAME = 'return_items'),
  'SELECT ''oc_return.return_items already exists''',
  'ALTER TABLE `oc_return` ADD `return_items` TEXT NULL AFTER `quantity`'
);
PREPARE opg_statement FROM @statement;
EXECUTE opg_statement;
DEALLOCATE PREPARE opg_statement;

-- SEO adrese za aktivne HR i EN jezike. Ne prepisuju se ključne riječi koje već pripadaju drugoj ruti.
UPDATE `oc_seo_url` AS current_url
INNER JOIN `oc_language` AS language_row ON language_row.language_id = current_url.language_id
LEFT JOIN `oc_seo_url` AS conflict_url
  ON conflict_url.store_id = current_url.store_id
  AND conflict_url.language_id = current_url.language_id
  AND conflict_url.keyword = CASE
    WHEN LOWER(language_row.code) = 'hr-hr' THEN 'jednostrani-raskid-i-povrat'
    ELSE 'contract-termination-and-return'
  END
  AND conflict_url.query <> 'account/return/add'
SET current_url.keyword = CASE
  WHEN LOWER(language_row.code) = 'hr-hr' THEN 'jednostrani-raskid-i-povrat'
  ELSE 'contract-termination-and-return'
END
WHERE current_url.store_id = 0
  AND current_url.query = 'account/return/add'
  AND LOWER(language_row.code) IN ('hr-hr', 'en-gb')
  AND conflict_url.seo_url_id IS NULL;

INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`)
SELECT
  0,
  language_row.language_id,
  'account/return/add',
  CASE
    WHEN LOWER(language_row.code) = 'hr-hr' THEN 'jednostrani-raskid-i-povrat'
    ELSE 'contract-termination-and-return'
  END
FROM `oc_language` AS language_row
WHERE LOWER(language_row.code) IN ('hr-hr', 'en-gb')
  AND language_row.status = 1
  AND NOT EXISTS (
    SELECT 1 FROM `oc_seo_url` AS route_url
    WHERE route_url.store_id = 0
      AND route_url.language_id = language_row.language_id
      AND route_url.query = 'account/return/add'
  )
  AND NOT EXISTS (
    SELECT 1 FROM `oc_seo_url` AS keyword_url
    WHERE keyword_url.store_id = 0
      AND keyword_url.language_id = language_row.language_id
      AND keyword_url.keyword = CASE
        WHEN LOWER(language_row.code) = 'hr-hr' THEN 'jednostrani-raskid-i-povrat'
        ELSE 'contract-termination-and-return'
      END
  );

-- Završna provjera: očekuju se četiri retka stupaca te po jedan SEO redak za svaki aktivni HR/EN jezik.
SELECT language_row.code, seo_url.query, seo_url.keyword
FROM `oc_seo_url` AS seo_url
INNER JOIN `oc_language` AS language_row ON language_row.language_id = seo_url.language_id
WHERE seo_url.store_id = 0
  AND seo_url.query = 'account/return/add'
ORDER BY language_row.code;

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @database_name
  AND TABLE_NAME = 'oc_return'
  AND COLUMN_NAME IN ('invoice_number', 'invoice_date', 'refund_iban', 'return_items')
ORDER BY ORDINAL_POSITION;

SET SESSION sql_mode = @previous_sql_mode;
