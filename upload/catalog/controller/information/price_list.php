<?php
class ControllerInformationPriceList extends Controller {
	public function index() {
		if (!$this->config->get('module_anchor_price_status')) {
			return $this->notFound();
		}

		$this->load->language('information/price_list');
		$this->load->model('extension/module/anchor_price');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_intro'] = $this->language->get('text_intro');
		$data['text_empty'] = $this->language->get('text_empty');
		$data['column_location'] = $this->language->get('column_location');
		$data['column_published'] = $this->language->get('column_published');
		$data['column_products'] = $this->language->get('column_products');
		$data['column_file'] = $this->language->get('column_file');
		$data['button_download'] = $this->language->get('button_download');
		$data['button_download_csv'] = $this->language->get('button_download_csv');
		$data['button_download_xml'] = $this->language->get('button_download_xml');
		$data['latest_csv'] = $this->url->link('information/price_list/download', 'format=csv');
		$data['latest_xml'] = $this->url->link('information/price_list/download', 'format=xml');

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		$data['breadcrumbs'][] = array(
			'text' => $data['heading_title'],
			'href' => $this->url->link('information/price_list')
		);

		$data['publications'] = array();
		$publications = $this->model_extension_module_anchor_price->getPublicationGroups();

		foreach ($publications as $publication) {
			$corrects_publication_id = !empty($publication['corrects_publication_id']) ? (int)$publication['corrects_publication_id'] : 0;
			$data['publications'][] = array(
				'location_name' => $this->language->get('text_location'),
				'published'     => date($this->language->get('datetime_format'), strtotime($publication['display_published_at'])),
				'product_count' => (int)$publication['product_count'],
				'filename'      => PriceListFormat::publicFilename($publication, $this->config->get('module_anchor_price_oib'), 'csv'),
				'xml_filename'  => PriceListFormat::publicFilename($publication, $this->config->get('module_anchor_price_oib'), 'xml'),
				'correction_note' => $corrects_publication_id ? sprintf($this->language->get('text_archive_corrected_at'), date($this->language->get('datetime_format'), strtotime($publication['published_at']))) : '',
				'download_csv'  => $this->url->link('information/price_list/download', 'publication_id=' . (int)$publication['publication_id'] . '&format=csv'),
				'download_xml'  => $this->url->link('information/price_list/download', 'publication_id=' . (int)$publication['publication_id'] . '&format=xml')
			);
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/price_list', $data));
	}

	public function download() {
		if (!$this->config->get('module_anchor_price_status')) {
			return $this->notFound();
		}

		$format = isset($this->request->get['format']) && strtolower($this->request->get['format']) === 'xml' ? 'xml' : 'csv';
		$publication_id = isset($this->request->get['publication_id']) ? (int)$this->request->get['publication_id'] : 0;
		$this->load->model('extension/module/anchor_price');
		$publication = $publication_id > 0
			? $this->model_extension_module_anchor_price->getPublication($publication_id, true)
			: $this->model_extension_module_anchor_price->getLatestPublication($format);

		if (!$publication) {
			return $this->notFound();
		}

		$path = $this->model_extension_module_anchor_price->publicationPath($publication, $format);

		if (!$path || !$this->model_extension_module_anchor_price->publicationFileIsValid($publication, $path, $format)) {
			return $this->notFound();
		}

		$filename = PriceListFormat::publicFilename($publication, $this->config->get('module_anchor_price_oib'), $format);
		if (PriceListFormat::isCurrentFormat($publication['filename'])) {
			$content = file_get_contents($path);
		} else {
			$model = $this->model_extension_module_anchor_price;
			$csv_path = $model->publicationPath($publication, 'csv');
			if (!$model->publicationFileIsValid($publication, $csv_path, 'csv')) { return $this->notFound(); }
			$stream = fopen($csv_path, 'rb');
			try {
				$rows = PriceListFormat::legacyRows($stream);
				$time = new DateTime($publication['published_at'], new DateTimeZone('Europe/Zagreb'));
				$content = $format === 'csv' ? PriceListFormat::csv($rows) : PriceListFormat::xml($rows, $this->config->get('module_anchor_price_oib'), $time);
			} catch (Exception $exception) {
				$this->log->write('Arhivski cjenik #' . $publication['publication_id'] . ': ' . $exception->getMessage());
				return $this->notFound();
			} finally {
				fclose($stream);
			}
		}
		$this->response->setCompression(0);
		$this->response->addHeader('Content-Type: ' . ($format === 'xml' ? 'application/xml' : 'text/csv') . '; charset=utf-8');
		$this->response->addHeader('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
		$this->response->addHeader('Content-Length: ' . strlen($content));
		$this->response->addHeader('X-Content-Type-Options: nosniff');
		$this->response->setOutput($content);
	}

	private function notFound() {
		$this->response->addHeader('HTTP/1.1 404 Not Found');
		$this->load->language('error/not_found');
		$this->document->setTitle($this->language->get('heading_title'));
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_error'] = $this->language->get('text_error');
		$data['button_continue'] = $this->language->get('button_continue');
		$data['continue'] = $this->url->link('common/home');
		$data['breadcrumbs'] = array();
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$this->response->setOutput($this->load->view('error/not_found', $data));
	}
}
