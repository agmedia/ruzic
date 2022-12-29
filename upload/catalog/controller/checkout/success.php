<?php
class ControllerCheckoutSuccess extends Controller {
	public function index() {
		$this->load->language('checkout/success');

        $this->log->write($this->session->data);

		if (isset($this->session->data['order_id'])) {
			$this->cart->clear();


			//

            $this->createWebracun($this->session->data['order_id']);

            $order_id = $this->session->data['order_id'];
            
            // fj.agmedia.hr
            // Dodaj u SC ako je narudžba uspješna.
            $order = \Agmedia\Models\Order\Order::query()->where('order_id', $this->session->data['order_id'])->first();
            if ($order->shipping_collector_id) {
                \Agmedia\Features\Models\ShippingCollector::query()->where('shipping_collector_id', $order->shipping_collector_id)->increment('collected');
            }


            

            //

			$shipping = $this->session->data['shipping_method'];

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_success'),
			'href' => $this->url->link('checkout/success')
		);




		if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
		} else {
			$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		}

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}

    public function createWebracun($order_id) {


        $order          = $this->getOrder($order_id);
        $order_products = $this->getOrderProducts($order_id);
        $order_totals   = $this->getOrderTotals($order_id);

        $this->log->write($order);
        $this->log->write($order_products);
        $this->log->write($order_totals);

        if ($order &&  $order['payment_code'] != 'cod') {

            // nacin_placanja

            $pay_code = 0;
            if ($order['payment_code'] == 'corvuspay') {
                $pay_code = 'Card';

            } else if ($order['payment_code'] == 'kekspay') {
                $pay_code = 'Card';

            }
            else if ($order['payment_code'] == 'cod') {
                $pay_code = 'Cash on Delivery (COD)';

            } else {
                $pay_code = 'Bank Deposit';

            }

            // jezik_ponude

            $lang = 0;
            if ($order['currency_code'] == 'HRK') {
                $lang = 1;
            } else {
                $lang = 2;
            }

            // valuta_ponude

            $curr = 0;
            if ($order['currency_code'] == 'HRK') {
                $curr = 1;
            } else if ($order['currency_code'] == 'EUR') {
                $curr = 14;
            } else if ($order['currency_code'] == 'AUD') {
                $curr = 2;
            } else if ($order['currency_code'] == 'GBP') {
                $curr = 11;
            } else if ($order['currency_code'] == 'USD') {
                $curr = 12;
            }



            // porez_stopa
            //$tax = 0;
            //if (in_array($order['payment_country'], $countries)) {
            $tax = 25;
            // }

            // Tečaj
            $curval = 1;
            if ($order['currency_code'] != 'HRK') {
                $curval = number_format($order['currency_value'], 2, ',', '.');
            }



            $order_items = array();

            foreach ($order_products as $item) {

                $item_data = [
                    "title" => $item['name'],
                    "quantity" =>  $item['quantity'],
                    "price" => $item['price'],
                    "sku" => $item['model'],


                ];
                array_push($order_items, $item_data);
            }


            foreach ($order_totals as $total) {
                if ($total['code'] == 'coupon' && $total['value'] != 0) {
                    $item_data2 = [
                        "title" => $total['title'],
                        "quantity" =>  '1',
                        "price" => $total['value'] ,
                        "sku" => 'kupon',

                    ];

                    array_push($order_items, $item_data2);
                }
            }


            $shippingcost = '0';

            foreach ($order_totals as $total) {
                if ($total['code'] == 'shipping' && $total['value'] != 0) {
                    $shippingcost = $total['value'];

                }

            }



            #order data - prepare JSON structure
            $order_data = array(
                'id'            => strval($order_id),
                'email'         => $order['email'],
                'name'          => '#'.$order_id,
                'gateway'       => $pay_code,

                'line_items'    => $order_items, //sending coupons as order items with negative value
                'customer'      =>
                    array(
                        'email'          => $order['email'],
                        'first_name'     => $order['firstname'],
                        'last_name'      => $order['lastname'],
                        'default_address' => [
                            'address1'    => $order['payment_address_1'].' '.$order['payment_address_2'],
                            'city'        => $order['payment_city'].' '.$order['payment_postcode'],
                            'country'     => $order['payment_country'],
                            'company'     => $order['payment_company']
                            //  'address2'     => $order['custom_field'][1] //OIB FIELD!
                        ]
                    ),
                'total_shipping_price_set' =>
                    array(
                        'shop_money'         => [
                            'amount'          => $shippingcost,
                            'currency_code'   => $order['currency_code']
                        ]
                    )
            );


            $this->log->write($order_data);


            $order_data = json_encode($order_data);

            #connect to WebRacun API
            $url = 'https://www.app.webracun.com/rest/shopify/api/v1/order-invoice/vro_cart/dZhj78kNwQkjgpLK/true';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $order_data);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($order_data)
                )
            );


            #send JSON data

            $result = curl_exec($ch);

            $this->log->write($result);
            // Neema razulatata u $response jer AP Ivraća samo HEADER

            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $this->log->write($httpcode);

            curl_close($ch);


            if ($httpcode == '200') {
                $this->db->query("UPDATE " . DB_PREFIX . "order SET webracun = 'Račun napravljen i poslan kupcu' WHERE order_id = '" . (int)$order['order_id'] . "'");
                //$message ='Račun napravljen i poslan kupcu';
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "order SET webracun = 'Greška kod izrade računa' WHERE order_id = '" . (int)$order['order_id'] . "'");
               // $message ='Greška kod izrade računa';
            }

            //return $message;
        }
    }


    public function getOrderProducts($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    public function getOrder($order_id) {
        $order_query = $this->db->query("SELECT *, (SELECT CONCAT(c.firstname, ' ', c.lastname) FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

        if ($order_query->num_rows) {
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

            if ($country_query->num_rows) {
                $payment_iso_code_2 = $country_query->row['iso_code_2'];
                $payment_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $payment_iso_code_2 = '';
                $payment_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $payment_zone_code = $zone_query->row['code'];
            } else {
                $payment_zone_code = '';
            }

            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

            if ($country_query->num_rows) {
                $shipping_iso_code_2 = $country_query->row['iso_code_2'];
                $shipping_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $shipping_iso_code_2 = '';
                $shipping_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $shipping_zone_code = $zone_query->row['code'];
            } else {
                $shipping_zone_code = '';
            }

            $reward = 0;

            $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

            foreach ($order_product_query->rows as $product) {
                $reward += $product['reward'];
            }



            $this->load->model('localisation/language');

            $language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

            if ($language_info) {
                $language_code = $language_info['code'];
            } else {
                $language_code = $this->config->get('config_language');
            }

            return array(
                'order_id'                => $order_query->row['order_id'],
                'invoice_no'              => $order_query->row['invoice_no'],
                'webracun'              => $order_query->row['webracun'],
                'webracun_link'              => $order_query->row['webracun_link'],
                'invoice_prefix'          => $order_query->row['invoice_prefix'],
                'store_id'                => $order_query->row['store_id'],
                'bill_id'                => $order_query->row['bill_id'],
                'amount'                => $order_query->row['amount'],
                'refunded'                => $order_query->row['refunded'],
                'refund_qty'                => $order_query->row['refund_qty'],
                'store_name'              => $order_query->row['store_name'],
                'store_url'               => $order_query->row['store_url'],
                'customer_id'             => $order_query->row['customer_id'],
                'customer'                => $order_query->row['customer'],
                'customer_group_id'       => $order_query->row['customer_group_id'],
                'firstname'               => $order_query->row['firstname'],
                'lastname'                => $order_query->row['lastname'],
                'email'                   => $order_query->row['email'],
                'telephone'               => $order_query->row['telephone'],
                'custom_field'            => json_decode($order_query->row['custom_field'], true),
                'payment_firstname'       => $order_query->row['payment_firstname'],
                'payment_lastname'        => $order_query->row['payment_lastname'],
                'payment_company'         => $order_query->row['payment_company'],
                'payment_address_1'       => $order_query->row['payment_address_1'],
                'payment_address_2'       => $order_query->row['payment_address_2'],
                'payment_postcode'        => $order_query->row['payment_postcode'],
                'payment_city'            => $order_query->row['payment_city'],
                'payment_zone_id'         => $order_query->row['payment_zone_id'],
                'payment_zone'            => $order_query->row['payment_zone'],
                'payment_zone_code'       => $payment_zone_code,
                'payment_country_id'      => $order_query->row['payment_country_id'],
                'payment_country'         => $order_query->row['payment_country'],
                'payment_iso_code_2'      => $payment_iso_code_2,
                'payment_iso_code_3'      => $payment_iso_code_3,
                'payment_address_format'  => $order_query->row['payment_address_format'],
                'payment_custom_field'    => json_decode($order_query->row['payment_custom_field'], true),
                'payment_method'          => $order_query->row['payment_method'],
                'payment_code'            => $order_query->row['payment_code'],
                'shipping_firstname'      => $order_query->row['shipping_firstname'],
                'shipping_lastname'       => $order_query->row['shipping_lastname'],
                'shipping_company'        => $order_query->row['shipping_company'],
                'shipping_address_1'      => $order_query->row['shipping_address_1'],
                'shipping_address_2'      => $order_query->row['shipping_address_2'],
                'shipping_postcode'       => $order_query->row['shipping_postcode'],
                'shipping_city'           => $order_query->row['shipping_city'],
                'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
                'shipping_zone'           => $order_query->row['shipping_zone'],
                'shipping_zone_code'      => $shipping_zone_code,
                'shipping_country_id'     => $order_query->row['shipping_country_id'],
                'shipping_country'        => $order_query->row['shipping_country'],
                'shipping_iso_code_2'     => $shipping_iso_code_2,
                'shipping_iso_code_3'     => $shipping_iso_code_3,
                'shipping_address_format' => $order_query->row['shipping_address_format'],
                'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
                'shipping_method'         => $order_query->row['shipping_method'],
                'shipping_code'           => $order_query->row['shipping_code'],
                'comment'                 => $order_query->row['comment'],
                'total'                   => $order_query->row['total'],
                'reward'                  => $reward,
                'order_status_id'         => $order_query->row['order_status_id'],
                'order_status'            => $order_query->row['order_status'],
                'affiliate_id'            => $order_query->row['affiliate_id'],
                'affiliate_firstname'     => '', //$affiliate_firstname,
                'affiliate_lastname'      => '', //$affiliate_lastname,
                'commission'              => $order_query->row['commission'],
                'language_id'             => $order_query->row['language_id'],
                'language_code'           => $language_code,
                'currency_id'             => $order_query->row['currency_id'],
                'currency_code'           => $order_query->row['currency_code'],
                'currency_value'          => $order_query->row['currency_value'],
                'ip'                      => $order_query->row['ip'],
                'forwarded_ip'            => $order_query->row['forwarded_ip'],
                'user_agent'              => $order_query->row['user_agent'],
                'accept_language'         => $order_query->row['accept_language'],
                'date_added'              => $order_query->row['date_added'],
                'date_modified'           => $order_query->row['date_modified']
            );
        } else {
            return;
        }
    }

    public function getOrderTotals($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");

        return $query->rows;
    }
}