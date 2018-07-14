<?php
class ModelWkposOutlet extends Model {
	/**
	 * Adds an outlet for POS
	 * @param int $data returns the outlet id
	 */
	public function addOutlet($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "wkpos_outlet SET name = '" . $this->db->escape($data['name']) . "', address = '" . $this->db->escape($data['address']) . "', country_id = '" . (int)$data['country_id'] . "', zone_id = '" . (int)$data['zone_id'] . "', status = '" . $this->db->escape($data['status']) . "'");

		return $this->db->getLastId();
	}

	/**
	 * edits an outlet
	 * @param  int $outlet_id contains the outlet id
	 * @param  array $data          contains the form data for outlet
	 * @return null                none
	 */
	public function editOutlet($outlet_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "wkpos_outlet SET name = '" . $this->db->escape($data['name']) . "', address = '" . $this->db->escape($data['address']) . "', country_id = '" . (int)$data['country_id'] . "', zone_id = '" . (int)$data['zone_id'] . "', status = '" . $this->db->escape($data['status']) . "' WHERE outlet_id = '" . (int)$outlet_id . "'");
	}

	/**
	 * Deletes an outlet
	 * @param  int $outlet_id contains the outlet id
	 * @return null                none
	 */
	public function deleteOutlet($outlet_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "wkpos_outlet WHERE outlet_id = '" . (int)$outlet_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "wkpos_products WHERE outlet_id = '" . (int)$outlet_id . "'");
		$this->db->query("UPDATE " . DB_PREFIX . "wkpos_user SET status = '0' WHERE outlet_id = '" . (int)$outlet_id . "'");
	}

	/**
	 * Fetches the content of outlet
	 * @param  int $outlet_id contains the outlet id
	 * @return array                returns the content of an outlet
	 */
	public function getOutlet($outlet_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "wkpos_outlet WHERE outlet_id = '" . (int)$outlet_id . "'");

		$outlet = array(
			'name'       => $query->row['name'],
			'address'    => $query->row['address'],
			'country_id' => $query->row['country_id'],
			'zone_id'    => $query->row['zone_id'],
			'status'     => $query->row['status']
		);

		return $outlet;
	}

	/**
	 * Fetches all outlets
	 * @param  array  $data contains the filter data
	 * @return array       return the outlets
	 */
	public function getOutlets($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "wkpos_outlet";

		$sort_data = array(
			'name',
			'status'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Fetches the total number of outlets
	 * @return int contains the total number of outlets
	 */
	public function getTotalOutlets() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "wkpos_outlet");

		return $query->row['total'];
	}
	// assigns all products to given outlet
	public function assignAll($outlet) {
		 $products = $this->db->query("SELECT product_id, quantity, status FROM " . DB_PREFIX . "product")->rows;

		 foreach ($products as $product) {
			 $assigned_quantity = $this->db->query("SELECT SUM(quantity) as assigned FROM " . DB_PREFIX . "wkpos_products WHERE product_id = '" . (int)$product['product_id'] . "' AND outlet_id != '" . (int)$outlet . "'")->row;

			 $quantity = $product['quantity'] - $assigned_quantity['assigned'];

			 $pos_product = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_products WHERE product_id = '" . $product['product_id'] . "' AND outlet_id = '" . (int)$outlet . "'")->row;

			 if (isset($pos_product['wkpos_products_id'])) {
				 $this->db->query("UPDATE " . DB_PREFIX . "wkpos_products SET status = '" . $product['status'] . "', quantity = '" . $quantity . "' WHERE wkpos_products_id = '" . $pos_product['wkpos_products_id'] . "' AND outlet_id = '" . (int)$outlet . "'");
			 } else {
				 $this->db->query("INSERT INTO " . DB_PREFIX . "wkpos_products SET status = '" . $product['status'] . "', quantity = '" . $quantity . "', product_id = '" . $product['product_id'] . "', outlet_id = '" . (int)$outlet . "'");
			 }
		 }
	}

	// public function addPermission($outlet_id, $type, $route) {
	// 	$outlet_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "wkpos_outlet WHERE outlet_id = '" . (int)$outlet_id . "'");

	// 	if ($outlet_query->num_rows) {
	// 		$data = json_decode($outlet_query->row['permission'], true);

	// 		$data[$type][] = $route;

	// 		$this->db->query("UPDATE " . DB_PREFIX . "wkpos_outlet SET permission = '" . $this->db->escape(json_encode($data)) . "' WHERE outlet_id = '" . (int)$outlet_id . "'");
	// 	}
	// }

	// public function removePermission($outlet_id, $type, $route) {
	// 	$outlet_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "wkpos_outlet WHERE outlet_id = '" . (int)$outlet_id . "'");

	// 	if ($outlet_query->num_rows) {
	// 		$data = json_decode($outlet_query->row['permission'], true);

	// 		$data[$type] = array_diff($data[$type], array($route));

	// 		$this->db->query("UPDATE " . DB_PREFIX . "wkpos_outlet SET permission = '" . $this->db->escape(json_encode($data)) . "' WHERE outlet_id = '" . (int)$outlet_id . "'");
	// 	}
	// }
}
