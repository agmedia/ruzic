<?php
require_once __DIR__ . '/anchor_price_unit.php';

/** Correct historical snapshots without reading today's selling prices or stock. */
class AnchorPriceArchive {
	public static function correct($csv, $xml, array $measures, array $dates, array $metadata) {
		if (strlen($csv) > 25 * 1024 * 1024 || strlen($xml) > 25 * 1024 * 1024) {
			throw new RuntimeException('Arhivski cjenik je prevelik za ispravak.');
		}
		if (empty($metadata['source_publication_id']) || empty($metadata['source_published_at']) || empty($metadata['corrected_at'])) {
			throw new RuntimeException('Nedostaju podaci o izvornoj objavi i ispravku.');
		}
		if (preg_match('/<!\s*(DOCTYPE|ENTITY)\b/i', $xml)) {
			throw new RuntimeException('Arhivski XML ne smije imati DTD ili vanjske entitete.');
		}
		$previous = libxml_use_internal_errors(true);
		$document = new DOMDocument();
		$document->preserveWhiteSpace = false;
		$document->formatOutput = true;
		$valid = $document->loadXML($xml, LIBXML_NONET);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		$root = $document->documentElement;
		if (!$valid || !$root || $root->tagName !== 'priceList' || $root->hasAttribute('sourcePublicationId')) {
			throw new RuntimeException('Neispravan XML ili objava koja je već ispravak.');
		}

		$stream = fopen('php://temp', 'w+b');
		fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $csv));
		rewind($stream);
		$headers = fgetcsv($stream, 0, ';', '"', '\\');
		$required = array('ID proizvoda', 'Jedinica mjere', 'Cijena po jedinici (EUR)', 'Redovna maloprodajna cijena (EUR)', 'Aktualna maloprodajna cijena (EUR)', 'Sidrena cijena (EUR)', 'Datum sidrene cijene', 'Dostupnost', 'Količina', 'Status zalihe');
		if (!$headers || count($headers) !== count(array_unique($headers)) || array_diff($required, $headers)) {
			fclose($stream);
			throw new RuntimeException('Nepoznato ili nepotpuno zaglavlje arhivskog CSV-a.');
		}
		$csv_products = array();
		while (($row = fgetcsv($stream, 0, ';', '"', '\\')) !== false) {
			if ($row === array(null)) { continue; }
			if (count($row) !== count($headers)) {
				fclose($stream);
				throw new RuntimeException('CSV redak nema očekivani broj stupaca.');
			}
			$row = array_combine($headers, $row);
			$id = $row['ID proizvoda'];
			if (!ctype_digit((string)$id) || (int)$id < 1 || isset($csv_products[(int)$id])) {
				fclose($stream);
				throw new RuntimeException('Nepoznat ili ponovljen ID proizvoda u arhivi.');
			}
			$csv_products[(int)$id] = $row;
		}
		fclose($stream);
		if (!$csv_products) { throw new RuntimeException('Arhivski cjenik je prazan.'); }
		$xml_ids = array();
		foreach ($root->childNodes as $node) {
			if (!$node instanceof DOMElement) { continue; }
			if ($node->tagName !== 'product') { throw new RuntimeException('Nepoznata struktura arhivskog XML-a.'); }
			$id_value = self::value($node, 'productId');
			$id = (int)$id_value;
			if (!ctype_digit($id_value) || $id < 1 || isset($xml_ids[$id]) || !isset($csv_products[$id])) {
				throw new RuntimeException('CSV i XML nemaju isti skup proizvoda.');
			}
			$xml_ids[$id] = true;
			$row = $csv_products[$id];
			foreach (array('Redovna maloprodajna cijena (EUR)' => 'regularPrice', 'Aktualna maloprodajna cijena (EUR)' => 'currentPrice', 'Sidrena cijena (EUR)' => 'anchorPrice') as $column => $tag) {
				if (abs(self::number($row[$column]) - self::number(self::value($node, $tag))) > 0.000001) {
					throw new RuntimeException('CSV i XML imaju različite cijene za proizvod ' . $id . '.');
				}
			}
			if (isset($row['Aktualna akcijska cijena (EUR)'])) {
				$special = self::value($node, 'specialPrice', false);
				if (($row['Aktualna akcijska cijena (EUR)'] === '') !== ($special === '')
					|| ($special !== '' && abs(self::number($row['Aktualna akcijska cijena (EUR)']) - self::number($special)) > 0.000001)) {
					throw new RuntimeException('CSV i XML imaju različite akcijske cijene.');
				}
			}
			if ($row['Datum sidrene cijene'] !== self::value($node, 'anchorDate')
				|| $row['Količina'] !== self::value($node, 'quantity')
				|| $row['Status zalihe'] !== self::value($node, 'stockStatus')
				|| $row['Jedinica mjere'] !== self::value($node, 'unit')
				|| !in_array(self::value($node, 'available'), array('true', 'false'), true)
				|| $row['Dostupnost'] !== (self::value($node, 'available') === 'true' ? 'Dostupno' : 'Nije dostupno')) {
				throw new RuntimeException('CSV i XML imaju različite datume, mjere ili zalihe za proizvod ' . $id . '.');
			}
			$archived_quantity = self::value($node, 'packageQuantity', false);
			if (isset($row['Količina pakiranja']) || $archived_quantity !== '') {
				if (!isset($row['Količina pakiranja']) || $archived_quantity === '' || !AnchorPriceUnit::isValid($row['Jedinica mjere'], self::number($row['Količina pakiranja']))
					|| abs(self::number($row['Količina pakiranja']) - self::number($archived_quantity)) > 0.000001) {
					throw new RuntimeException('Neusklađena količina pakiranja u arhivi.');
				}
				$measure = array('unit' => $row['Jedinica mjere'], 'package_quantity' => self::number($archived_quantity));
			} else {
				$measure = isset($measures[$id]) ? $measures[$id] : array();
			}
			if (!isset($measure['unit'], $measure['package_quantity']) || !AnchorPriceUnit::isValid($measure['unit'], $measure['package_quantity'])) {
				throw new RuntimeException('Nedostaje potvrđena količina pakiranja za proizvod ' . $id . '.');
			}
			if (!isset($dates[$id]['reference_date'], $dates[$id]['gross_price']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $dates[$id]['reference_date'])) {
				throw new RuntimeException('Nedostaje potvrđen povijesni datum za proizvod ' . $id . '.');
			}
			$reference_date = $dates[$id]['reference_date'];
			$parsed_date = DateTime::createFromFormat('!Y-m-d', $reference_date);
			if (!$parsed_date || $parsed_date->format('Y-m-d') !== $reference_date) {
				throw new RuntimeException('Neispravan potvrđeni povijesni datum.');
			}
			if ($row['Datum sidrene cijene'] !== $reference_date && abs(self::number($row['Sidrena cijena (EUR)']) - (float)$dates[$id]['gross_price']) > 0.000001) {
				throw new RuntimeException('Povijesni iznos proizvoda ' . $id . ' nije potvrđen za traženi datum.');
			}
			$row['Jedinica mjere'] = $measure['unit'];
			$row['Količina pakiranja'] = str_replace('.', ',', rtrim(rtrim(number_format($measure['package_quantity'], 6, '.', ''), '0'), '.'));
			$row['Cijena po jedinici (EUR)'] = number_format(AnchorPriceUnit::calculate(self::number($row['Aktualna maloprodajna cijena (EUR)']), $measure['package_quantity']), 2, ',', '');
			$row['Sidrena cijena po jedinici (EUR)'] = number_format(AnchorPriceUnit::calculate(self::number($row['Sidrena cijena (EUR)']), $measure['package_quantity']), 2, ',', '');
			$row['Datum sidrene cijene'] = $reference_date;
			$row['Izvorna objava'] = (string)$metadata['source_publication_id'];
			$row['Vrijeme izvorne objave'] = $metadata['source_published_at'];
			$row['Vrijeme ispravka'] = $metadata['corrected_at'];
			$csv_products[$id] = $row;
			foreach (array('unit' => $measure['unit'], 'packageQuantity' => str_replace(',', '.', $row['Količina pakiranja']), 'unitPrice' => str_replace(',', '.', $row['Cijena po jedinici (EUR)']), 'anchorUnitPrice' => str_replace(',', '.', $row['Sidrena cijena po jedinici (EUR)']), 'anchorDate' => $reference_date) as $tag => $value) {
				self::setValue($document, $node, $tag, $value);
			}
		}
		if (count($xml_ids) !== count($csv_products)) { throw new RuntimeException('CSV i XML nemaju isti broj proizvoda.'); }
		foreach (array('sourcePublicationId' => $metadata['source_publication_id'], 'sourcePublishedAt' => $metadata['source_published_at'], 'correctedAt' => $metadata['corrected_at']) as $attribute => $value) {
			$root->setAttribute($attribute, (string)$value);
		}
		foreach (array('Količina pakiranja', 'Sidrena cijena po jedinici (EUR)', 'Izvorna objava', 'Vrijeme izvorne objave', 'Vrijeme ispravka') as $column) {
			if (!in_array($column, $headers, true)) { $headers[] = $column; }
		}
		$output = fopen('php://temp', 'w+b');
		fwrite($output, "\xEF\xBB\xBF");
		fputcsv($output, $headers, ';', '"', '\\');
		foreach ($csv_products as $row) {
			$values = array();
			foreach ($headers as $column) { $values[] = $row[$column]; }
			fputcsv($output, $values, ';', '"', '\\');
		}
		rewind($output);
		$corrected_csv = stream_get_contents($output);
		fclose($output);
		$corrected_xml = $document->saveXML();
		if ($corrected_csv === false || $corrected_xml === false) { throw new RuntimeException('Ispravak cjenika nije moguće zapisati.'); }
		return array('csv' => $corrected_csv, 'xml' => $corrected_xml, 'product_count' => count($csv_products));
	}

	private static function number($value) {
		$value = trim((string)$value);
		if (!preg_match('/^\d+(?:[.,]\d{1,6})?$/D', $value) || !is_finite((float)str_replace(',', '.', $value))) {
			throw new RuntimeException('Neispravan novčani iznos ili količina u arhivi.');
		}
		return (float)str_replace(',', '.', $value);
	}

	private static function value(DOMElement $node, $tag, $required = true) {
		$found = array();
		foreach ($node->childNodes as $child) {
			if ($child instanceof DOMElement && $child->tagName === $tag) { $found[] = $child; }
		}
		if (count($found) > 1 || ($required && count($found) !== 1)) { throw new RuntimeException('Nedostaje ili je ponovljeno XML polje ' . $tag . '.'); }
		return $found ? $found[0]->textContent : '';
	}

	private static function setValue(DOMDocument $document, DOMElement $node, $tag, $value) {
		$field = null;
		foreach ($node->childNodes as $child) {
			if ($child instanceof DOMElement && $child->tagName === $tag) { $field = $child; break; }
		}
		if (!$field) { $field = $document->createElement($tag); $node->appendChild($field); }
		$field->textContent = '';
		$field->appendChild($document->createTextNode((string)$value));
	}
}
