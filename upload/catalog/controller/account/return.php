<?php
class ControllerAccountReturn extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/return', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/return');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/return', $url, true)
		);

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_empty'] = $this->language->get('text_empty');

		$data['column_return_id'] = $this->language->get('column_return_id');
		$data['column_order_id'] = $this->language->get('column_order_id');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_customer'] = $this->language->get('column_customer');

		$data['button_view'] = $this->language->get('button_view');
		$data['button_continue'] = $this->language->get('button_continue');

		$this->load->model('account/return');

		if (isset($this->request->get['page'])) {
			$page = max(1, (int)$this->request->get['page']);
		} else {
			$page = 1;
		}

		$data['returns'] = array();

		$return_total = $this->model_account_return->getTotalReturns();

		$limit = 10;
		$results = $this->model_account_return->getReturns(($page - 1) * $limit, $limit);

		foreach ($results as $result) {
			$data['returns'][] = array(
				'return_id'  => $result['return_id'],
				'order_id'   => $this->escapeReturnOutput($result['order_id']),
				'name'       => $this->escapeReturnOutput($result['firstname'] . ' ' . $result['lastname']),
				'status'     => $this->escapeReturnOutput($result['status']),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'href'       => $this->url->link('account/return/info', 'return_id=' . $result['return_id'] . $url, true)
			);
		}

		$pagination = new Pagination();
		$pagination->total = $return_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('account/return', 'page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), $return_total ? (($page - 1) * $limit) + 1 : 0, min($page * $limit, $return_total), $return_total, $return_total ? ceil($return_total / $limit) : 1);

		$data['continue'] = $this->url->link('account/account', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/return_list', $data));
	}

	public function info() {
		$this->load->language('account/return');

		if (isset($this->request->get['return_id'])) {
			$return_id = (int)$this->request->get['return_id'];
		} else {
			$return_id = 0;
		}

		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/return/info', 'return_id=' . $return_id, true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->model('account/return');

		$return_info = $this->model_account_return->getReturn($return_id);

		if ($return_info) {
			$this->document->setTitle($this->language->get('text_return'));

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', '', true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', '', true)
			);

			$url = '';

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('account/return', $url, true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_return'),
				'href' => $this->url->link('account/return/info', 'return_id=' . $this->request->get['return_id'] . $url, true)
			);

			$data['heading_title'] = $this->language->get('text_return');

			$data['text_return_detail'] = $this->language->get('text_return_detail');
			$data['text_return_id'] = $this->language->get('text_return_id');
			$data['text_order_id'] = $this->language->get('text_order_id');
			$data['text_date_ordered'] = $this->language->get('text_date_ordered');
			$data['text_customer'] = $this->language->get('text_customer');
			$data['text_email'] = $this->language->get('text_email');
			$data['text_telephone'] = $this->language->get('text_telephone');
			$data['text_status'] = $this->language->get('text_status');
			$data['text_date_added'] = $this->language->get('text_date_added');
			$data['text_product'] = $this->language->get('text_product');
			$data['text_return_products_title'] = $this->language->get('text_return_products_title');
			$data['text_reason'] = $this->language->get('text_reason');
			$data['text_comment'] = $this->language->get('text_comment');
			$data['text_history'] = $this->language->get('text_history');
			$data['text_no_results'] = $this->language->get('text_no_results');

			$data['column_product'] = $this->language->get('column_product');
			$data['column_model'] = $this->language->get('column_model');
			$data['column_quantity'] = $this->language->get('column_quantity');
			$data['column_price'] = $this->language->get('column_price');
			$data['column_opened'] = $this->language->get('column_opened');
			$data['column_reason'] = $this->language->get('column_reason');
			$data['column_action'] = $this->language->get('column_action');
			$data['column_date_added'] = $this->language->get('column_date_added');
			$data['column_status'] = $this->language->get('column_status');
			$data['column_comment'] = $this->language->get('column_comment');
			$data['entry_product_code'] = $this->language->get('entry_product_code');
			$data['entry_refund_iban'] = $this->language->get('entry_refund_iban');

			$data['button_continue'] = $this->language->get('button_continue');

			$data['return_id'] = $return_info['return_id'];
			$data['order_id'] = $this->escapeReturnOutput($return_info['order_id']);
			$data['date_ordered'] = date($this->language->get('date_format_short'), strtotime($return_info['date_ordered']));
			$data['date_added'] = date($this->language->get('date_format_short'), strtotime($return_info['date_added']));
			$data['firstname'] = $this->escapeReturnOutput($return_info['firstname']);
			$data['lastname'] = $this->escapeReturnOutput($return_info['lastname']);
			$data['email'] = $this->escapeReturnOutput($return_info['email']);
			$data['telephone'] = $this->escapeReturnOutput($return_info['telephone']);
			$data['refund_iban'] = $this->escapeReturnOutput(isset($return_info['refund_iban']) ? $return_info['refund_iban'] : '');
			$data['product'] = $this->escapeReturnOutput($return_info['product']);
			$data['model'] = $this->escapeReturnOutput($return_info['model']);
			$data['quantity'] = $return_info['quantity'];
			$data['return_products'] = json_decode(isset($return_info['return_items']) ? $return_info['return_items'] : '', true);

			if (!is_array($data['return_products']) || !$data['return_products']) {
				$data['return_products'] = array(array(
					'code'     => $return_info['model'],
					'quantity' => $return_info['quantity'],
					'price'    => ''
				));
			}
			$data['return_products'] = $this->escapeReturnProductsForOutput($data['return_products']);

			$data['reason'] = $this->escapeReturnOutput($return_info['reason']);
			$data['opened'] = $return_info['opened'] ? $this->language->get('text_yes') : $this->language->get('text_no');
			$data['comment'] = nl2br($this->escapeReturnOutput($return_info['comment']));
			$data['action'] = $this->escapeReturnOutput($return_info['action']);

			$data['histories'] = array();

			$results = $this->model_account_return->getReturnHistories($this->request->get['return_id']);

			foreach ($results as $result) {
				$data['histories'][] = array(
					'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
					'status'     => $this->escapeReturnOutput($result['status']),
					'comment'    => nl2br($this->escapeReturnOutput($result['comment']))
				);
			}

			$data['continue'] = $this->url->link('account/return', $url, true);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('account/return_info', $data));
		} else {
			$this->document->setTitle($this->language->get('text_return'));

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', '', true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('account/return', '', true)
			);

			$url = '';

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_return'),
				'href' => $this->url->link('account/return/info', 'return_id=' . $return_id . $url, true)
			);

			$data['heading_title'] = $this->language->get('text_return');

			$data['text_error'] = $this->language->get('text_error');

			$data['button_continue'] = $this->language->get('button_continue');

			$data['continue'] = $this->url->link('account/return', '', true);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function add() {
		$this->load->language('account/return');

		$this->load->model('account/return');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$return_data = $this->prepareReturnData($this->request->post);

			$return_id = $this->model_account_return->addReturn($return_data);

			// Add to activity log
			if ($this->config->get('config_customer_activity')) {
				$this->load->model('account/activity');

				if ($this->customer->isLogged()) {
					$activity_data = array(
						'customer_id' => $this->customer->getId(),
						'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
						'return_id'   => $return_id
					);

					$this->model_account_activity->addActivity('return_account', $activity_data);
				} else {
					$activity_data = array(
						'name'      => $return_data['firstname'] . ' ' . $return_data['lastname'],
						'return_id' => $return_id
					);

					$this->model_account_activity->addActivity('return_guest', $activity_data);
				}
			}

			$this->sendReturnEmails($return_id, $return_data);

			$this->response->redirect($this->url->link('account/return/success', '', true));
		}

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/return/add', '', true)
		);

		$data['heading_title'] = $this->language->get('heading_title');
		$data['return_title_class'] = trim((string)$this->config->get('basel_titles_default'));

		if (!$data['return_title_class']) {
			$data['return_title_class'] = 'title_in_bc normal_height_bc';
		}

		$data['text_description'] = $this->language->get('text_description');
		$data['text_order'] = $this->language->get('text_order');
		$data['text_product'] = $this->language->get('text_product');
		$data['text_return_products_title'] = $this->language->get('text_return_products_title');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

		$data['entry_order_id'] = $this->language->get('entry_order_id');
		$data['entry_date_ordered'] = $this->language->get('entry_date_ordered');
		$data['entry_invoice_number'] = $this->language->get('entry_invoice_number');
		$data['entry_invoice_date'] = $this->language->get('entry_invoice_date');
		$data['entry_firstname'] = $this->language->get('entry_firstname');
		$data['entry_lastname'] = $this->language->get('entry_lastname');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_telephone'] = $this->language->get('entry_telephone');
		$data['entry_product'] = $this->language->get('entry_product');
		$data['entry_model'] = $this->language->get('entry_model');
		$data['entry_product_code'] = $this->language->get('entry_product_code');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['entry_reason'] = $this->language->get('entry_reason');
		$data['entry_opened'] = $this->language->get('entry_opened');
		$data['entry_fault_detail'] = $this->language->get('entry_fault_detail');
		$data['entry_refund_iban'] = $this->language->get('entry_refund_iban');

		$data['button_submit'] = $this->language->get('button_submit');
		$data['button_back'] = $this->language->get('button_back');
		$data['button_add_product'] = $this->language->get('button_add_product');
		$data['button_remove'] = $this->language->get('button_remove');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['order_id'])) {
			$data['error_order_id'] = $this->error['order_id'];
		} else {
			$data['error_order_id'] = '';
		}

		if (isset($this->error['date_ordered'])) {
			$data['error_date_ordered'] = $this->error['date_ordered'];
		} else {
			$data['error_date_ordered'] = '';
		}

		if (isset($this->error['firstname'])) {
			$data['error_firstname'] = $this->error['firstname'];
		} else {
			$data['error_firstname'] = '';
		}

		if (isset($this->error['lastname'])) {
			$data['error_lastname'] = $this->error['lastname'];
		} else {
			$data['error_lastname'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = '';
		}

		if (isset($this->error['product'])) {
			$data['error_product'] = $this->error['product'];
		} else {
			$data['error_product'] = '';
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['reason'])) {
			$data['error_reason'] = $this->error['reason'];
		} else {
			$data['error_reason'] = '';
		}

		if (isset($this->error['return_products'])) {
			$data['error_return_products'] = $this->error['return_products'];
		} else {
			$data['error_return_products'] = '';
		}

		if (isset($this->error['refund_iban'])) {
			$data['error_refund_iban'] = $this->error['refund_iban'];
		} else {
			$data['error_refund_iban'] = '';
		}

		$data['action'] = $this->url->link('account/return/add', '', true);

		$this->load->model('account/order');

		if (isset($this->request->get['order_id'])) {
			$order_info = $this->model_account_order->getOrder($this->request->get['order_id']);
		}

		$this->load->model('catalog/product');

		if (isset($this->request->get['product_id'])) {
			$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
		}

		if (isset($this->request->post['invoice_number'])) {
			$data['invoice_number'] = $this->request->post['invoice_number'];
		} elseif (!empty($order_info) && (int)$order_info['invoice_no']) {
			$data['invoice_number'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
		} elseif (!empty($order_info)) {
			$data['invoice_number'] = $order_info['order_id'];
		} else {
			$data['invoice_number'] = '';
		}

		$data['order_id'] = $data['invoice_number'];

		if (isset($this->request->post['product_id'])) {
			$data['product_id'] = (int)$this->request->post['product_id'];
		} elseif (!empty($product_info)) {
			$data['product_id'] = (int)$product_info['product_id'];
		} else {
			$data['product_id'] = 0;
		}

		if (isset($this->request->post['invoice_date'])) {
			$data['invoice_date'] = $this->request->post['invoice_date'];
		} elseif (!empty($order_info)) {
			$data['invoice_date'] = date('Y-m-d', strtotime($order_info['date_added']));
		} else {
			$data['invoice_date'] = '';
		}

		$data['date_ordered'] = $data['invoice_date'];

		if (isset($this->request->post['firstname'])) {
			$data['firstname'] = $this->request->post['firstname'];
		} elseif (!empty($order_info)) {
			$data['firstname'] = $order_info['firstname'];
		} else {
			$data['firstname'] = $this->customer->getFirstName();
		}

		if (isset($this->request->post['lastname'])) {
			$data['lastname'] = $this->request->post['lastname'];
		} elseif (!empty($order_info)) {
			$data['lastname'] = $order_info['lastname'];
		} else {
			$data['lastname'] = $this->customer->getLastName();
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} elseif (!empty($order_info)) {
			$data['email'] = $order_info['email'];
		} else {
			$data['email'] = $this->customer->getEmail();
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} elseif (!empty($order_info)) {
			$data['telephone'] = $order_info['telephone'];
		} else {
			$data['telephone'] = $this->customer->getTelephone();
		}

		if (isset($this->request->post['product'])) {
			$data['product'] = $this->request->post['product'];
		} elseif (!empty($product_info)) {
			$data['product'] = $product_info['name'];
		} else {
			$data['product'] = '';
		}

		if (isset($this->request->post['model'])) {
			$data['model'] = $this->request->post['model'];
		} elseif (!empty($product_info)) {
			$data['model'] = $product_info['model'];
		} else {
			$data['model'] = '';
		}

		if (isset($this->request->post['quantity'])) {
			$data['quantity'] = $this->request->post['quantity'];
		} else {
			$data['quantity'] = 1;
		}

		if (isset($this->request->post['return_products'])) {
			$data['return_products'] = $this->getReturnProducts($this->request->post, true);
		} elseif (!empty($product_info)) {
			$data['return_products'] = array(array(
				'code'     => $product_info['model'],
				'quantity' => 1,
				'price'    => ''
			));
		} else {
			$data['return_products'] = array(array(
				'code'     => '',
				'quantity' => '',
				'price'    => ''
			));
		}

		if (isset($this->request->post['opened'])) {
			$data['opened'] = $this->request->post['opened'];
		} else {
			$data['opened'] = false;
		}

		if (isset($this->request->post['return_reason_id'])) {
			$data['return_reason_id'] = $this->request->post['return_reason_id'];
		} else {
			$data['return_reason_id'] = '';
		}

		$this->load->model('localisation/return_reason');

		$data['return_reasons'] = $this->model_localisation_return_reason->getReturnReasons();

		if (isset($this->request->post['comment'])) {
			$data['comment'] = $this->request->post['comment'];
		} else {
			$data['comment'] = '';
		}

		if (isset($this->request->post['refund_iban'])) {
			$data['refund_iban'] = $this->request->post['refund_iban'];
		} else {
			$data['refund_iban'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('return', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		if ($this->config->get('config_return_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_return_id'));

			if ($information_info) {
				$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_return_id'), true), $information_info['title'], $information_info['title']);
			} else {
				$data['text_agree'] = $this->language->get('text_agree_fallback');
			}
		} else {
			$data['text_agree'] = $this->language->get('text_agree_fallback');
		}

		if (isset($this->request->post['agree'])) {
			$data['agree'] = $this->request->post['agree'];
		} else {
			$data['agree'] = false;
		}

		$data['back'] = $this->url->link('account/account', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$data = $this->escapeReturnFormForOutput($data);

		$this->response->setOutput($this->load->view('account/return_form', $data));
	}

	protected function prepareReturnData($data) {
		$return_products = $this->getReturnProducts($data);
		$first_product = reset($return_products);
		$invoice_number = isset($data['invoice_number']) ? trim(strip_tags((string)$data['invoice_number'])) : '';

		$data['return_products'] = $return_products;
		$data['return_items'] = json_encode($return_products, JSON_UNESCAPED_UNICODE);
		$data['invoice_number'] = utf8_substr($invoice_number, 0, 64);
		$data['invoice_date'] = $this->normalizeReturnDate(isset($data['invoice_date']) ? $data['invoice_date'] : '');
		$data['refund_iban'] = $this->normalizeReturnIban(isset($data['refund_iban']) ? $data['refund_iban'] : '');
		$data['firstname'] = utf8_substr(trim(strip_tags((string)$data['firstname'])), 0, 32);
		$data['lastname'] = utf8_substr(trim(strip_tags((string)$data['lastname'])), 0, 32);
		$data['email'] = utf8_substr(trim(strip_tags((string)$data['email'])), 0, 96);
		$data['telephone'] = utf8_substr(trim(strip_tags((string)$data['telephone'])), 0, 32);
		$data['return_reason_id'] = isset($data['return_reason_id']) ? (int)$data['return_reason_id'] : 0;
		$data['customer_comment'] = isset($data['comment']) ? utf8_substr(trim(strip_tags((string)$data['comment'])), 0, 2000) : '';
		$data['order_id'] = (int)preg_replace('/\D+/', '', $invoice_number);
		$data['date_ordered'] = $data['invoice_date'];
		$data['product_id'] = isset($data['product_id']) ? (int)$data['product_id'] : 0;
		$data['product'] = $this->language->get('text_return_products_title');
		$data['model'] = isset($first_product['code']) ? $first_product['code'] : '';
		$data['quantity'] = isset($first_product['quantity']) ? (int)$first_product['quantity'] : 1;
		$data['opened'] = 0;
		$data['comment'] = $this->buildReturnStoredComment($data, $return_products);

		return $data;
	}

	protected function getReturnProducts($data, $include_empty = false) {
		$return_products = array();

		if (isset($data['return_products']) && is_array($data['return_products'])) {
			foreach ($data['return_products'] as $product) {
				$code = isset($product['code']) ? utf8_substr(trim(strip_tags((string)$product['code'])), 0, 128) : '';
				$quantity = isset($product['quantity']) ? utf8_substr(trim(strip_tags((string)$product['quantity'])), 0, 32) : '';
				$price = isset($product['price']) ? utf8_substr(trim(strip_tags((string)$product['price'])), 0, 64) : '';

				if (!$include_empty && $code === '' && $quantity === '' && $price === '') {
					continue;
				}

				$return_products[] = array(
					'code'     => $code,
					'quantity' => $quantity,
					'price'    => $price
				);
			}
		}

		if ($include_empty && !$return_products) {
			$return_products[] = array(
				'code'     => '',
				'quantity' => '',
				'price'    => ''
			);
		}

		return $return_products;
	}

	protected function validateReturnProducts($return_products) {
		if (!$return_products) {
			return false;
		}

		foreach ($return_products as $product) {
			if ($product['code'] === '' || $product['quantity'] === '' || $product['price'] === '') {
				return false;
			}

			$quantity = str_replace(',', '.', $product['quantity']);
			$price = str_replace(',', '.', $product['price']);

			if (!is_numeric($quantity) || (float)$quantity <= 0 || !is_numeric($price) || (float)$price < 0) {
				return false;
			}
		}

		return true;
	}

	protected function buildReturnStoredComment($data, $return_products) {
		$lines = array();

		$lines[] = $this->language->get('entry_invoice_number') . ': ' . $data['invoice_number'];
		$lines[] = $this->language->get('entry_invoice_date') . ': ' . $data['invoice_date'];
		$lines[] = $this->language->get('entry_refund_iban') . ': ' . $data['refund_iban'];
		$lines[] = '';
		$lines[] = $this->language->get('text_return_products_title') . ':';

		foreach ($return_products as $index => $product) {
			$lines[] = ($index + 1) . '. ' . $this->language->get('entry_product_code') . ': ' . $product['code'] . ', ' . $this->language->get('entry_quantity') . ': ' . $product['quantity'] . ', ' . $this->language->get('entry_price') . ': ' . $product['price'];
		}

		if (!empty($data['customer_comment'])) {
			$lines[] = '';
			$lines[] = $this->language->get('entry_fault_detail') . ':';
			$lines[] = $data['customer_comment'];
		}

		return implode("\n", $lines);
	}

	protected function sendReturnEmails($return_id, $data) {
		try {
			$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
			$store_email = $this->config->get('config_email');
			$mail_from = $this->getReturnMailFrom($store_email);
			$customer_email = isset($data['email']) ? $data['email'] : '';

			$this->load->model('localisation/return_reason');

			$return_reason = '';

			foreach ($this->model_localisation_return_reason->getReturnReasons() as $reason) {
				if ((int)$reason['return_reason_id'] === (int)$data['return_reason_id']) {
					$return_reason = $reason['name'];
					break;
				}
			}

			$message_details = $this->buildReturnEmailDetails($return_id, $data, $return_reason);

			$admin_message  = html_entity_decode($this->language->get('mail_return_admin_intro'), ENT_QUOTES, 'UTF-8') . "\n\n";
			$admin_message .= $message_details;

			$admin_subject = sprintf($this->language->get('mail_return_admin_subject'), $store_name, $return_id);
			$admin_recipients = $this->getReturnAdminRecipients();

			if (!$admin_recipients) {
				$this->log->write('Return form mail warning: no valid admin email recipient configured.');
			}

			foreach ($admin_recipients as $admin_email) {
				try {
					$admin_mail = $this->createReturnMail();
					$admin_mail->setTo($admin_email);
					$admin_mail->setFrom($mail_from);
					$admin_mail->setSender($store_name);

					if ($customer_email && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
						$admin_mail->setReplyTo($customer_email);
					}

					$admin_mail->setSubject($admin_subject);
					$admin_mail->setText($admin_message);
					$admin_mail->send();
				} catch (Exception $e) {
					$this->log->write('Return form admin mail error for ' . $admin_email . ': ' . $e->getMessage());
				}
			}

			if ($customer_email && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
				$customer_message  = html_entity_decode($this->language->get('mail_return_customer_intro'), ENT_QUOTES, 'UTF-8') . "\n\n";
				$customer_message .= $message_details;
				$customer_message .= "\n" . html_entity_decode($this->language->get('mail_return_customer_footer'), ENT_QUOTES, 'UTF-8') . "\n";

				$customer_mail = $this->createReturnMail();
				$customer_mail->setTo($customer_email);
				$customer_mail->setFrom($mail_from);
				$customer_mail->setSender($store_name);
				$customer_mail->setSubject(sprintf($this->language->get('mail_return_customer_subject'), $store_name, $return_id));
				$customer_mail->setText($customer_message);
				$customer_mail->send();
			}
		} catch (Exception $e) {
			$this->log->write('Return form mail error: ' . $e->getMessage());
		}
	}

	protected function getReturnMailFrom($fallback_email) {
		$smtp_username = trim((string)$this->config->get('config_mail_smtp_username'));
		$fallback_email = trim((string)$fallback_email);

		if ($smtp_username && filter_var($smtp_username, FILTER_VALIDATE_EMAIL)) {
			return $smtp_username;
		}

		if ($fallback_email && filter_var($fallback_email, FILTER_VALIDATE_EMAIL)) {
			return $fallback_email;
		}

		return $smtp_username ? $smtp_username : $fallback_email;
	}

	protected function getReturnAdminRecipients() {
		$recipients = array();
		$config_email = trim((string)$this->config->get('config_email'));

		if ($config_email && filter_var($config_email, FILTER_VALIDATE_EMAIL)) {
			$recipients[] = $config_email;
		}

		foreach (array('config_mail_alert_email', 'config_alert_email') as $setting_key) {
			foreach (explode(',', (string)$this->config->get($setting_key)) as $email) {
				$email = trim($email);

				if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$recipients[] = $email;
				}
			}
		}

		return array_values(array_unique($recipients));
	}

	protected function createReturnMail() {
		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		return $mail;
	}

	protected function buildReturnEmailDetails($return_id, $data, $return_reason) {
		$fields = array(
			$this->language->get('mail_return_label_return_id') => $return_id,
			$this->language->get('entry_invoice_number') => isset($data['invoice_number']) ? $data['invoice_number'] : '',
			$this->language->get('entry_invoice_date') => isset($data['invoice_date']) ? $data['invoice_date'] : '',
			$this->language->get('entry_firstname') => isset($data['firstname']) ? $data['firstname'] : '',
			$this->language->get('entry_lastname') => isset($data['lastname']) ? $data['lastname'] : '',
			$this->language->get('entry_email') => isset($data['email']) ? $data['email'] : '',
			$this->language->get('entry_telephone') => isset($data['telephone']) ? $data['telephone'] : '',
			$this->language->get('entry_reason') => $return_reason,
			$this->language->get('entry_refund_iban') => isset($data['refund_iban']) ? $data['refund_iban'] : ''
		);

		$message = '';

		foreach ($fields as $label => $value) {
			$message .= $label . ': ' . html_entity_decode(strip_tags((string)$value), ENT_QUOTES, 'UTF-8') . "\n";
		}

		$message .= "\n" . $this->language->get('text_return_products_title') . ":\n";

		if (!empty($data['return_products']) && is_array($data['return_products'])) {
			foreach ($data['return_products'] as $index => $product) {
				$message .= ($index + 1) . '. ' . $this->language->get('entry_product_code') . ': ' . html_entity_decode(strip_tags((string)$product['code']), ENT_QUOTES, 'UTF-8') . ', ' . $this->language->get('entry_quantity') . ': ' . html_entity_decode(strip_tags((string)$product['quantity']), ENT_QUOTES, 'UTF-8') . ', ' . $this->language->get('entry_price') . ': ' . html_entity_decode(strip_tags((string)$product['price']), ENT_QUOTES, 'UTF-8') . "\n";
			}
		}

		if (!empty($data['customer_comment'])) {
			$message .= "\n" . $this->language->get('entry_fault_detail') . ":\n";
			$message .= html_entity_decode(strip_tags($data['customer_comment']), ENT_QUOTES, 'UTF-8') . "\n";
		}

		return $message;
	}

	protected function validate() {
		foreach (array('invoice_number', 'invoice_date', 'firstname', 'lastname', 'email', 'telephone', 'refund_iban', 'comment', 'return_reason_id') as $key) {
			if (!isset($this->request->post[$key]) || is_array($this->request->post[$key])) {
				$this->request->post[$key] = '';
			}
		}

		$this->request->post['invoice_date'] = $this->normalizeReturnDate($this->request->post['invoice_date']);
		$this->request->post['refund_iban'] = $this->normalizeReturnIban($this->request->post['refund_iban']);

		if ((utf8_strlen(trim($this->request->post['invoice_number'])) < 1) || (utf8_strlen(trim($this->request->post['invoice_number'])) > 64)) {
			$this->error['order_id'] = $this->language->get('error_order_id');
		}

		if (empty($this->request->post['invoice_date'])) {
			$this->error['date_ordered'] = $this->language->get('error_date_ordered');
		}

		if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
			$this->error['firstname'] = $this->language->get('error_firstname');
		}

		if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
			$this->error['lastname'] = $this->language->get('error_lastname');
		}

		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		if (!$this->validateReturnProducts($this->getReturnProducts($this->request->post))) {
			$this->error['return_products'] = $this->language->get('error_return_products');
		}

		if ($this->request->post['refund_iban'] !== '' && !$this->isValidReturnIban($this->request->post['refund_iban'])) {
			$this->error['refund_iban'] = $this->language->get('error_refund_iban');
		}

		$requested_reason_id = (int)$this->request->post['return_reason_id'];
		if ($requested_reason_id > 0) {
			$this->load->model('localisation/return_reason');
			$valid_reason = false;

			foreach ($this->model_localisation_return_reason->getReturnReasons() as $reason) {
				if ((int)$reason['return_reason_id'] === $requested_reason_id) {
					$valid_reason = true;
					break;
				}
			}

			if (!$valid_reason) {
				$this->error['reason'] = $this->language->get('error_reason');
			}
		}

		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('return', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$this->error['captcha'] = $captcha;
			}
		}

		if ($this->config->get('config_return_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_return_id'));

			if (!isset($this->request->post['agree'])) {
				if ($information_info) {
					$this->error['warning'] = sprintf($this->language->get('error_agree'), $information_info['title']);
				} else {
					$this->error['warning'] = $this->language->get('error_agree_fallback');
				}
			}
		} elseif (!isset($this->request->post['agree'])) {
			$this->error['warning'] = $this->language->get('error_agree_fallback');
		}

		return !$this->error;
	}

	protected function normalizeReturnDate($value) {
		$value = trim((string)$value);

		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) && checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
			return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
		}

		if (preg_match('/^(\d{1,2})[.\/]\s*(\d{1,2})[.\/]\s*(\d{4})\.?$/', $value, $matches) && checkdate((int)$matches[2], (int)$matches[1], (int)$matches[3])) {
			return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
		}

		return '';
	}

	protected function normalizeReturnIban($value) {
		return utf8_substr(strtoupper(preg_replace('/[\s-]+/', '', trim(strip_tags((string)$value)))), 0, 64);
	}

	protected function isValidReturnIban($value) {
		return (bool)preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $value);
	}

	protected function escapeReturnOutput($value) {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	protected function escapeReturnProductsForOutput($products) {
		foreach ((array)$products as &$product) {
			foreach (array('code', 'quantity', 'price') as $key) {
				$product[$key] = $this->escapeReturnOutput(isset($product[$key]) ? $product[$key] : '');
			}
		}
		unset($product);

		return $products;
	}

	protected function escapeReturnFormForOutput($data) {
		$data['product_id'] = isset($data['product_id']) ? (int)$data['product_id'] : 0;
		$data['return_reason_id'] = isset($data['return_reason_id']) ? (int)$data['return_reason_id'] : 0;

		foreach (array('invoice_number', 'invoice_date', 'firstname', 'lastname', 'email', 'telephone', 'refund_iban', 'comment', 'product', 'model') as $key) {
			if (isset($data[$key])) {
				$data[$key] = $this->escapeReturnOutput($data[$key]);
			}
		}

		$data['return_products'] = $this->escapeReturnProductsForOutput(isset($data['return_products']) ? $data['return_products'] : array());

		foreach ($data['return_reasons'] as &$reason) {
			$reason['name'] = $this->escapeReturnOutput($reason['name']);
		}
		unset($reason);

		return $data;
	}

	public function success() {
		$this->load->language('account/return');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/return', '', true)
		);

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_message'] = $this->language->get('text_message');

		$data['button_continue'] = $this->language->get('button_continue');

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
}
