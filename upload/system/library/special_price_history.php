<?php
// Private store records in the shop's base currency, including tax. The public
// archive expires after 15 days and cannot establish a complete 30-day minimum.
class SpecialPriceHistory {
	private $path;
	private $data;
	private static $snapshots = array();

	public function __construct($store, $group) {
		$this->path = rtrim(DIR_DOWNLOAD, '/\\') . '/sale-history-' . (int)$store . '-' . (int)$group . '.json';
		if (!isset(self::$snapshots[$this->path])) { self::$snapshots[$this->path] = $this->read(); }
		$this->data = self::$snapshots[$this->path];
	}

	private function read() {
		if (!is_file($this->path)) { return array('items' => array(), 'confirmed' => array()); }
		$data = json_decode(file_get_contents($this->path), true);
		if (!isset($data['items'], $data['confirmed'])) { throw new RuntimeException('Evidencija prodajnih cijena nije čitljiva.'); }
		return $data;
	}

	private function update($callback) {
		$lock = fopen($this->path . '.lock', 'c');
		if (!$lock || !flock($lock, LOCK_EX)) { throw new RuntimeException('Nije moguće zaključati evidenciju cijena.'); }
		try {
			$this->data = $this->read();
			if (!$callback()) { self::$snapshots[$this->path] = $this->data; return; }
			$tmp = tempnam(dirname($this->path), '.sale-');
			try {
				$json = json_encode($this->data);
				if ($json === false || $tmp === false || file_put_contents($tmp, $json) !== strlen($json) || !rename($tmp, $this->path)) {
					throw new RuntimeException('Nije moguće spremiti evidenciju cijena.');
				}
				self::$snapshots[$this->path] = $this->data;
			} finally {
				if ($tmp && is_file($tmp)) { unlink($tmp); }
			}
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	public static function campaign($net, $start) {
		return number_format($net, 4, '.', '') . '/' . ($start && $start !== '0000-00-00' ? $start : 'open');
	}

	private static function cutoff($start) {
		$date = new DateTime('@' . $start);
		$date->setTimezone(new DateTimeZone('Europe/Zagreb'));
		return $date->modify('-30 days')->getTimestamp();
	}

	// Include the price carried into the window and every subsequent change.
	// The first price of the promotion itself is never a reference for it.
	private function minimum($id, $start, $complete) {
		if (empty($this->data['items'][$id]['points'])) { return null; }
		$from = self::cutoff($start);
		if ($complete && (!isset($this->data['since']) || $this->data['since'] > $from)) { return null; }
		$before = null;
		$prices = array();
		foreach ($this->data['items'][$id]['points'] as $point) {
			if ($point[0] >= $start) { break; }
			if ($point[0] <= $from) { $before = $point[1]; }
			elseif ($point[1] !== null) { $prices[] = $point[1]; }
		}
		if ($before !== null) { $prices[] = $before; }
		return $prices ? min($prices) : null;
	}

	private function reference($id, $key, $start) {
		$item = isset($this->data['items'][$id]) ? $this->data['items'][$id] : array();
		if (!empty($item['sale']['eligible']) && $item['sale']['key'] === $key) {
			if ($item['sale']['reference'] !== null) { return $item['sale']['reference']; }
			$start = $item['sale']['start'];
		}
		$observed = $this->minimum($id, $start, true);
		if ($observed !== null && isset($this->data['last']) && $this->data['last'] >= $start - 300) { return $observed; }
		if (!isset($this->data['confirmed'][$id][$key])) { return null; }
		$known = $this->minimum($id, $start, false);
		$confirmed = $this->data['confirmed'][$id][$key];
		return $known === null ? $confirmed : min($known, $confirmed);
	}

	public function confirmation($id, $key) {
		return isset($this->data['confirmed'][$id][$key]) ? $this->data['confirmed'][$id][$key] : '';
	}

	public function confirm($id, $key, $gross) {
		$this->update(function () use ($id, $key, $gross) {
			if ($this->confirmation($id, $key) !== '' && (float)$this->confirmation($id, $key) === (float)$gross) { return false; }
			$this->data['confirmed'][$id][$key] = (float)$gross;
			if (isset($this->data['items'][$id]['sale']) && $this->data['items'][$id]['sale']['key'] === $key) {
				$this->data['items'][$id]['sale']['reference'] = null;
				$this->data['items'][$id]['sale']['eligible'] = false;
			}
			return true;
		});
	}

	public function activeReference($id, $key) {
		$sale = isset($this->data['items'][$id]['sale']) ? $this->data['items'][$id]['sale'] : null;
		return $sale && $sale['key'] === $key && !empty($sale['eligible']) ? $sale['reference'] : null;
	}

	// A Special entered by import or quick edit is made public only once its
	// exact price and campaign start have a verified comparison price.
	public static function table($config) {
		$table = DB_PREFIX . 'product_special';
		if (!$config->get('module_anchor_price_status')) { return $table; }
		$group = (int)$config->get('config_customer_group_id');
		$history = new self($config->get('config_store_id'), $group);
		$conditions = array();
		foreach ($history->data['items'] as $id => $item) {
			if (empty($item['sale']['eligible'])) { continue; }
			list($price, $start) = explode('/', $item['sale']['key']);
			$start = $start === 'open' ? '0000-00-00' : $start;
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $start)) { continue; }
			$conditions[] = "(product_id = " . (int)$id . " AND price = " . number_format($price, 4, '.', '') . " AND date_start = '" . $start . "')";
		}
		$where = $conditions ? "customer_group_id = " . $group . " AND (" . implode(' OR ', $conditions) . ")" : '1 = 0';
		return "(SELECT * FROM `" . $table . "` WHERE " . $where . ")";
	}

	public function observe(array $offers, $now) {
		$catalog_changed = false;
		$this->update(function () use ($offers, $now, &$catalog_changed) {
			$last = isset($this->data['last']) ? $this->data['last'] : null;
			if ($last === null || $now - $last > 300) { $this->data['since'] = $now; }
			$changed = false;
			$cutoff = self::cutoff($now);
			foreach (array_unique(array_merge(array_keys($offers), array_keys($this->data['items']))) as $id) {
				$offer = isset($offers[$id]) ? $offers[$id] : array('price' => null, 'key' => null);
				if (!isset($this->data['items'][$id])) { $this->data['items'][$id] = array('points' => array()); }
				$item = &$this->data['items'][$id];
				$old_sale = !empty($item['sale']['eligible']) ? $item['sale']['key'] : null;
				if (isset($item['sale']) && $offer['key'] !== $item['sale']['key']) {
					unset($this->data['confirmed'][$id][$item['sale']['key']], $item['sale']);
					$changed = true;
				}
				if ($offer['key'] !== null && empty($item['sale']['eligible'])) {
					$start = $now;
					$reference = $this->reference($id, $offer['key'], $start);
					$item['sale'] = array('key' => $offer['key'], 'start' => $start, 'reference' => $reference);
					$changed = true;
				}
				$applied = $offer['price'];
				if ($offer['key'] !== null) {
					$item['sale']['eligible'] = $item['sale']['reference'] !== null && (float)$applied < $item['sale']['reference'];
					if (!$item['sale']['eligible']) { $applied = $offer['regular']; }
				}
				$point = end($item['points']);
				if (!$point || $point[1] !== $applied) {
					$item['points'][] = array($now, $applied);
					$catalog_changed = true;
					$changed = true;
				}
				if ($old_sale !== (!empty($item['sale']['eligible']) ? $item['sale']['key'] : null)) { $catalog_changed = true; }
				$points = &$item['points'];
				while (isset($points[1]) && $points[1][0] < $cutoff) { array_shift($points); }
				unset($points, $item);
			}
			$this->data['last'] = $now;
			return $changed || $last !== $now;
		});
		return $catalog_changed;
	}
}
