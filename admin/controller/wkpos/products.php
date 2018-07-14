<?php
class ControllerWkposProducts extends Controller {
	public function index() {
		$data = $this->load->language('catalog/product');
		$data = array_merge($data, $this->load->language('wkpos/products'));
		$this->load->model('wkpos/outlet');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('wkpos/products', 'user_token=' . $this->session->data['user_token'], true)
		);

		if (isset($this->session->data['warning'])) {
			$data['error_warning'] = $this->session->data['warning'];
			unset($this->session->data['warning']);
		} else {
			$data['error_warning'] = '';
		}

		$data['outlets'] = $this->model_wkpos_outlet->getOutlets();

		$data['user_token'] = $this->session->data['user_token'];
		$data['print_action'] = $this->url->link('wkpos/products/generatePrint', 'user_token=' . $this->session->data['user_token'], true);
		$data['mass_print'] = $this->url->link('wkpos/products/massPrint', 'user_token=' . $this->session->data['user_token'], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('wkpos/products', $data));
	}

	public function loadProducts() {
		$this->load->model('wkpos/products');
		$this->load->model('catalog/product');

		if (isset($this->request->post['outlet']) && $this->request->post['outlet']) {
			$outlet = $this->request->post['outlet'];
		} else {
			$outlet = 0;
		}

		if (isset($this->request->post['start']) && $this->request->post['start']) {
			$start = $this->request->post['start'];
		} else {
			$start = 0;
		}

		if (isset($this->request->post['order']) && $this->request->post['order']) {
			$order = $this->request->post['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->post['sort']) && $this->request->post['sort']) {
			$sort = $this->request->post['sort'];
		} else {
			$sort = '';
		}

		if (isset($this->request->post['filter_name']) && $this->request->post['filter_name']) {
			$filter_name = $this->request->post['filter_name'];
		} else {
			$filter_name = null;
		}

		if (isset($this->request->post['filter_model']) && $this->request->post['filter_model']) {
			$filter_model = $this->request->post['filter_model'];
		} else {
			$filter_model = null;
		}

		if (isset($this->request->post['filter_price']) && $this->request->post['filter_price']) {
			$filter_price = $this->request->post['filter_price'];
		} else {
			$filter_price = null;
		}

		if (isset($this->request->post['filter_quantity']) && !($this->request->post['filter_quantity'] == '')) {
			$filter_quantity = $this->request->post['filter_quantity'];
		} else {
			$filter_quantity = null;
		}

		if (isset($this->request->post['filter_assign']) && !($this->request->post['filter_assign'] == '')) {
			$filter_assign = $this->request->post['filter_assign'];
		} else {
			$filter_assign = null;
		}

		if (isset($this->request->post['filter_status']) && !($this->request->post['filter_status'] == '')) {
			$filter_status = $this->request->post['filter_status'];
		} else {
			$filter_status = null;
		}

		if (isset($this->request->post['filter_pos_status']) && !($this->request->post['filter_pos_status'] == '')) {
			$filter_pos_status = $this->request->post['filter_pos_status'];
		} else {
			$filter_pos_status = null;
		}

		$json['products'] = array();

		$filter_data = array(
			'filter_name'	    => $filter_name,
			'filter_model'	    => $filter_model,
			'filter_price'	    => $filter_price,
			'filter_quantity'   => $filter_quantity,
			'filter_assign'     => $filter_assign,
			'filter_status'     => $filter_status,
			'filter_pos_status' => $filter_pos_status,
			'sort'              => $sort,
			'order'             => $order,
			'start'             => $start,
			'limit'             => $this->config->get('config_limit_admin')
		);

		$this->load->model('tool/image');

		if ($outlet) {
			$this->load->model('catalog/product');
			$json['product_total'] = $this->model_catalog_product->getTotalProducts($filter_data);

			$results = $this->model_catalog_product->getProducts($filter_data);
		} else {
			$json['product_total'] = $this->model_wkpos_products->getTotalProducts($filter_data);

			$results = $this->model_wkpos_products->getProducts($filter_data);
		}

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], 40, 40);
			} else {
				$image = $this->model_tool_image->resize('no_image.png', 40, 40);
			}

			$special = false;

			$product_specials = $this->model_catalog_product->getProductSpecials($result['product_id']);

			foreach ($product_specials  as $product_special) {
				if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
					$special = $product_special['price'];

					break;
				}
			}

			if ($outlet) {
				$outlet_data = $this->model_wkpos_products->getOutletProductData($result['product_id'], $outlet);
				$pos_quantity = $outlet_data['quantity'];
				$pos_status = $outlet_data['status'];
				$result['barcode'] = '';
			} else {
				$pos_quantity = $result['pos_quantity'];
				$pos_status = $result['pos_status'];
			}

			if ($result['barcode'] && is_file(str_replace('system/', 'wkpos/barcode/img/', DIR_SYSTEM) . $result['barcode'] . '.png')) {
				$barcode = HTTPS_CATALOG . 'wkpos/barcode/img/' . $result['barcode'] . '.png?lastimage='.uniqid();
			} else {
				$barcode = 0;
			}

			$json['products'][] = array(
				'product_id'   => $result['product_id'],
				'image'        => $image,
				'name'         => $result['name'],
				'model'        => $result['model'],
				'price'        => $result['price'],
				'special'      => $special,
				'quantity'     => $result['quantity'],
				'barcode'      => $barcode,
				'pos_quantity' => $pos_quantity ? $pos_quantity : 0,
				'status'       => ($result['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'pos_status'   => $pos_status
			);
		}

		if (count($json['products'])) {
			$json['success'] = 'Success';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function assignQuantity() {
		$json = array();
		$this->load->language('wkpos/products');

		if (!$this->user->hasPermission('modify', 'wkpos/products')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json && isset($this->request->post['product_id']) && $this->request->post['product_id'] && isset($this->request->post['quantity']) && isset($this->request->post['outlet'])) {
			if (ctype_digit($this->request->post['quantity']) && ($this->request->post['quantity'] >= 0)) {
				$this->load->model('wkpos/products');
				$this->load->model('catalog/product');
				$product = $this->model_catalog_product->getProduct($this->request->post['product_id']);
				$assigned_quantity = $this->model_wkpos_products->getAssignedQuantity($this->request->post['product_id'], $this->request->post['outlet']);
				$total_quantity = $assigned_quantity + $this->request->post['quantity'];

				if ($product['quantity'] < $total_quantity) {
					$json['error'] = $this->language->get('error_quantity');
				} else {
					$this->model_wkpos_products->assignQuantity($this->request->post['product_id'], $this->request->post['quantity'], $this->request->post['outlet']);
					$json['success'] = $this->language->get('text_success');
				}
			} else {
				$json['error'] = $this->language->get('error_num_quantity');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function changeStatus() {
		$json = array();
		$this->load->language('wkpos/products');

		if (!$this->user->hasPermission('modify', 'wkpos/products')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json && isset($this->request->post['product_id']) && $this->request->post['product_id'] && isset($this->request->post['status']) && isset($this->request->post['outlet'])) {
			$this->load->model('wkpos/products');
			$this->model_wkpos_products->changeStatus($this->request->post['product_id'], $this->request->post['status'], $this->request->post['outlet']);
			$json['success'] = $this->language->get('text_success_status');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function generateBarcode() {
		$json = array();
		$this->load->language('wkpos/products');

		if (isset($this->request->post['product_id']) && $this->request->post['product_id']) {
			$this->load->model('wkpos/products');
			$image = $this->model_wkpos_products->generateBarcode($this->request->post['product_id']);
			$json['image'] = HTTPS_CATALOG . 'wkpos/barcode/img/' . $image . '.png?lastimage='.uniqid();
			$json['success'] = $this->language->get('text_success_barcode');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function generatePrint() {
		$this->load->model('wkpos/products');
		$this->load->language('wkpos/products');

		if (isset($this->request->post['product_id']) && $this->request->post['product_id']) {
			$product_id = $this->request->post['product_id'];
			if (isset($this->request->post['quantity']) && $this->request->post['quantity']) {
				$quantity = $this->request->post['quantity'];
			} else {
				$quantity = 1;
			}

			$data['button_back'] = $this->language->get('button_back');
			$data['button_print'] = $this->language->get('button_print');
			$data['text_print_barcode'] = $this->language->get('text_print_barcode');

			$barcode = $this->model_wkpos_products->getBarcode($product_id);

			$data['image'] = HTTPS_CATALOG . 'wkpos/barcode/img/' . $barcode['barcode'] . '.png';
			$data['name'] = $barcode['name'];
			$data['barcode_name'] = $this->config->get('wkpos_barcode_name');
			$data['quantity'] = $quantity;
			$data['cancel'] = $this->url->link('wkpos/products', 'user_token=' . $this->session->data['user_token'], true);

			$this->response->setOutput($this->load->view('wkpos/print_barcode', $data));
		}
	}

	public function massPrint() {
		$this->load->language('wkpos/products');
		if (isset($this->request->post['selected']) && $this->request->post['selected']) {
			if (isset($this->request->post['allcheckbox']) && $this->request->post['allcheckbox']) {
				$all = 1;
			} else {
				$all = 0;
			}
			if (isset($this->request->post['print_quantity']) && $this->request->post['print_quantity']) {
				$data['quantity'] = $this->request->post['print_quantity'];
			} else {
				$data['quantity'] = 1;
			}

			$this->load->model('wkpos/products');
			$mass_data = $this->model_wkpos_products->massBarcode($this->request->post['selected'], $all);

			$data['button_back'] = $this->language->get('button_back');
			$data['button_print'] = $this->language->get('button_print');
			$data['text_print_barcode'] = $this->language->get('text_print_barcode');

			$data['barcodes'] = array();

			foreach ($mass_data as $barcode) {
				if ($barcode['barcode']) {
					$data['barcodes'][] = array(
						'name'  => $barcode['name'],
						'image' => HTTPS_CATALOG . 'wkpos/barcode/img/' . $barcode['barcode'] . '.png'
						);
				}
			}
//.uniqid() remove then show error
			$data['mass'] = true;
			$data['cancel'] = $this->url->link('wkpos/products', 'user_token=' . $this->session->data['user_token'], true);

			$this->response->setOutput($this->load->view('wkpos/print_barcode', $data));
		} else {
			$this->session->data['warning'] = $this->language->get('error_product');
			$this->response->redirect($this->url->link('wkpos/products', 'user_token=' . $this->session->data['user_token'], true));
		}
	}
}
