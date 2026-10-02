<?php
/**
 * Isolated regression checks; no OpenCart config or database connection is loaded.
 * Run: php tests/anchor_price_units.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
	if (!(error_reporting() & $severity)) {
		return false;
	}
	throw new ErrorException($message, 0, $severity, $file, $line);
});

define('DIR_SYSTEM', dirname(__DIR__) . '/upload/system/');
define('DB_PREFIX', 'test_');
define('DB_DATABASE', 'isolated_fixture');
define('HTTPS_SERVER', 'https://example.invalid/');
define('HTTPS_CATALOG', HTTPS_SERVER);
$temporary_directory = sys_get_temp_dir() . '/anchor-price-test-' . bin2hex(random_bytes(10));
if (!mkdir($temporary_directory, 0700)) {
	throw new RuntimeException('Cannot create isolated test directory.');
}
define('DIR_DOWNLOAD', $temporary_directory . '/');
register_shutdown_function(function () use ($temporary_directory) {
	// Only remove files from this test's uniquely named directory.
	$publication_directory = $temporary_directory . '/anchor_price';
	if (is_dir($publication_directory)) {
		foreach (scandir($publication_directory) as $file) {
			if ($file !== '.' && $file !== '..' && is_file($publication_directory . '/' . $file)) {
				unlink($publication_directory . '/' . $file);
			}
		}
		rmdir($publication_directory);
	}
	rmdir($temporary_directory);
});

require DIR_SYSTEM . 'engine/model.php';
require DIR_SYSTEM . 'engine/registry.php';
require DIR_SYSTEM . 'library/config.php';
require DIR_SYSTEM . 'library/anchor_price_unit.php';

class AnchorUnitTestCurrency {
	public function format($price, $code) {
		return number_format($price, 2, ',', '') . ' ' . $code;
	}
}

class AnchorUnitTestTax {
	public function calculate($price, $tax_class_id, $calculate = true) {
		return (float)$price;
	}
}

/** Stores publication SQL in memory only; unexpected queries fail the test. */
class AnchorUnitTestDb {
	public $publication = array('publication_id' => 1);
	public function escape($value) {
		return addslashes((string)$value);
	}
	public function getLastId() {
		return 1;
	}
	public function query($sql) {
		if (strpos($sql, 'INFORMATION_SCHEMA.TABLES') !== false) {
			return $this->result(array(array('TABLE_NAME' => 'test_anchor_price_publication')));
		}
		if (strpos($sql, 'COALESCE(MAX(sequence_no)') !== false) {
			return $this->result(array(array('next_sequence' => 1, 'sequence_no' => 1)));
		}
		if (preg_match('/^(INSERT INTO|UPDATE) `test_anchor_price_publication` SET /', $sql)) {
			preg_match_all("/([a-z_]+) = '((?:\\\\.|[^'])*)'/", preg_split('/ WHERE /', $sql, 2)[0], $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$this->publication[$match[1]] = stripslashes($match[2]);
			}
			return $this->result(array());
		}
		if (strpos($sql, 'SELECT * FROM `test_anchor_price_publication`') === 0) {
			return $this->result(array($this->publication));
		}
		throw new RuntimeException('Unexpected fixture SQL: ' . $sql);
	}
	private function result($rows) {
		return (object)array('rows' => $rows, 'row' => $rows ? $rows[0] : array(), 'num_rows' => count($rows));
	}
}

$checks = 0;
function check($condition, $message) {
	global $checks;
	$checks++;
	if (!$condition) {
		throw new RuntimeException('FAIL: ' . $message);
	}
}

function near($actual, $expected, $message) {
	check(abs((float)$actual - (float)$expected) < 0.000001, $message);
}

function invokePrivate($model, $name, $arguments) {
	$method = new ReflectionMethod($model, $name);
	$method->setAccessible(true);
	return $method->invokeArgs($model, $arguments);
}

function expectInvalid($callback, $message) {
	try {
		$callback();
	} catch (InvalidArgumentException $exception) {
		check(true, $message);
		return;
	}
	check(false, $message);
}

function productFixtures() {
	$packages = array(
		array(775, 'Gala paket 5 kg', 'kg', 5, 11, 2.2),
		array(804, 'Jabučni ocat 1 litra boca', 'l', 1, 4.5, 4.5),
		array(803, 'Mariri Red paket 5 kg', 'kg', 5, 12, 2.4),
		array(773, 'Pinova paket 5 kg', 'kg', 5, 12, 2.4),
		array(821, 'Šljiva Top Five 1 kg', 'kg', 1, 2.5, 2.5),
		array(771, 'Sok od jabuka 3 litre', 'l', 3, 10, 10 / 3)
	);
	$products = array();
	foreach ($packages as $package) {
		$products[] = array(
			'product_id' => $package[0], 'name' => $package[1], 'product_name' => $package[1],
			'model' => 'OPG-' . $package[0], 'sku' => 'SKU-' . $package[0], 'manufacturer' => 'OPG Ružić',
			'unit' => $package[2], 'package_quantity' => $package[3], 'price' => $package[4],
			'regular_price' => $package[4], 'expected_unit_price' => $package[5],
			'anchor_price_id' => $package[0], 'anchor_gross_price' => $package[4],
			'gross_price' => $package[4], 'verification_status' => 'confirmed', 'rule_code' => 'base_2025_05_02',
			'reference_date' => '2025-05-02', 'tax_class_id' => 0, 'currency_code' => 'EUR',
			'discount' => null, 'discount_price' => null, 'special' => null, 'special_price' => null,
			'quantity' => 10, 'stock_status' => 'Nije više dostupno',
			'ean' => '', 'upc' => '', 'jan' => '', 'isbn' => ''
		);
	}
	return $products;
}

function publicationFixture($mode) {
	$config = new Config();
	foreach (array(
		'config_store_id' => 0, 'config_currency' => 'EUR', 'config_language' => 'hr-hr',
		'module_anchor_price_publication_type' => 'webshop',
		'module_anchor_price_publication_address' => 'Ulica jabuka 1',
		'module_anchor_price_publication_code' => 'WEB'
	) as $key => $value) {
		$config->set($key, $value);
	}
	$registry = new Registry();
	$registry->set('config', $config);
	$registry->set('session', (object)array('data' => array('currency' => 'EUR')));
	$registry->set('currency', new AnchorUnitTestCurrency());
	$registry->set('tax', new AnchorUnitTestTax());
	$registry->set('db', new AnchorUnitTestDb());
	$model = new ModelExtensionModuleAnchorPrice($registry);
	if ($mode === 'catalog') {
		$property = new ReflectionProperty($model, 'public_tax');
		$property->setAccessible(true);
		$property->setValue($model, new AnchorUnitTestTax());
	}
	return $model;
}

function generatedRows($model, $mode) {
	$model->config->set('module_anchor_price_publication_address', '');
	$default_locations = array();
	foreach (array(
		'actual_newline' => "Bana Jelačića 74\r\nKlokočevik, 35212 Garčin",
		'mixedcase_html' => 'Bana Jelačića 74<BR />Klokočevik, 35212 Garčin'
	) as $format => $address) {
		$model->config->set('config_address', $address);
		$default_locations[$format] = invokePrivate($model, 'publicationLocation', array('WEB'));
		check($default_locations[$format]['address'] === 'bana-jelacica-74-klokocevik-35212-garcin', $mode . ' actual default address transliterates Croatian accents: ' . $format);
	}
	$model->config->set('module_anchor_price_publication_address', 'Ulica jabuka 1');
	$products = productFixtures();
	invokePrivate($model, 'assertPublicationProducts', array($products));
	check(true, $mode . ' complete measured fixtures can be published');
	foreach (array('', 'kom') as $bad_unit) {
		$invalid = $products;
		$invalid[0]['unit'] = $bad_unit;
		try {
			invokePrivate($model, 'assertPublicationProducts', array($invalid));
			check(false, $mode . ' refuses publication with missing or unsupported measure');
		} catch (Exception $exception) {
			check(strpos($exception->getMessage(), 'Objava je zaustavljena') !== false, $mode . ' stops invalid measure before file generation');
		}
	}
	$invalid = $products;
	$invalid[0]['package_quantity'] = 0;
	try {
		invokePrivate($model, 'assertPublicationProducts', array($invalid));
		check(false, $mode . ' refuses publication with zero package quantity');
	} catch (Exception $exception) {
		check(strpos($exception->getMessage(), 'Objava je zaustavljena') !== false, $mode . ' stops zero quantity before dividing');
	}
	// Distinct sale and anchor prices expose accidental reuse of the wrong figure.
	$products[0]['special'] = $products[0]['special_price'] = 9.5;
	$products[1]['discount'] = $products[1]['discount_price'] = 4;
	$now = new DateTime('2026-10-02 07:00:00', new DateTimeZone('Europe/Zagreb'));
	$arguments = $mode === 'catalog'
		? array('WEB', $products, $now, str_repeat('a', 32))
		: array(0, 7, 'WEB', $products, $now, str_repeat('a', 32));
	$result = invokePrivate($model, 'generateLocationPublication', $arguments);
	$publication = $mode === 'catalog' ? $result['publication'] : $result;
	check(!empty($publication['filename']), $mode . ' creates CSV metadata');
	check($publication['filename'] === 'webshop_ulica-jabuka-1_web_000001_20261002_070000.csv', $mode . ' filename contains all required elements');
	check($publication['xml_filename'] === str_replace('.csv', '.xml', $publication['filename']), $mode . ' CSV/XML share naming elements');
	$csv = fopen(DIR_DOWNLOAD . $publication['relative_path'], 'rb');
	fseek($csv, 3); // UTF-8 byte-order mark.
	$headers = fgetcsv($csv, 0, ';', '"', '\\');
	check(count($headers) === 22, $mode . ' CSV contains expected 22 fields');
	$rows = array();
	while (($row = fgetcsv($csv, 0, ';', '"', '\\')) !== false) {
		check(count($row) === count($headers), $mode . ' CSV row field count matches header');
		$rows[] = array_combine($headers, $row);
	}
	fclose($csv);
	$xml = simplexml_load_file(DIR_DOWNLOAD . $publication['xml_relative_path']);
	check($xml !== false && count($xml->product) === 6, $mode . ' XML has six products');
	check(count($rows) === 6, $mode . ' CSV has six products');
	foreach ($products as $index => $product) {
		$current = $index === 0 ? 9.5 : ($index === 1 ? 4 : $product['price']);
		$expected_current = number_format($current / $product['package_quantity'], 2, '.', '');
		$expected_anchor = number_format($product['expected_unit_price'], 2, '.', '');
		$node = $xml->product[$index];
		check((string)$node->unit === $product['unit'], $mode . ' XML uses product-specific kg/l');
		check((string)$node->packageQuantity === (string)$product['package_quantity'], $mode . ' XML package quantity');
		check((string)$node->unitPrice === $expected_current, $mode . ' XML current unit price');
		check((string)$node->anchorUnitPrice === $expected_anchor, $mode . ' XML anchor unit price');
		check((string)$node->anchorDate === '2025-05-02', $mode . ' XML retains historical reference date');
		check((string)$node->stockStatus === 'Dostupno', $mode . ' in-stock product never carries stale unavailable label');
		check($rows[$index]['Jedinica mjere'] === $product['unit'], $mode . ' CSV unit matches XML');
		check(str_replace(',', '.', $rows[$index]['Cijena po jedinici (EUR)']) === $expected_current, $mode . ' CSV current unit price matches XML');
		check(str_replace(',', '.', $rows[$index]['Sidrena cijena po jedinici (EUR)']) === $expected_anchor, $mode . ' CSV anchor unit price matches XML');
	}
	return array('default_location' => $default_locations, 'headers' => $headers, 'rows' => $rows, 'xml' => (string)$xml->asXML());
}

try {
	$mode = isset($argv[1]) && $argv[1] === '--admin' ? 'admin' : 'catalog';
	require dirname(__DIR__) . '/upload/' . $mode . '/model/extension/module/anchor_price.php';
	$model = publicationFixture($mode);
	if ($mode === 'catalog') {
		foreach (productFixtures() as $product) {
			check(AnchorPriceUnit::isValid($product['unit'], $product['package_quantity']), 'each known package has valid measure');
			near(AnchorPriceUnit::calculate($product['gross_price'], $product['package_quantity']), $product['expected_unit_price'], 'six expected unit prices');
			$display = $model->getDisplayData($product);
			check($display['anchor_price_date'] === '2. 5. 2025.', 'Croatian display reference date');
			check($display['anchor_unit_price'] === number_format($product['expected_unit_price'], 2, ',', '') . ' EUR/' . $product['unit'], 'storefront displays correct unit price');
		}
		near(AnchorPriceUnit::calculate(3, 0.75), 4, 'fractional 0.75 litre package');
		$fractional = productFixtures()[1];
		$fractional['gross_price'] = 3;
		$fractional['package_quantity'] = 0.75;
		check($model->getDisplayData($fractional)['anchor_unit_price'] === '4,00 EUR/l', 'storefront formats fractional litre price');
		near(AnchorPriceUnit::calculate(0, 0.5), 0, 'zero product price is valid');
		check(AnchorPriceUnit::isValid('kg', '0.125'), 'fractional kg measure valid');
		check(AnchorPriceUnit::isValid('l', '0.000001'), 'smallest representable positive package quantity is valid');
		foreach (array(0, -1, '', '1,5', 'not-a-number', INF, NAN, 1000000000) as $quantity) {
			check(!AnchorPriceUnit::isValid('kg', $quantity), 'invalid or zero quantity rejected');
		}
		foreach (array('0.0000001', '0.0000005', '0.0000009999', '1.2345678', '1.0000000001') as $quantity) {
			check(!AnchorPriceUnit::isValid('l', $quantity), 'package quantity cannot be silently rounded in DECIMAL(15,6): ' . $quantity);
		}
		foreach (array('kom', 'ml', 'g', 'KG', '') as $unit) {
			check(!AnchorPriceUnit::isValid($unit, 1), 'unsupported units rejected');
		}
		foreach (array(0, -1, 'abc', INF, NAN) as $quantity) {
			expectInvalid(function () use ($quantity) { AnchorPriceUnit::calculate(10, $quantity); }, 'invalid divisor throws');
		}
		expectInvalid(function () { AnchorPriceUnit::calculate(INF, 1); }, 'infinite price throws');
		expectInvalid(function () { AnchorPriceUnit::calculate('wrong', 1); }, 'invalid price throws');
		check(AnchorPriceUnit::filenamePart('../foo/../../bar\\name') === 'foo-bar-name', 'filename strips traversal components');
		check(AnchorPriceUnit::filenamePart('  WEB &amp; shop  ') === 'web-shop', 'filename normalizes HTML and spaces');
		check(AnchorPriceUnit::filenamePart('ČĆĐŠŽ čćđšž') === 'ccdsz-ccdsz', 'Croatian accents normalize deterministically');
		check(AnchorPriceUnit::filenamePart('.../\\') === '', 'invalid filename part does not invent an address');
		$missing = productFixtures()[0];
		unset($missing['unit'], $missing['package_quantity']);
		$display = $model->getDisplayData($missing);
		check($display['anchor_unit_price'] === '', 'missing measure does not fabricate a unit price');
		check(strpos($display['anchor_price_text'], '()') === false && strpos($display['anchor_price_text'], '/kom') === false, 'missing measure does not append nonsense');
		check($model->getDisplayData(array()) === array(), 'empty anchor record is safe');
		$zero = productFixtures()[0];
		$zero['package_quantity'] = 0;
		check($model->getDisplayData($zero)['anchor_unit_price'] === '', 'zero measure does not divide in storefront');
		$product = array('product_id' => 1, 'name' => 'A & B <fruit>', 'unit' => 'l', 'package_quantity' => '0.75', 'unit_price' => '4.00', 'anchor_unit_price' => '4.80', 'current_price' => '3.00', 'anchor_price' => '3.60', 'anchor_date' => '2025-05-02');
		$xml = simplexml_load_string(invokePrivate($model, 'xmlProduct', array($product)));
		check((string)$xml->name === $product['name'], 'XML escapes product text');
		check((string)$xml->unitPrice === '4.00' && (string)$xml->anchorUnitPrice === '4.80', 'XML keeps current and historical unit prices separate');
	}
	$data = generatedRows($model, $mode);
	if ($mode === 'admin') {
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	} else {
		// Separate process avoids the identical OpenCart admin/catalog model class name.
		$output = array();
		$status = 0;
		exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --admin 2>&1', $output, $status);
		check($status === 0, 'admin publication fixture passes: ' . implode("\n", $output));
		$admin = json_decode(implode("\n", $output), true);
		check(is_array($admin), 'admin test returns comparison data');
		check($data['default_location'] === $admin['default_location'], 'admin and daily-cron catalog use identical accented default address');
		check($data['headers'] === $admin['headers'], 'admin and daily-cron catalog CSV headers/order match exactly');
		check($data['rows'] === $admin['rows'], 'admin and daily-cron catalog CSV product data match exactly');
		check($data['xml'] === $admin['xml'], 'admin and daily-cron catalog XML match exactly');
		echo 'PASS: ' . $checks . ' isolated anchor unit/publication checks (plus admin child checks).' . PHP_EOL;
	}
} catch (Throwable $exception) {
	fwrite(STDERR, $exception->getMessage() . PHP_EOL);
	exit(1);
}
