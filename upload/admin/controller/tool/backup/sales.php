<?php
// Nested under tool/backup so existing backup permissions apply without installation.
class ControllerToolBackupSales extends Controller {
	public function index() {
		if (!$this->authorize(false)) {
			return;
		}
		$data = $this->load->language('tool/sales_export');
		$this->document->setTitle($this->language->get('heading_title'));
		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->link(''))
		);
		$config = array('urls' => array(), 'messages' => array());
		foreach (array('start', 'status', 'step', 'delete', 'download') as $action) {
			$config['urls'][$action] = $this->link('/' . $action);
		}
		foreach (array('text_ready', 'text_running', 'text_paused', 'text_complete', 'text_progress', 'error_network', 'text_confirm_delete', 'button_download') as $key) {
			$config['messages'][$key] = $this->language->get($key);
		}
		$data['export_config'] = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		$this->document->addScript('view/javascript/sales_export.js');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('tool/sales_export', $data));
	}

	public function start() {
		$this->run('start', true);
	}

	public function status() {
		$this->run('status', false);
	}

	public function step() {
		$this->run('step', true);
	}

	public function delete() {
		$this->run('delete', true);
	}

	public function download() {
		if (!$this->authorize(false)) {
			return;
		}
		$this->load->model('tool/sales_export');
		try {
			$job_id = isset($this->request->get['job']) ? $this->request->get['job'] : '';
			$table = isset($this->request->get['table']) ? $this->request->get['table'] : '';
			$file = $this->model_tool_sales_export->download($this->user->getId(), $job_id, $table);
		} catch (Throwable $e) {
			$this->failure($e);
			return;
		}
		// Stream the finished file directly: Response::setOutput would buffer the entire dump.
		@set_time_limit(0);
		@ini_set('zlib.output_compression', '0');
		while (ob_get_level()) {
			if (!ob_end_clean()) {
				break;
			}
		}
		$start = 0;
		$end = $file['size'] - 1;
		$range = isset($this->request->server['HTTP_RANGE']) ? $this->request->server['HTTP_RANGE'] : '';
		if ($range) {
			if (!preg_match('/^bytes=(\d*)-(\d*)$/D', $range, $match) || ($match[1] === '' && $match[2] === '')) {
				header('HTTP/1.1 416 Range Not Satisfiable');
				header('Content-Range: bytes */' . $file['size']);
				fclose($file['handle']);
				fclose($file['lock']);
				exit;
			}
			if ($match[1] === '') {
				$start = max(0, $file['size'] - (int)$match[2]);
			} else {
				$start = (int)$match[1];
				$end = $match[2] === '' ? $end : min($end, (int)$match[2]);
			}
			if ($start > $end || $start >= $file['size']) {
				header('HTTP/1.1 416 Range Not Satisfiable');
				header('Content-Range: bytes */' . $file['size']);
				fclose($file['handle']);
				fclose($file['lock']);
				exit;
			}
			header('HTTP/1.1 206 Partial Content');
			header('Content-Range: bytes ' . $start . '-' . $end . '/' . $file['size']);
		}
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
		header('Content-Length: ' . ($end - $start + 1));
		header('Accept-Ranges: bytes');
		header('Cache-Control: private, no-store, max-age=0');
		header('Pragma: no-cache');
		header('X-Content-Type-Options: nosniff');
		header('X-Accel-Buffering: no');
		fseek($file['handle'], $start);
		$remaining = $end - $start + 1;
		while ($remaining > 0 && !feof($file['handle']) && !connection_aborted()) {
			$chunk = fread($file['handle'], min(1048576, $remaining));
			if ($chunk === false || $chunk === '') {
				break;
			}
			echo $chunk;
			$remaining -= strlen($chunk);
			flush();
		}
		fclose($file['handle']);
		fclose($file['lock']);
		exit;
	}

	private function run($action, $post) {
		if (!$this->authorize($post)) {
			return;
		}
		$this->load->model('tool/sales_export');
		try {
			$job_id = isset($this->request->post['job']) ? $this->request->post['job'] : '';
			if ($action === 'start' || $action === 'status') {
				$job = $this->model_tool_sales_export->{$action}($this->user->getId());
			} else {
				$job = $this->model_tool_sales_export->{$action}($this->user->getId(), $job_id);
			}
			$this->json(array('job' => $job));
		} catch (Throwable $e) {
			$this->failure($e);
		}
	}

	private function authorize($post) {
		$this->load->language('tool/sales_export');
		$token = isset($this->request->get['user_token']) ? $this->request->get['user_token'] : '';
		if (!$this->user->isLogged() || !$this->user->hasPermission('access', 'tool/backup') || !$this->user->hasPermission('modify', 'tool/backup') || !is_string($token) || !isset($this->session->data['user_token']) || !hash_equals($this->session->data['user_token'], $token)) {
			$this->response->addHeader('HTTP/1.1 403 Forbidden');
			$this->json(array('error' => $this->language->get('error_permission')));
			return false;
		}
		if ($post && $this->request->server['REQUEST_METHOD'] !== 'POST') {
			$this->response->addHeader('HTTP/1.1 405 Method Not Allowed');
			$this->response->addHeader('Allow: POST');
			$this->json(array('error' => $this->language->get('error_method')));
			return false;
		}
		return true;
	}

	private function failure($exception) {
		$known = array('error_storage', 'error_busy', 'error_job', 'error_incomplete', 'error_permission', 'error_schema');
		$key = $exception instanceof RuntimeException && in_array($exception->getMessage(), $known, true) ? $exception->getMessage() : 'error_export';
		// Do not expose SQL, server paths or customer data in responses/logs.
		$this->log->write('Sales export: ' . $key . ' (' . get_class($exception) . ')');
		$this->json(array('error' => $this->language->get($key), 'retry' => $key === 'error_busy'));
	}

	private function json($data) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->addHeader('Cache-Control: private, no-store, max-age=0');
		$this->response->setOutput(json_encode($data));
	}

	private function link($suffix) {
		return str_replace('&amp;', '&', $this->url->link('tool/backup/sales' . $suffix, 'user_token=' . $this->session->data['user_token'], true));
	}
}
