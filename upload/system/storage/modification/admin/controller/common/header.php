<?php
class ControllerCommonHeader extends Controller {
	public function index() {

			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "manufacturer_description` (
	`manufacturer_id` int(11) NOT NULL,
	`language_id` int(11) NOT NULL,
	`description` text COLLATE utf8_bin NOT NULL,
	`meta_description` varchar(255) COLLATE utf8_bin NOT NULL,
	`meta_keyword` varchar(255) COLLATE utf8_bin NOT NULL,
	`custom_title` varchar(255) COLLATE utf8_bin DEFAULT '',
	PRIMARY KEY (`manufacturer_id`,`language_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");
			
$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_description_seo` (
  `product_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `custom_imgtitle` varchar(255) NULL DEFAULT '',
  `custom_alt` varchar(255) NULL DEFAULT '',
  `custom_h1` varchar(255) NULL DEFAULT '',
  `custom_h2` varchar(255) NULL DEFAULT '',
  PRIMARY KEY (`product_id`,`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "category_description_seo` (
  `category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `custom_imgtitle` varchar(255) NULL DEFAULT '',
  `custom_alt` varchar(255) NULL DEFAULT '',
  `custom_h1` varchar(255) NULL DEFAULT '',
  `custom_h2` varchar(255) NULL DEFAULT '',
  PRIMARY KEY (`category_id`,`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "information_description_seo` (
  `information_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `custom_imgtitle` varchar(255) NULL DEFAULT '',
  `custom_alt` varchar(255) NULL DEFAULT '',
  `custom_h1` varchar(255) NULL DEFAULT '',
  `custom_h2` varchar(255) NULL DEFAULT '',
  PRIMARY KEY (`information_id`,`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "autoredirect` (
	`link` varchar(321) NOT NULL,
	`fixedlink` varchar(321) NOT NULL,
	`date` datetime NOT NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");

$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "404s_report` (
	`link` varchar(321) NOT NULL,	
	`date` datetime NOT NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");

$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "bots_report` (
	`link` varchar(321) NOT NULL,	
	`bot` varchar(321) NOT NULL,	
	`ip` varchar(321) NOT NULL,	
	`date` datetime NOT NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");
/*
$titles = $this->config->get('config_meta_title');
$meta_descriptions = $this->config->get('config_meta_description');
$meta_keywords = $this->config->get('config_meta_keyword');
if (!isset($titles[$this->config->get('config_language_id')])) {
			$this->load->model('setting/setting');
			$titles = array();
			$lquery = $this->db->query("SELECT * FROM " . DB_PREFIX . "language;");			
			foreach ($lquery->rows as $language) {
					$language_id = $language['language_id'];
					$titles[$language_id] = $this->config->get('config_meta_title');
				}
			$this->model_setting_setting->editSetting('config_meta_title', $titles);		
}*/
			
		$data['title'] = $this->document->getTitle();

				if ($this->config->get('module_clicker_ckeditor_status')) {
					$enable_ckeditor = true;

					$exclude_ckeditor_routes = array(
						'common/login',
						'common/forgotten',
						'common/reset',
						'marketplace/modification',
						'design/theme',
						'module/journal2',
					);

					if (!empty($this->request->get['route'])) {
						foreach ($exclude_ckeditor_routes as $exclude_ckeditor_route) {
							if (strpos($this->request->get['route'], $exclude_ckeditor_route) === 0) {
								$enable_ckeditor = false;
								break;
							}
						}
					} else {
						$enable_ckeditor = false;
					}

					if ($enable_ckeditor) {
						global $cke_settings, $cke_status;
						$cke_settings = $this->load->controller('extension/module/clicker_ckeditor/getSettings');
						$cke_status = $this->config->get('module_clicker_ckeditor_status');

						$cke_plugins = $this->load->controller('extension/module/clicker_ckeditor/getPlugins');

						if ($cke_plugins) {
							$cke_settings['externalPlugins'] = $cke_plugins;
						}

						$cke_version = $this->load->controller('extension/module/clicker_ckeditor/getVersion');
						$cke_t = date('Ymd000000');

						$onload = 'cke_settings = ' . str_replace('"', "'", json_encode($cke_settings, JSON_HEX_QUOT)) . ';';
						$onload .= "CKEDITOR.config.customConfig = 'clicker/config.js';";

						$this->document->addScript('view/javascript/clicker_ckeditor/ckeditor.js' . '?t=' . date('YmdHis', filemtime(DIR_APPLICATION . 'view/javascript/clicker_ckeditor/ckeditor.js')) . '" onload="' . $onload);

						$this->document->addScript('view/javascript/clicker_ckeditor/clicker/ck_clicker.js?oc=' . (defined('VERSION') ? urlencode(VERSION) : urlencode('3.x')) . '&v=' . urlencode($cke_version) . '&t=' . date('YmdHis', filemtime(DIR_APPLICATION . 'view/javascript/clicker_ckeditor/clicker/ck_clicker.js')));

						$this->document->addScript('view/javascript/clicker_ckeditor/clicker/config.js' . '?t=' . date('YmdHis', filemtime(DIR_APPLICATION . 'view/javascript/clicker_ckeditor/clicker/config.js')));

						$this->document->addStyle('view/javascript/clicker_ckeditor/clicker/ck_clicker.css' . '?t=' . date('YmdHis', filemtime(DIR_APPLICATION . 'view/javascript/clicker_ckeditor/clicker/ck_clicker.css')));
					}
				}
			

		if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}

		if ($this->request->server['HTTPS']) {
            $server = HTTPS_CATALOG;
        } else {
            $server = HTTP_CATALOG;
        }

        if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
        }

		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts();
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$this->load->language('common/header');

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->user->getUserName());

		if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
			$data['logged'] = '';

			$data['home'] = $this->url->link('common/login', '', true);
		} else {
			$data['logged'] = true;

			$data['home'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
			$data['logout'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token'], true);
			$data['profile'] = $this->url->link('common/profile', 'user_token=' . $this->session->data['user_token'], true);

			$this->load->model('user/user');

			$this->load->model('tool/image');

			$user_info = $this->model_user_user->getUser($this->user->getId());

			if ($user_info) {
				$data['firstname'] = $user_info['firstname'];
				$data['lastname'] = $user_info['lastname'];
				$data['username']  = $user_info['username'];
				$data['user_group'] = $user_info['user_group'];

				if (is_file(DIR_IMAGE . $user_info['image'])) {
					$data['image'] = $this->model_tool_image->resize($user_info['image'], 45, 45);
				} else {
					$data['image'] = $this->model_tool_image->resize('profile.png', 45, 45);
				}
			} else {
				$data['firstname'] = '';
				$data['lastname'] = '';
				$data['user_group'] = '';
				$data['image'] = '';
			}

			// Online Stores
			$data['stores'] = array();

			$data['stores'][] = array(
				'name' => $this->config->get('config_name'),
				'href' => HTTP_CATALOG
			);

			$this->load->model('setting/store');

			$results = $this->model_setting_store->getStores();

			foreach ($results as $result) {
				$data['stores'][] = array(
					'name' => $result['name'],
					'href' => $result['url']
				);
			}
		}

		return $this->load->view('common/header', $data);
	}
}
