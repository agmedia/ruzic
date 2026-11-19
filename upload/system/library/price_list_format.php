<?php
require_once __DIR__ . '/anchor_price_unit.php';

class PriceListFormat {
	const MERCHANT = 'Voćarstvo Ružić d.o.o.';
	const DEFAULT_OIB = '96162140350';
	const SCOPE = 'ZA_SVA_PRODAJNA_MJESTA';

	private static $columns = array(
		'name' => 'Naziv robe',
		'code' => 'Šifra robe',
		'brand' => 'Marka robe',
		'netQuantity' => 'Neto količina',
		'unit' => 'Jedinica mjere',
		'retailPrice' => 'Maloprodajna cijena (EUR) i oznaka posebnog oblika prodaje',
		'unitPrice' => 'Cijena za jedinicu mjere (EUR)',
		'barcode' => 'Barkod',
		'ean' => 'EAN barkod',
		'basePrice' => 'Bazna cijena (EUR)'
	);

	public static function oib($setting) {
		$oib = $setting === null || $setting === '' ? self::DEFAULT_OIB : trim($setting);
		if (!preg_match('/^[0-9]{11}$/D', $oib)) {
			throw new InvalidArgumentException('OIB trgovca mora sadržavati 11 znamenki.');
		}
		return $oib;
	}

	public static function filename($oib, $sequence, DateTime $now) {
		return self::oib($oib) . '_' . self::SCOPE . '_ROBA_' . str_pad($sequence, 6, '0', STR_PAD_LEFT) . '_' . $now->format('Ymd_Hi');
	}

	public static function isCurrentFormat($filename) {
		return (bool)preg_match('/^[0-9]{11}_ZA_SVA_PRODAJNA_MJESTA_ROBA_[0-9]+_[0-9]{8}_[0-9]{4}\.csv$/D', $filename);
	}

	public static function publicFilename(array $publication, $oib, $format) {
		if (self::isCurrentFormat($publication['filename'])) {
			return basename($format === 'xml' ? $publication['xml_filename'] : $publication['filename']);
		}
		$published = new DateTime($publication['published_at'], new DateTimeZone('Europe/Zagreb'));
		return self::filename($oib, (int)$publication['sequence_no'], $published) . '.' . ($format === 'xml' ? 'xml' : 'csv');
	}

	public static function productRow(array $p, $tax) {
		$id = '#' . (int)$p['product_id'] . ' ' . $p['name'];
		if ($p['verification_status'] !== 'confirmed') {
			throw new RuntimeException($id . ' — potvrdite baznu cijenu u modulu Bazne cijene.');
		}
		if (!AnchorPriceUnit::isValid($p['unit'], $p['package_quantity'])) {
			throw new RuntimeException($id . ' — unesite jedinicu mjere i neto količinu pakiranja.');
		}
		if (trim((string)$p['name']) === '') {
			throw new RuntimeException($id . ' — nedostaje naziv robe.');
		}
		$has_special = $p['special'] !== null && $p['special'] !== '';
		$has_discount = $p['discount'] !== null && $p['discount'] !== '';
		$net = $has_special ? $p['special'] : ($has_discount ? $p['discount'] : $p['price']);
		$retail = $tax->calculate((float)$net, (int)$p['tax_class_id'], true);
		$barcode = '';
		foreach (array('upc', 'jan', 'isbn', 'ean') as $field) {
			$value = trim((string)$p[$field]);
			if ($value !== '' && $barcode === '') {
				$barcode = $value;
			}
		}
		$ean = trim((string)$p['ean']);
		if ($ean !== '' && !self::validEan($ean)) {
			// Legacy imports also stored UPC and GTIN-14 in OpenCart's EAN field.
			if (!self::validGtin($ean)) { throw new RuntimeException($id . ' — provjerite EAN barkod.'); }
			$ean = '';
		}
		return array(
			'name' => self::plain($p['name']),
			'code' => self::plain(trim($p['model']) !== '' ? $p['model'] : $p['sku']),
			'brand' => self::plain($p['manufacturer']),
			'netQuantity' => rtrim(rtrim(number_format($p['package_quantity'], 6, '.', ''), '0'), '.'),
			'unit' => $p['unit'],
			'retailPrice' => number_format($retail, 2, '.', ''),
			'sale' => ($has_special || $has_discount) ? 'da' : 'ne',
			'unitPrice' => number_format(AnchorPriceUnit::calculate($retail, $p['package_quantity']), 2, '.', ''),
			'barcode' => $barcode,
			'ean' => $ean,
			'basePrice' => number_format($p['anchor_gross_price'], 2, '.', '')
		);
	}

	private static function validEan($ean) {
		return in_array(strlen($ean), array(8, 13), true) && self::validGtin($ean);
	}

	private static function validGtin($ean) {
		if (!ctype_digit($ean) || !in_array(strlen($ean), array(8, 12, 13, 14), true)) { return false; }
		$sum = 0;
		$weight = 1;
		for ($i = strlen($ean) - 1; $i >= 0; $i--) {
			$sum += (int)$ean[$i] * $weight;
			$weight = 4 - $weight;
		}
		return $sum % 10 === 0;
	}

	private static function plain($value) {
		return trim(html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8'));
	}

	public static function csv(array $rows) {
		$file = fopen('php://temp', 'w+b');
		try {
			fwrite($file, "\xEF\xBB\xBF");
			$escape = PHP_VERSION_ID >= 70400 ? '' : '\\';
			fputcsv($file, array_values(self::$columns), ';', '"', $escape);
			foreach ($rows as $row) {
				$values = array();
				foreach (self::$columns as $key => $label) {
					$value = $row[$key];
					if (in_array($key, array('netQuantity', 'retailPrice', 'unitPrice', 'basePrice'), true)) {
						$value = str_replace('.', ',', $value);
					}
					if ($key === 'retailPrice') { $value .= ' (' . $row['sale'] . ')'; }
					if (preg_match('/^\s*[=+@-]/u', $value)) { $value = "'" . $value; }
					$values[] = $value;
				}
				if (!fputcsv($file, $values, ';', '"', $escape)) { throw new RuntimeException('Pogreška zapisa CSV cjenika.'); }
			}
			rewind($file);
			return stream_get_contents($file);
		} finally {
			fclose($file);
		}
	}

	public static function xml(array $rows, $oib, DateTime $published) {
		$document = new DOMDocument('1.0', 'UTF-8');
		$document->formatOutput = true;
		$root = $document->appendChild($document->createElement('priceList'));
		foreach (array('brand' => self::MERCHANT, 'oib' => self::oib($oib), 'location' => self::SCOPE, 'currency' => 'EUR', 'generatedAt' => $published->format(DateTime::ATOM)) as $key => $value) {
			$root->setAttribute($key, $value);
		}
		foreach ($rows as $row) {
			$product = $root->appendChild($document->createElement('product'));
			foreach (self::$columns as $key => $label) {
				$element = $product->appendChild($document->createElement($key));
				if ($key === 'retailPrice') { $element->setAttribute('sale', $row['sale']); }
				$element->appendChild($document->createTextNode($row[$key]));
			}
		}
		return $document->saveXML();
	}

	// Serve historic amounts and measures from the archived CSV, including corrections.
	public static function legacyRows($file) {
		rewind($file);
		if (fread($file, 3) !== "\xEF\xBB\xBF") { rewind($file); }
		$headers = fgetcsv($file, 0, ';', '"', '\\');
		$required = array('Naziv proizvoda', 'Šifra/model', 'Marka/proizvođač', 'Jedinica mjere', 'Aktualna maloprodajna cijena (EUR)', 'Cijena po jedinici (EUR)', 'Sidrena cijena (EUR)', 'Barkod');
		if (!$headers || array_diff($required, $headers)) { throw new RuntimeException('Nepoznato zaglavlje arhivskog cjenika.'); }
		$rows = array();
		while (($values = fgetcsv($file, 0, ';', '"', '\\')) !== false) {
			if ($values === array(null)) { continue; }
			if (count($values) !== count($headers)) { throw new RuntimeException('Neispravan redak arhivskog cjenika.'); }
			$p = array_combine($headers, $values);
			$barcode = $p['Barkod'];
			$sale = !empty($p['Naziv posebnog oblika prodaje']) ? $p['Naziv posebnog oblika prodaje'] : (isset($p['Poseban oblik prodaje']) ? $p['Poseban oblik prodaje'] : '');
			$rows[] = array(
				'name' => $p['Naziv proizvoda'], 'code' => $p['Šifra/model'], 'brand' => $p['Marka/proizvođač'],
				'netQuantity' => isset($p['Količina pakiranja']) ? str_replace(',', '.', $p['Količina pakiranja']) : '',
				'unit' => $p['Jedinica mjere'], 'retailPrice' => str_replace(',', '.', $p['Aktualna maloprodajna cijena (EUR)']),
				'sale' => in_array(strtolower(trim($sale)), array('', 'ne', 'no', '0'), true) ? 'ne' : 'da', 'unitPrice' => str_replace(',', '.', $p['Cijena po jedinici (EUR)']),
				'barcode' => $barcode, 'ean' => self::validEan($barcode) ? $barcode : '',
				'basePrice' => str_replace(',', '.', $p['Sidrena cijena (EUR)'])
			);
		}
		return $rows;
	}
}
