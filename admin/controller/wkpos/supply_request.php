<?php
class ControllerWkposSupplyRequest extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('wkpos/supply_request');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('wkpos/supply_request');

		$this->getList();
	}

	public function add() {
		$this->load->language('wkpos/supply_request');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('wkpos/supply_request');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_wkpos_supply_request->addSupplyRequest($this->request->post);

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

			$this->response->redirect($this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('wkpos/supply_request');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('wkpos/supply_request');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_wkpos_supply_request->editSupplyRequest($this->request->get['supply_request_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function update() {
		if (isset($this->request->get['request_id']) && $this->request->get['request_id']) {
			$this->load->language('wkpos/supply_request');
			$this->load->model('wkpos/supply_request');
			$this->model_wkpos_supply_request->updateStatus($this->request->get['request_id']);
			$this->session->data['success'] = $this->language->get('text_success_update');
		}
		$this->response->redirect($this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function cancel() {
		$this->load->language('wkpos/supply_request');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('wkpos/supply_request');

		if (isset($this->request->post['selected']) && $this->validateCancel()) {
			foreach ($this->request->post['selected'] as $supply_request_id) {
				$this->model_wkpos_supply_request->cancelSupplyRequest($supply_request_id);
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

			$this->response->redirect($this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		$data = $this->load->language('wkpos/supply_request');

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'date_added';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
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
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('wkpos/supply_request/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['cancel'] = $this->url->link('wkpos/supply_request/cancel', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['supply_requests'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$supply_request_total = $this->model_wkpos_supply_request->getTotalSupplyRequests();

		$results = $this->model_wkpos_supply_request->getSupplyRequests($filter_data);

		foreach ($results as $result) {
			if ($result['status']) {
				$status = $this->language->get('text_fulfilled');
			} else {
				$status = $this->language->get('text_unfulfilled');
			}

			if ($result['cancel']) {
				$status = $this->language->get('text_cancelled');
			}

			$data['supply_requests'][] = array(
				'request_id'    => $result['request_id'],
				'name'          => $result['name'],
				'status_text'   => $status,
				'status'        => $result['status'],
				'cancel'        => $result['cancel'],
				'date_added'    => $result['date_added'],
				'comment'       => $result['comment'],
				'update'        => $this->url->link('wkpos/supply_request/update', 'user_token=' . $this->session->data['user_token'] . '&request_id=' . $result['request_id'] . $url, true)
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

		$data['sort_request_id'] = $this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . '&sort=request_id' . $url, true);

		$data['sort_date_added'] = $this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . '&sort=date_added' . $url, true);

		$data['sort_name'] = $this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);

		$data['sort_status'] = $this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $supply_request_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($supply_request_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($supply_request_total - $this->config->get('config_limit_admin'))) ? $supply_request_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $supply_request_total, ceil($supply_request_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('wkpos/supply_request_list', $data));
	}

	protected function getForm() {
		$data = array_merge($this->load->language('catalog/product'), $this->load->language('wkpos/products'), $this->load->language('wkpos/supply_request'));

		$data['text_form'] = !isset($this->request->get['supply_request_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
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
			'href' => $this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['supply_request_id'])) {
			$data['action'] = $this->url->link('wkpos/supply_request/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('wkpos/supply_request/edit', 'user_token=' . $this->session->data['user_token'] . '&supply_request_id=' . $this->request->get['supply_request_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('wkpos/supply_request', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['supply_request_id']) && $this->request->server['REQUEST_METHOD'] != 'POST') {
			$supply_request_info = $this->model_wkpos_supply_request->getSupplyRequest($this->request->get['supply_request_id']);
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($supply_request_info)) {
			$data['name'] = $supply_request_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['address'])) {
			$data['address'] = $this->request->post['address'];
		} elseif (!empty($supply_request_info)) {
			$data['address'] = $supply_request_info['address'];
		} else {
			$data['address'] = '';
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($supply_request_info)) {
			$data['status'] = $supply_request_info['status'];
		} else {
			$data['status'] = '';
		}

		if (isset($supply_request_info) && $supply_request_info) {
			$data['supply_request_id'] = $this->request->get['supply_request_id'];
		} else {
			$data['supply_request_id'] = 0;
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('wkpos/supply_request_form', $data));
	}

	public function view() {
		$json = array();

		if (isset($this->request->get['request_id']) && $this->request->get['request_id']) {
			$this->load->model('wkpos/supply_request');
			$json['supply_info'] = $this->model_wkpos_supply_request->getRequestInfo($this->request->get['request_id']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'wkpos/supply_request')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		return !$this->error;
	}

	protected function validateCancel() {
		if (!$this->user->hasPermission('modify', 'wkpos/supply_request')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
