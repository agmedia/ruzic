<?php
/** Run: php -d memory_limit=64M tests/sales_export.php
 * Optional real database check: SALES_EXPORT_TEST_SOCKET=/path/to/disposable/mysql.sock
 * Never loads shop credentials. Creates and drops only a randomly named fixture database.
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
	if (error_reporting() & $severity) {
		throw new ErrorException($message, 0, $severity, $file, $line);
	}
	return false;
});
define('DIR_APPLICATION', sys_get_temp_dir() . '/sales-export-test-' . bin2hex(random_bytes(8)) . '/admin/');
define('DB_PREFIX', 'fixture_');
require dirname(__DIR__) . '/upload/system/engine/registry.php';
require dirname(__DIR__) . '/upload/system/engine/model.php';
require dirname(__DIR__) . '/upload/system/engine/controller.php';
require dirname(__DIR__) . '/upload/admin/model/tool/sales_export.php';
require dirname(__DIR__) . '/upload/admin/controller/tool/backup/sales.php';

function check($condition, $message) {
	if (!$condition) { throw new RuntimeException($message); }
}
function rejects($callback, $message) {
	try { $callback(); } catch (RuntimeException $e) {
		check($e->getMessage() === $message, 'Unexpected error: ' . $e->getMessage());
		return;
	}
	throw new RuntimeException('Expected rejection: ' . $message);
}
function decodeCell($value) {
	if ($value === '\\N') { return null; }
	if (substr($value, 0, 2) === '\\\\' || substr($value, 0, 1) === "'") { return substr($value, 1); }
	return $value;
}

class ExportFixtureDb {
	public $sizes = array('order' => 10003, 'order_product' => 20003, 'order_option' => 0, 'order_total' => 125, 'order_history' => 125, 'customer' => 125, 'address' => 125, 'product' => 125);
	public $fail = false;
	public $largest = 0;
	public function row($table, $index) {
		$notes = array('Ružić; "jabuke" 😀', "line 1\r\nline 2", '=HYPERLINK("https://example.invalid")', '\\N', '\\\\N', "'original", '@SUM(1;2)', "\t=1+1", null, '', '0', '-12.5000', ' +cmd');
		return array($table . '_id' => (string)($index * 3), 'order_status_id' => (string)($index % 5), 'custom_field' => $notes[$index % count($notes)], 'total' => '1234567890.1234', 'nullable' => null, 'empty_value' => '');
	}
	public function query($sql) {
		if ($sql === 'SET NAMES utf8mb4' || $sql === "SET time_zone = '+00:00'") { return true; }
		if (preg_match('/^SELECT MAX\(`\w+`\) AS maximum FROM `fixture_(\w+)`$/', $sql, $match)) {
			return $this->result(array(array('maximum' => $this->sizes[$match[1]] ? ($this->sizes[$match[1]] - 1) * 3 : null)));
		}
		if (preg_match('/^SHOW COLUMNS FROM `fixture_(\w+)`$/', $sql, $match)) {
			return $this->result(array_map(function ($column) { return array('Field' => $column); }, array_keys($this->row($match[1], 0))));
		}
		if (preg_match('/^SELECT \* FROM `fixture_(\w+)` WHERE `\w+` > (-?\d+) AND `\w+` <= (-?\d+) ORDER BY `\w+` ASC LIMIT (\d+)$/', $sql, $match)) {
			if ($this->fail) { $this->fail = false; throw new RuntimeException('simulated_database_failure'); }
			check((int)$match[4] === ModelToolSalesExport::BATCH_SIZE, 'Unbounded SELECT');
			$rows = array();
			for ($i = max(0, (int)floor($match[2] / 3) + 1); $i < $this->sizes[$match[1]] && $i * 3 <= $match[3] && count($rows) < $match[4]; $i++) {
				$rows[] = $this->row($match[1], $i);
			}
			$this->largest = max($this->largest, count($rows));
			return $this->result($rows);
		}
		throw new RuntimeException('Unexpected or mutating query: ' . $sql);
	}
	private function result($rows) { return (object)array('rows' => $rows, 'row' => $rows ? $rows[0] : array(), 'num_rows' => count($rows)); }
}

$root = rtrim(sys_get_temp_dir(), '/\\') . '/opgruzic-sales-' . substr(hash('sha256', DIR_APPLICATION), 0, 20);
$registry = new Registry();
$db = new ExportFixtureDb();
$registry->set('db', $db);
$model = new ModelToolSalesExport($registry);
try {
	check($model->status(1) === null, 'Fresh status');
	$job = $model->start(1);
	$expected = $db->sizes;
	check($model->start(1)['id'] === $job['id'], 'Repeated start must reuse job');
	rejects(function () use ($model, $job) { $model->step(2, $job['id']); }, 'error_job');
	rejects(function () use ($model, $job) { $model->download(1, $job['id'], 'order'); }, 'error_incomplete');
	$lock = fopen($root . '/1/lock', 'c');
	flock($lock, LOCK_EX);
	rejects(function () use ($model, $job) { $model->step(1, $job['id']); }, 'error_busy');
	fclose($lock);
	$db->fail = true;
	rejects(function () use ($model, $job) { $model->step(1, $job['id']); }, 'simulated_database_failure');
	check($model->status(1)['rows'] === 0, 'Failed batch changed checkpoint');
	$job = $model->step(1, $job['id']);
	check($job['rows'] === 100, 'Batch size');
	file_put_contents($root . '/1/order.csv', 'partial row after interrupted write', FILE_APPEND);
	$db->sizes['order'] += 10; // New rows after the captured upper bound must be excluded.
	$model = new ModelToolSalesExport($registry); // A fresh request must resume from disk.
	$steps = 0;
	while (!$job['complete']) {
		$job = $model->step(1, $job['id']);
		check(++$steps < 1000, 'Export did not finish');
	}
	check($job['counts'] === $expected, 'Wrong counts / duplicate or missing records');
	check($job['rows'] === array_sum($expected), 'Wrong total');
	check($model->step(1, $job['id']) === $job, 'Completed retry changed export');
	foreach ($expected as $table => $count) {
		$file = $model->download(1, $job['id'], $table);
		check(fread($file['handle'], 3) === "\xEF\xBB\xBF", 'Missing UTF-8 BOM');
		check(fgetcsv($file['handle'], 0, ';', '"', '') === array_keys($db->row($table, 0)), 'Missing/custom columns or empty table header');
		$actual = 0;
		while (($row = fgetcsv($file['handle'], 0, ';', '"', '')) !== false) {
			check(array_map('decodeCell', $row) === array_values($db->row($table, $actual)), 'CSV round trip failed in ' . $table . ' row ' . $actual);
			check(!preg_match('/^[=+@]/', (string)$row[2]), 'Unsafe spreadsheet formula');
			$actual++;
		}
		check($actual === $count, 'CSV count mismatch');
		fclose($file['handle']);
		fclose($file['lock']);
	}
	rejects(function () use ($model, $job) { $model->download(1, $job['id'], '../../state'); }, 'error_job');
	rejects(function () use ($model) { $model->delete(1, 'wrong-job'); }, 'error_job');
	$state = json_decode(file_get_contents($root . '/1/state.json'), true);
	$state['created'] = time() - ModelToolSalesExport::MAX_AGE;
	file_put_contents($root . '/1/state.json', json_encode($state));
	check($model->status(1) === null && !file_exists($root . '/1/order.csv'), 'Expired export retained');
	$new = $model->start(1);
	check($new['id'] !== $job['id'], 'New job reused old ID');
	$model->delete(1, $new['id']);
	$model->delete(1, $new['id']);
	check($model->status(1) === null, 'Delete failed');
	echo 'PASS: ' . $job['rows'] . ' rows, ' . $steps . ' resumable batches, exact CSV round trip, empty table, custom fields, formula escaping, NULL, retries, locks, ownership and expiry. Peak memory: ' . round(memory_get_peak_usage(true) / 1048576, 1) . " MB\n";

	// Exercise authorization on every endpoint without loading a model or database.
	$registry->set('language', new class { public function get($key) { return $key; } });
	$registry->set('load', new class {
		public function language($route) {}
		public function model($route) { throw new RuntimeException('Unauthorized model access'); }
	});
	$user = new class {
		public $allowed = false;
		public function isLogged() { return 1; }
		public function hasPermission($type, $route) { return $this->allowed; }
	};
	$registry->set('user', $user);
	$registry->set('session', (object)array('data' => array('user_token' => 'valid-token')));
	$request = (object)array('get' => array('user_token' => 'valid-token'), 'post' => array(), 'server' => array('REQUEST_METHOD' => 'POST'));
	$registry->set('request', $request);
	$response = new class {
		public $headers = array(); public $output;
		public function addHeader($header) { $this->headers[] = $header; }
		public function setOutput($output) { $this->output = $output; }
	};
	$registry->set('response', $response);
	$controller = new ControllerToolBackupSales($registry);
	foreach (array('index', 'start', 'status', 'step', 'delete', 'download') as $action) {
		$controller->{$action}();
		check(json_decode($response->output, true)['error'] === 'error_permission', 'Permission bypass: ' . $action);
	}
	$user->allowed = true;
	$request->get['user_token'] = 'wrong';
	$controller->start();
	check(json_decode($response->output, true)['error'] === 'error_permission', 'Token bypass');
	$request->get['user_token'] = 'valid-token';
	$request->server['REQUEST_METHOD'] = 'GET';
	foreach (array('start', 'step', 'delete') as $action) {
		$controller->{$action}();
		check(json_decode($response->output, true)['error'] === 'error_method', 'GET mutation: ' . $action);
	}
	echo "PASS: endpoint permissions, CSRF token and POST-only mutations.\n";

	if (getenv('SALES_EXPORT_TEST_SOCKET')) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$connection = new mysqli('localhost', 'root', '', '', 0, getenv('SALES_EXPORT_TEST_SOCKET'));
		$database = 'sales_export_fixture_' . bin2hex(random_bytes(6));
		$connection->query('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4');
		try {
			$connection->select_db($database);
			$connection->set_charset('utf8mb4');
			$connection->query("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");
			foreach ($expected as $table => $unused) {
				$connection->query('CREATE TABLE `fixture_' . $table . '` (`' . $table . '_id` INT PRIMARY KEY, `custom_field` LONGTEXT NULL, `total` DECIMAL(16,4), `stamp` TIMESTAMP NULL)');
				if ($table === 'order_option') { continue; }
				$stmt = $connection->prepare('INSERT INTO `fixture_' . $table . '` VALUES (?, ?, ?, ?)');
				for ($i = 0; $i < 203; $i++) {
					$row = $db->row($table, $i);
					$date = '2026-01-02 03:04:05';
					$stmt->bind_param('isss', $row[$table . '_id'], $row['custom_field'], $row['total'], $date);
					$stmt->execute();
				}
				$stmt->close();
			}
			$registry->set('db', new class($connection) {
				private $connection;
				public function __construct($connection) { $this->connection = $connection; }
				public function query($sql) {
					$result = $this->connection->query($sql);
					if ($result === true) { return true; }
					$rows = $result->fetch_all(MYSQLI_ASSOC);
					return (object)array('rows' => $rows, 'row' => $rows ? $rows[0] : array());
				}
			});
			$real = new ModelToolSalesExport($registry);
			$job = $real->start(3);
			while (!$job['complete']) { $job = $real->step(3, $job['id']); }
			foreach ($expected as $table => $unused) {
				$file = $real->download(3, $job['id'], $table);
				fread($file['handle'], 3);
				fgetcsv($file['handle'], 0, ';', '"', '');
				$source = $connection->query('SELECT * FROM `fixture_' . $table . '` ORDER BY `' . $table . '_id`');
				while ($row = $source->fetch_assoc()) {
					check(array_map('decodeCell', fgetcsv($file['handle'], 0, ';', '"', '')) === array_values($row), 'Real database CSV mismatch');
				}
				check(fgetcsv($file['handle'], 0, ';', '"', '') === false, 'Extra real database rows');
				fclose($file['handle']); fclose($file['lock']);
			}
			$real->delete(3, $job['id']);
			echo "PASS: MariaDB export of all eight tables matches source values, including UTF-8, decimals, NULL and timestamps.\n";
		} finally {
			$connection->query('DROP DATABASE `' . $database . '`');
			$connection->close();
		}
	}
} finally {
	foreach (glob($root . '/*') ?: array() as $directory) {
		foreach (glob($directory . '/*') ?: array() as $file) { unlink($file); }
		rmdir($directory);
	}
	if (is_dir($root)) { rmdir($root); }
}
