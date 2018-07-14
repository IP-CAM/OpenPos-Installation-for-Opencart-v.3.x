<?php
class ModelWkposWkpos extends Model {
/**
 * Returns the name of a zone by zone id
 * @param  integer $zone_id contains the id of a zone
 * @return string          returns the name of the zone
 */
	public function getZoneById($zone_id) {
		$zone = $this->db->query("SELECT `name` FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int)$zone_id . "'")->row;

		return $zone['name'];
	}
/**
 * Returns the name of a country by country id
 * @param  integer $country_id contains the id of a country
 * @return string          returns the name of the country
 */
	public function getCountryById($country_id) {
		$country = $this->db->query("SELECT `name` FROM " . DB_PREFIX . "country WHERE country_id = '" . (int)$country_id . "'")->row;

		return $country['name'];
	}
/**
 * validates the coupon code
 * @param  string $code contains the coupon code
 * @param  array  $data contains the parameters used to validate the coupon code
 * @return array       returns the information about the coupon if exists
 */
	public function getCoupon($code, $data = array()) {
		$status = true;

		$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) AND status = '1'");

		if ($coupon_query->num_rows) {
			if (!isset($data['subtotal']) || ($coupon_query->row['total'] > $data['subtotal'])) {
				$status = false;
			}

			$coupon_history_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch WHERE ch.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

			if ($coupon_query->row['uses_total'] > 0 && ($coupon_history_query->row['total'] >= $coupon_query->row['uses_total'])) {
				$status = false;
			}

			if ($coupon_query->row['logged'] && (!isset($data['customer']) || !$data['customer'])) {
				$status = false;
			}

			if (isset($data['customer']) && $data['customer']) {
				$coupon_history_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch WHERE ch.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "' AND ch.customer_id = '" . (int)$data['customer'] . "'");

				if ($coupon_query->row['uses_customer'] > 0 && ($coupon_history_query->row['total'] >= $coupon_query->row['uses_customer'])) {
					$status = false;
				}
			}

			// Products
			$coupon_product_data = array();

			$coupon_product_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_product` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

			foreach ($coupon_product_query->rows as $product) {
				$coupon_product_data[] = $product['product_id'];
			}

			// Categories
			$coupon_category_data = array();

			$coupon_category_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_category` cc LEFT JOIN `" . DB_PREFIX . "category_path` cp ON (cc.category_id = cp.path_id) WHERE cc.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

			foreach ($coupon_category_query->rows as $category) {
				$coupon_category_data[] = $category['category_id'];
			}

			$product_data = array();

			if (($coupon_product_data || $coupon_category_data) && isset($data['cart'])) {
				foreach ($data['cart'] as $product) {
					if (in_array($product['product_id'], $coupon_product_data)) {
						$product_data[] = $product['product_id'];

						continue;
					}

					foreach ($coupon_category_data as $category_id) {
						$coupon_category_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . (int)$product['product_id'] . "' AND category_id = '" . (int)$category_id . "'");

						if ($coupon_category_query->row['total']) {
							$product_data[] = $product['product_id'];

							continue;
						}
					}
				}

				if (!$product_data) {
					$status = false;
				}
			}
		} else {
			$status = false;
		}

		if ($status) {
			return array(
				'code'          => $coupon_query->row['code'],
				'name'          => $coupon_query->row['name'],
				'type'          => $coupon_query->row['type'],
				'discount'      => $coupon_query->row['discount'],
				'shipping'      => $coupon_query->row['shipping'],
				'product'       => $product_data,
			);
		}
	}

	public function confirmCoupon($order_info, $code) {
		if ($code) {
			$coupon_info = $this->db->query("SELECT coupon_id FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "'")->row;

			if ($coupon_info) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon_history` SET coupon_id = '" . (int)$coupon_info['coupon_id'] . "', order_id = '" . (int)$order_info['order_id'] . "', customer_id = '" . (int)$order_info['customer_id'] . "', amount = '" . (float)$order_info['value'] . "', date_added = NOW()");
			}
		}
	}
}
