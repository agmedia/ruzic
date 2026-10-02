-- OPG Ruzic: zasebne ispravljene verzije arhiviranih cjenika.
-- U phpMyAdminu odabrati OpenCart bazu s prefiksom oc_ i pokrenuti ovaj SQL
-- PRIJE povlacenja koda. Prethodno primijeniti 2026_10_02_anchor_price_units.sql.
-- Ne mijenja cijene, zalihe ni postojece datoteke. Ispravci se zatim pokrecu
-- kroz Katalog > Sidrene cijene > Ispravi postojece cjenike.
-- Izvorna objava ostaje sacuvana; po izvornoj objavi dopusten je jedan ispravak.

SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND column_name = 'corrects_publication_id') = 0,
  "ALTER TABLE `oc_anchor_price_publication` ADD `corrects_publication_id` INT UNSIGNED NULL DEFAULT NULL AFTER `publication_id`", "SELECT 1");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND column_name = 'source_published_at') = 0,
  "ALTER TABLE `oc_anchor_price_publication` ADD `source_published_at` DATETIME NULL DEFAULT NULL AFTER `published_at`", "SELECT 1");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q := IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'oc_anchor_price_publication' AND index_name = 'store_correction') = 0,
  "ALTER TABLE `oc_anchor_price_publication` ADD UNIQUE KEY `store_correction` (`store_id`, `corrects_publication_id`)", "SELECT 1");
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SELECT `publication_id`, `corrects_publication_id`, `source_published_at`, `filename`, `status`
FROM `oc_anchor_price_publication` ORDER BY `publication_id` DESC LIMIT 20;
