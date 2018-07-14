<?php
class ControllerWkposProduct extends Controller {
	public function index() {
		$json['products'] = array();

		if (isset($this->request->post['user_id']) && $this->request->post['user_id']) {
			$user_id = $this->request->post['user_id'];
		} else {
			$user_id = 0;
		}

		$this->load->model('wkpos/product');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		if (isset($this->request->get['start'])) {
			$start = $this->request->get['start'];
		} else {
			$start = 0;
		}

		// assuming customer as guest
		$this->session->data['customer'] = 0;
		$outlet_id = $this->model_wkpos_product->getOutlet($user_id);

		$json['total_products'] = $this->model_wkpos_product->getTotalProducts($outlet_id);
		$products = $this->model_wkpos_product->getProduct(0, $start, $outlet_id);

		unset($this->session->data['shipping_address']);
		unset($this->session->data['payment_address']);

		$outlet_info = $this->model_wkpos_product->getOutletInfo($this->session->data['wkpos_outlet']);

		$this->tax->setShippingAddress($outlet_info['country_id'], $outlet_info['zone_id']);
		$this->tax->setPaymentAddress($outlet_info['country_id'], $outlet_info['zone_id']);
		$this->tax->setStoreAddress($outlet_info['country_id'], $outlet_info['zone_id']);

		if ($products) {
			foreach ($products as $product) {
				if ($product['image'] && is_file(DIR_IMAGE . $product['image'])) {
					$image = $this->model_tool_image->resize($product['image'], 200, 200);
				} else {
					$image = $this->model_tool_image->resize('no_image.png', 200, 200);
				}

				$product['price'] = $product['discount'] ? $product['discount'] : $product['price'];

				if ($product['price']) {
					$price = $this->currency->format($product['price'], $this->session->data['currency']);
					$price_unformat = $this->currency->convert($product['price'], $this->config->get('config_currency'), $this->session->data['currency']);
				} else {
					$price = 0;
					$price_unformat = 0;
					$pwt = 0;
				}

				if ($product['special']) {
					$special = $this->currency->format($product['special'], $this->session->data['currency']);
					$special_unformat = $this->currency->convert($product['special'], $this->config->get('config_currency'), $this->session->data['currency']);
				} else {
					$special = 0;
					$special_unformat = 0;
					$swt = 0;
				}

				$options = array();

				if ($product['options']) {
					$options = $this->productOptions($product['product_id'], $product['tax_class_id']);
				}

				$supplies = $this->model_wkpos_product->getProductSuppliers($product['product_id']);

				$suppliers = array();

				foreach ($supplies as $supplier) {
					$suppliers[$supplier['id']] = array(
						'id'   => $supplier['id'],
						'name' => $supplier['name'],
						'min'  => $supplier['min'],
						'max'  => $supplier['max']
						);
				}

				$categories = $this->model_catalog_product->getCategories($product['product_id']);

				$product_categories = array();

				foreach ($categories as $category) {
					$product_categories[] = $category['category_id'];
				}

				$requests = $this->model_wkpos_product->getTotalProductRequests($product['product_id']);

				$json['products'][$product['product_id']] = array(
					'product_id'   => $product['product_id'],
					'name'         => html_entity_decode($product['name']),
					'image'        => $image,
					'price'        => $price,
					'quantity'     => $product['quantity'],
					'price_uf'     => $special_unformat ? $special_unformat : $price_unformat,
					'special'      => $special,
					'tax_class_id' => $product['tax_class_id'],
					'option'       => $product['options'] ? true : false,
					'options'      => $options,
					'suppliers'    => $suppliers,
					'requests'     => $requests['total'],
					'sku'					=>$product['sku'],
					'model'				=>$product['model'],
					'categories'   => $product_categories
					);
			}
		}

		$json['count'] = count($json['products']);
		$json['no_image'] = $this->model_tool_image->resize('no_image.png', 50, 50);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function productOptions($product_id, $tax_class_id) {
		$options = array();

		foreach ($this->model_catalog_product->getProductOptions($product_id) as $option) {
			$product_option_value_data = array();

			foreach ($option['product_option_value'] as $option_value) {
				if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
					if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
						$uf = $this->tax->calculate($option_value['price'], $tax_class_id, $this->config->get('config_tax') ? 'P' : 0);
						$option_price = $this->currency->format($uf, $this->session->data['currency']);
					} else {
						$option_price = 0;
						$uf = 0;
					}

					if ($option_value['image'] && is_file(DIR_IMAGE . $option_value['image'])) {
						$image = $this->model_tool_image->resize($option_value['image'], 50, 50);
					} else {
						$image = null;
					}

					$product_option_value_data[] = array(
						'product_option_value_id' => $option_value['product_option_value_id'],
						'option_value_id'         => $option_value['option_value_id'],
						'name'                    => $option_value['name'],
						'image'                   => $image,
						'price'                   => $option_price,
						'uf'             	      => $uf,
						'price_prefix'            => $option_value['price_prefix']
					);
				}
			}

			$options[] = array(
				'product_option_id'    => $option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $option['option_id'],
				'name'                 => $option['name'],
				'type'                 => $option['type'],
				'value'                => $option['value'],
				'required'             => $option['required']
			);
		}

		return $options;
	}

	public function getPopularProducts() {
		$this->load->model('wkpos/product');

		$products = $this->model_wkpos_product->getPopularProducts($this->config->get('wkpos_populars'));

		$json['products'] = array();

		foreach ($products as $product) {
			if ($product['product_id']) {
				$json['products'][] = $product['product_id'];
			}
		}

		$tax_classes = $this->model_wkpos_product->getTaxClasses();
		$taxes = array();

		foreach ($tax_classes as $tax_class) {
			$taxes[$tax_class['tax_class_id']] = $this->tax->getRates(0, $tax_class['tax_class_id']);
		}

		$json['taxes'] = $taxes;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
