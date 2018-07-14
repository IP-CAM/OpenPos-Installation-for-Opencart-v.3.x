<?php
class ControllerWkposSupplier extends Controller {
  public function index() {
  }

	public function addRequest() {
		$json = array();

		$this->load->model('wkpos/wkpos');
		$this->load->model('wkpos/supplier');
		$this->load->language('wkpos/wkpos');

		if (isset($this->request->post['request_data']) && $this->request->post['request_data'] && $this->load->controller('wkpos/wkpos/checkUserLogin')) {
			$this->model_wkpos_supplier->addSupplyRequest($this->request->post['request_data'], $this->request->post['comment']);

			$json['success'] = $this->language->get('text_supply_success');
		} else {
			$json['error'] = $this->language->get('error_supply');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getRequestHistory() {
		$json = array();

		$this->load->model('wkpos/supplier');

		$requests = $this->model_wkpos_supplier->getRequestHistory();

		$json['requests'] = array();

		foreach ($requests as $request) {
			$info = $this->model_wkpos_supplier->getRequestInfo($request['request_id']);

			$json['requests'][] = array(
				'request_id' => $request['request_id'],
				'date_added' => $request['date_added'],
				'details'    => $info,
				'status'     => $request['status'] ? 'Completed' : ($request['cancel'] ? 'Cancelled' : 'Pending')
				);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
