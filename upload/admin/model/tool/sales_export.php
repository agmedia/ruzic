<?php
/** Resumable, read-only export. Each request reads at most one indexed batch. */
class ModelToolSalesExport extends Model {
	const BATCH_SIZE = 100;
	const MAX_AGE = 86400;

	private $tables = array(
		'order' => 'order_id',
		'order_product' => 'order_product_id',
		'order_option' => 'order_option_id',
		'order_total' => 'order_total_id',
		'order_history' => 'order_history_id',
		'customer' => 'customer_id',
		'address' => 'address_id',
		'product' => 'product_id'
	);

	public function start($user_id) {
		list($directory, $lock) = $this->lock($user_id);
		try {
			$state = $this->readState($directory);
			// A retried start request must not replace a running or completed export.
			if ($state) {
				return $this->summary($state);
			}
			$this->prepareConnection();
			$state = array(
				'id' => bin2hex(random_bytes(16)), 'created' => time(),
				'table_index' => 0, 'cursor' => -1, 'table_started' => false,
				'rows' => 0, 'counts' => array(), 'maximum' => array(),
				'bytes' => 0, 'sizes' => array(), 'columns' => array(), 'complete' => false
			);
			foreach ($this->tables as $table => $key) {
				// MAX on the primary key avoids a full COUNT or OFFSET scan.
				$query = $this->db->query('SELECT MAX(' . $this->identifier($key) . ') AS maximum FROM ' . $this->identifier(DB_PREFIX . $table));
				$state['maximum'][$table] = $query->row['maximum'] === null ? -1 : (int)$query->row['maximum'];
				$state['counts'][$table] = 0;
				$state['sizes'][$table] = 0;
			}
			$this->saveState($directory, $state);
			return $this->summary($state);
		} finally {
			fclose($lock);
		}
	}

	public function status($user_id) {
		list($directory, $lock) = $this->lock($user_id);
		try {
			$state = $this->readState($directory);
			return $state ? $this->summary($state) : null;
		} finally {
			fclose($lock);
		}
	}

	public function step($user_id, $job_id) {
		list($directory, $lock) = $this->lock($user_id);
		$handle = null;
		try {
			$state = $this->requireJob($directory, $job_id);
			if ($state['complete']) {
				return $this->summary($state);
			}
			$this->prepareConnection();
			$tables = array_keys($this->tables);
			$table = $tables[$state['table_index']];
			$key = $this->tables[$table];
			$name = $this->identifier(DB_PREFIX . $table);
			$handle = fopen($directory . '/' . $table . '.csv', 'c+b');
			if (!$handle || fstat($handle)['size'] < $state['sizes'][$table] || !ftruncate($handle, $state['sizes'][$table]) || fseek($handle, $state['sizes'][$table]) !== 0) {
				throw new RuntimeException('error_storage');
			}
			// Truncating to the last committed offset makes interrupted batches safe to retry.
			if (!$state['table_started']) {
				$schema = $this->db->query('SHOW COLUMNS FROM ' . $name);
				$state['columns'] = array_column($schema->rows, 'Field');
				$this->write($handle, "\xEF\xBB\xBF");
				$this->writeCsv($handle, $state['columns']);
				$state['table_started'] = true;
			}
			$query = $this->db->query('SELECT * FROM ' . $name . ' WHERE ' . $this->identifier($key) . ' > ' . (int)$state['cursor'] . ' AND ' . $this->identifier($key) . ' <= ' . (int)$state['maximum'][$table] . ' ORDER BY ' . $this->identifier($key) . ' ASC LIMIT ' . self::BATCH_SIZE);
			$started = microtime(true);
			$processed = 0;
			foreach ($query->rows as $row) {
				$values = array();
				if (array_keys($row) !== $state['columns']) {
					throw new RuntimeException('error_schema');
				}
				foreach ($row as $value) {
					$values[] = $this->csvValue($value);
				}
				$this->writeCsv($handle, $values);
				$state['cursor'] = (int)$row[$key];
				$state['rows']++;
				$state['counts'][$table]++;
				$processed++;
				if (microtime(true) - $started >= 2) {
					break;
				}
			}
			if ($processed === count($query->rows) && (count($query->rows) < self::BATCH_SIZE || $state['cursor'] >= $state['maximum'][$table])) {
				$state['table_index']++;
				$state['cursor'] = -1;
				$state['table_started'] = false;
				if ($state['table_index'] === count($tables)) {
					$state['complete'] = true;
				}
			}
			if (!fflush($handle)) {
				throw new RuntimeException('error_storage');
			}
			$state['sizes'][$table] = ftell($handle);
			$state['bytes'] = array_sum($state['sizes']);
			$this->saveState($directory, $state);
			return $this->summary($state);
		} finally {
			if (is_resource($handle)) {
				fclose($handle);
			}
			fclose($lock);
		}
	}

	public function delete($user_id, $job_id) {
		list($directory, $lock) = $this->lock($user_id);
		try {
			$state = $this->readState($directory);
			if ($state) {
				$this->requireJob($directory, $job_id);
				$this->removeFiles($directory);
			}
		} finally {
			fclose($lock);
		}
	}

	/** Caller owns both handles and must close them after streaming. */
	public function download($user_id, $job_id, $table) {
		list($directory, $lock) = $this->lock($user_id);
		try {
			$state = $this->requireJob($directory, $job_id);
			if (!is_string($table) || !isset($this->tables[$table])) {
				throw new RuntimeException('error_job');
			}
			if (!$state['complete']) {
				throw new RuntimeException('error_incomplete');
			}
			$handle = fopen($directory . '/' . $table . '.csv', 'rb');
			if (!$handle || fstat($handle)['size'] !== $state['sizes'][$table]) {
				if (is_resource($handle)) {
					fclose($handle);
				}
				throw new RuntimeException('error_storage');
			}
			return array('handle' => $handle, 'lock' => $lock, 'size' => $state['sizes'][$table], 'filename' => $table . '-' . gmdate('Y-m-d_H-i-s', $state['created']) . '.csv');
		} catch (Throwable $e) {
			fclose($lock);
			throw $e;
		}
	}

	private function prepareConnection() {
		$this->db->query('SET NAMES utf8mb4');
		$this->db->query("SET time_zone = '+00:00'");
	}

	private function lock($user_id) {
		if ((int)$user_id < 1) {
			throw new RuntimeException('error_permission');
		}
		// Kept outside the web root, never in a publicly downloadable shop directory.
		$root = rtrim(sys_get_temp_dir(), '/\\') . '/opgruzic-sales-' . substr(hash('sha256', DIR_APPLICATION), 0, 20);
		$directory = $root . '/' . (int)$user_id;
		foreach (array($root, $directory) as $path) {
			if (is_link($path) || (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path))) {
				throw new RuntimeException('error_storage');
			}
			$real = realpath($path) . '/';
			$web_roots = array(realpath(DIR_APPLICATION . '..'));
			if (!empty($_SERVER['DOCUMENT_ROOT'])) {
				$web_roots[] = realpath($_SERVER['DOCUMENT_ROOT']);
			}
			foreach ($web_roots as $web_root) {
				if ($web_root && strpos($real, rtrim($web_root, '/') . '/') === 0) {
					throw new RuntimeException('error_storage');
				}
			}
			if (!chmod($path, 0700)) {
				throw new RuntimeException('error_storage');
			}
		}
		$lock = fopen($directory . '/lock', 'c');
		if (!$lock) {
			throw new RuntimeException('error_storage');
		}
		if (!flock($lock, LOCK_EX | LOCK_NB)) {
			fclose($lock);
			throw new RuntimeException('error_busy');
		}
		return array($directory, $lock);
	}

	private function readState($directory) {
		if (!is_file($directory . '/state.json')) {
			return null;
		}
		$state = json_decode(file_get_contents($directory . '/state.json'), true);
		if (!is_array($state) || !isset($state['id'], $state['created'], $state['bytes'])) {
			throw new RuntimeException('error_storage');
		}
		if (time() - $state['created'] >= self::MAX_AGE) {
			$this->removeFiles($directory);
			return null;
		}
		return $state;
	}

	private function requireJob($directory, $job_id) {
		$state = $this->readState($directory);
		if (!$state || !is_string($job_id) || !hash_equals($state['id'], $job_id)) {
			throw new RuntimeException('error_job');
		}
		return $state;
	}

	private function saveState($directory, $state) {
		$json = json_encode($state);
		if ($json === false || file_put_contents($directory . '/state.tmp', $json) !== strlen($json) || !rename($directory . '/state.tmp', $directory . '/state.json')) {
			throw new RuntimeException('error_storage');
		}
	}

	private function removeFiles($directory) {
		$files = array('state.tmp', 'state.json');
		foreach (array_keys($this->tables) as $table) {
			$files[] = $table . '.csv';
		}
		foreach ($files as $file) {
			if (is_file($directory . '/' . $file) && !unlink($directory . '/' . $file)) {
				throw new RuntimeException('error_storage');
			}
		}
	}

	private function summary($state) {
		$tables = array_keys($this->tables);
		return array(
			'id' => $state['id'], 'created' => gmdate('Y-m-d H:i:s', $state['created']) . ' UTC',
			'complete' => $state['complete'], 'rows' => $state['rows'], 'bytes' => $state['bytes'],
			'counts' => $state['counts'], 'tables_done' => $state['table_index'],
			'tables_total' => count($tables), 'table' => $state['complete'] ? '' : $tables[$state['table_index']]
		);
	}

	private function identifier($value) {
		return '`' . str_replace('`', '``', $value) . '`';
	}

	private function csvValue($value) {
		if ($value === null) {
			return '\\N';
		}
		$value = (string)$value;
		// Reversible escaping: distinguish NULL and neutralize spreadsheet formulas.
		if (substr($value, 0, 1) === '\\') {
			return '\\' . $value;
		}
		if (substr($value, 0, 1) === "'" || (!is_numeric($value) && preg_match('/^(?:[\x00-\x20]*[=+@-]|[\t\r\n])/', $value))) {
			return "'" . $value;
		}
		return $value;
	}

	private function writeCsv($handle, $values) {
		// Quote every cell and handle short disk writes without committing partial rows.
		$quoted = array();
		foreach ($values as $value) {
			$quoted[] = '"' . str_replace('"', '""', $value) . '"';
		}
		$this->write($handle, implode(';', $quoted) . "\r\n");
	}

	private function write($handle, $text) {
		$length = strlen($text);
		$offset = 0;
		while ($offset < $length) {
			$written = fwrite($handle, substr($text, $offset));
			if ($written === false || $written === 0) {
				throw new RuntimeException('error_storage');
			}
			$offset += $written;
		}
	}
}
