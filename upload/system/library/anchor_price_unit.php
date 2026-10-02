<?php
/** Selling-package measures, independent of OpenCart shipping weights. */
class AnchorPriceUnit {
	public static function isValid($unit, $quantity) {
		if (!in_array($unit, array('kg', 'l'), true) || !is_numeric($quantity)
			|| !is_finite((float)$quantity) || (float)$quantity <= 0
			|| (float)$quantity > 999999999.999999) {
			return false;
		}
		$stored_quantity = round((float)$quantity, 6);
		return $stored_quantity > 0 && (float)$quantity === $stored_quantity;
	}

	public static function calculate($price, $quantity) {
		if (!is_numeric($price) || !is_numeric($quantity) || !is_finite((float)$price)
			|| !is_finite((float)$quantity) || (float)$quantity <= 0) {
			throw new InvalidArgumentException('Neispravna cijena ili količina pakiranja.');
		}
		return (float)$price / (float)$quantity;
	}

	public static function filenamePart($value) {
		$value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
		$value = strtr($value, array('č' => 'c', 'ć' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z', 'Č' => 'C', 'Ć' => 'C', 'Đ' => 'D', 'Š' => 'S', 'Ž' => 'Z'));
		if (function_exists('iconv')) {
			$converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
			if ($converted !== false) {
				$value = $converted;
			}
		}
		return trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value)), '-');
	}
}
