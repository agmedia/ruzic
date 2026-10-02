<?php
/**
 * Isolated archived CSV/XML correction checks. No OpenCart config, DB or files are
 * loaded or modified; fixtures and output live only in memory.
 * Run: php tests/anchor_price_archive_corrections.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
	if (!(error_reporting() & $severity)) {
		return false;
	}
	throw new ErrorException($message, 0, $severity, $file, $line);
});

require dirname(__DIR__) . '/upload/system/library/anchor_price_unit.php';
require dirname(__DIR__) . '/upload/system/library/anchor_price_archive.php';

$checks = 0;
function archiveCheck($condition, $message) {
	global $checks;
	$checks++;
	if (!$condition) {
		throw new RuntimeException('FAIL: ' . $message);
	}
}

function archiveInvalid($callback, $message) {
	try {
		$callback();
	} catch (Exception $exception) {
		archiveCheck(true, $message);
		return;
	}
	archiveCheck(false, $message);
}

function archiveCsvEncode(array $rows) {
	$stream = fopen('php://temp', 'w+b');
	fwrite($stream, "\xEF\xBB\xBF");
	if ($rows) {
		fputcsv($stream, array_keys($rows[0]), ';', '"', '\\');
		foreach ($rows as $row) {
			fputcsv($stream, array_values($row), ';', '"', '\\');
		}
	}
	rewind($stream);
	$result = stream_get_contents($stream);
	fclose($stream);
	return $result;
}

function archiveCsvDecode($contents) {
	$stream = fopen('php://temp', 'w+b');
	fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $contents));
	rewind($stream);
	$headers = fgetcsv($stream, 0, ';', '"', '\\');
	archiveCheck(is_array($headers), 'corrected CSV has headers');
	archiveCheck(count(array_unique($headers)) === count($headers), 'corrected CSV has unique headers');
	$rows = array();
	while (($row = fgetcsv($stream, 0, ';', '"', '\\')) !== false) {
		archiveCheck(count($row) === count($headers), 'corrected CSV row matches header width');
		$rows[] = array_combine($headers, $row);
	}
	fclose($stream);
	return $rows;
}

function archiveFixtures($modern = false) {
	$rows = array();
	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
		. '<priceList brand="OPGRUZIC" location="WEB" generatedAt="2026-09-30T15:34:44+02:00" currency="EUR">' . "\n";
	foreach (array(
		array(775, 'Gala; paket "5 kg" & voće', 'kg', 5, '11,00', '9,50', '11,00', 'true', '9895', 'Nije više dostupno', 'Akcija'),
		array(771, 'Sok od jabuka 3 litre', 'l', 3, '12,00', '10,00', '10,00', 'false', '0', 'Nije više dostupno', 'Popust')
	) as $product) {
		$row = array(
			'Prodajni kanal' => 'Web trgovina',
			'ID proizvoda' => (string)$product[0],
			'Naziv proizvoda' => $product[1],
			'Šifra/model' => 'OPG-' . $product[0],
			'SKU' => 'SKU-' . $product[0],
			'Marka/proizvođač' => 'OPG Ružić',
			'Jedinica mjere' => $modern ? $product[2] : 'kom'
		);
		if ($modern) {
			$row['Količina pakiranja'] = (string)$product[3];
		}
		$row['Cijena po jedinici (EUR)'] = $modern ? number_format((float)str_replace(',', '.', $product[5]) / $product[3], 2, ',', '') : $product[5];
		$row['Redovna maloprodajna cijena (EUR)'] = $product[4];
		$row['Aktualna maloprodajna cijena (EUR)'] = $product[5];
		$row['Poseban oblik prodaje'] = 'DA';
		$row['Naziv posebnog oblika prodaje'] = $product[10];
		$row['Aktualna akcijska cijena (EUR)'] = $product[5];
		$row['Sidrena cijena (EUR)'] = $product[6];
		if ($modern) {
			$row['Sidrena cijena po jedinici (EUR)'] = number_format((float)str_replace(',', '.', $product[6]) / $product[3], 2, ',', '');
		}
		$row['Datum sidrene cijene'] = $modern ? '2025-05-02' : '2026-09-30';
		$row['Barkod'] = '';
		$row['Dostupnost'] = $product[7] === 'true' ? 'Dostupno' : 'Nije dostupno';
		$row['Količina'] = $product[8];
		$row['Status zalihe'] = $product[9];
		$row['Valuta'] = 'EUR';
		$rows[] = $row;
		$fields = array(
			'productId' => $row['ID proizvoda'], 'name' => $row['Naziv proizvoda'],
			'model' => $row['Šifra/model'], 'sku' => $row['SKU'],
			'manufacturer' => $row['Marka/proizvođač'], 'unit' => $row['Jedinica mjere']
		);
		if ($modern) {
			$fields['packageQuantity'] = $row['Količina pakiranja'];
			$fields['unitPrice'] = str_replace(',', '.', $row['Cijena po jedinici (EUR)']);
			$fields['anchorUnitPrice'] = str_replace(',', '.', $row['Sidrena cijena po jedinici (EUR)']);
		}
		$fields += array(
			'regularPrice' => str_replace(',', '.', $product[4]),
			'currentPrice' => str_replace(',', '.', $product[5]),
			'specialPrice' => str_replace(',', '.', $product[5]),
			'anchorPrice' => str_replace(',', '.', $product[6]),
			'anchorDate' => $row['Datum sidrene cijene'], 'barcode' => '',
			'available' => $product[7], 'quantity' => $product[8],
			'stockStatus' => $product[9], 'currency' => 'EUR'
		);
		$xml .= "  <product>\n";
		foreach ($fields as $tag => $value) {
			$xml .= '    <' . $tag . '>' . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</' . $tag . ">\n";
		}
		$xml .= "  </product>\n";
	}
	return array('rows' => $rows, 'csv' => archiveCsvEncode($rows), 'xml' => $xml . "</priceList>\n");
}

function archiveCorrect($fixture, $measures, $dates, $metadata) {
	return AnchorPriceArchive::correct($fixture['csv'], $fixture['xml'], $measures, $dates, $metadata);
}

$measures = array(
	775 => array('unit' => 'kg', 'package_quantity' => 5),
	771 => array('unit' => 'l', 'package_quantity' => 3)
);
$dates = array(
	775 => array('reference_date' => '2025-05-02', 'gross_price' => 11),
	771 => array('reference_date' => '2025-05-02', 'gross_price' => 10)
);
$metadata = array('source_publication_id' => 1, 'source_published_at' => '2026-09-30 15:34:44', 'corrected_at' => '2026-10-02T14:00:00+02:00');

foreach (array(false, true) as $modern) {
	$fixture = archiveFixtures($modern);
	$csv_before = $fixture['csv'];
	$xml_before = $fixture['xml'];
	$result = archiveCorrect($fixture, $measures, $dates, $metadata);
	archiveCheck($fixture['csv'] === $csv_before && $fixture['xml'] === $xml_before, 'source fixtures remain byte-for-byte unchanged');
	archiveCheck(substr($result['csv'], 0, 3) === "\xEF\xBB\xBF", 'corrected CSV retains UTF-8 byte-order mark');
	archiveCheck($result['product_count'] === 2, 'correction retains every archived product');
	$rows = archiveCsvDecode($result['csv']);
	$xml = simplexml_load_string($result['xml']);
	archiveCheck($xml !== false && count($xml->product) === 2, 'corrected XML has every archived product');
	foreach (array('brand', 'location', 'generatedAt', 'currency') as $attribute) {
		$source = simplexml_load_string($fixture['xml']);
		archiveCheck((string)$xml[$attribute] === (string)$source[$attribute], 'source XML metadata retained: ' . $attribute);
	}
	archiveCheck((string)$xml['sourcePublicationId'] === '1', 'XML records source publication ID');
	archiveCheck((string)$xml['sourcePublishedAt'] === $metadata['source_published_at'], 'XML records original publication time');
	archiveCheck((string)$xml['correctedAt'] === $metadata['corrected_at'], 'XML records correction time separately');
	foreach ($fixture['rows'] as $index => $old_row) {
		$product_id = (int)$old_row['ID proizvoda'];
		$measure = $measures[$product_id];
		$node = $xml->product[$index];
		$current_unit = number_format((float)str_replace(',', '.', $old_row['Aktualna maloprodajna cijena (EUR)']) / $measure['package_quantity'], 2, '.', '');
		$anchor_unit = number_format((float)str_replace(',', '.', $old_row['Sidrena cijena (EUR)']) / $measure['package_quantity'], 2, '.', '');
		foreach ($old_row as $column => $value) {
			if (!in_array($column, array('Jedinica mjere', 'Količina pakiranja', 'Cijena po jedinici (EUR)', 'Sidrena cijena po jedinici (EUR)', 'Datum sidrene cijene'), true)) {
				archiveCheck($rows[$index][$column] === $value, 'archived field preserved: ' . $column);
			}
		}
		archiveCheck($rows[$index]['Jedinica mjere'] === $measure['unit'], 'corrected CSV kg/l measurement');
		archiveCheck((float)str_replace(',', '.', $rows[$index]['Količina pakiranja']) === (float)$measure['package_quantity'], 'corrected CSV package quantity');
		archiveCheck(str_replace(',', '.', $rows[$index]['Cijena po jedinici (EUR)']) === $current_unit, 'CSV unit price derives from archived current price');
		archiveCheck(str_replace(',', '.', $rows[$index]['Sidrena cijena po jedinici (EUR)']) === $anchor_unit, 'CSV anchor unit price derives from archived anchor price');
		archiveCheck($rows[$index]['Datum sidrene cijene'] === '2025-05-02', 'CSV corrected to explicit confirmed historical date');
		archiveCheck($rows[$index]['Izvorna objava'] === '1', 'CSV records source publication ID');
		archiveCheck($rows[$index]['Vrijeme izvorne objave'] === $metadata['source_published_at'], 'CSV records original publication time');
		archiveCheck($rows[$index]['Vrijeme ispravka'] === $metadata['corrected_at'], 'CSV records separate correction time');
		archiveCheck((string)$node->unit === $measure['unit'], 'corrected XML kg/l measurement');
		archiveCheck((float)$node->packageQuantity === (float)$measure['package_quantity'], 'corrected XML package quantity');
		archiveCheck((string)$node->unitPrice === $current_unit, 'XML unit price agrees with CSV');
		archiveCheck((string)$node->anchorUnitPrice === $anchor_unit, 'XML anchor unit price agrees with CSV');
		archiveCheck((string)$node->anchorDate === '2025-05-02', 'XML corrected historical date');
		foreach (array('regularPrice', 'currentPrice', 'specialPrice', 'anchorPrice', 'available', 'quantity', 'stockStatus', 'currency', 'name', 'model', 'sku', 'manufacturer', 'barcode') as $tag) {
			archiveCheck((string)$node->$tag === (string)$source->product[$index]->$tag, 'archived XML field preserved: ' . $tag);
		}
	}
}

// Current product settings must not replace valid measures already recorded in
// newer archived publications, or falsely reject their unchanged historic date.
$modern_fixture = archiveFixtures(true);
$changed_measures = $measures;
$changed_measures[775] = array('unit' => 'l', 'package_quantity' => 1);
$changed_dates = $dates;
$changed_dates[775]['gross_price'] = 99;
$modern_result = archiveCorrect($modern_fixture, $changed_measures, $changed_dates, $metadata);
$modern_rows = archiveCsvDecode($modern_result['csv']);
archiveCheck($modern_rows[0]['Jedinica mjere'] === 'kg', 'valid archived unit wins over current product unit');
archiveCheck((float)$modern_rows[0]['Količina pakiranja'] === 5.0, 'valid archived package quantity wins over current setting');
archiveCheck(str_replace(',', '.', $modern_rows[0]['Cijena po jedinici (EUR)']) === '1.90', 'archived package measurement is used to calculate unit price');
archiveCheck($modern_rows[0]['Sidrena cijena (EUR)'] === '11,00', 'already historical anchor amount is not replaced by current confirmed price');
$already_corrected = array('csv' => $modern_result['csv'], 'xml' => $modern_result['xml']);
archiveInvalid(function () use ($already_corrected, $measures, $dates, $metadata) { archiveCorrect($already_corrected, $measures, $dates, $metadata); }, 'corrected copies are not corrected recursively');

$fixture = archiveFixtures();
$missing_measure = $measures;
unset($missing_measure[771]);
archiveInvalid(function () use ($fixture, $missing_measure, $dates, $metadata) { archiveCorrect($fixture, $missing_measure, $dates, $metadata); }, 'missing package measure aborts correction');
$missing_date = $dates;
unset($missing_date[771]);
archiveInvalid(function () use ($fixture, $measures, $missing_date, $metadata) { archiveCorrect($fixture, $measures, $missing_date, $metadata); }, 'missing confirmed historical date aborts correction');
foreach (array(0, -1, 'not-a-number') as $quantity) {
	$invalid = $measures;
	$invalid[775]['package_quantity'] = $quantity;
	archiveInvalid(function () use ($fixture, $invalid, $dates, $metadata) { archiveCorrect($fixture, $invalid, $dates, $metadata); }, 'invalid package quantity aborts correction');
}
$invalid = $measures;
$invalid[775]['unit'] = 'kom';
archiveInvalid(function () use ($fixture, $invalid, $dates, $metadata) { archiveCorrect($fixture, $invalid, $dates, $metadata); }, 'unsupported unit aborts correction');
$mismatched_dates = $dates;
$mismatched_dates[775]['gross_price'] = 12;
archiveInvalid(function () use ($fixture, $measures, $mismatched_dates, $metadata) { archiveCorrect($fixture, $measures, $mismatched_dates, $metadata); }, 'historical date cannot be assigned to a different archived anchor amount');
$invalid_dates = $dates;
$invalid_dates[775]['reference_date'] = '2025-02-30';
archiveInvalid(function () use ($fixture, $measures, $invalid_dates, $metadata) { archiveCorrect($fixture, $measures, $invalid_dates, $metadata); }, 'impossible reference date aborts correction');

foreach (array(
	array('<productId>775</productId>', '<productId>804</productId>', 'product IDs disagree'),
	array('<currentPrice>9.50</currentPrice>', '<currentPrice>8.50</currentPrice>', 'current prices disagree'),
	array('<regularPrice>11.00</regularPrice>', '<regularPrice>12.00</regularPrice>', 'regular prices disagree'),
	array('<anchorPrice>11.00</anchorPrice>', '<anchorPrice>12.00</anchorPrice>', 'anchor prices disagree'),
	array('<specialPrice>9.50</specialPrice>', '<specialPrice>8.50</specialPrice>', 'special prices disagree'),
	array('<quantity>9895</quantity>', '<quantity>9894</quantity>', 'stock quantities disagree'),
	array('<available>true</available>', '<available>false</available>', 'availability disagrees'),
	array('<stockStatus>Nije više dostupno</stockStatus>', '<stockStatus>Dostupno</stockStatus>', 'stock status text disagrees'),
	array('<productId>771</productId>', '<productId>775</productId>', 'duplicate product IDs')
) as $change) {
	$invalid = $fixture;
	$invalid['xml'] = str_replace($change[0], $change[1], $fixture['xml']);
	archiveInvalid(function () use ($invalid, $measures, $dates, $metadata) { archiveCorrect($invalid, $measures, $dates, $metadata); }, 'reject CSV/XML mismatch: ' . $change[2]);
}
$invalid = $fixture;
$invalid['rows'][] = $invalid['rows'][0];
$invalid['csv'] = archiveCsvEncode($invalid['rows']);
archiveInvalid(function () use ($invalid, $measures, $dates, $metadata) { archiveCorrect($invalid, $measures, $dates, $metadata); }, 'duplicate CSV product IDs abort correction');
$invalid = $fixture;
$invalid['xml'] = '<?xml version="1.0"?><!DOCTYPE priceList [<!ENTITY secret SYSTEM "file:///nonexistent-sensitive-fixture">]>' . substr($fixture['xml'], strpos($fixture['xml'], '<priceList'));
archiveInvalid(function () use ($invalid, $measures, $dates, $metadata) { archiveCorrect($invalid, $measures, $dates, $metadata); }, 'DOCTYPE/entity XML is rejected without resolution');
$invalid = $fixture;
$invalid['xml'] = '<priceList><product></priceList>';
archiveInvalid(function () use ($invalid, $measures, $dates, $metadata) { archiveCorrect($invalid, $measures, $dates, $metadata); }, 'malformed XML aborts correction');
$invalid = $fixture;
$invalid['csv'] = "\xEF\xBB\xBF";
archiveInvalid(function () use ($invalid, $measures, $dates, $metadata) { archiveCorrect($invalid, $measures, $dates, $metadata); }, 'empty CSV aborts correction');

echo 'OK: ' . $checks . " isolated archive-correction checks.\n";
