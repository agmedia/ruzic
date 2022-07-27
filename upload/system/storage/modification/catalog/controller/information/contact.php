<?php
class ControllerInformationContact extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('information/contact');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			/*start gdpr 28-07-2018*/
			/*mpgdpr starts*/
			if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_contactus') && $this->config->get('mpgdpr_policy_contactus')) {
				$this->load->language('mpgdpr/gdpr');
				$this->load->model('catalog/information');
				$this->load->model('mpgdpr/mpgdpr');
				$information_info = $this->model_catalog_information->getInformation($this->config->get('mpgdpr_policy_contactus'));
				if ($information_info) {
					$email = '';
					if(isset($this->request->post['email']) && !$this->customer->getId()) {
						$email = $this->request->post['email'];
					}
					$insert_data = array(
						'customer_id' => $this->customer->getId(),
						'email' => $email,
						'policy_id' => $information_info['information_id'],
						'policy_title' => $information_info['title'],
						'policy_description' => $information_info['description'],
					);

					/*13 sep 2019 gdpr session starts*/
					$mpgdpr_policyacceptance_id = $this->model_mpgdpr_mpgdpr->addPolicyAcceptance($this->mpgdpr->codepolicyacceptcontactus, $insert_data);

					// Add to request log
					$request_data = array(
						'customer_id' => $this->customer->getId(),
						'email' => $email,
						'date' => date('Y-m-d H:i:s'),
						'custom_string' => sprintf($this->language->get('text_gdpr_policyacceptcontactus_custom_msg'), $mpgdpr_policyacceptance_id ),
					);
					$mpgdpr_requestlist_id = $this->model_mpgdpr_mpgdpr->addRequest($this->mpgdpr->codepolicyacceptcontactus, $request_data);
					/*13 sep 2019 gdpr session ends*/
				}
			}
			/*mpgdpr ends*/
			/*end gdpr 28-07-2018*/
			
			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		      $message  = 'Ime: '.$this->request->post['name'] . "\n";
            $message  .= 'Telefon: '.$this->request->post['telefon'] . "\n";

            $message  .= 'Email: '.$this->request->post['email'] . "\n";
            $message .= $this->request->post['enquiry']. "\n\n";

            $mail->setTo($this->config->get('config_email'));
            $mail->setFrom($this->request->post['email']);
            $mail->setReplyTo($this->request->post['email']);
            $mail->setSender(html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8'));
            $mail->setSubject('Upit sa kontakt obrasca - Videonadzor.hr');
            $mail->setText($message);
            $mail->send();

            $this->response->redirect($this->url->link('information/contact/success'));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/contact')
		);

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}


			/*start gdpr 28-07-2018*/
			/*mpgdpr starts*/
			if (isset($this->error['warning'])) {
				$data['error_warning'] = $this->error['warning'];
			} else {
				$data['error_warning'] = '';
			}
			/*mpgdpr ends*/
			/*end gdpr 28-07-2018*/
			
		if (isset($this->error['enquiry'])) {
			$data['error_enquiry'] = $this->error['enquiry'];
		} else {
			$data['error_enquiry'] = '';
		}

		$data['button_submit'] = $this->language->get('button_submit');

		$data['action'] = $this->url->link('information/contact', '', true);

		$this->load->model('tool/image');

		if ($this->config->get('config_image')) {
			$data['image'] = $this->model_tool_image->resize($this->config->get('config_image'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'));
		} else {
			$data['image'] = false;
		}


		$data['basel_map_style'] = $this->config->get('basel_map_style');
		$data['basel_map_lat'] = $this->config->get('basel_map_lat');
		$data['basel_map_lon'] = $this->config->get('basel_map_lon');
		if ($this->config->get('basel_map_style')) {
		$this->document->addScript('https://maps.google.com/maps/api/js?sensor=false&libraries=geometry&v=3.24&key=' . $this->config->get('basel_map_api') . '');
		$this->document->addScript('catalog/view/theme/basel/js/maplace.min.js');
		}
		
		$data['store'] = $this->config->get('config_name');
		$data['address'] = nl2br($this->config->get('config_address'));
		$data['geocode'] = $this->config->get('config_geocode');
		$data['geocode_hl'] = $this->config->get('config_language');
		$data['telephone'] = $this->config->get('config_telephone');
		$data['fax'] = $this->config->get('config_fax');
		$data['open'] = nl2br($this->config->get('config_open'));
		$data['comment'] = $this->config->get('config_comment');

		$data['locations'] = array();

		$this->load->model('localisation/location');

		foreach((array)$this->config->get('config_location') as $location_id) {
			$location_info = $this->model_localisation_location->getLocation($location_id);

			if ($location_info) {
				if ($location_info['image']) {
					$image = $this->model_tool_image->resize($location_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'));
				} else {
					$image = false;
				}

				$data['locations'][] = array(
					'location_id' => $location_info['location_id'],
					'name'        => $location_info['name'],
					'address'     => nl2br($location_info['address']),
					'geocode'     => $location_info['geocode'],
					'telephone'   => $location_info['telephone'],
					'fax'         => $location_info['fax'],
					'image'       => $image,
					'open'        => nl2br($location_info['open']),
					'comment'     => $location_info['comment']
				);
			}
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} else {
			$data['name'] = $this->customer->getFirstName();
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = $this->customer->getEmail();
		}


			/*start gdpr 28-07-2018*/
			/*mpgdpr starts*/
			$data['mpgdpr_status'] = $this->config->get('mpgdpr_status');
			if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_contactus') && $this->config->get('mpgdpr_policy_contactus')) {

				$this->load->language('mpgdpr/gdpr');

				$this->load->model('catalog/information');
				$information_info = $this->model_catalog_information->getInformation($this->config->get('mpgdpr_policy_contactus'));
				if ($information_info) {
					$data['text_mpgdpr_agree'] = sprintf($this->language->get('text_mpgdpr_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('mpgdpr_policy_contactus'), true), $information_info['title'], $information_info['title']);
				} else {
					$data['text_mpgdpr_agree'] = '';
				}
			} else {
				$data['text_mpgdpr_agree'] = '';
			}

			if (isset($this->request->post['mpgdpr_agree'])) {
				$data['mpgdpr_agree'] = $this->request->post['mpgdpr_agree'];
			} else {
				$data['mpgdpr_agree'] = false;
			}
			/*mpgdpr ends*/
			/*end gdpr 28-07-2018*/
			
		if (isset($this->request->post['enquiry'])) {
			$data['enquiry'] = $this->request->post['enquiry'];
		} else {
			$data['enquiry'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

                $this->load->model('extension/module/hb_seo_snippets');
			    $this->model_extension_module_hb_seo_snippets->breadcrumbs_sd($data['breadcrumbs']);
			    $this->model_extension_module_hb_seo_snippets->local_business();
				
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/contact', $data));
	}

	protected function validate() {
		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 32)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}


			/*start gdpr 28-07-2018*/
			/*mpgdpr starts*/

			if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_contactus') && $this->config->get('mpgdpr_policy_contactus')) {

				$this->load->language('mpgdpr/gdpr');

				$this->load->model('catalog/information');
				$information_info = $this->model_catalog_information->getInformation($this->config->get('mpgdpr_policy_contactus'));
				if ($information_info && !isset($this->request->post['mpgdpr_agree'])) {
					$this->error['warning'] = sprintf($this->language->get('error_mpgdpr_agree'), $information_info['title']);
				}
			}
			/*mpgdpr ends*/
			/*end gdpr 28-07-2018*/
			
		if ((utf8_strlen($this->request->post['enquiry']) < 10) || (utf8_strlen($this->request->post['enquiry']) > 3000)) {
			$this->error['enquiry'] = $this->language->get('error_enquiry');
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$this->error['captcha'] = $captcha;
			}
		}

		return !$this->error;
	}

	public function success() {
		$this->load->language('information/contact');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/contact')
		);

 		$data['text_message'] = $this->language->get('text_message'); 

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

                $this->load->model('extension/module/hb_seo_snippets');
			    $this->model_extension_module_hb_seo_snippets->breadcrumbs_sd($data['breadcrumbs']);
			    $this->model_extension_module_hb_seo_snippets->local_business();
				
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
}
