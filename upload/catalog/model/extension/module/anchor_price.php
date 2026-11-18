<?php
require_once DIR_SYSTEM . 'library/anchor_price_publication.php';
class ModelExtensionModuleAnchorPrice extends Model {
	use AnchorPricePublication;

	const PUBLICATION_LOCATION_CODE = 'WEB';

	private $table_exists;
	private $audit_table_exists;
	private $archive_correction_columns_exist;

	public function tableExists() {
		if ($this->table_exists !== null) {
			return $this->table_exists;
		}

		$query = $this->db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $this->db->escape(DB_DATABASE) . "' AND TABLE_NAME = '" . $this->db->escape(DB_PREFIX . "anchor_price") . "' LIMIT 1");
		$this->table_exists = (bool)$query->num_rows;

		return $this->table_exists;
	}

	public function getByProductIds($product_ids, $store_id = null) {
		$records = array();

		if (!$this->config->get('module_anchor_price_status') || !$this->tableExists() || !is_array($product_ids)) {
			return $records;
		}

		$ids = array();

		foreach ($product_ids as $product_id) {
			$product_id = (int)$product_id;

			if ($product_id > 0) {
				$ids[$product_id] = $product_id;
			}
		}

		if (!$ids) {
			return $records;
		}

		if ($store_id === null) {
			$store_id = (int)$this->config->get('config_store_id');
		}

		$query = $this->db->query("SELECT ap.*, p.price AS selling_price, p.tax_class_id AS selling_tax_class, " . $this->sellingPriceColumns($this->config->get('config_customer_group_id')) . " FROM `" . DB_PREFIX . "anchor_price` ap JOIN `" . DB_PREFIX . "product` p ON p.product_id = ap.product_id WHERE ap.store_id = '" . (int)$store_id . "' AND ap.product_id IN (" . implode(',', $ids) . ")");

		foreach ($query->rows as $row) {
			$records[(int)$row['product_id']] = $row;
		}

		return $records;
	}

	public function getDisplayData($record, $package_price = null) {
		if (!$record || empty($record['reference_date'])) {
			return array();
		}

		$currency_code = !empty($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
		$current_unit_price = '';
		$has_measure = isset($record['unit'], $record['package_quantity']) && AnchorPriceUnit::isValid($record['unit'], $record['package_quantity']);
		if ($package_price === null && isset($record['selling_price'])) {
			$net = $record['special'] !== null ? $record['special'] : ($record['discount'] !== null ? $record['discount'] : $record['selling_price']);
			$package_price = $this->tax->calculate($net, $record['selling_tax_class'], $this->config->get('config_tax'));
		}
		if ($package_price !== null && $has_measure) {
			$current_unit_price = $this->currency->format(AnchorPriceUnit::calculate($package_price, $record['package_quantity']), $currency_code) . '/' . $record['unit'];
		}
		if ($record['verification_status'] !== 'confirmed') { return array('current_unit_price' => $current_unit_price); }
		$price = $this->currency->format((float)$record['gross_price'], $currency_code);
		$timestamp = strtotime($record['reference_date']);
		$language_code = (string)$this->config->get('config_language');

		if (strpos($language_code, 'hr') === 0 || strpos($language_code, 'croatia') === 0) {
			$date = date('j. n. Y.', $timestamp);
			$text = 'Bazna cijena na ' . $date . ': ' . $price;
		} else {
			$date = date('j M Y', $timestamp);
			$text = 'Base price on ' . $date . ': ' . $price;
		}
		$unit_price = '';
		if ($has_measure) {
			$unit_price = $this->currency->format(AnchorPriceUnit::calculate($record['gross_price'], $record['package_quantity']), $currency_code) . '/' . $record['unit'];
			$text .= ' (' . $unit_price . ')';
		}

		return array(
			'current_unit_price' => $current_unit_price,
			'anchor_price'       => $price,
			'anchor_price_value' => $price,
			'anchor_price_date'  => $date,
			'anchor_price_text'  => $text,
			'anchor_unit_price'  => $unit_price,
			'anchor_price_rule'  => $record['rule_code'],
			'anchor_price_status'=> $record['verification_status']
		);
	}

	private function syncPublicationProducts($created_by) {
		$this->syncMissingProducts('price_list_sync');
	}

	public function syncMissingProducts($source = 'cron_sync') {
		if (!$this->tableExists() || !$this->auditTableExists()) {
			return 0;
		}

		$currency_code = $this->config->get('config_currency');
		$store_id = (int)$this->config->get('config_store_id');
		$count = 0;
		$now = new DateTime('now', new DateTimeZone('Europe/Zagreb'));
		$observed_date = $now->format('Y-m-d');

		$query = $this->db->query("SELECT p.product_id, p.price, p.tax_class_id FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.product_id = p.product_id AND p2s.store_id = '" . $store_id . "') LEFT JOIN `" . DB_PREFIX . "anchor_price` ap ON (ap.product_id = p.product_id AND ap.store_id = p2s.store_id) WHERE ap.anchor_price_id IS NULL AND p.status = '1' AND p.date_available <= NOW()");

		$tax = $this->getPublicTax();

		foreach ($query->rows as $product) {
			// A missing active product has no trustworthy historical first-listing date.
			// Keep the observed value pending until an administrator verifies it.
			$anchor_date = $observed_date;
			$rule_code = 'first_listing';
			$gross_price = $tax->calculate((float)$product['price'], (int)$product['tax_class_id'], true);
			$tax_context = $tax->getRates((float)$product['price'], (int)$product['tax_class_id']);
			$tax_context_json = json_encode($tax_context);

			if ($tax_context_json === false) {
				$tax_context_json = '{}';
			}

			$this->db->query('START TRANSACTION');
			try {
				$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "anchor_price` SET product_id = '" . (int)$product['product_id'] . "', store_id = '" . $store_id . "', price = '" . (float)$product['price'] . "', gross_price = '" . (float)$gross_price . "', currency_code = '" . $this->db->escape($currency_code) . "', tax_class_id = '" . (int)$product['tax_class_id'] . "', tax_context = '" . $this->db->escape($tax_context_json) . "', reference_date = '" . $this->db->escape($anchor_date) . "', rule_code = '" . $this->db->escape($rule_code) . "', source = '" . $this->db->escape($source) . "', verification_status = 'pending', created_by = '0', date_added = NOW(), date_modified = NOW()");

				if ($this->db->countAffected()) {
					$anchor_price_id = (int)$this->db->getLastId();
					$this->addSnapshotAudit($anchor_price_id, $product, $store_id, $currency_code, $gross_price, $tax_context_json, $anchor_date, $rule_code, $source, 'pending');
					$count++;
				}
				$this->db->query('COMMIT');
			} catch (Exception $exception) {
				$this->db->query('ROLLBACK');
				throw $exception;
			}
		}

		return $count;
	}

	public function capturePublishedProduct($product_id, $source = 'import_edit_event') {
		if (!$this->tableExists() || !$this->auditTableExists()) {
			return false;
		}

		$product_id = (int)$product_id;
		$store_id = (int)$this->config->get('config_store_id');
		$source = $source === 'import_add_event' ? 'import_add_event' : 'import_edit_event';
		$now = new DateTime('now', new DateTimeZone('Europe/Zagreb'));
		$query = $this->db->query("SELECT p.product_id, p.price, p.tax_class_id FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.product_id = p.product_id AND p2s.store_id = '" . $store_id . "') LEFT JOIN `" . DB_PREFIX . "anchor_price` ap ON (ap.product_id = p.product_id AND ap.store_id = p2s.store_id) WHERE p.product_id = '" . $product_id . "' AND p.status = '1' AND p.date_available <= '" . $this->db->escape($now->format('Y-m-d')) . "' AND ap.anchor_price_id IS NULL LIMIT 1");

		if (!$query->num_rows) {
			return false;
		}

		$product = $query->row;
		$currency_code = (string)$this->config->get('config_currency');
		$tax = $this->getPublicTax();
		$gross_price = $tax->calculate((float)$product['price'], (int)$product['tax_class_id'], true);
		$tax_context = json_encode($tax->getRates((float)$product['price'], (int)$product['tax_class_id']));

		if ($tax_context === false) {
			$tax_context = '{}';
		}

		// Selling measure and historical/first-listing price need explicit review.
		$verification_status = 'pending';
		$reference_date = $now->format('Y-m-d');
		$this->db->query('START TRANSACTION');
		try {
			$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "anchor_price` SET product_id = '" . $product_id . "', store_id = '" . $store_id . "', price = '" . (float)$product['price'] . "', gross_price = '" . (float)$gross_price . "', currency_code = '" . $this->db->escape($currency_code) . "', tax_class_id = '" . (int)$product['tax_class_id'] . "', tax_context = '" . $this->db->escape($tax_context) . "', reference_date = '" . $this->db->escape($reference_date) . "', rule_code = 'first_listing', source = '" . $this->db->escape($source) . "', verification_status = '" . $verification_status . "', created_by = '0', date_added = NOW(), date_modified = NOW()");

			if (!$this->db->countAffected()) {
				$this->db->query('COMMIT');
				return false;
			}

			$this->addSnapshotAudit((int)$this->db->getLastId(), $product, $store_id, $currency_code, $gross_price, $tax_context, $reference_date, 'first_listing', $source, $verification_status);
			$this->db->query('COMMIT');
		} catch (Exception $exception) {
			$this->db->query('ROLLBACK');
			throw $exception;
		}

		return true;
	}

	private function addSnapshotAudit($anchor_price_id, array $product, $store_id, $currency_code, $gross_price, $tax_context, $reference_date, $rule_code, $source, $verification_status) {
		if (!$this->auditTableExists()) {
			return;
		}

		$new_data = json_encode(array(
			'price' => number_format((float)$product['price'], 4, '.', ''),
			'gross_price' => number_format((float)$gross_price, 4, '.', ''),
			'currency_code' => $currency_code,
			'tax_class_id' => (int)$product['tax_class_id'],
			'tax_context' => $tax_context,
			'reference_date' => $reference_date,
			'rule_code' => $rule_code,
			'source' => $source,
			'verification_status' => $verification_status
		));

		if ($new_data === false) {
			$new_data = '{}';
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "anchor_price_audit` SET anchor_price_id = '" . (int)$anchor_price_id . "', product_id = '" . (int)$product['product_id'] . "', store_id = '" . (int)$store_id . "', user_id = '0', action = 'create', old_data = '{}', new_data = '" . $this->db->escape($new_data) . "', reason = 'Automatic snapshot: " . $this->db->escape($source) . "', date_added = NOW()");
	}

	private function auditTableExists() {
		if ($this->audit_table_exists === null) {
			$query = $this->db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $this->db->escape(DB_DATABASE) . "' AND TABLE_NAME = '" . $this->db->escape(DB_PREFIX . "anchor_price_audit") . "' LIMIT 1");
			$this->audit_table_exists = (bool)$query->num_rows;
		}

		return $this->audit_table_exists;
	}

	public function generatePublication($force = false) {
		try {
			$location = self::PUBLICATION_LOCATION_CODE;
			$result = $this->publishCurrent((int)$this->config->get('config_store_id'), 0, $force);
			$publication = $this->publicationRecord($result['publication_id']);
			return array('success' => true, 'locations' => array($location => array('success' => true, 'existing' => $result['existing'], 'publication' => $publication)));
		} catch (Exception $exception) {
			return array('success' => false, 'error' => $exception->getMessage());
		}
	}

	private function getPublicationPath($row, $format) {
		return $this->publicationPath($row, $format);
	}

	public function getPublications() {
		if (!$this->publicationTableExists()) {
			return array();
		}

		$store_id = (int)$this->config->get('config_store_id');
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "anchor_price_publication` WHERE store_id = '" . $store_id . "' AND location_code = '" . self::PUBLICATION_LOCATION_CODE . "' AND status = 'published' AND " . $this->archiveIdExpression() . " >= " . $this->retainedFromId((int)$this->config->get('config_store_id'), new DateTime('now', new DateTimeZone('Europe/Zagreb'))) . " ORDER BY published_at DESC, publication_id DESC");
		$publications = array();
		foreach ($query->rows as $row) {
			if ($this->validPublicationMetadata($row, array(self::PUBLICATION_LOCATION_CODE)) && $this->publicationFilesExist($row)) {
				$publications[] = $row;
			}
		}
		return $publications;
	}

	public function getPublicationGroups() {
		$groups = array();

		foreach ($this->getPublications() as $publication) {
			$original_id = !empty($publication['corrects_publication_id']) ? (int)$publication['corrects_publication_id'] : (int)$publication['publication_id'];

			if (!isset($groups[$original_id])) {
				$groups[$original_id] = array('original' => false, 'correction' => false);
			}

			if (empty($publication['corrects_publication_id'])) {
				$groups[$original_id]['original'] = $publication;
				continue;
			}

			$existing = $groups[$original_id]['correction'];
			// Normally there is one correction; keep the choice deterministic if there are more.
			if (!$existing || strcmp($publication['published_at'], $existing['published_at']) > 0
				|| ($publication['published_at'] === $existing['published_at'] && (int)$publication['publication_id'] > (int)$existing['publication_id'])) {
				$groups[$original_id]['correction'] = $publication;
			}
		}

		$publications = array();
		foreach ($groups as $group) {
			$publication = $group['correction'] ? $group['correction'] : $group['original'];
			$publication['display_published_at'] = $group['original'] ? $group['original']['published_at']
				: (!empty($publication['source_published_at']) ? $publication['source_published_at'] : $publication['published_at']);
			// An expired or unavailable original must not produce a broken download link.
			$publication['original_publication'] = $group['correction'] ? $group['original'] : false;
			$publications[] = $publication;
		}

		usort($publications, function ($left, $right) {
			$comparison = strcmp($right['display_published_at'], $left['display_published_at']);
			$left_id = !empty($left['corrects_publication_id']) ? (int)$left['corrects_publication_id'] : (int)$left['publication_id'];
			$right_id = !empty($right['corrects_publication_id']) ? (int)$right['corrects_publication_id'] : (int)$right['publication_id'];
			return $comparison !== 0 ? $comparison : $right_id - $left_id;
		});

		return $publications;
	}

	public function getPublication($publication_id, $public_only = true) {
		if (!$this->publicationTableExists()) {
			return false;
		}

		$sql = "SELECT * FROM `" . DB_PREFIX . "anchor_price_publication` WHERE publication_id = '" . (int)$publication_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if ($public_only) {
			$sql .= " AND status = 'published' AND " . $this->archiveIdExpression() . " >= " . $this->retainedFromId((int)$this->config->get('config_store_id'), new DateTime('now', new DateTimeZone('Europe/Zagreb')));
		}

		$query = $this->db->query($sql . " LIMIT 1");
		if (!$query->num_rows || !$public_only) {
			return $query->num_rows ? $query->row : false;
		}

		$publication = $query->row;
		return $this->validPublicationMetadata($publication, array(self::PUBLICATION_LOCATION_CODE)) && $this->publicationFilesExist($publication) ? $publication : false;
	}

	public function getLatestPublication($format = 'csv') {
		foreach ($this->getPublicationGroups() as $publication) {
			if ($this->publicationFilesAreValid($publication)) { return $publication; }
			if ($publication['original_publication'] && $this->publicationFilesAreValid($publication['original_publication'])) {
				return $publication['original_publication'];
			}
		}
		return false;
	}

	private function validPublicationMetadata(array $row, array $allowed_location_codes) {
		$location_code = isset($row['location_code']) ? $row['location_code'] : '';
		$batch_key = isset($row['batch_key']) ? strtolower(trim((string)$row['batch_key'])) : '';

		return in_array($location_code, $allowed_location_codes, true)
			&& preg_match('/^[a-f0-9]{32}$/', $batch_key)
			&& !empty($row['published_at']);
	}

	private function publicationFilesExist(array $row) {
		foreach (array('csv', 'xml') as $format) {
			$path = $this->publicationPath($row, $format);

			if (!$path || !is_file($path) || !is_readable($path)) {
				return false;
			}
		}

		return true;
	}

	public function publicationPath($publication, $format = 'csv') {
		$format = strtolower((string)$format) === 'xml' ? 'xml' : 'csv';
		$field = $format === 'xml' ? 'xml_relative_path' : 'relative_path';
		if (!$publication || empty($publication[$field])) {
			return false;
		}

		$relative = str_replace('\\', '/', (string)$publication[$field]);

		if (strpos($relative, 'anchor_price/') !== 0 || strpos($relative, '..') !== false || substr($relative, -4) !== '.' . $format) {
			return false;
		}

		$base = rtrim(DIR_DOWNLOAD, '/\\') . DIRECTORY_SEPARATOR;
		$path = $base . str_replace('/', DIRECTORY_SEPARATOR, $relative);

		return $path;
	}

	public function publicationFileIsValid($publication, $path = false, $format = 'csv') {
		$format = strtolower((string)$format) === 'xml' ? 'xml' : 'csv';
		$checksum_field = $format === 'xml' ? 'xml_checksum_sha256' : 'checksum_sha256';
		if (!$publication || !isset($publication[$checksum_field])) {
			return false;
		}

		if ($path === false) {
			$path = $this->publicationPath($publication, $format);
		}

		$expected_checksum = strtolower(trim((string)$publication[$checksum_field]));
		if (!$path || !is_file($path) || !is_readable($path) || !preg_match('/^[a-f0-9]{64}$/', $expected_checksum)) {
			return false;
		}

		$actual_checksum = hash_file('sha256', $path);
		if ($actual_checksum === false) {
			return false;
		}

		return function_exists('hash_equals') ? hash_equals($expected_checksum, strtolower($actual_checksum)) : $expected_checksum === strtolower($actual_checksum);
	}

	public function publicationFilesAreValid($publication) {
		return $this->publicationFileIsValid($publication, false, 'csv')
			&& $this->publicationFileIsValid($publication, false, 'xml');
	}

	private function publicationTableExists() {
		$query = $this->db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $this->db->escape(DB_DATABASE) . "' AND TABLE_NAME = '" . $this->db->escape(DB_PREFIX . "anchor_price_publication") . "' LIMIT 1");

		return (bool)$query->num_rows;
	}

	private function archiveCorrectionColumnsExist() {
		if ($this->archive_correction_columns_exist === null) {
			$columns = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "anchor_price_publication` WHERE Field IN ('corrects_publication_id', 'source_published_at')");
			$this->archive_correction_columns_exist = $columns->num_rows === 2;
		}
		return $this->archive_correction_columns_exist;
	}

}
