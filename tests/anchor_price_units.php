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
define('DIR_LOGS', DIR_DOWNLOAD);
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
	foreach (glob(DIR_LOGS . 'price_list_*.log') as $path) { unlink($path); }
	rmdir($temporary_directory);
});

require DIR_SYSTEM . 'engine/model.php';
require DIR_SYSTEM . 'engine/controller.php';
require DIR_SYSTEM . 'library/response.php';
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

// Exercise the publisher's joins, updates, transactions, and retention queries in memory.
class AnchorUnitTestDb {
	public $pdo;
	private $affected;

	public function __construct() {
		$this->pdo = new PDO('sqlite::memory:');
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->sqliteCreateFunction('NOW', function () {
			return (new DateTime('now', new DateTimeZone('Europe/Zagreb')))->format('Y-m-d H:i:s');
		});
		$this->pdo->exec("CREATE TABLE test_anchor_price_publication (publication_id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INTEGER, batch_key, location_code, sequence_no INTEGER, filename, relative_path, xml_filename, xml_relative_path, status, product_count INTEGER, checksum_sha256, xml_checksum_sha256, created_by, date_added, published_at, corrects_publication_id INTEGER, source_published_at, error_message, UNIQUE(store_id, location_code, sequence_no));
			CREATE TABLE test_product (product_id INTEGER PRIMARY KEY, model, sku, manufacturer_id INTEGER, upc, jan, isbn, ean, tax_class_id INTEGER, price NUMERIC, status INTEGER, date_available, date_added);
			CREATE TABLE test_product_description (product_id INTEGER, language_id INTEGER, name);
			CREATE TABLE test_product_to_store (product_id INTEGER, store_id INTEGER);
			CREATE TABLE test_manufacturer (manufacturer_id INTEGER, name);
			CREATE TABLE test_product_discount (product_id INTEGER, customer_group_id INTEGER, quantity INTEGER, price NUMERIC, date_start, date_end, priority INTEGER);
			CREATE TABLE test_product_special (product_id INTEGER, customer_group_id INTEGER, price NUMERIC, date_start, date_end, priority INTEGER);
			CREATE TABLE test_anchor_price (anchor_price_id INTEGER, product_id INTEGER, store_id INTEGER, verification_status, gross_price NUMERIC, unit, package_quantity NUMERIC, reference_date DEFAULT '2025-05-02', rule_code DEFAULT 'baseline_configured');
			CREATE TABLE test_setting (setting_id INTEGER PRIMARY KEY, store_id INTEGER, key, value);
			CREATE TABLE test_language (language_id INTEGER, code);
			INSERT INTO test_setting VALUES (1,0,'config_language','hr-hr'),(2,0,'config_customer_group_id','1');
			INSERT INTO test_language VALUES (1,'hr-hr');
			INSERT INTO test_manufacturer VALUES (1,'Marka & Co.');");
		foreach (productFixtures() as $p) {
			$id = $p['product_id'];
			$this->pdo->prepare("INSERT INTO test_product VALUES (?,?,?,1,'','','','',0,?,1,'2025-01-01','2025-01-01')")->execute(array($id, $p['model'], $p['sku'], $p['price']));
			$this->pdo->prepare('INSERT INTO test_product_description VALUES (?,1,?)')->execute(array($id, $p['name']));
			$this->pdo->prepare('INSERT INTO test_product_to_store VALUES (?,0)')->execute(array($id));
			$this->pdo->prepare("INSERT INTO test_anchor_price (anchor_price_id,product_id,store_id,verification_status,gross_price,unit,package_quantity) VALUES (?,?,0,'confirmed',?,?,?)")->execute(array($id, $id, $p['gross_price'], $p['unit'], $p['package_quantity']));
		}
		$this->pdo->exec("INSERT INTO test_product_special VALUES (775,1,9.5,'0000-00-00','0000-00-00',1);
			INSERT INTO test_product_discount VALUES (804,1,1,4,'0000-00-00','0000-00-00',1);
			UPDATE test_product SET upc='001234567890',ean='4006381333931' WHERE product_id=775");
	}

	public function escape($value) { return str_replace("'", "''", (string)$value); }
	public function getLastId() { return $this->pdo->lastInsertId(); }
	public function countAffected() { return $this->affected; }

	public function query($sql) {
		if (strpos($sql, 'INFORMATION_SCHEMA.TABLES') !== false || strpos($sql, 'GET_LOCK(') !== false) {
			$rows = array(array('acquired' => 1));
		} elseif (strpos($sql, 'RELEASE_LOCK(') !== false) {
			$rows = array();
		} elseif (strpos($sql, 'SHOW COLUMNS') === 0) {
			$rows = array(array('Field' => 'corrects_publication_id'), array('Field' => 'source_published_at'));
		} else {
			if (preg_match('/^INSERT INTO (`\w+`) SET (.*)$/', $sql, $matches)) {
				$columns = $values = array();
				foreach (explode(', ', $matches[2]) as $assignment) {
					list($columns[], $values[]) = explode(' = ', $assignment, 2);
				}
				$sql = 'INSERT INTO ' . $matches[1] . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';
			}
			if ($sql === 'START TRANSACTION') { $sql = 'BEGIN'; }
			$stmt = $this->pdo->query($sql);
			$this->affected = $stmt->rowCount();
			$rows = $stmt->columnCount() ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
		}
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

function publicationFixture() {
	$config = new Config();
	foreach (array(
		'config_store_id' => 0, 'config_currency' => 'EUR', 'config_language' => 'hr-hr',
		'module_anchor_price_status' => 1, 'config_customer_group_id' => 1
	) as $key => $value) {
		$config->set($key, $value);
	}
	$registry = new Registry();
	$registry->set('config', $config);
	$registry->set('session', (object)array('data' => array('currency' => 'EUR')));
	$registry->set('currency', new AnchorUnitTestCurrency());
	$registry->set('tax', new AnchorUnitTestTax());
	$registry->set('db', new AnchorUnitTestDb());
	$registry->set('log', new class { public function write($message) {} });
	$model = new ModelExtensionModuleAnchorPrice($registry);
	$property = new ReflectionProperty($model, 'public_tax');
	if (PHP_VERSION_ID < 80100) { $property->setAccessible(true); }
	$property->setValue($model, new AnchorUnitTestTax());
	$registry->set('model_extension_module_anchor_price', $model);
	$registry->set('load', new class { public function model($route) {} });
	$registry->set('response', new Response());
	$registry->set('request', (object)array('server' => array(), 'get' => array()));
	return array($model, $registry);
}

function generatedRows($model, $registry, $mode) {
	$controller = new ControllerExtensionModuleAnchorPrice($registry);
	$db = $model->db->pdo;
	$run = function () use ($model, $mode) {
		if ($mode === 'admin') { return $model->generateDailyPublications()[0]; }
		$result = $model->generatePublication();
		if (!$result['success']) { throw new RuntimeException($result['error']); }
		$web = $result['locations']['WEB'];
		return $web['publication'] + array('existing' => $web['existing']);
	};
	check(PriceListFormat::filename('96162140350', 50, new DateTime('2026-11-18 07:00:59', new DateTimeZone('Europe/Zagreb'))) === '96162140350_ZA_SVA_PRODAJNA_MJESTA_ROBA_000050_20261118_0700', 'statutory filename order and minute precision');
	$publication = $run();
	$id = $publication['publication_id'];
	check(PriceListFormat::isCurrentFormat($publication['filename']), 'new filename uses OIB and goods scope');
	$csv = fopen(DIR_DOWNLOAD . $publication['relative_path'], 'rb');
	check(fread($csv, 3) === "\xEF\xBB\xBF", 'UTF-8 BOM');
	$headers = fgetcsv($csv, 0, ';', '"', '');
	check($headers === array('Naziv robe','Šifra robe','Marka robe','Neto količina','Jedinica mjere','Maloprodajna cijena (EUR) i oznaka posebnog oblika prodaje','Cijena za jedinicu mjere (EUR)','Barkod','EAN barkod','Bazna cijena (EUR)'), 'exact CSV field order');
	$xml = simplexml_load_file(DIR_DOWNLOAD . $publication['xml_relative_path']);
	check((string)$xml['brand'] === 'Voćarstvo Ružić d.o.o.' && (string)$xml['oib'] === '96162140350', 'XML names the merchant');
	$rows = array();
	foreach ($xml->product as $node) {
		$row = fgetcsv($csv, 0, ';', '"', '');
		$keys = array();
		foreach ($node->children() as $key => $value) { $keys[] = $key; }
		check($keys === array('name','code','brand','netQuantity','unit','retailPrice','unitPrice','barcode','ean','basePrice'), 'XML has exactly the ten public fields');
		check($row === array((string)$node->name, (string)$node->code, (string)$node->brand, str_replace('.', ',', $node->netQuantity), (string)$node->unit, str_replace('.', ',', $node->retailPrice) . ' (' . $node->retailPrice['sale'] . ')', str_replace('.', ',', $node->unitPrice), (string)$node->barcode, (string)$node->ean, str_replace('.', ',', $node->basePrice)), 'CSV and XML describe identical amounts and sale status');
		$rows[$row[1]] = $row;
	}
	check(count($rows) === 6 && !fgetcsv($csv, 0, ';', '"', ''), 'both files contain all six packages');
	fclose($csv);
	check($rows['OPG-775'][5] === '9,50 (akcija)' && $rows['OPG-775'][6] === '1,90' && $rows['OPG-775'][9] === '11,00', 'special, unit and base prices');
	check($rows['OPG-775'][7] === '001234567890' && $rows['OPG-775'][8] === '4006381333931', 'independent barcode fields retain leading zeros');
	check($rows['OPG-804'][5] === '4,00 (popust)', 'quantity-one discount');
	check($run()['existing'], 'unchanged cron or save is a no-op');
	if ($mode === 'catalog') {
		$view = 'product/category';
		$data = array('products' => array(
			array('product_id' => 775, 'price' => '11,00 EUR', 'special' => '9,50 EUR'),
			array('product_id' => 775, 'price' => '8,00 EUR', 'current_package_price' => 8),
			array('product_id' => 775, 'price' => false)
		));
		$controller->beforeView($view, $data);
		check($data['products'][0]['current_unit_price'] === '1,90 EUR/kg', 'storefront resolves session price');
		check($data['products'][1]['current_unit_price'] === '1,60 EUR/kg', 'cart option or tier price overrides the catalogue price');
		check(!isset($data['products'][2]['current_unit_price']), 'no visible unit price when prices are hidden');
		$model->config->set('module_anchor_price_cron_key', 'fixture');
		$controller->cron();
		check(!json_decode($controller->response->getOutput(), true)['success'] && !is_file(DIR_LOGS . 'price_list_cron.log'), 'unauthenticated request cannot mark cron healthy');
		$controller->request->server['HTTP_X_ANCHOR_PRICE_KEY'] = 'fixture';
		$controller->cron();
		check(json_decode($controller->response->getOutput(), true)['success'] && strpos(file_get_contents(DIR_LOGS . 'price_list_cron.log'), 'Bez izmjena') !== false, 'authenticated cron records its result');
	}

	// Age the complete pair, including the XML publication timestamp.
	$xml_path = DIR_DOWNLOAD . $publication['xml_relative_path'];
	$old_xml = str_replace((string)$xml['generatedAt'], '2025-01-01T07:00:00+01:00', file_get_contents($xml_path));
	file_put_contents($xml_path, $old_xml);
	$checksum = hash_file('sha256', $xml_path);
	$db->exec("UPDATE test_anchor_price_publication SET published_at='2025-01-01 07:00:00', xml_checksum_sha256='" . $checksum . "'");
	check($run()['existing'], 'no daily republication, even after a long idle period');
	if ($mode === 'catalog') { check($model->getLatestPublication()['publication_id'] == $id, 'long-lived current publication stays public'); }
	$db->exec("UPDATE test_product SET status=0 WHERE product_id=775");
	$replacement = $run();
	check(!$replacement['existing'] && $replacement['product_count'] == 5, 'deactivation replaces the list immediately');
	if ($mode === 'catalog') { check((bool)$model->getPublication($id), 'old list still downloadable immediately after replacement'); }
	$db->exec("UPDATE test_product SET status=1 WHERE product_id=775");
	$db->exec("UPDATE test_anchor_price SET verification_status='pending' WHERE product_id=775");
	try {
		$run();
		check(false, 'pending product blocks incomplete publication');
	} catch (Exception $exception) {
		check(strpos($exception->getMessage(), '#775') !== false, 'blocked publication identifies the product');
	}
	check((int)$db->query("SELECT COUNT(*) FROM test_anchor_price_publication WHERE status='published'")->fetchColumn() === 2, 'failed preflight preserves published pairs');
	$db->exec("UPDATE test_anchor_price SET verification_status='confirmed' WHERE product_id=775");
	check($run()['product_count'] == 6, 'confirmation publishes the activated product');
	$db->exec("UPDATE test_anchor_price SET gross_price=13 WHERE product_id=775");
	check(!$run()['existing'], 'base price change publishes on the same day');
	$db->exec("UPDATE test_product_special SET date_end='2020-01-01'");
	check(!$run()['existing'], 'scheduled sale expiry detected by reconciliation');
	$db->exec("UPDATE test_product SET price=20 WHERE product_id=775");
	$previous_sequence = $db->query('SELECT MAX(sequence_no) FROM test_anchor_price_publication')->fetchColumn();
	if ($mode === 'admin') {
		$controller->captureQuickEdit('extension/module/product_quick_edit/quickEditProduct', array(775,'price',20), 20);
	} else {
		$controller->captureProduct('module/oc_model/editProduct', array(775));
	}
	check($db->query('SELECT MAX(sequence_no) FROM test_anchor_price_publication')->fetchColumn() == $previous_sequence + 1 && $run()['existing'], 'edit event publishes before the next cron call');
	$db->exec("UPDATE test_anchor_price_publication SET published_at='2025-02-01 07:00:00' WHERE publication_id=" . (int)$replacement['publication_id']);
	$run();
	check($db->query('SELECT status FROM test_anchor_price_publication WHERE publication_id=' . (int)$id)->fetchColumn() === 'expired', 'expires after replacement is older than fifteen days');
	if ($mode === 'catalog') { check(!$model->getPublication($id), 'expired list cannot be downloaded by ID'); }
	$model->config->set('module_anchor_price_oib','01234567890');
	check(strpos($run()['filename'], '01234567890_') === 0, 'saved OIB changes file identity');
	$db->exec('UPDATE test_product SET status=0');
	check($run()['product_count'] == 0 && $run()['existing'], 'last deactivation publishes a reusable empty list');
	if ($mode === 'catalog') { check($model->getLatestPublication()['product_count'] == 0, 'empty list remains current'); }
	return array('headers' => $headers, 'rows' => $rows);
}

try {
	$mode = isset($argv[1]) && $argv[1] === '--admin' ? 'admin' : 'catalog';
	require dirname(__DIR__) . '/upload/' . $mode . '/model/extension/module/anchor_price.php';
	require dirname(__DIR__) . '/upload/' . $mode . '/controller/extension/module/anchor_price.php';
	list($model, $registry) = publicationFixture();
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
		$offer = productFixtures()[0];
		check($model->getDisplayData($offer, 9.50)['current_unit_price'] === '1,90 EUR/kg', 'current package price is independent of base price');
		$offer['verification_status'] = 'pending';
		check($model->getDisplayData($offer, 9.50) === array('current_unit_price' => '1,90 EUR/kg'), 'known measure still supports current price while base price awaits confirmation');
	}
	$data = generatedRows($model, $registry, $mode);
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
		check($data['headers'] === $admin['headers'], 'admin and daily-cron catalog CSV headers/order match exactly');
		check($data['rows'] === $admin['rows'], 'admin and daily-cron catalog CSV product data match exactly');
		echo 'PASS: ' . $checks . ' isolated anchor unit/publication checks (plus admin child checks).' . PHP_EOL;
	}
} catch (Throwable $exception) {
	fwrite(STDERR, $exception->getMessage() . PHP_EOL);
	exit(1);
}
