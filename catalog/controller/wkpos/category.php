<?php
class ControllerWkposCategory extends Controller {
	public function index() {
		$json['categories'] = array();

		$this->load->model('catalog/category');

		$json['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			if ($category['top']) {
				// Level 2
				$children_data = array();

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					$filter_data = array(
						'filter_category_id'  => $child['category_id'],
						'filter_sub_category' => true
					);

					$children_data[] = array(
						'name'  => $child['name'],
						'category_id' => $child['category_id']
					);
				}

				// Level 1
				$json['categories'][] = array(
					'name'     => $category['name'],
					'children' => $children_data,
					'category_id' => $category['category_id']
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
