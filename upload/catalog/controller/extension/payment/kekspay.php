<?php
require_once DIR_APPLICATION . 'controller/extension/payment/kekspay/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class ControllerExtensionPaymentKeksPay extends Controller
{
    public function index()
    {
        $this->load->model('checkout/order');
        
        $this->load->language('extension/payment/kekspay');
        
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
        if ( ! $this->config->get('payment_kekspay_test')) {
            $data['action'] = 'https://kekspayuat.erstebank.hr/eretailer';
            $data['deep_link'] = 'https://kekspay.hr/bpay';
        } else {

            $data['action'] = 'https://kekspayuat.erstebank.hr/eretailer';
            $data['deep_link'] = 'https://kekspay.hr/galebpay';
        }
        
        if ($this->request->server['HTTPS']) {
            $data['logo'] = HTTPS_SERVER . 'image/keks-logo.svg';
        } else {
            $data['logo'] = HTTP_SERVER . 'image/keks-logo.svg';
        }
        
        if ($this->request->server['HTTPS']) {
            $success_url = HTTPS_SERVER . 'index.php?route=extension/payment/kekspay/success';
            $fail_url    = HTTPS_SERVER . 'index.php?route=extension/payment/kekspay/fail';
        } else {
            $success_url = HTTP_SERVER . 'index.php?route=extension/payment/kekspay/success';
            $fail_url    = HTTP_SERVER . 'index.php?route=extension/payment/kekspay/fail';
        }
        
        $store_name = $this->config->get('payment_kekspay_shop_title') != '' ? $this->config->get('payment_kekspay_shop_title') : 'Trgovina';
        
        $data['qr_code']     = 1;
        $data['cid']         = $this->config->get('payment_kekspay_cid');
        $data['tid']         = $this->config->get('payment_kekspay_tid');
        $data['bill_id']     = $this->config->get('payment_kekspay_cid') . time() . $order_info['order_id'];
        $data['amount']      = number_format($order_info['total'], 2, '.', '');
        $data['store']       = rawurlencode($store_name);
        $data['success_url'] = rawurlencode($success_url);
        $data['fail_url']    = rawurlencode($fail_url);
        
        $data['button_confirm'] = $this->language->get('kekspay_btn_confirm');
        $data['order_id']       = $order_info['order_id'];
        
        $options = new QROptions([
            'version'          => 6,
            'quietzoneSize'    => 4,
            'eccLevel'         => QRCode::ECC_L,
            'imageTransparent' => false,
        ]);
        
        $qrdata = [
            "qr_type" => 1,
            "cid"     => $this->config->get('payment_kekspay_cid'),
            "tid"     => $this->config->get('payment_kekspay_tid'),
            "bill_id" => $this->config->get('payment_kekspay_cid') . time() . $order_info['order_id'],
            "amount"  => number_format($order_info['total'], 2, '.', ''),
            "store"   => rawurlencode($store_name),
        ];
        
        $qrcode = new QRCode($options);
        
        $data['qrcode'] = $qrcode->render(json_encode($qrdata));
        
        return $this->load->view('extension/payment/kekspay', $data);
    }
    
    
    public function callback()
    {
        $this->load->model('checkout/order');
        
        $json_response = json_decode(file_get_contents('php://input'), true);

        $headers = apache_request_headers();
        $headers = $headers['Authorization'];

        
        $order_id = substr($json_response['bill_id'], 16);
        
        if ( ! $json_response['status'] && $this->verify_kekspay_token($headers)) {
            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_kekspay_order_status_id'), '', true);
            
            $this->response->addHeader('Content-Type: application/json');
            return $this->response->setOutput(json_encode([
                'status'  => 0,
                'message' => 'Accepted'
            ]));
            
        } else {
            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'), '', true);
            
            $this->response->addHeader('Content-Type: application/json');
            return $this->response->setOutput(json_encode([
                'status'  => 1,
                'message' => 'Failed'
            ]));
        }
        
    }
    
    
    public function check()
    {
        $json           = [];
        $json['status'] = 0;
    
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->request->post['order_id']);
        
        if ($order_info['order_status_id']) {
            $json['redirect'] = $this->url->link('checkout/checkout');
            $json['status']   = 1;
            
            if ($order_info['order_status_id'] == $this->config->get('payment_kekspay_order_status_id')) {
                $json['redirect'] = $this->url->link('checkout/success');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    
    public function success()
    {
        $json_response = json_decode(file_get_contents('php://input'), true);
        

    }
    
    
    public function fail()
    {
        $json_response = json_decode(file_get_contents('php://input'), true);
        

    }

    public function verify_kekspay_token($headers) {

        $token    = isset( $headers ) ? filter_var(stripslashes( $headers ), FILTER_SANITIZE_STRING ) : false;

        return hash_equals( $this->config->get('payment_kekspay_token'), str_replace( 'Token ', '', $token ) );


    }
}

?>