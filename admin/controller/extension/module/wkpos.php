<?php
class ControllerExtensionModuleWkpos extends Controller {
	private $error = array();

	public function install() {
		$this->load->model('wkpos/wkpos');
		$this->model_wkpos_wkpos->createTables();
	}

	public function uninstall() {
		$this->load->model('wkpos/wkpos');
		$this->model_wkpos_wkpos->deleteTables();
	}

	public function index() {
		$data = $this->load->language('extension/module/wkpos');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_wkpos', $this->request->post);
			$this->model_setting_setting->editSetting('wkpos', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'], true));
		}

		$error_array = array(
			'warning',
			'firstname',
			'lastname',
			'email',
			'telephone',
			'address_1',
			'city',
			'postcode',
			'country',
			'zone',
			'store_country',
			'store_zone',
			'cash_title',
			'card_title',
			'low_stock',
			'wkpos_heading1',
			);

		foreach ($error_array as $error) {
			if (isset($this->error[$error])) {
				$data['error_' . $error] = $this->error[$error];
			} else {
				$data['error_' . $error] = '';
			}
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/wkpos', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/wkpos', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'], true);

		$data['front_end'] = HTTPS_CATALOG . 'wkpos';

		$data['user_token'] = $this->session->data['user_token'];

		$config_array = array(
			'heading1',
			'heading2',
			'logcontent',
			'show_note',
			'populars',
			'low_stock',
			'show_whole',
			'show_lowstock_prod',
			'email_agent',
			'new_customer_group_id',
			'newsletter',
			'customer_password',
			'customer_group_id',
			'firstname',
			'lastname',
			'email',
			'telephone',
			'fax',
			'company',
			'address_1',
			'address_2',
			'city',
			'postcode',
			'country_id',
			'zone_id',
			'store_country_id',
			'store_zone_id',
			'cash_status',
			'cash_title',
			'cash_order_status_id',
			'card_status',
			'card_title',
			'card_order_status_id',
			'discount_status',
			'coupon_status',
			'tax_status',
			'store_logo',
			'store_name',
			'store_address',
			'order_date',
			'order_time',
			'order_id',
			'cashier_name',
			'shipping_mode',
			'payment_mode',
			'store_detail',
			'barcode_width',
			'barcode_name',
			'barcode_type'
			);

		foreach ($config_array as $config_index) {
			if (isset($this->request->post['wkpos_' . $config_index])) {
				$data['wkpos_' . $config_index] = trim($this->request->post['wkpos_' . $config_index]);
			} else {
				$data['wkpos_' . $config_index] = $this->config->get('wkpos_' . $config_index);
			}
		}

		if (isset($this->request->post['module_wkpos_status'])) {
			$data['module_wkpos_status'] = $this->request->post['module_wkpos_status'];
		} else {
			$data['module_wkpos_status'] = $this->config->get('module_wkpos_status');
		}

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/wkpos', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/wkpos')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen(trim($this->request->post['wkpos_firstname'])) < 1) || (utf8_strlen(trim($this->request->post['wkpos_firstname'])) > 32)) {
			$this->error['firstname'] = $this->language->get('error_firstname');
		}

		if ((utf8_strlen(trim($this->request->post['wkpos_lastname'])) < 1) || (utf8_strlen(trim($this->request->post['wkpos_lastname'])) > 32)) {
			$this->error['lastname'] = $this->language->get('error_lastname');
		}

		if ((utf8_strlen($this->request->post['wkpos_email']) > 96) || !filter_var($this->request->post['wkpos_email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ((utf8_strlen(trim($this->request->post['wkpos_telephone'])) < 3) || (utf8_strlen($this->request->post['wkpos_telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		if ((utf8_strlen(trim($this->request->post['wkpos_address_1'])) < 3) || (utf8_strlen(trim($this->request->post['wkpos_address_1'])) > 128)) {
			$this->error['address_1'] = $this->language->get('error_address_1');
		}

		if ((utf8_strlen(trim($this->request->post['wkpos_city'])) < 2) || (utf8_strlen(trim($this->request->post['wkpos_city'])) > 128)) {
			$this->error['city'] = $this->language->get('error_city');
		}

		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->post['wkpos_country_id']);

		if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($this->request->post['wkpos_postcode'])) < 2 || utf8_strlen(trim($this->request->post['wkpos_postcode'])) > 10)) {
			$this->error['postcode'] = $this->language->get('error_postcode');
		}

		if ($this->request->post['wkpos_country_id'] == '') {
			$this->error['country'] = $this->language->get('error_country');
		}

		if (!isset($this->request->post['wkpos_zone_id']) || $this->request->post['wkpos_zone_id'] == '' || !is_numeric($this->request->post['wkpos_zone_id'])) {
			$this->error['zone'] = $this->language->get('error_zone');
		}

		if ($this->request->post['wkpos_store_country_id'] == '') {
			$this->error['store_country'] = $this->language->get('error_store_country');
		}

		if (!isset($this->request->post['wkpos_store_zone_id']) || $this->request->post['wkpos_store_zone_id'] == '' || !is_numeric($this->request->post['wkpos_store_zone_id'])) {
			$this->error['store_zone'] = $this->language->get('error_store_zone');
		}

		if ((utf8_strlen(trim($this->request->post['wkpos_cash_title'])) < 3) || (utf8_strlen(trim($this->request->post['wkpos_cash_title'])) > 64)) {
        $this->error['cash_title'] = $this->language->get('error_cash_title');
		}

			if ((utf8_strlen(trim($this->request->post['wkpos_card_title'])) < 3) || (utf8_strlen(trim($this->request->post['wkpos_card_title'])) > 64)) {
	        $this->error['card_title'] = $this->language->get('error_card_title');
			}


			if ($this->request->post['wkpos_low_stock'] < 0) {
			 $this->error['low_stock'] = $this->language->get('error_low_stock');
			}
			if ((utf8_strlen(trim($this->request->post['wkpos_heading1'])) < 1) || (utf8_strlen(trim($this->request->post['wkpos_heading1'])) > 64)) {
	        $this->error['wkpos_heading1'] = $this->language->get('error_wkpos_heading1');
			}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}
}
