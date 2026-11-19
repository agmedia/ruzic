<?php
require_once __DIR__ . '/price_list_format.php';
require_once __DIR__ . '/special_price_history.php';

// Both the admin events and the catalog cron publish the same public store prices.
trait AnchorPricePublication {
	private $public_tax;
	private $publication_language_id;
	private $default_customer_group_id;

	private function publishCurrent($store_id, $created_by, $force) {
		$lock = 'anchor_price_publication_' . (int)$store_id;
		$this->acquirePublicationLock($lock);
		try {
			$this->discardUnpublishedPublications($store_id);
			$this->syncPublicationProducts($created_by);
			$tax = $this->getPublicTax();
			$rows = array();
			$products = $this->getPublicationProducts($store_id);
			$pending = array();
			$group = $this->getDefaultCustomerGroupId();
			$this->observeOffers($products, $store_id, $group, $tax, $pending);
			foreach ($this->db->query("SELECT customer_group_id FROM `" . DB_PREFIX . "customer_group` WHERE customer_group_id <> " . (int)$group)->rows as $other) {
				$other_group = (int)$other['customer_group_id'];
				$other_products = $this->getPublicationProducts($store_id, $other_group);
				$this->observeOffers($other_products, $store_id, $other_group, $this->getPublicTax($other_group), $pending);
			}
			foreach ($products as $product) {
				$rows[] = PriceListFormat::productRow($product, $tax);
			}
			$csv = PriceListFormat::csv($rows);
			$oib = PriceListFormat::oib($this->config->get('module_anchor_price_oib'));
			$now = new DateTime('now', new DateTimeZone('Europe/Zagreb'));
			$location = self::PUBLICATION_LOCATION_CODE;
			$correction_filter = $this->archiveCorrectionColumnsExist() ? ' AND corrects_publication_id IS NULL' : '';
			$existing = $this->db->query("SELECT * FROM `" . DB_PREFIX . "anchor_price_publication` WHERE store_id = '" . (int)$store_id . "' AND location_code = '" . $location . "' AND status = 'published'" . $correction_filter . " ORDER BY sequence_no DESC LIMIT 1")->row;
			if (!$force && $existing && $this->matchesPublication($existing, $rows, $csv, $oib)) {
				$result = $this->publicationResult($existing, true);
			} else {
				$xml = PriceListFormat::xml($rows, $oib, $now);
				$batch_key = $this->createBatchKey();
				$publication = $this->reservePublication($store_id, $location, $created_by, $now, $batch_key);
				try {
					$this->stageFiles($publication, $csv, $xml, count($rows));
					$result = $this->publishPublication($store_id, $publication, $now, $batch_key);
				} catch (Exception $exception) {
					$this->invalidatePublication($publication['publication_id'], $exception->getMessage());
					throw $exception;
				}
			}
			$this->expireReplacedPublications($store_id, $now);
			$this->setPublicationError(implode("\n", $pending));
			return $result;
		} catch (Exception $exception) {
			$this->setPublicationError($exception->getMessage());
			$this->log->write('Digitalni cjenik: ' . $exception->getMessage());
			throw $exception;
		} finally {
			$this->releasePublicationLock($lock);
		}
	}

	private function matchesPublication(array $publication, array $rows, $csv, $oib) {
		if (!PriceListFormat::isCurrentFormat($publication['filename']) || empty($publication['published_at'])
			|| !preg_match('/^[a-f0-9]{32}$/D', $publication['batch_key'])
			|| (int)$publication['product_count'] !== count($rows)
			|| $publication['checksum_sha256'] !== hash('sha256', $csv)) {
			return false;
		}
		$published = new DateTime($publication['published_at'], new DateTimeZone('Europe/Zagreb'));
		$xml = PriceListFormat::xml($rows, $oib, $published);
		return $publication['xml_checksum_sha256'] === hash('sha256', $xml) && $this->publicationFilesAreValid($publication);
	}

	private function setPublicationError($message) {
		$path = DIR_LOGS . 'price_list_error.log';
		if ($message === '') {
			if (is_file($path)) { unlink($path); }
		} else {
			file_put_contents($path, $message, LOCK_EX);
		}
	}

	private function stageFiles(array $publication, $csv, $xml, $count) {
		$directory = rtrim(DIR_DOWNLOAD, '/\\') . DIRECTORY_SEPARATOR . 'anchor_price';
		if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
			throw new RuntimeException('Nije moguće pripremiti mapu za objavu.');
		}
		foreach (array('filename' => $csv, 'xml_filename' => $xml) as $field => $content) {
			$path = $directory . DIRECTORY_SEPARATOR . $publication[$field];
			$temp = tempnam($directory, '.cjenik-');
			if ($temp === false) { throw new RuntimeException('Nije moguće otvoriti privremenu datoteku cjenika.'); }
			try {
				if (file_put_contents($temp, $content) !== strlen($content) || !rename($temp, $path)) {
					throw new RuntimeException('Nije moguće dovršiti zapis cjenika.');
				}
				@chmod($path, 0640);
			} finally {
				if (is_file($temp)) { unlink($temp); }
			}
		}
		$this->db->query("UPDATE `" . DB_PREFIX . "anchor_price_publication` SET status = 'staged', product_count = '" . (int)$count . "', checksum_sha256 = '" . hash('sha256', $csv) . "', xml_checksum_sha256 = '" . hash('sha256', $xml) . "' WHERE publication_id = '" . (int)$publication['publication_id'] . "' AND status = 'generating'");
	}

	// A snapshot is retained if it was current at any time in the last 15 days.
	// A late correction belongs to the original snapshot's sequence position.
	private function retainedFromId($store_id, DateTime $now) {
		$cutoff = clone $now;
		$cutoff->modify('-15 days');
		$corrected = $this->archiveCorrectionColumnsExist();
		$id = $this->archiveIdExpression();
		$time = $corrected ? 'COALESCE(source_published_at, published_at)' : 'published_at';
		$query = $this->db->query("SELECT MAX(" . $id . ") AS oldest FROM `" . DB_PREFIX . "anchor_price_publication` WHERE store_id = '" . (int)$store_id . "' AND location_code = '" . self::PUBLICATION_LOCATION_CODE . "' AND status = 'published' AND " . $time . " < '" . $this->db->escape($cutoff->format('Y-m-d H:i:s')) . "'");
		return (int)$query->row['oldest'];
	}

	private function archiveIdExpression() {
		return $this->archiveCorrectionColumnsExist() ? 'COALESCE(corrects_publication_id, publication_id)' : 'publication_id';
	}

	private function expireReplacedPublications($store_id, DateTime $now) {
		$oldest = $this->retainedFromId($store_id, $now);
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "anchor_price_publication` WHERE store_id = '" . (int)$store_id . "' AND location_code = '" . self::PUBLICATION_LOCATION_CODE . "' AND status = 'published' AND " . $this->archiveIdExpression() . " < " . $oldest);
		foreach ($query->rows as $row) {
			$this->db->query("UPDATE `" . DB_PREFIX . "anchor_price_publication` SET status = 'expired' WHERE publication_id = '" . (int)$row['publication_id'] . "'");
			foreach (array('csv', 'xml') as $format) {
				$path = $this->getPublicationPath($row, $format);
				if ($path && is_file($path)) { unlink($path); }
			}
		}
	}

	private function publicationRecord($id) {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "anchor_price_publication` WHERE publication_id = '" . (int)$id . "' LIMIT 1")->row;
	}

	private function publishPublication($store_id, array $publication, DateTime $now, $batch_key) {
		if (empty($publication['publication_id'])) {
			throw new Exception('Cjenik nije spreman za objavu.');
		}

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "anchor_price_publication` WHERE store_id = '" . (int)$store_id . "' AND batch_key = '" . $this->db->escape($batch_key) . "' AND location_code = '" . self::PUBLICATION_LOCATION_CODE . "' AND status = 'staged'");
		if ($query->num_rows !== 1) {
			throw new Exception('Cjenik nije potpun.');
		}

		$publication = $query->row;
		if (!$this->publicationFilesAreValid($publication)) {
			throw new Exception('Kontrolni zbroj pripremljenog cjenika nije valjan.');
		}
		$publication_id = (int)$publication['publication_id'];

		$this->db->query('START TRANSACTION');
		try {
			$this->db->query("UPDATE `" . DB_PREFIX . "anchor_price_publication` SET status = 'published', published_at = '" . $this->db->escape($now->format('Y-m-d H:i:s')) . "' WHERE publication_id = '" . $publication_id . "' AND batch_key = '" . $this->db->escape($batch_key) . "' AND status = 'staged'");
			if ($this->db->countAffected() !== 1) {
				throw new Exception('Objava cjenika nije dovršena.');
			}
			$this->db->query('COMMIT');
		} catch (Exception $exception) {
			$this->db->query('ROLLBACK');
			throw $exception;
		}

		return $this->publicationResult($this->publicationRecord($publication_id), false);
	}

	private function discardUnpublishedPublications($store_id) {
		$query = $this->db->query("SELECT publication_id FROM `" . DB_PREFIX . "anchor_price_publication` WHERE store_id = '" . (int)$store_id . "' AND status IN ('generating', 'staged')");
		foreach ($query->rows as $publication) {
			$this->invalidatePublication((int)$publication['publication_id'], 'Incomplete publication preparation removed before retry.');
		}
	}

	private function createBatchKey() {
		return md5(uniqid((string)mt_rand(), true));
	}

	private function reservePublication($store_id, $location_code, $created_by, DateTime $now, $batch_key) {
		$query = $this->db->query("SELECT COALESCE(MAX(sequence_no), 0) + 1 AS sequence_no FROM `" . DB_PREFIX . "anchor_price_publication` WHERE store_id = '" . (int)$store_id . "' AND location_code = '" . $this->db->escape($location_code) . "'");
		$sequence_no = (int)$query->row['sequence_no'];
		$base_filename = PriceListFormat::filename($this->config->get('module_anchor_price_oib'), $sequence_no, $now);
		$filename = $base_filename . '.csv';
		$xml_filename = $base_filename . '.xml';
		$relative_path = 'anchor_price/' . $filename;
		$xml_relative_path = 'anchor_price/' . $xml_filename;
		$this->db->query("INSERT INTO `" . DB_PREFIX . "anchor_price_publication` SET store_id = '" . (int)$store_id . "', batch_key = '" . $this->db->escape($batch_key) . "', location_code = '" . $this->db->escape($location_code) . "', sequence_no = '" . $sequence_no . "', filename = '" . $this->db->escape($filename) . "', relative_path = '" . $this->db->escape($relative_path) . "', xml_filename = '" . $this->db->escape($xml_filename) . "', xml_relative_path = '" . $this->db->escape($xml_relative_path) . "', status = 'generating', product_count = '0', checksum_sha256 = '', xml_checksum_sha256 = '', created_by = '" . (int)$created_by . "', date_added = '" . $this->db->escape($now->format('Y-m-d H:i:s')) . "'");
		$publication_id = (int)$this->db->getLastId();
		return array('publication_id' => $publication_id, 'filename' => $filename, 'xml_filename' => $xml_filename);
	}

	private function acquirePublicationLock($lock_name) {
		$query = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock_name) . "', 10) AS acquired");
		if (empty($query->row['acquired'])) {
			throw new Exception('Druga objava cjenika je u tijeku. Cron će ponoviti provjeru.');
		}
	}

	private function releasePublicationLock($lock_name) {
		$this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock_name) . "')");
	}

	private function publicationResult(array $publication, $existing) {
		return array(
			'publication_id' => (int)$publication['publication_id'],
			'batch_key' => isset($publication['batch_key']) ? $publication['batch_key'] : '',
			'location_code' => $publication['location_code'],
			'filename' => $publication['filename'],
			'relative_path' => $publication['relative_path'],
			'xml_filename' => isset($publication['xml_filename']) ? $publication['xml_filename'] : '',
			'xml_relative_path' => isset($publication['xml_relative_path']) ? $publication['xml_relative_path'] : '',
			'product_count' => (int)$publication['product_count'],
			'checksum_sha256' => $publication['checksum_sha256'],
			'xml_checksum_sha256' => isset($publication['xml_checksum_sha256']) ? $publication['xml_checksum_sha256'] : '',
			'existing' => (bool)$existing
		);
	}

	private function invalidatePublication($publication_id, $reason) {
		$publication = $this->publicationRecord((int)$publication_id);
		if ($publication) {
			foreach (array('csv', 'xml') as $format) {
				$path = $this->getPublicationPath($publication, $format);
				if ($path) {
					@unlink($path);
				}
			}
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "anchor_price_publication` SET status = 'failed', error_message = '" . $this->db->escape(substr($reason, 0, 2000)) . "', published_at = NULL WHERE publication_id = '" . (int)$publication_id . "'");
	}

	private function observeOffers(array &$products, $store, $group, $tax, array &$pending) {
		$history = new SpecialPriceHistory($store, $group);
		$offers = array();
		foreach ($products as $p) {
			$regular = $p['discount'] !== null ? $p['discount'] : $p['price'];
			$key = $p['special'] === null ? null : SpecialPriceHistory::campaign($p['special'], $p['special_start']);
			$offers[$p['product_id']] = array('key' => $key,
				'regular' => number_format($tax->calculate($regular, $p['tax_class_id'], true), 2, '.', ''),
				'price' => number_format($tax->calculate($key === null ? $regular : $p['special'], $p['tax_class_id'], true), 2, '.', ''));
		}
		if ($history->observe($offers, time()) && $this->cache) { $this->cache->delete('product'); }
		foreach ($products as &$p) {
			$key = $offers[$p['product_id']]['key'];
			if ($key !== null && $history->activeReference($p['product_id'], $key) === null) {
				$pending[] = $p['model'] . ' (#' . $p['product_id'] . ') — akcija čeka ispravnu najnižu cijenu za 30 dana prije početka. Provjerite karticu Akcija u uređivanju artikla.';
				$p['special'] = null;
			}
		}
	}

	private function sellingPriceColumns($customer_group_id, $public_only = false) {
		$customer_group_id = (int)$customer_group_id;
		$table = $public_only ? SpecialPriceHistory::table($this->config) : DB_PREFIX . 'product_special';
		$special = " FROM " . $table . " ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . $customer_group_id . "' AND (ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()) ORDER BY ps.priority ASC, ps.price ASC, ps.date_start ASC LIMIT 1";
		return "(SELECT pdsc.price FROM `" . DB_PREFIX . "product_discount` pdsc WHERE pdsc.product_id = p.product_id AND pdsc.customer_group_id = '" . $customer_group_id . "' AND pdsc.quantity = '1' AND ((pdsc.date_start = '0000-00-00' OR pdsc.date_start < NOW()) AND (pdsc.date_end = '0000-00-00' OR pdsc.date_end > NOW())) ORDER BY pdsc.priority ASC, pdsc.price ASC LIMIT 1) AS discount,
			(SELECT ps.price" . $special . ") AS special, (SELECT ps.date_start" . $special . ") AS special_start";
	}

	private function getPublicationProducts($store_id, $customer_group_id = null) {
		$language_id = $this->getPublicationLanguageId();
		if ($customer_group_id === null) { $customer_group_id = $this->getDefaultCustomerGroupId(); }
		$sql = "SELECT p.product_id, p.model, p.sku, p.upc, p.ean, p.jan, p.isbn, p.price, p.tax_class_id, pd.name, COALESCE(m.name, '') AS manufacturer, ap.unit, ap.package_quantity, ap.verification_status, ap.gross_price AS anchor_gross_price, " . $this->sellingPriceColumns($customer_group_id) . " FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.product_id = p.product_id AND p2s.store_id = '" . (int)$store_id . "') LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "') LEFT JOIN `" . DB_PREFIX . "anchor_price` ap ON (ap.product_id = p.product_id AND ap.store_id = p2s.store_id) LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON (m.manufacturer_id = p.manufacturer_id) WHERE p.status = '1' AND p.date_available <= NOW() ORDER BY p.product_id ASC";

		return $this->db->query($sql)->rows;
	}

	private function getPublicationLanguageId() {
		if ($this->publication_language_id !== null) {
			return $this->publication_language_id;
		}

		$language_code = (string)$this->getStoreSettingValue('config_language');
		if ($language_code === '') {
			throw new Exception('Default store language is not configured.');
		}
		$query = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language` WHERE code = '" . $this->db->escape($language_code) . "' LIMIT 1");
		if (!$query->num_rows) {
			throw new Exception('Default store language is not available.');
		}
		$this->publication_language_id = (int)$query->row['language_id'];

		return $this->publication_language_id;
	}

	private function getDefaultCustomerGroupId() {
		if ($this->default_customer_group_id !== null) {
			return $this->default_customer_group_id;
		}

		$this->default_customer_group_id = (int)$this->getStoreSettingValue('config_customer_group_id');
		if ($this->default_customer_group_id < 1) {
			throw new Exception('Default customer group is not configured.');
		}

		return $this->default_customer_group_id;
	}

	private function getStoreSettingValue($key) {
		$store_id = (int)$this->config->get('config_store_id');
		$query = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . $store_id . "' AND `key` = '" . $this->db->escape($key) . "' ORDER BY setting_id DESC LIMIT 1");

		if (!$query->num_rows && $store_id !== 0) {
			$query = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = '" . $this->db->escape($key) . "' ORDER BY setting_id DESC LIMIT 1");
		}

		return $query->num_rows ? $query->row['value'] : '';
	}

	private function getPublicTax($group = null) {
		if ($group === null && $this->public_tax !== null) {
			return $this->public_tax;
		}

		$session_customer_group_id = $this->config->get('config_customer_group_id');
		$this->config->set('config_customer_group_id', $group === null ? $this->getDefaultCustomerGroupId() : $group);

		try {
			$tax = new Cart\Tax($this->registry);
			$country_id = (int)$this->config->get('config_country_id');
			$zone_id = (int)$this->config->get('config_zone_id');

			if ($this->config->get('config_tax_default') === 'shipping') {
				$tax->setShippingAddress($country_id, $zone_id);
			} elseif ($this->config->get('config_tax_default') === 'payment') {
				$tax->setPaymentAddress($country_id, $zone_id);
			}

			$tax->setStoreAddress($country_id, $zone_id);
			if ($group === null) { $this->public_tax = $tax; }
			return $tax;
		} finally {
			$this->config->set('config_customer_group_id', $session_customer_group_id);
		}

	}
}
