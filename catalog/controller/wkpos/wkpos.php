<?php
class ControllerWkposWkpos extends Controller {
	public function index() {
		if (!$this->config->get('module_wkpos_status')) {
			die('The POS module is not enabled from the backend');
		}

		// Menu
		$this->load->model('catalog/category');
		$this->load->model('tool/image');
		$this->load->model('wkpos/user');

		$data = $this->load->language('wkpos/wkpos');

		$this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->config->get('config_name'));

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		}

		$data['title'] = $this->document->getTitle();

		// $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment.js');
		// $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		// $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
		$data['base'] = $server;
		$data['base_pos'] = $server . 'wkpos/';
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts();
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$data['store_name'] = $this->config->get('config_name');
		$data['language'] = $this->session->data['language'];
		$data['currency'] = $this->session->data['currency'];
		if ($this->currency->getSymbolLeft($data['currency'])) {
			$data['currency_code'] = $this->currency->getSymbolLeft($data['currency']);
			$data['symbol_position'] = 'L';
		} else {
			$data['currency_code'] = $this->currency->getSymbolRight($data['currency']);
			$data['symbol_position'] = 'R';
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$data['no_image'] = $this->model_tool_image->resize('no_image.png', 50, 50);

		if ($this->checkUserLogin()) {
			$data['user_login'] = $this->session->data['user_login_id'];
			$user = $this->model_wkpos_user->getUser($this->session->data['user_login_id']);
			$data['name'] = $user['name'];
			$data['group_name'] = $user['group_name'];
			$data['image'] = $user['image'];
			$data['firstname'] = $user['firstname'];
			$data['lastname'] = $user['lastname'];
			$data['email'] = $user['email'];
			$data['username'] = $user['username'];
		} else {
			$data['user_login'] = 0;
			$data['name'] = '';
			$data['username'] = '';
			$data['group_name'] = '';
			$data['image'] = $this->model_tool_image->resize('no_image.png', 100, 100);
			$data['firstname'] = '';
			$data['lastname'] = '';
			$data['email'] = '';
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$this->load->model('localisation/currency');

		$data['currencies'] = array();

		$results = $this->model_localisation_currency->getCurrencies();

		foreach ($results as $result) {
			if ($result['status']) {
				$data['currencies'][] = array(
					'title'        => $result['title'],
					'code'         => $result['code'],
					'symbol_left'  => $result['symbol_left'],
					'symbol_right' => $result['symbol_right']
				);
			}
		}

		$this->load->model('localisation/language');

		$data['languages'] = array();

		$results = $this->model_localisation_language->getLanguages();

		foreach ($results as $result) {
			if ($result['status']) {
				$data['languages'][] = array(
					'name' => $result['name'],
					'code' => $result['code']
				);
			}
		}

		if (isset($this->session->data['wkpos_outlet']) && $this->session->data['wkpos_outlet']) {
			$this->load->model('wkpos/product');
			$outlet_info = $this->model_wkpos_product->getOutletInfo($this->session->data['wkpos_outlet']);

			if ($outlet_info) {
				$this->session->data['shipping_address']['zone_id'] = $outlet_info['zone_id'];
				$this->session->data['shipping_address']['country_id'] = $outlet_info['country_id'];
				$this->session->data['payment_address']['zone_id'] = $outlet_info['zone_id'];
				$this->session->data['payment_address']['country_id'] = $outlet_info['country_id'];
			}
		}

		$data['guest_name'] = $this->config->get('wkpos_firstname') . ' ' . $this->config->get('wkpos_lastname');
		$data['cash_payment_title'] = $this->config->get('wkpos_cash_title') ? $this->config->get('wkpos_cash_title') : $this->language->get('text_cash_payment');
		$data['cash_payment_status'] = $this->config->get('wkpos_cash_status');
		$data['card_payment_title'] = $this->config->get('wkpos_card_title') ? $this->config->get('wkpos_card_title') : $this->language->get('text_card_payment');
		$data['card_payment_status'] = $this->config->get('wkpos_card_status');
		$data['discount_status'] = $this->config->get('wkpos_discount_status');
		$data['coupon_status'] = $this->config->get('wkpos_coupon_status');
		$data['tax_status'] = $this->config->get('wkpos_tax_status');
		$data['pos_heading1'] = $this->config->get('wkpos_heading1');
		$data['pos_heading2'] = $this->config->get('wkpos_heading2');
		$data['pos_content'] = $this->config->get('wkpos_logcontent');
		$data['show_note'] = $this->config->get('wkpos_show_note');
		$data['show_store_logo'] = $this->config->get('wkpos_store_logo');
		$data['show_store_name'] = $this->config->get('wkpos_store_name');
		$data['show_store_address'] = $this->config->get('wkpos_store_address');
		$data['show_order_date'] = $this->config->get('wkpos_order_date');
		$data['show_order_time'] = $this->config->get('wkpos_order_time');
		$data['show_order_id'] = $this->config->get('wkpos_order_id');
		$data['show_cashier_name'] = $this->config->get('wkpos_cashier_name');
		$data['show_shipping_mode'] = $this->config->get('wkpos_shipping_mode');
		$data['show_payment_mode'] = $this->config->get('wkpos_payment_mode');
		$data['low_stock'] = $this->config->get('wkpos_low_stock') ? $this->config->get('wkpos_low_stock') : 0;
		$data['show_lowstock_prod'] = $this->config->get('wkpos_show_lowstock_prod');
		$data['store_logo'] = $this->model_tool_image->resize($this->config->get('config_logo'), 200, 50);
		$store_detail = preg_replace('~\r?\n~', "\n", $this->config->get('wkpos_store_detail'));
		$data['store_detail'] = implode('<br>', explode("\n", ($store_detail)));
		$store_address = preg_replace('~\r?\n~', "\n", $this->config->get('config_address'));
		$data['store_address'] = implode('<br>', explode("\n", ($store_address)));
		$data['screen_image'] = $this->model_tool_image->resize('wkpos/monitor.png', 50, 50);

		$data['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			if ($category['top']) {
				// Level 1
				$data['categories'][] = array(
					'name'     => $category['name'],
					'category_id' => $category['category_id']
				);
			}
		}

		$this->response->setOutput($this->load->view('wkpos/wkpos', $data));
	}

	public function userLogin()	{
		$this->load->language('wkpos/wkpos');
		$this->load->model('wkpos/user');
		$json = array();
		if ($this->request->post['username'] && $this->request->post['password']) {
			$login = $this->model_wkpos_user->login($this->request->post['username'], $this->request->post['password']);

			if ($login) {
				$json = $login;
				$json['success'] = $this->language->get('text_success_login');
			} else {
				$json['error'] = $this->language->get('error_login');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function updateProfile() {
		$this->load->language('wkpos/wkpos');
		$this->load->model('wkpos/user');
		$json = array();

		if ($this->checkUserLogin() && $this->session->data['user_login_id']) {
			$user = $this->model_wkpos_user->getUser($this->session->data['user_login_id']);
		} else {
			$json['warning'] = $this->language->get('error_online');
		}

		if (!$json) {
			if (empty($this->request->post['firstname']) || (utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
				$json['first_name'] = $this->language->get('error_firstname');
			}

			if (empty($this->request->post['lastname']) || (utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
				$json['last_name'] = $this->language->get('error_lastname');
			}

			if (empty($this->request->post['account_email']) || (utf8_strlen($this->request->post['account_email']) > 96) || !filter_var($this->request->post['account_email'], FILTER_VALIDATE_EMAIL)) {
				$json['account_email'] = $this->language->get('error_email');
			} elseif ($this->model_wkpos_user->getUsersByEmail($this->request->post['account_email'])) {
				$json['account_email'] = $this->language->get('error_email_exists');
			}

			if (empty($this->request->post['username']) || (utf8_strlen(trim($this->request->post['username'])) < 4) || (utf8_strlen(trim($this->request->post['username'])) > 32)) {
				$json['user_name'] = $this->language->get('error_username');
			}

			if (empty($this->request->post['account_ppwd']) || !$this->model_wkpos_user->checkPreviousPwd($this->request->post['account_ppwd'])) {
				$json['account_ppwd'] = $this->language->get('error_ppwd');
			}

			if (empty($this->request->post['account_npwd']) || (utf8_strlen(trim($this->request->post['account_npwd'])) < 4) || (utf8_strlen(trim($this->request->post['account_npwd'])) > 32)) {
				$json['account_npwd'] = $this->language->get('error_password');
			}

			if (empty($this->request->post['account_cpwd']) || !($this->request->post['account_npwd'] == $this->request->post['account_cpwd'])) {
				$json['account_cpwd'] = $this->language->get('error_confirm');
			}
		}

		if ($json) {
			$json['errors'] = $json;
			if (isset($json['warning'])) {
				$json['error'] = $json['warning'];
			} else {
				$json['error'] = $this->language->get('error_form');
			}
		} else {
			$this->model_wkpos_user->updateProfile($this->request->post);
			$json['success'] = $this->language->get('text_profile_update');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function changeSettings() {
		$this->load->language('wkpos/wkpos');
		$json = array();

		if ($this->request->post['language'] && $this->request->post['currency']) {
			$this->session->data['language'] = $this->request->post['language'];
			$this->session->data['currency'] = $this->request->post['currency'];
			$this->load->model('localisation/currency');
			$currency = $this->model_localisation_currency->getCurrencyByCode($this->request->post['currency']);

			$json['currency'] = $this->session->data['currency'];
			if ($this->currency->getSymbolLeft($json['currency'])) {
				$json['currency_code'] = $this->currency->getSymbolLeft($json['currency']);
				$json['symbol_position'] = 'L';
			} else {
				$json['currency_code'] = $this->currency->getSymbolRight($json['currency']);
				$json['symbol_position'] = 'R';
			}
			$json['success'] = $this->language->get('text_success_update');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function logout() {
		unset($this->session->data['user_login_id']);
		$this->response->redirect($_SERVER['HTTP_REFERER']);
	}

	public function checkUserLogin() {
		$this->load->model('wkpos/user');
		$userLogin = $this->model_wkpos_user->checkUserLogin();
		if ($userLogin) {
			return true;
		} else {
			return false;
		}
	}
}
