<?php
class ControllerProductCategory extends Controller {
	public function index() {
		$this->load->language('product/category');

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		// Basel start
		$this->load->model('extension/basel/basel');
		$this->load->language('basel/basel_theme');
		$data['basel_button_quickview'] = $this->language->get('basel_button_quickview');
		$data['basel_text_new'] = $this->language->get('basel_text_new');
		$data['basel_text_days'] = $this->language->get('basel_text_days');
		$data['basel_text_hours'] = $this->language->get('basel_text_hours');
		$data['basel_text_mins'] = $this->language->get('basel_text_mins');
		$data['basel_text_secs'] = $this->language->get('basel_text_secs');
		$data['category_thumb_status'] = $this->config->get('category_thumb_status');
		$data['category_subs_status'] = $this->config->get('category_subs_status');
		$data['countdown_status'] = $this->config->get('countdown_status');
		$data['salebadge_status'] = $this->config->get('salebadge_status');
		$data['basel_subs_grid'] = $this->config->get('basel_subs_grid');
		$data['basel_prod_grid'] = $this->config->get('basel_prod_grid');
		$data['basel_list_style'] = $this->config->get('basel_list_style');
		$data['stock_badge_status'] = $this->config->get('stock_badge_status');
		$data['basel_text_out_of_stock'] = $this->language->get('basel_text_out_of_stock');
		$data['default_button_cart'] = $this->language->get('button_cart');
		$data['direction'] = $this->language->get('direction');
		if ($this->language->get('direction') == 'rtl') { $data['tooltip_align'] = 'right'; } else { $data['tooltip_align'] = 'left'; }
		// Basel end
		

		$this->load->model('tool/image');

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		} else {
			$filter = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.price';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['path'])) {
			$url = '';

                // Flexi filter
                $url .= $this->load->controller('extension/module/tf_filter/url', $url);
                

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path . $url)
					);
				}
			}
		} else {
			$category_id = 0;
		}

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if ($category_info) {
			$this->document->setTitle($category_info['meta_title']?$category_info['meta_title']:$category_info['name']);

			/** + robot, noindex */
			if( isset( $this->request->get['page'] ) || isset( $this->request->get['limit'] ) || isset( $this->request->get['order'] ) ) {
				$this->document->setRobots( 'noindex,follow' );
			}
				
			$this->document->setDescription($category_info['meta_description']);
			$this->document->setKeywords($category_info['meta_keyword']);

			$data['heading_title'] = ($category_info['custom_h1'] <> '')?$category_info['custom_h1']:$category_info['name'];

			$data['custom_alt'] = $category_info['custom_alt'];
			$data['custom_imgtitle'] = $category_info['custom_imgtitle'];
			

			$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

			// Set the last category breadcrumb
			$data['breadcrumbs'][] = array(
				'text' => $category_info['name'],
				'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'])
			);

			if ($category_info['image']) {
				$data['thumb'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
			} else {
				$data['thumb'] = '';
			}

			$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
$data['description'] = ($category_info['custom_h2'] != '')?'<h2>'.$category_info['custom_h2'].'</h2>'.$data['description']:$data['description'];
			$data['compare'] = $this->url->link('product/compare');

			$url = '';

                // Flexi filter
                $url .= $this->load->controller('extension/module/tf_filter/url', $url);
                

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['categories'] = array();

			$results = $this->model_catalog_category->getCategories($category_id);

			foreach ($results as $result) {
				$filter_data = array(
					'filter_category_id'  => $result['category_id'],
					'filter_sub_category' => true
				);


		//Basel start
		if ($result['image']) {
		$image = $this->model_tool_image->resize($result['image'], $this->config->get('subcat_image_width'), $this->config->get('subcat_image_height'));
		} else {
		$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('subcat_image_width'), $this->config->get('subcat_image_height'));
		}
		
				$data['categories'][] = array(

		//Basel start
		'thumb' => $image,
		

		//Basel start
		'thumb' => $image,
		
					'name' => $result['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $result['category_id'] . $url)
				);
			}

			$data['products'] = array();

			$filter_data = array(
				'filter_category_id' => $category_id,
				'filter_filter'      => $filter,
				'sort'               => $sort,
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);


                // Flexi filter
                $filter_data = $this->load->controller('extension/module/tf_filter/filter_data', $filter_data);
                
			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}


		$image2 = $this->model_catalog_product->getProductImages($result['product_id']);
		if(isset($image2[0]['image']) && !empty($image2[0]['image']) && $this->config->get('basel_thumb_swap')){
			if (isset($this->request->get['route']) == 'product/product' && isset($this->request->get['product_id'])) {
			$image2 = $this->model_tool_image->resize($image2[0]['image'], $this->config->get('theme_default_image_related_width'), $this->config->get('theme_default_image_related_height'));
			} else {
			$image2 = $this->model_tool_image->resize($image2[0]['image'], $this->config->get('theme_default_image_product_width'), $this->config->get('theme_default_image_product_height'));
			}
		} else {
			$image2 = false;
		}
		if ((float)$result['special']) {
			$date_end = $this->model_extension_basel_basel->getSpecialEndDate($result['product_id']);
		} else {
			$date_end = false;
		}
		if ( (float)$result['special'] && ($this->config->get('salebadge_status')) ) {
			if ($this->config->get('salebadge_status') == '2') {
				$sale_badge = '-' . number_format(((($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')))-($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax'))))/(($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')))/100)), 0, ',', '.') . '%';
			} else {
				$sale_badge = $this->language->get('basel_text_sale');
			}		
		} else {
			$sale_badge = false;
		}
		if (strtotime($result['date_available']) > strtotime('-' . $this->config->get('newlabel_status') . ' day')) {
			$is_new = true;
		} else {
			$is_new = false;
		}
		
				if (!is_null($result['special']) && (float)$result['special'] >= 0) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$tax_price = (float)$result['special'];
				} else {
					$special = false;
					$tax_price = (float)$result['price'];
				}
	
				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format($tax_price, $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

				$data['products'][] = array(
					'product_id'  => $result['product_id'],

		'thumb2'  => $image2,
		'quantity'  => $result['quantity'],
		'sale_badge' => $sale_badge,
		'sale_end_date' => $date_end['date_end'] ?? '',
		'new_label'  => $is_new,
		
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'] . $url)
				);
			}

			$url = '';

                // Flexi filter
                $url .= $this->load->controller('extension/module/tf_filter/url', $url);
                

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.sort_order&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=DESC' . $url)
			);

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=DESC' . $url)
				);

				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=ASC' . $url)
				);
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=DESC' . $url)
			);

			$url = '';

                // Flexi filter
                $url .= $this->load->controller('extension/module/tf_filter/url', $url);
                

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = array();

			$limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&limit=' . $value)
				);
			}

			$url = '';

                // Flexi filter
                $url .= $this->load->controller('extension/module/tf_filter/url', $url);
                

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}


			    $this->load->model('extension/module/hb_seo_snippets');
		    	$this->model_extension_module_hb_seo_snippets->itemlist($data['products']);
		    	$this->model_extension_module_hb_seo_snippets->category_social($category_info);
			
			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&page={page}');

			$data['pagination'] = $pagination->render();
			
				foreach ($pagination->prevnext() as $pagelink) {
					$this->document->addLink($pagelink['href'], $pagelink['rel']);
				}
				

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			/* // http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html 
			if ($page == 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id']), 'canonical');
			} else {
				$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. $page), 'canonical');
			}
			
			if ($page > 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . (($page - 2) ? '&page='. ($page - 1) : '')), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. ($page + 1)), 'next');
			}

			// */ 
			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			$data['continue'] = $this->url->link('common/home');


                    if(!isset($this->request->get['_tf_ajax'])){
                

		//Basel start
		if ($this->config->get('theme_default_directory') == 'basel') {
		$data['position_category_top'] = $this->load->controller('extension/basel/position_category_top');
		}
		
			$data['column_left'] = $this->load->controller('common/column_left');

				$canonicals = $this->config->get('canonicals');
				if (isset($canonicals['canonicals_categories'])) {
					$this->document->removeLink('canonical');
					$pathx = explode('_', $this->request->get['path']);
					$pathx = end($pathx);
					$this->document->addLink($this->url->link('product/category', 'path=' . $pathx .((isset($page) && ($page > 1))?('&page='.$page):'') ), 'canonical');
					}
			
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');

			    $this->load->model('extension/module/hb_seo_snippets');
			    $this->model_extension_module_hb_seo_snippets->breadcrumbs_sd($data['breadcrumbs']);
			
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('product/category', $data));
                    } else {
                        $data['column_left'] = $data['column_right'] = $data['content_top'] = $data['content_bottom'] = $data['footer'] = $data['header'] = '';

				$canonicals = $this->config->get('canonicals');
				if (isset($canonicals['canonicals_categories'])) {
					$this->document->removeLink('canonical');
					$pathx = explode('_', $this->request->get['path']);
					$pathx = end($pathx);
					$this->document->addLink($this->url->link('product/category', 'path=' . $pathx .((isset($page) && ($page > 1))?('&page='.$page):'') ), 'canonical');
					}
			
                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode(['content' => $this->load->view('product/category', $data),
                            'filter' => $this->load->controller('extension/module/tf_filter/getPostFilterValues')]));
                    }
		} else {
			$url = '';

                // Flexi filter
                $url .= $this->load->controller('extension/module/tf_filter/url', $url);
                

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/category', $url)
			);

			$this->document->setTitle($this->language->get('text_error'));

			/** + robot, noindex */
			if( isset( $this->request->get['page'] ) || isset( $this->request->get['limit'] ) || isset( $this->request->get['order'] ) ) {
				$this->document->setRobots( 'noindex,follow' );
			}
				

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');


                    if(!isset($this->request->get['_tf_ajax'])){
                

		//Basel start
		if ($this->config->get('theme_default_directory') == 'basel') {
		$data['position_category_top'] = $this->load->controller('extension/basel/position_category_top');
		}
		
			$data['column_left'] = $this->load->controller('common/column_left');

				$canonicals = $this->config->get('canonicals');
				if (isset($canonicals['canonicals_categories'])) {
					$this->document->removeLink('canonical');
					$pathx = explode('_', $this->request->get['path']);
					$pathx = end($pathx);
					$this->document->addLink($this->url->link('product/category', 'path=' . $pathx .((isset($page) && ($page > 1))?('&page='.$page):'') ), 'canonical');
					}
			
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');

			    $this->load->model('extension/module/hb_seo_snippets');
			    $this->model_extension_module_hb_seo_snippets->breadcrumbs_sd($data['breadcrumbs']);
			
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
                    } else {
                        $data['column_left'] = $data['column_right'] = $data['content_top'] = $data['content_bottom'] = $data['footer'] = $data['header'] = '';

				$canonicals = $this->config->get('canonicals');
				if (isset($canonicals['canonicals_categories'])) {
					$this->document->removeLink('canonical');
					$pathx = explode('_', $this->request->get['path']);
					$pathx = end($pathx);
					$this->document->addLink($this->url->link('product/category', 'path=' . $pathx .((isset($page) && ($page > 1))?('&page='.$page):'') ), 'canonical');
					}
			
                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode(['content' => $this->load->view('error/not_found', $data),
                            'filter' => $this->load->controller('extension/module/tf_filter/getPostFilterValues')]));
                    }
		}
	}
}
