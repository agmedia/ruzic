<?php
class ControllerExtensionFeedBoostSitemap extends Controller {
	private $types = array('category', 'information', 'manufacturer', 'product');

	public function index() {
		if (!$this->config->get('feed_boost_sitemap_status')) {
			$this->notFound();
			return;
		}

		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();
		$store = (int)$this->config->get('config_store_id');
		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		$namespace = 'http://www.sitemaps.org/schemas/sitemap/0.9';

		if (isset($this->request->get['map'])) {
			$type = $this->request->get['map'];
			$language = isset($this->request->get['map_language']) ? (int)$this->request->get['map_language'] : 0;
			if (!in_array($type, $this->types, true) || !isset($this->request->get['map_store'])
				|| (int)$this->request->get['map_store'] !== $store || !in_array($language, array_column($languages, 'language_id'))) {
				$this->notFound();
				return;
			}

			$previous = $this->config->get('config_language_id');
			$this->config->set('config_language_id', $language);
			try {
				$xml->startElementNS(null, 'urlset', $namespace);
				$this->writeCatalog($xml, $type, $store, $language);
			} finally {
				$this->config->set('config_language_id', $previous);
			}
		} else {
			$xml->startElementNS(null, 'sitemapindex', $namespace);
			$base = rtrim($this->config->get('config_ssl') ?: $this->config->get('config_url'), '/');
			foreach ($languages as $language) {
				foreach ($this->types as $type) {
					$xml->startElement('sitemap');
					$xml->writeElement('loc', $base . '/sitemaps/sitemap_' . $store . '_' . (int)$language['language_id'] . '_' . $type . '.xml');
					$xml->endElement();
				}
			}
			// Keep optional blog, custom and relationship maps generated in the admin.
			$directory = dirname(rtrim(DIR_SYSTEM, '/\\')) . '/sitemaps/';
			foreach (glob($directory . 'sitemap_' . $store . '_*.xml') ?: array() as $file) {
				$name = basename($file);
				if (!preg_match('/^sitemap_\d+_(?:custom_link|\d+_(?:blog_category|blog_post|category_product|manufacturer_product))(?:_\d+)?\.xml$/D', $name)) { continue; }
				$map = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
				if (!$map || !$map->url) { continue; }
				foreach ($map->url as $entry) {
					if (parse_url((string)$entry->loc, PHP_URL_HOST) !== parse_url($base, PHP_URL_HOST)) { continue 2; }
				}
				$xml->startElement('sitemap');
				$xml->writeElement('loc', $base . '/sitemaps/' . $name);
				$xml->endElement();
			}
		}

		$xml->endElement();
		$xml->endDocument();
		$this->response->addHeader('Content-Type: application/xml; charset=UTF-8');
		$this->response->setOutput($xml->outputMemory());
	}

	private function writeCatalog($xml, $type, $store, $language) {
		switch ($type) {
			case 'product':
				// Only IDs are needed. Loading the whole catalog also calculates customer prices.
				$products = $this->db->query("SELECT p.product_id FROM `" . DB_PREFIX . "product` p
					JOIN `" . DB_PREFIX . "product_to_store` ps ON ps.product_id = p.product_id
					JOIN `" . DB_PREFIX . "product_description` pd ON pd.product_id = p.product_id
					WHERE ps.store_id = " . (int)$store . " AND pd.language_id = " . (int)$language . "
					AND p.status = 1 AND p.date_available <= NOW() ORDER BY p.product_id");
				foreach ($products->rows as $product) {
					$this->writeUrl($xml, 'product/product', 'product_id=' . (int)$product['product_id']);
				}
				break;
			case 'category':
				$this->load->model('catalog/category');
				$this->writeCategories($xml, 0, '');
				break;
			case 'manufacturer':
				$this->load->model('catalog/manufacturer');
				foreach ($this->model_catalog_manufacturer->getManufacturers() as $brand) {
					$this->writeUrl($xml, 'product/manufacturer/info', 'manufacturer_id=' . (int)$brand['manufacturer_id']);
				}
				break;
			case 'information':
				$this->load->model('catalog/information');
				foreach ($this->model_catalog_information->getInformations() as $info) {
					$this->writeUrl($xml, 'information/information', 'information_id=' . (int)$info['information_id']);
				}
				break;
		}
	}

	private function writeCategories($xml, $parent, $prefix) {
		foreach ($this->model_catalog_category->getCategories($parent) as $category) {
			$id = (int)$category['category_id'];
			$path = $prefix . $id;
			$this->writeUrl($xml, 'product/category', 'path=' . $path);
			$this->writeCategories($xml, $id, $path . '_');
		}
	}

	private function writeUrl($xml, $route, $args) {
		$xml->startElement('url');
		$xml->writeElement('loc', html_entity_decode($this->url->link($route, $args, true), ENT_QUOTES, 'UTF-8'));
		$xml->endElement();
	}

	private function notFound() {
		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
		$this->response->setOutput('');
	}
}
