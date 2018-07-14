<?php
class ControllerWkposOrders extends Controller {
	private $error = array();

	public function index() {
		$data = $this->load->language('sale/order');
		$data = array_merge($data, $this->load->language('wkpos/orders'));

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('wkpos/orders', 'user_token=' . $this->session->data['user_token'], true)
		);

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('wkpos/orders', $data));
	}

	public function loadOrders() {
		$json = array();
		$this->load->model('wkpos/orders');

		if (isset($this->request->post['start']) && $this->request->post['start']) {
			$start = $this->request->post['start'];
		} else {
			$start = 0;
		}

		if (isset($this->request->post['order']) && $this->request->post['order']) {
			$order = $this->request->post['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->post['sort']) && $this->request->post['sort']) {
			$sort = $this->request->post['sort'];
		} else {
			$sort = 'o.order_id';
		}

		if (isset($this->request->post['filter_order_id'])) {
			$filter_order_id = $this->request->post['filter_order_id'];
		} else {
			$filter_order_id = null;
		}

		if (isset($this->request->post['filter_txn_id'])) {
			$filter_txn_id = $this->request->post['filter_txn_id'];
		} else {
			$filter_txn_id = null;
		}

		if (isset($this->request->post['filter_customer'])) {
			$filter_customer = $this->request->post['filter_customer'];
		} else {
			$filter_customer = null;
		}

		if (isset($this->request->post['filter_user'])) {
			$filter_user = $this->request->post['filter_user'];
		} else {
			$filter_user = null;
		}

		if (isset($this->request->post['filter_order_status'])) {
			$filter_order_status = $this->request->post['filter_order_status'];
		} else {
			$filter_order_status = null;
		}

		if (isset($this->request->post['filter_total'])) {
			$filter_total = $this->request->post['filter_total'];
		} else {
			$filter_total = null;
		}

		if (isset($this->request->post['filter_date_added'])) {
			$filter_date_added = $this->request->post['filter_date_added'];
		} else {
			$filter_date_added = null;
		}

		if (isset($this->request->post['filter_date_modified'])) {
			$filter_date_modified = $this->request->post['filter_date_modified'];
		} else {
			$filter_date_modified = null;
		}

		if (isset($this->request->post['filter_pos_status']) && !($this->request->post['filter_pos_status'] == '')) {
			$filter_pos_status = $this->request->post['filter_pos_status'];
		} else {
			$filter_pos_status = null;
		}

		$json['orders'] = array();

		$filter_data = array(
			'filter_order_id'      => $filter_order_id,
			'filter_txn_id'        => $filter_txn_id,
			'filter_customer'	     => $filter_customer,
			'filter_user'          => $filter_user,
			'filter_order_status'  => $filter_order_status,
			'filter_total'         => $filter_total,
			'filter_date_added'    => $filter_date_added,
			'filter_date_modified' => $filter_date_modified,
			'sort'                 => $sort,
			'order'                => $order,
			'start'                => $start,
			'limit'                => $this->config->get('config_limit_admin')
		);

		$json['order_total'] = $this->model_wkpos_orders->getTotalOrders($filter_data);

		$results = $this->model_wkpos_orders->getOrders($filter_data);

		foreach ($results as $result) {
			$json['orders'][] = array(
				'order_id'      => $result['order_id'],
				'txn_id'        => $result['txn_id'] ? $result['txn_id'] : 'Online Order',
				'customer'      => $result['customer'],
				'user'          => $result['user'],
				'order_status'  => $result['order_status'],
				'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
				'view'          => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true),
				'edit'          => $this->url->link('sale/order/edit', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true)
			);
		}

		if (count($json['orders'])) {
			$json['success'] = 'Success';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
