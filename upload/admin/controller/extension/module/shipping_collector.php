<?php
class ControllerExtensionModuleShippingCollector extends Controller {
	private $error = array();
    
    
    public function install()
    {
        $this->load->model('extension/module/shipping_collector');
        $this->model_extension_module_shipping_collector->installShippingCollector();
    }
    
    
    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "shipping_collector`");
    }
    
    
    public function index()
    {
        $this->load->language('extension/module/shipping_collector');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('extension/module/shipping_collector');
    
        $this->getList();
    }
    
    
    public function add()
    {
        $this->load->language('extension/module/shipping_collector');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('extension/module/shipping_collector');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_shipping_collector->addShippingCollector($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    
    public function edit() {
        $this->load->language('extension/module/shipping_collector');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('extension/module/shipping_collector');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_shipping_collector->editShippingCollector($this->request->get['shipping_collector_id'], $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    
    public function delete() {
        $this->load->language('extension/module/shipping_collector');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('extension/module/shipping_collector');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $shipping_collector_id) {
                $this->model_extension_module_shipping_collector->deleteShippingCollector($shipping_collector_id);
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getList();
    }
    
    
    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'sc.collect_date';
        }
        
        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }
        
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        
        $url = '';
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        
        $data['breadcrumbs'] = array();
    
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
    
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );
    
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'], true)
        );
    
        $data['advance'] = $this->url->link('extension/module/shipping_collector/advance', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['add'] = $this->url->link('extension/module/shipping_collector/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('extension/module/shipping_collector/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        $data['shipping_collectors'] = array();
        
        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
        
        $shipping_collector_total = $this->model_extension_module_shipping_collector->getTotalShippingCollectors();
        
        $results = $this->model_extension_module_shipping_collector->getShippingCollectors($filter_data);
        
        foreach ($results as $result) {
            $data['shipping_collectors'][] = array(
                'shipping_collector_id' => $result['shipping_collector_id'],
                'collect_date'          => \Carbon\Carbon::make($result['collect_date'])->format('d.m.Y'),
                'collect_time'          => $result['collect_time'],
                'collect_destination'   => $result['collect_destination'],
                'collect_max'           => $result['collect_max'],
                'collected'             => $result['collected'],
                'status'                => $result['status'],
                'edit'                  => $this->url->link('extension/module/shipping_collector/edit', 'user_token=' . $this->session->data['user_token'] . '&shipping_collector_id=' . $result['shipping_collector_id'] . $url, true)
            );
        }
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }
        
        $url = '';
        
        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }
        
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        
        $data['sort_date'] = $this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.collect_date' . $url, true);
        
        $url = '';
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        $pagination = new Pagination();
        $pagination->total = $shipping_collector_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        
        $data['results'] = sprintf($this->language->get('text_pagination'), ($shipping_collector_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($shipping_collector_total - $this->config->get('config_limit_admin'))) ? $shipping_collector_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $shipping_collector_total, ceil($shipping_collector_total / $this->config->get('config_limit_admin')));
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/shipping_collector_list', $data));
    }
    
    
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['shipping_collector_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
    
        if (isset($this->error['collect_date'])) {
            $data['error_collect_date'] = $this->error['collect_date'];
        } else {
            $data['error_collect_date'] = '';
        }
    
        if (isset($this->error['collect_time'])) {
            $data['error_collect_time'] = $this->error['collect_time'];
        } else {
            $data['error_collect_time'] = '';
        }
    
        if (isset($this->error['collect_max'])) {
            $data['error_collect_max'] = $this->error['collect_max'];
        } else {
            $data['error_collect_max'] = '';
        }
        
        $url = '';
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        if (!isset($this->request->get['shipping_collector_id'])) {
            $data['action'] = $this->url->link('extension/module/shipping_collector/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('extension/module/shipping_collector/edit', 'user_token=' . $this->session->data['user_token'] . '&shipping_collector_id=' . $this->request->get['shipping_collector_id'] . $url, true);
        }
        
        $data['cancel'] = $this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        if (isset($this->request->get['shipping_collector_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $shipping_collector_info = $this->model_extension_module_shipping_collector->getShippingCollector($this->request->get['shipping_collector_id']);
        }
        
        $this->load->model('localisation/language');
        
        $data['languages'] = $this->model_localisation_language->getLanguages();
        
        $data['default_buttons'] = $this->setDefaultButtons();
        
        if (isset($this->request->post['collect_date'])) {
            $data['collect_date'] = $this->request->post['collect_date'];
        } elseif (!empty($shipping_collector_info)) {
            $data['collect_date'] = \Carbon\Carbon::make($shipping_collector_info['collect_date'])->format('Y-m-d');
        } else {
            $data['collect_date'] = '';
        }
    
        if (isset($this->request->post['collect_time'])) {
            $data['collect_time'] = $this->request->post['collect_time'];
        } elseif (!empty($shipping_collector_info)) {
            $data['collect_time'] = $shipping_collector_info['collect_time'];
        } else {
            $data['collect_time'] = '';
        }
    
        if (isset($this->request->post['collect_destination'])) {
            $data['collect_destination'] = $this->request->post['collect_destination'];
        } elseif (!empty($shipping_collector_info)) {
            $data['collect_destination'] = $shipping_collector_info['collect_destination'];
        } else {
            $data['collect_destination'] = '';
        }
    
        if (isset($this->request->post['collect_max'])) {
            $data['collect_max'] = $this->request->post['collect_max'];
        } elseif (!empty($shipping_collector_info)) {
            $data['collect_max'] = $shipping_collector_info['collect_max'];
        } else {
            $data['collect_max'] = '';
        }

        if (isset($this->request->post['collected'])) {
            $data['collected'] = $this->request->post['collected'];
        } elseif (!empty($shipping_collector_info)) {
            $data['collected'] = $shipping_collector_info['collected'];
        } else {
            $data['collected'] = 0;
        }

        if (isset($this->request->post['price'])) {
            $data['price'] = $this->request->post['price'];
        } elseif (!empty($shipping_collector_info)) {
            $data['price'] = $shipping_collector_info['price'];
        } else {
            $data['price'] = 0;
        }
    
        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($shipping_collector_info)) {
            $data['status'] = $shipping_collector_info['status'];
        } else {
            $data['status'] = '';
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/shipping_collector_form', $data));
    }
    
    
    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/shipping_collector')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
    
        if ((utf8_strlen($this->request->post['collect_date']) < 1) || (utf8_strlen($this->request->post['collect_date']) > 64)) {
            $this->error['collect_date'] = $this->language->get('error_collect_date');
        }
    
        if ((utf8_strlen($this->request->post['collect_time']) < 1) || (utf8_strlen($this->request->post['collect_time']) > 64)) {
            $this->error['collect_time'] = $this->language->get('error_collect_time');
        }
    
        if ((utf8_strlen($this->request->post['collect_max']) < 0) || (utf8_strlen($this->request->post['collect_max']) > 5)) {
            $this->error['collect_max'] = $this->language->get('error_collect_max');
        }
        
        return !$this->error;
    }
    
    
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/shipping_collector')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    
    protected function setDefaultButtons()
    {
        $btn = [];
    
        foreach (agconf('shipping_collector_defaults') as $default) {
            $btn[] = [
                'label' => $default['time'],
                'max'   => $default['max'],
            ];
        }
        
        return $btn;
    }
    
    
    public function advance()
    {
        $this->load->model('extension/module/shipping_collector');
        
        $last = \Agmedia\Features\Models\ShippingCollector::orderBy('collect_date', 'desc')->first();
    
        $date = ! $last ? \Carbon\Carbon::now() : \Carbon\Carbon::make($last->collect_date)->addDay();
        
        $counter = 0;
        
        for ($i = 0; $i < 7; $i++) {
            $destination = $last ? (($last->collect_destination == 'zapad') ? 'istok' : 'zapad') : 'istok';
            if (($counter+1) % 2 == 0) {
                $destination = ($destination == 'zapad') ? 'istok' : 'zapad';
            }
            //if ( ! $date->isWeekend()) {
                foreach (agconf('shipping_collector_defaults') as $default) {
                    $default['date'] = $date;
                    $default['destination'] = $destination;
                    $this->model_extension_module_shipping_collector->addDefaultShippingCollector($default);
                }
            //}
            
            $counter++;
            $date->addDay();
        }
        
        $this->index();
    }
}