<?php
class ModelWkposProducts extends Model {
	/**
	 * Fetches the products based on the filters
	 * @param  array  $data contains different filters
	 * @return array       product data
	 */
	public function getProducts($data = array()) {
		$sql = "SELECT p.*, pd.*, sum(wp.quantity) as pos_quantity, wp.status as pos_status, wb.barcode FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "wkpos_products wp ON (p.product_id = wp.product_id) LEFT JOIN " . DB_PREFIX . "wkpos_barcode wb ON (p.product_id = wb.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_model'])) {
			$sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
		}

		if (isset($data['filter_price']) && !is_null($data['filter_price'])) {
			$sql .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
		}

		if (isset($data['filter_quantity']) && !is_null($data['filter_quantity'])) {
			$sql .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
		}

		if (isset($data['filter_assign']) && !is_null($data['filter_assign'])) {
			$sql .= " AND wp.quantity = '" . (int)$data['filter_assign'] . "'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}

		if (isset($data['filter_pos_status']) && !is_null($data['filter_pos_status'])) {
			$sql .= " AND wp.status = '" . (int)$data['filter_pos_status'] . "'";
		}

		$sql .= " GROUP BY p.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'p.price',
			'p.quantity',
			'p.status',
			'wp.status',
			'wp.quantity',
			'p.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pd.name";
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
	 * Returns the count of filtered product
	 * @param  array  $data contains different filters
	 * @return int       contains the number of products
	 */
	public function getTotalProducts($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "wkpos_products wp ON (p.product_id = wp.product_id)";

		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_model'])) {
			$sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
		}

		if (isset($data['filter_price']) && !is_null($data['filter_price'])) {
			$sql .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
		}

		if (isset($data['filter_quantity']) && !is_null($data['filter_quantity'])) {
			$sql .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
		}

		if (isset($data['filter_assign']) && !is_null($data['filter_assign'])) {
			$sql .= " AND wp.quantity = '" . (int)$data['filter_assign'] . "'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}

		if (isset($data['filter_pos_status']) && !is_null($data['filter_pos_status'])) {
			$sql .= " AND wp.status = '" . (int)$data['filter_pos_status'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getOutletProductData($product_id, $outlet) {
		$sql = "SELECT quantity, status FROM " . DB_PREFIX . "wkpos_products WHERE product_id = '" . $product_id . "' AND outlet_id = '" . $outlet . "'";

		$query = $this->db->query($sql);

		return array(
			'quantity' => isset($query->row['quantity']) ? $query->row['quantity'] : 0,
			'status'   => isset($query->row['status']) ? $query->row['status'] : 0
			);
	}

	public function getAssignedQuantity($product_id, $outlet) {
		$sql = "SELECT SUM(quantity) as assigned FROM " . DB_PREFIX . "wkpos_products WHERE product_id = '" . (int)$product_id . "' AND outlet_id != '" . (int)$outlet . "'";
		$query = $this->db->query($sql)->row;

		if (isset($query['assigned'])) {
			return $query['assigned'];
		} else {
			return 0;
		}
	}

	/**
	 * Assigns the quantity of product to POS
	 * @param  int $product_id contains the id of product
	 * @param  int $quantity   contains the quantity of product to be assigned
	 * @return null             none
	 */
	public function assignQuantity($product_id, $quantity, $outlet) {
		$pos_product = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_products WHERE product_id = '" . $product_id . "' AND outlet_id = '" . (int)$outlet . "'")->row;

		if (isset($pos_product['wkpos_products_id'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "wkpos_products SET quantity = '" . $quantity . "' WHERE wkpos_products_id = '" . $pos_product['wkpos_products_id'] . "' AND outlet_id = '" . (int)$outlet . "'");
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "wkpos_products SET quantity = '" . $quantity . "', product_id = '" . $product_id . "', outlet_id = '" . (int)$outlet . "'");
		}
	}

	/**
	 * Changes the status of product for the POS
	 * @param  int $product_id contains the id of the product
	 * @param  int $status     contains the product status to be set
	 * @return null             none
	 */
	public function changeStatus($product_id, $status, $outlet) {
		$pos_product = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_products WHERE product_id = '" . $product_id . "' AND outlet_id = '" . (int)$outlet . "'")->row;

		if (isset($pos_product['wkpos_products_id'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "wkpos_products SET status = '" . $status . "' WHERE wkpos_products_id = '" . $pos_product['wkpos_products_id'] . "' AND outlet_id = '" . (int)$outlet . "'");
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "wkpos_products SET status = '" . $status . "', product_id = '" . $product_id . "', outlet_id = '" . (int)$outlet . "'");
		}
	}

	/**
	 * Generate a barcode image for a given product
	 * @param  integer $product_id contains the ID of the product
	 * @return string             returns the barcode image name
	 */
	public function generateBarcode($product_id) {
		$barcode_path = str_replace('system/', 'wkpos/barcode/', DIR_SYSTEM);
		require_once($barcode_path . 'barcode.php');

		$product = 'wkpos' . $product_id;
		$filepath = $barcode_path . 'img/' . $product . '.png';

		if ($this->config->get('wkpos_barcode_width')) {
			$size = $this->config->get('wkpos_barcode_width');
		} else {
			$size = 20;
		}

		barcode($filepath, $product, $size, $this->config->get('wkpos_barcode_type'));

		$pos_product = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_barcode WHERE product_id = '" . $product_id . "'")->row;

		if (isset($pos_product['barcode_id'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "wkpos_barcode SET barcode = '" . $product . "' WHERE barcode_id = '" . $pos_product['barcode_id'] . "'");
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "wkpos_barcode SET barcode = '" . $product . "', product_id = '" . $product_id . "'");
		}

		return $product;
	}

	/**
	 * Fetches the barcode image name of the given product
	 * @param  integer $product_id contains the ID of the product
	 * @return array             contains the name of product and barcode image
	 */
	public function getBarcode($product_id) {
		$pos_product = $this->db->query("SELECT pd.name, wb.barcode FROM " . DB_PREFIX . "wkpos_barcode wb LEFT JOIN " . DB_PREFIX . "product_description pd ON (wb.product_id = pd.product_id) WHERE pd.language_id = '" . $this->config->get('config_language_id') . "' AND wb.product_id = '" . $product_id . "'")->row;
		return $pos_product;
	}

	/**
	 * Fetches the product name and barcode image for given products
	 * @param  array $selected contains the IDs of the given products
	 * @param  boolean $all      contains a boolean value to refer whether to print all products or given ones
	 * @return array           returns the name and barcode of the products
	 */
	public function massBarcode($selected, $all) {
		if ($all) {
			$barcodes = $this->db->query("SELECT pd.name, wb.barcode FROM " . DB_PREFIX . "wkpos_barcode wb LEFT JOIN " . DB_PREFIX . "product_description pd ON (wb.product_id = pd.product_id) WHERE pd.language_id = '" . $this->config->get('config_language_id') . "'")->rows;
		} else {
			$select = "'" . implode("','", $selected) . "'";
			$sql = "SELECT pd.name, wb.barcode FROM " . DB_PREFIX . "wkpos_barcode wb LEFT JOIN " . DB_PREFIX . "product_description pd ON (wb.product_id = pd.product_id) WHERE pd.language_id = '" . $this->config->get('config_language_id') . "' AND wb.product_id IN (" . $select . ")";
			$barcodes = $this->db->query($sql)->rows;
		}

		return $barcodes;
	}
}
