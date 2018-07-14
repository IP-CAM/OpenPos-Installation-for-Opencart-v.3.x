<?php
class ControllerWkposOutlets extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('wkpos/outlet');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('wkpos/outlet');

		$this->getList();
	}

	public function add() {
		$this->load->language('wkpos/outlet');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('wkpos/outlet');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_wkpos_outlet->addOutlet($this->request->post);

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

			$this->response->redirect($this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('wkpos/outlet');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('wkpos/outlet');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_wkpos_outlet->editOutlet($this->request->get['outlet_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('wkpos/outlet');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('wkpos/outlet');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $outlet_id) {
				$this->model_wkpos_outlet->deleteOutlet($outlet_id);
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

			$this->response->redirect($this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
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
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('wkpos/outlets/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('wkpos/outlets/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['outlets'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$outlet_total = $this->model_wkpos_outlet->getTotalOutlets();

		$results = $this->model_wkpos_outlet->getOutlets($filter_data);

		foreach ($results as $result) {
			$data['outlets'][] = array(
				'outlet_id' => $result['outlet_id'],
				'name'          => $result['name'],
				'status'        => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit'          => $this->url->link('wkpos/outlets/edit', 'user_token=' . $this->session->data['user_token'] . '&outlet_id=' . $result['outlet_id'] . $url, true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['column_oname'] = $this->language->get('column_oname');
		$data['column_ostatus'] = $this->language->get('column_ostatus');
		$data['column_action'] = $this->language->get('column_action');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');

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

		$data['sort_name'] = $this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);

		$data['sort_status'] = $this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $outlet_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($outlet_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($outlet_total - $this->config->get('config_limit_admin'))) ? $outlet_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $outlet_total, ceil($outlet_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('wkpos/outlet_list', $data));
	}

	protected function getForm() {
		$data = array_merge($this->load->language('catalog/product'), $this->load->language('wkpos/products'), $this->load->language('wkpos/outlet'));

		$data['text_form'] = !isset($this->request->get['outlet_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

		if (isset($this->error['country'])) {
			$data['error_country'] = $this->error['country'];
		} else {
			$data['error_country'] = '';
		}

		if (isset($this->error['zone'])) {
			$data['error_zone'] = $this->error['zone'];
		} else {
			$data['error_zone'] = '';
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
			'href' => $this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['outlet_id'])) {
			$data['action'] = $this->url->link('wkpos/outlets/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('wkpos/outlets/edit', 'user_token=' . $this->session->data['user_token'] . '&outlet_id=' . $this->request->get['outlet_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['outlet_id']) && $this->request->server['REQUEST_METHOD'] != 'POST') {
			$outlet_info = $this->model_wkpos_outlet->getOutlet($this->request->get['outlet_id']);
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($outlet_info)) {
			$data['name'] = $outlet_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['address'])) {
			$data['address'] = $this->request->post['address'];
		} elseif (!empty($outlet_info)) {
			$data['address'] = $outlet_info['address'];
		} else {
			$data['address'] = '';
		}

		if (isset($this->request->post['country_id'])) {
			$data['country_id'] = $this->request->post['country_id'];
		} elseif (!empty($outlet_info)) {
			$data['country_id'] = $outlet_info['country_id'];
		} else {
			$data['country_id'] = '';
		}

		if (isset($this->request->post['zone_id'])) {
			$data['zone_id'] = $this->request->post['zone_id'];
		} elseif (!empty($outlet_info)) {
			$data['zone_id'] = $outlet_info['zone_id'];
		} else {
			$data['zone_id'] = '';
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($outlet_info)) {
			$data['status'] = $outlet_info['status'];
		} else {
			$data['status'] = '';
		}

		if (isset($this->request->get['outlet_id']) && $this->request->get['outlet_id']) {
			$data['outlet_id'] = $this->request->get['outlet_id'];
		} else {
			$data['outlet_id'] = 0;
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$data['user_token'] = $this->session->data['user_token'];
		// url to assign all products to this outlet
		$data['assignAll'] = $this->url->link('wkpos/outlets/assignAll', 'user_token=' . $this->session->data['user_token'] . '&outlet_id=' . $data['outlet_id'], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('wkpos/outlet_form', $data));
	}
	// assigns all products to given outlet
	public function assignAll() {
		$this->load->language('wkpos/outlet');

		if (isset($this->request->get['outlet_id']) && $this->request->get['outlet_id']) {
			$this->load->model('wkpos/outlet');
			$this->model_wkpos_outlet->assignAll($this->request->get['outlet_id']);

			$this->session->data['success'] = $this->language->get('text_assign_all');
		}

		$this->response->redirect($this->url->link('wkpos/outlets', 'user_token=' . $this->session->data['user_token'], true));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'wkpos/outlets')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen(trim($this->request->post['name'])) < 3) || (utf8_strlen(trim($this->request->post['name'])) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if ($this->request->post['country_id'] == '') {
			$this->error['country'] = $this->language->get('error_country');
		}

		if (!isset($this->request->post['zone_id']) || $this->request->post['zone_id'] == '' || !is_numeric($this->request->post['zone_id'])) {
			$this->error['zone'] = $this->language->get('error_zone');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'wkpos/outlets')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('wkpos/user');

		foreach ($this->request->post['selected'] as $outlet_id) {
			$user_total = $this->model_wkpos_user->getTotalUsersByGroupId($outlet_id);

			if ($user_total) {
				$this->error['warning'] = sprintf($this->language->get('error_user'), $user_total);
			}
		}

		return !$this->error;
	}
}
