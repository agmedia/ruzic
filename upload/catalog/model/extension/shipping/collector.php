<?php
class ModelExtensionShippingCollector extends Model {
    function getQuote($address) {
        $this->load->language('extension/shipping/collector');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_collector_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if (!$this->config->get('shipping_collector_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $quote_data = array();

            $value = agconf('shipping_collector_price');
            if (isset($this->session->data['shipping_collector_id']) && ! empty($this->session->data['shipping_collector_id'])) {
                $collector = \Agmedia\Kaonekad\Models\ShippingCollector::find($this->session->data['shipping_collector_id']);

                if ($collector) {
                    $value = $collector->price;
                }
            }

            $total = $this->cart->getSubTotal() - ($this->getTotal($this->cart->getSubTotal()));

            if ($total > agconf('free_shipping_amount')) {
                $value = 0;
            }

            $quote_data['collector'] = array(
                'code'         => 'collector.collector',
                'title'        => $this->language->get('text_description'),
                'cost'         => $value,
                'tax_class_id' => 0,
                'text'         => $this->currency->format($value, $this->session->data['currency'])
            );

            $method_data = array(
                'code'       => 'collector',
                'title'      => $this->language->get('text_title'),
                'quote'      => $quote_data,
                'sort_order' => $this->config->get('shipping_collector_sort_order'),
                'error'      => false
            );
        }

        return $method_data;
    }


    public function getTotal($total)
    {
        $response = 0;

        if (isset($this->session->data['coupon'])) {
            $this->load->language('extension/total/coupon', 'coupon');
            $coupon_info = $this->getCoupon($this->session->data['coupon']);

            if ($coupon_info) {
                $discount_total = $coupon_info['type'] == 'F' ? $coupon_info['discount'] : ($coupon_info['discount'] * $total) / 100;

                // If discount greater than total
                if ($discount_total > $total) {
                    $discount_total = $total;
                }

                if ($discount_total > 0) {
                    $response = $discount_total;
                }
            }
        }

        return $response;
    }


    public function getCoupon($code) {
        $status = true;

        $coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) AND status = '1'");

        if ($coupon_query->num_rows) {
            if ($coupon_query->row['total'] > $this->cart->getSubTotal()) {
                $status = false;
            }

            $coupon_total = $this->getTotalCouponHistoriesByCoupon($code);

            if ($coupon_query->row['uses_total'] > 0 && ($coupon_total >= $coupon_query->row['uses_total'])) {
                $status = false;
            }

            if ($coupon_query->row['logged'] && !$this->customer->getId()) {
                $status = false;
            }

            if ($this->customer->getId()) {
                $customer_total = $this->getTotalCouponHistoriesByCustomerId($code, $this->customer->getId());

                if ($coupon_query->row['uses_customer'] > 0 && ($customer_total >= $coupon_query->row['uses_customer'])) {
                    $status = false;
                }
            }

            // Products
            $coupon_product_data = array();

            $coupon_product_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_product` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

            foreach ($coupon_product_query->rows as $product) {
                $coupon_product_data[] = $product['product_id'];
            }

            // Categories
            $coupon_category_data = array();

            $coupon_category_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_category` cc LEFT JOIN `" . DB_PREFIX . "category_path` cp ON (cc.category_id = cp.path_id) WHERE cc.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

            foreach ($coupon_category_query->rows as $category) {
                $coupon_category_data[] = $category['category_id'];
            }

            $product_data = array();

            if ($coupon_product_data || $coupon_category_data) {
                foreach ($this->cart->getProducts() as $product) {
                    if (in_array($product['product_id'], $coupon_product_data)) {
                        $product_data[] = $product['product_id'];

                        continue;
                    }

                    foreach ($coupon_category_data as $category_id) {
                        $coupon_category_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . (int)$product['product_id'] . "' AND category_id = '" . (int)$category_id . "'");

                        if ($coupon_category_query->row['total']) {
                            $product_data[] = $product['product_id'];

                            continue;
                        }
                    }
                }

                if (!$product_data) {
                    $status = false;
                }
            }
        } else {
            $status = false;
        }

        if ($status) {
            return array(
                'coupon_id'     => $coupon_query->row['coupon_id'],
                'code'          => $coupon_query->row['code'],
                'name'          => $coupon_query->row['name'],
                'type'          => $coupon_query->row['type'],
                'discount'      => $coupon_query->row['discount'],
                'shipping'      => $coupon_query->row['shipping'],
                'total'         => $coupon_query->row['total'],
                'product'       => $product_data,
                'date_start'    => $coupon_query->row['date_start'],
                'date_end'      => $coupon_query->row['date_end'],
                'uses_total'    => $coupon_query->row['uses_total'],
                'uses_customer' => $coupon_query->row['uses_customer'],
                'status'        => $coupon_query->row['status'],
                'date_added'    => $coupon_query->row['date_added']
            );
        }
    }

    public function getTotalCouponHistoriesByCoupon($coupon) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "'");

        return $query->row['total'];
    }

    public function getTotalCouponHistoriesByCustomerId($coupon, $customer_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "' AND ch.customer_id = '" . (int)$customer_id . "'");

        return $query->row['total'];
    }
}