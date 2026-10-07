<?php
/**
 * Isolated regression checks for audited edits of a confirmed anchor's date.
 * No OpenCart configuration, database connection or publication files are loaded.
 * Run: php tests/anchor_price_date_edit.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
	if (!(error_reporting() & $severity)) {
		return false;
	}
	throw new ErrorException($message, 0, $severity, $file, $line);
});

define('DIR_SYSTEM', dirname(__DIR__) . '/upload/system/');
define('DB_PREFIX', 'date_fixture_');
require DIR_SYSTEM . 'engine/model.php';
require DIR_SYSTEM . 'engine/registry.php';
require DIR_SYSTEM . 'library/config.php';
require DIR_SYSTEM . 'helper/utf8.php';
require dirname(__DIR__) . '/upload/admin/model/extension/module/anchor_price.php';

class AnchorDateTestTax {
	public function getRates($price, $tax_class_id) {
		return array();
	}
}

/**
 * Only the model's date-edit queries are supported. Every other query, including
 * any modification of published/archived price lists, fails the test immediately.
 */
class AnchorDateTestDb {
	public $anchor;
	public $queries = array();
	public $audits = array();
	public $updates = array();
	private $transaction_anchor;
	private $transaction_audits;

	public function __construct() {
		$this->anchor = array(
			'anchor_price_id' => 12, 'product_id' => 775, 'store_id' => 0,
			'price' => '11.0000', 'gross_price' => '11.0000', 'unit' => 'kg',
			'package_quantity' => '5.000000', 'currency_code' => 'EUR',
			'tax_class_id' => 0, 'tax_context' => '{"initial_fixture":true}',
			'reference_date' => '2025-05-02', 'rule_code' => 'baseline_configured',
			'source' => 'verified_baseline_correction', 'verification_status' => 'confirmed',
			'product_status' => 1, 'product_date_added' => '2025-08-15 12:00:00',
			'product_name' => 'Gala paket 5 kg', 'model' => 'Gala jabuke', 'sku' => '',
			'manufacturer' => 'OPG Ružić'
		);
	}

	public function escape($value) {
		return addslashes((string)$value);
	}

	public function query($sql) {
		$this->queries[] = $sql;
		if (strpos($sql, 'SELECT language_id FROM `date_fixture_language`') === 0) {
			return $this->result(array(array('language_id' => 2)));
		}
		if (strpos($sql, 'SELECT ap.*, p.model, p.sku, p.status AS product_status') === 0
			&& strpos($sql, "WHERE ap.anchor_price_id = '12' LIMIT 1") !== false) {
			return $this->result(array($this->anchor));
		}
		if ($sql === "SHOW COLUMNS FROM `date_fixture_anchor_price` WHERE Field IN ('unit', 'package_quantity')") {
			return $this->result(array(array('Field' => 'unit'), array('Field' => 'package_quantity')));
		}
		if ($sql === 'START TRANSACTION') {
			$this->transaction_anchor = $this->anchor;
			$this->transaction_audits = $this->audits;
			return $this->result(array());
		}
		if ($sql === 'COMMIT') {
			$this->transaction_anchor = null;
			$this->transaction_audits = null;
			return $this->result(array());
		}
		if ($sql === 'ROLLBACK') {
			$this->anchor = $this->transaction_anchor;
			$this->audits = $this->transaction_audits;
			return $this->result(array());
		}
		if (strpos($sql, 'UPDATE `date_fixture_anchor_price` SET ') === 0) {
			if ($this->transaction_anchor === null || strpos($sql, " WHERE anchor_price_id = '12'") === false) {
				throw new RuntimeException('Anchor edit must be transactional and target only fixture #12.');
			}
			$fields = $this->fields($sql);
			$this->updates[] = $fields;
			// MySQL returns DECIMAL columns in their declared fixed-point format.
			foreach (array('price' => 4, 'gross_price' => 4, 'package_quantity' => 6) as $field => $precision) {
				$fields[$field] = number_format((float)$fields[$field], $precision, '.', '');
			}
			$this->anchor = array_replace($this->anchor, $fields);
			return $this->result(array());
		}
		if (strpos($sql, 'INSERT INTO `date_fixture_anchor_price_audit` SET ') === 0) {
			if ($this->transaction_anchor === null) {
				throw new RuntimeException('Audit must be in the same transaction as the edit.');
			}
			$this->audits[] = $this->fields($sql);
			return $this->result(array());
		}
		throw new RuntimeException('Unexpected fixture SQL: ' . $sql);
	}

	private function fields($sql) {
		$fields = array();
		$set = preg_split('/ WHERE /', $sql, 2)[0];
		preg_match_all("/([a-z_]+) = '((?:\\\\.|[^'])*)'/", $set, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$fields[$match[1]] = stripslashes($match[2]);
		}
		return $fields;
	}

	private function result($rows) {
		return (object)array('rows' => $rows, 'row' => $rows ? $rows[0] : array(), 'num_rows' => count($rows));
	}
}

$checks = 0;
function dateCheck($condition, $message) {
	global $checks;
	$checks++;
	if (!$condition) {
		throw new RuntimeException('FAIL: ' . $message);
	}
}

function dateFixture() {
	$config = new Config();
	foreach (array(
		'config_language' => 'hr-hr', 'config_language_id' => 2,
		'config_tax' => false, 'config_country_id' => 53, 'config_zone_id' => 0,
		'config_customer_group_id' => 1, 'module_anchor_price_reference_date' => '2025-05-02'
	) as $key => $value) {
		$config->set($key, $value);
	}
	$registry = new Registry();
	$db = new AnchorDateTestDb();
	$registry->set('config', $config);
	$registry->set('db', $db);
	$registry->set('tax', new AnchorDateTestTax());
	return array(new ModelExtensionModuleAnchorPrice($registry), $db);
}

function dateInput($date) {
	return array(
		'price' => '11.0000', 'gross_price' => '11.0000', 'unit' => 'kg',
		'package_quantity' => '5.000000', 'reference_date' => $date,
		'verification_status' => 'confirmed'
	);
}

function checkAudit($audit, $old_date, $new_date, $old_rule, $new_rule, $reason, $user_id) {
	$before = json_decode($audit['old_data'], true);
	$after = json_decode($audit['new_data'], true);
	dateCheck(is_array($before) && is_array($after), 'audit contains valid old/new JSON snapshots');
	dateCheck($audit['anchor_price_id'] === '12' && $audit['product_id'] === '775' && $audit['store_id'] === '0', 'audit targets the correct anchor/product/store');
	dateCheck($audit['user_id'] === (string)$user_id, 'audit records the administrator ID');
	dateCheck($audit['action'] === 'update', 'audit records an explicit update');
	dateCheck($audit['reason'] === trim($reason), 'audit stores the supplied justification exactly');
	dateCheck($before['reference_date'] === $old_date && $after['reference_date'] === $new_date, 'audit keeps old and new reference dates');
	dateCheck($before['rule_code'] === $old_rule && $after['rule_code'] === $new_rule, 'audit keeps old and updated date rule');
	foreach (array('price', 'gross_price', 'unit', 'package_quantity', 'currency_code', 'tax_class_id', 'verification_status') as $field) {
		dateCheck($before[$field] === $after[$field], 'date-only edit preserves ' . $field . ' in the audit');
	}
	$tax = json_decode($after['tax_context'], true);
	dateCheck($tax['manual_edit'] === true && $tax['entered_gross_price'] === '11.0000', 'manual edit retains the explicitly entered gross price');
	dateCheck($after['source'] === 'admin', 'audit identifies manual administrator edit');
}

function rejectDateEdit($data, $reason, $expected_message, $label) {
	list($model, $db) = dateFixture();
	$before = $db->anchor;
	$exception = null;
	try {
		$model->updateAnchorPrice(12, $data, $reason, 42);
	} catch (Exception $caught) {
		$exception = $caught;
	}
	dateCheck($exception !== null && strpos($exception->getMessage(), $expected_message) !== false, $label . ' is rejected for the expected reason');
	dateCheck($db->anchor === $before, $label . ' leaves the saved anchor unchanged');
	dateCheck(count($db->updates) === 0 && count($db->audits) === 0, $label . ' performs no update or audit insertion');
	dateCheck(!in_array('START TRANSACTION', $db->queries, true), $label . ' is rejected before opening a write transaction');
}

try {
	list($model, $db) = dateFixture();
	$reason = "  Ispravak povijesnog datuma prema evidenciji OPG-a.  ";
	dateCheck($model->updateAnchorPrice(12, dateInput('2025-05-03'), $reason, 42) === true, 'confirmed baseline anchor can change to a justified later date');
	dateCheck(count($db->updates) === 1 && count($db->audits) === 1, 'date edit writes one anchor update and one audit');
	dateCheck($db->anchor['reference_date'] === '2025-05-03' && $db->anchor['rule_code'] === 'first_listing', 'later date receives the corresponding date rule');
	dateCheck((float)$db->anchor['price'] === 11.0 && (float)$db->anchor['gross_price'] === 11.0, 'saved package prices remain unchanged');
	dateCheck($db->anchor['unit'] === 'kg' && (float)$db->anchor['package_quantity'] === 5.0, 'saved measure and package quantity remain unchanged');
	checkAudit($db->audits[0], '2025-05-02', '2025-05-03', 'baseline_configured', 'first_listing', $reason, 42);
	dateCheck(in_array('COMMIT', $db->queries, true) && !in_array('ROLLBACK', $db->queries, true), 'successful date edit commits its audit transaction');

	$reason = 'Potvrđen izvorni datum 2. svibnja 2025.';
	dateCheck($model->updateAnchorPrice(12, dateInput('2025-05-02'), $reason, 43) === true, 'date can return to the configured baseline');
	dateCheck(count($db->updates) === 2 && count($db->audits) === 2, 'reverse edit receives its own audit entry');
	dateCheck($db->anchor['reference_date'] === '2025-05-02' && $db->anchor['rule_code'] === 'baseline_configured', 'returning to baseline restores the baseline date rule');
	checkAudit($db->audits[1], '2025-05-03', '2025-05-02', 'first_listing', 'baseline_configured', $reason, 43);
	dateCheck(count(array_filter($db->queries, function ($sql) {
		return stripos($sql, 'publication') !== false;
	})) === 0, 'editing or reverting dates never changes archived publications');

	$today = new DateTime('now', new DateTimeZone('Europe/Zagreb'));
	list($model, $db) = dateFixture();
	dateCheck($model->updateAnchorPrice(12, dateInput($today->format('Y-m-d')), 'Datum potvrđen prema evidenciji.', 44) === true, 'today is a permitted upper date boundary');
	dateCheck($db->anchor['reference_date'] === $today->format('Y-m-d'), 'today boundary is saved as selected');
	dateCheck($db->anchor['rule_code'] === 'first_listing', 'later actual date updates the rule');

	foreach (array('2025-02-30', '2025-05-32', '2025-5-03', '', '03.05.2025.', '2025-05-01') as $date) {
		rejectDateEdit(dateInput($date), 'Opravdan ispravak datuma.', 'Referentni datum', 'Invalid/out-of-range date ' . var_export($date, true));
	}
	$tomorrow = clone $today;
	$tomorrow->modify('+1 day');
	rejectDateEdit(dateInput($tomorrow->format('Y-m-d')), 'Opravdan ispravak datuma.', 'Referentni datum', 'Future date');
	foreach (array('', '   ', 'ab', str_repeat('a', 256)) as $reason) {
		rejectDateEdit(dateInput('2025-05-03'), $reason, 'An audit reason is required.', 'Missing/invalid audit reason of ' . utf8_strlen(trim($reason)) . ' characters');
	}
	foreach (array('čćž', str_repeat('č', 255)) as $reason) {
		list($model, $db) = dateFixture();
		dateCheck($model->updateAnchorPrice(12, dateInput('2025-05-03'), $reason, 45) === true, 'audit reason accepts Unicode boundary of ' . utf8_strlen($reason) . ' characters');
		dateCheck($db->audits[0]['reason'] === $reason, 'Unicode audit justification is preserved without truncation');
	}

	$invalid = dateInput('2025-05-03');
	$invalid['verification_status'] = 'unknown';
	rejectDateEdit($invalid, 'Opravdan ispravak datuma.', 'Invalid anchor price status.', 'Invalid verification status');
	$invalid['verification_status'] = 'pending';
	rejectDateEdit($invalid, 'Opravdan ispravak datuma.', 'An active product must keep a confirmed anchor price.', 'Unconfirming an active product');
	$invalid = dateInput('2025-05-03');
	$invalid['package_quantity'] = 0;
	rejectDateEdit($invalid, 'Opravdan ispravak datuma.', 'Unesite jedinicu kg ili l', 'Invalid measure during date edit');

	echo 'PASS: ' . $checks . " audited anchor date-edit checks.\n";
} catch (Throwable $exception) {
	fwrite(STDERR, $exception->getMessage() . "\n");
	exit(1);
}
