<?php
class ModelWkposSupplier extends Model {
	/**
	 * Adds an supplier for POS
	 * @param int $data returns the supplier id
	 */
	public function addSupplier($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "wkpos_supplier SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', website = '" . $this->db->escape($data['website']) . "', outlets = '" . $this->db->escape(isset($data['outlets']) ? json_encode($data['outlets']) : '') . "', extra_info = '" . $this->db->escape($data['extra_info']) . "', status = '" . $this->db->escape($data['status']) . "', address = '" . $this->db->escape($data['address']) . "', city = '" . $this->db->escape($data['city']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', country_id = '" . $this->db->escape($data['country_id']) . "', zone_id = '" . $this->db->escape($data['zone_id']) . "', date_added = NOW()");

		return $this->db->getLastId();
	}

	/**
	 * edits an supplier
	 * @param  int $supplier_id contains the supplier id
	 * @param  array $data          contains the form data for supplier
	 * @return null                none
	 */
	public function editSupplier($supplier_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "wkpos_supplier SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', website = '" . $this->db->escape($data['website']) . "', outlets = '" . $this->db->escape(isset($data['outlets']) ? json_encode($data['outlets']) : '') . "', extra_info = '" . $this->db->escape($data['extra_info']) . "', status = '" . $this->db->escape($data['status']) . "', address = '" . $this->db->escape($data['address']) . "', city = '" . $this->db->escape($data['city']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', country_id = '" . $this->db->escape($data['country_id']) . "', zone_id = '" . $this->db->escape($data['zone_id']) . "' WHERE supplier_id = '" . (int)$supplier_id . "'");
	}

	/**
	 * Deletes an supplier
	 * @param  int $supplier_id contains the supplier id
	 * @return null                none
	 */
	public function deleteSupplier($supplier_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "wkpos_supplier WHERE supplier_id = '" . (int)$supplier_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "wkpos_supplier_product WHERE supplier_id = '" . (int)$supplier_id . "'");
	}

	/**
	 * Fetches the content of supplier
	 * @param  int $supplier_id contains the supplier id
	 * @return array                returns the content of an supplier
	 */
	public function getSupplier($supplier_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "wkpos_supplier WHERE supplier_id = '" . (int)$supplier_id . "'");

		return $query->row;
	}

	/**
	 * Fetches all suppliers
	 * @param  array  $data contains the filter data
	 * @return array       return the suppliers
	 */
	public function getSuppliers($data = array()) {
		$sql = "SELECT *, CONCAT(firstname, ' ', lastname) as name FROM " . DB_PREFIX . "wkpos_supplier";

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
	 * Fetches the total number of suppliers
	 * @return int contains the total number of suppliers
	 */
	public function getTotalSuppliers() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "wkpos_supplier");

		return $query->row['total'];
	}

	public function getSupplierByEmail($email) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_supplier WHERE LOWER(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row;
	}

	public function getSupplierProductData($product_id, $supplier) {
		$sql = "SELECT min_quantity, max_quantity, status FROM " . DB_PREFIX . "wkpos_supplier_product WHERE product_id = '" . $product_id . "' AND supplier_id = '" . $supplier . "'";

		$query = $this->db->query($sql);

		return array(
			'min_quantity' => isset($query->row['min_quantity']) ? $query->row['min_quantity'] : 0,
			'max_quantity' => isset($query->row['max_quantity']) ? $query->row['max_quantity'] : 0,
			'status'       => isset($query->row['status']) ? $query->row['status'] : 0
			);
	}

	/**
	 * Assigns the quantity of product to supplier
	 * @param  int $product_id contains the id of product
	 * @param  int $quantity   contains the quantity of product to be assigned
	 * @return null             none
	 */
	public function assignQuantity($product_id, $quantity, $supplier, $qtype) {
		$pos_product = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_supplier_product WHERE product_id = '" . $product_id . "' AND supplier_id = '" . (int)$supplier . "'")->row;

		if (isset($pos_product['supplier_product_id'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "wkpos_supplier_product SET `" . $qtype . "_quantity` = '" . $quantity . "' WHERE supplier_product_id = '" . $pos_product['supplier_product_id'] . "' AND supplier_id = '" . (int)$supplier . "'");
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "wkpos_supplier_product SET `" . $qtype . "_quantity` = '" . $quantity . "', product_id = '" . $product_id . "', supplier_id = '" . (int)$supplier . "'");
		}
	}

	/**
	 * Changes the status of product for the supplier
	 * @param  int $product_id contains the id of the product
	 * @param  int $status     contains the product status to be set
	 * @return null             none
	 */
	public function changeStatus($product_id, $status, $supplier) {
		$pos_product = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_supplier_product WHERE product_id = '" . $product_id . "' AND supplier_id = '" . (int)$supplier . "'")->row;

		if (isset($pos_product['supplier_product_id'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "wkpos_supplier_product SET status = '" . $status . "' WHERE supplier_product_id = '" . $pos_product['supplier_product_id'] . "' AND supplier_id = '" . (int)$supplier . "'");
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "wkpos_supplier_product SET status = '" . $status . "', product_id = '" . $product_id . "', supplier_id = '" . (int)$supplier . "'");
		}
	}
}
