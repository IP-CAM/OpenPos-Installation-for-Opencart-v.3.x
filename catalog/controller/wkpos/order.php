<?php
class ControllerWkposOrder extends Controller {
	public function index() {
		$json['orders'] = array();
		$json['order_products'] = array();

		if (isset($this->request->post['user_id']) && $this->request->post['user_id']) {
			$user_id = $this->request->post['user_id'];
		} else {
			$user_id = 0;
		}

		$this->load->model('wkpos/order');
		$this->load->model('catalog/product');
		$this->load->model('account/order');

		$orders = $this->model_wkpos_order->getOrders($user_id);
		$json['orders'] = array();
		$json['order_products'] = array();
		foreach ($orders as $order) {
			$order_info = $this->model_wkpos_order->getOrder($order['order_id']);
			$datetime = explode(' ', ($order['date_added']));

			$json['orders'][] = array(
				'date'     => $datetime[0],
				'time'     => $datetime[1],
				'name'     => $order['firstname'] . ' ' . $order['lastname'],
				'status'   => $order['status'],
				'order_id' => $order['order_id'],
				'total'    => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'])
				);

			// Products
			$data['products'] = array();

			$products = $this->model_account_order->getOrderProducts($order['order_id']);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_account_order->getOrderOptions($order['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$value = $option['value'];
					} else {
						$value = '';
					}

					$option_data[] = array(
						'name'  => $option['name'],
						'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
					);
				}

				$data['products'][] = array(
					'name'     => $product['name'],
					'model'    => $product['model'],
					'option'   => $option_data,
					'quantity' => $product['quantity'],
					'price'    => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
				);
			}

			// Totals
			$data['totals'] = array();

			$totals = $this->model_account_order->getOrderTotals($order['order_id']);

			foreach ($totals as $total) {
				$data['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']),
				);
			}

			$address = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'] . "<br>" . $order_info['payment_address_1'] . "<br>" . $order_info['payment_city'] . ' ' . $order_info['payment_postcode'] . "<br>" . $order_info['payment_zone'] . "<br>" . $order_info['payment_country'];

			$json['order_products'][$order['order_id']]['products'] = $data['products'];
			$json['order_products'][$order['order_id']]['totals'] = $data['totals'];
			$json['order_products'][$order['order_id']]['address'] = $address;
			$json['order_products'][$order['order_id']]['date'] = $datetime[0];
			$json['order_products'][$order['order_id']]['time'] = $datetime[1];
			$json['order_products'][$order['order_id']]['payment_method'] = $order_info['payment_method'];
			$json['order_products'][$order['order_id']]['username'] = $order['username'];
			$json['order_products'][$order['order_id']]['note'] = $order['order_note'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function addOrder() {
		$this->load->language('wkpos/wkpos');
		$this->load->model('wkpos/wkpos');
		$this->load->model('wkpos/user');
		$this->load->model('wkpos/order');
		$this->load->model('wkpos/product');
		$this->load->model('wkpos/customer');

		$order_data = array();

		if ($this->request->post['customer_id']) {
			$this->load->model('account/customer');

			$customer_info = $this->model_account_customer->getCustomer($this->request->post['customer_id']);

			$order_data['customer_id'] = $this->request->post['customer_id'];
			$order_data['customer_group_id'] = $customer_info['customer_group_id'];
			$order_data['firstname'] = $customer_info['firstname'];
			$order_data['lastname'] = $customer_info['lastname'];
			$order_data['email'] = $customer_info['email'];
			$order_data['telephone'] = $customer_info['telephone'];
			$order_data['fax'] = $customer_info['fax'];
			$order_data['custom_field'] = json_decode($customer_info['custom_field'], true);

			if ($customer_info['address_id']) {
				$address = $this->model_wkpos_customer->getAddress($customer_info['address_id']);
			}
		} else {
			$order_data['customer_id'] = 0;
			$order_data['customer_group_id'] = $this->config->get('wkpos_customer_group_id');
			$order_data['firstname'] = $this->config->get('wkpos_firstname');
			$order_data['lastname'] = $this->config->get('wkpos_lastname');
			$order_data['email'] = $this->config->get('wkpos_email');
			$order_data['telephone'] = $this->config->get('wkpos_telephone');
			$order_data['fax'] = $this->config->get('wkpos_fax');
			$order_data['custom_field'] = array();
		}

		$order_data['totals'] = array();

		$this->load->language('checkout/checkout');

		$order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
		$order_data['store_id'] = $this->config->get('config_store_id');
		$order_data['store_name'] = $this->config->get('config_name');

		if ($order_data['store_id']) {
			$order_data['store_url'] = $this->config->get('config_url');
		} else {
			$order_data['store_url'] = HTTP_SERVER;
		}

		if (isset($address)) {
			$order_data['payment_firstname'] = $address['firstname'];
			$order_data['payment_lastname'] = $address['lastname'];
			$order_data['payment_company'] = $address['company'];
			$order_data['payment_address_1'] = $address['address_1'];
			$order_data['payment_address_2'] = $address['address_2'];
			$order_data['payment_city'] = $address['city'];
			$order_data['payment_postcode'] = $address['postcode'];
			$order_data['payment_zone'] = $address['zone'];
			$order_data['payment_zone_id'] = $address['zone_id'];
			$order_data['payment_country'] = $address['country'];
			$order_data['payment_country_id'] = $address['country_id'];
		} else {
			$order_data['payment_firstname'] = $this->config->get('wkpos_firstname');
			$order_data['payment_lastname'] = $this->config->get('wkpos_lastname');
			$order_data['payment_company'] = $this->config->get('wkpos_company');
			$order_data['payment_address_1'] = $this->config->get('wkpos_address_1');
			$order_data['payment_address_2'] = $this->config->get('wkpos_address_2');
			$order_data['payment_city'] = $this->config->get('wkpos_city');
			$order_data['payment_postcode'] = $this->config->get('wkpos_postcode');
			$order_data['payment_zone'] = $this->model_wkpos_wkpos->getZoneById($this->config->get('wkpos_zone_id'));
			$order_data['payment_zone_id'] = $this->config->get('wkpos_zone_id');
			$order_data['payment_country'] = $this->model_wkpos_wkpos->getCountryById($this->config->get('wkpos_country_id'));
			$order_data['payment_country_id'] = $this->config->get('wkpos_country_id');
		}

		$order_data['payment_address_format'] = '';
		$order_data['payment_custom_field'] = array();

		$order_data['payment_code'] = $this->request->post['payment_method'];

		if ($this->request->post['payment_method'] == 'cash') {
			$order_data['payment_method'] = $this->config->get('wkpos_cash_title');
			$order_status_id = $this->config->get('wkpos_cash_order_status_id');
		} elseif ($this->request->post['payment_method'] == 'card') {
			$order_data['payment_method'] = $this->config->get('wkpos_card_title');
			$order_status_id = $this->config->get('wkpos_card_order_status_id');
		}

		$order_data['shipping_firstname'] = '';
		$order_data['shipping_lastname'] = '';
		$order_data['shipping_company'] = '';
		$order_data['shipping_address_1'] = '';
		$order_data['shipping_address_2'] = '';
		$order_data['shipping_city'] = '';
		$order_data['shipping_postcode'] = '';
		$order_data['shipping_zone'] = '';
		$order_data['shipping_zone_id'] = '';
		$order_data['shipping_country'] = '';
		$order_data['shipping_country_id'] = '';
		$order_data['shipping_address_format'] = '';
		$order_data['shipping_custom_field'] = array();
		$order_data['shipping_method'] = '';
		$order_data['shipping_code'] = '';

		$subtotal = 0;
		$order_data['products'] = array();

		if (isset($this->request->post['cart']) && $this->request->post['cart']) {
			foreach ($this->request->post['cart'] as $product) {
				$option_price = 0;
				$option_points = 0;
				$option_weight = 0;

				$option_data = array();

				if (isset($product['options']) && $product['options'])
					foreach ($product['options'] as $option) {
						if (isset($option['value']) && $option['value']) {
							$option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$option['product_option_id'] . "' AND po.product_id = '" . (int)$product['product_id'] . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

							if ($option_query->num_rows) {
								if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio' || $option_query->row['type'] == 'image') {
									$option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$option['value'] . "' AND pov.product_option_id = '" . (int)$option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

									if ($option_value_query->num_rows) {
										if ($option_value_query->row['price_prefix'] == '+') {
											$option_price += $option_value_query->row['price'];
										} elseif ($option_value_query->row['price_prefix'] == '-') {
											$option_price -= $option_value_query->row['price'];
										}

										if ($option_value_query->row['points_prefix'] == '+') {
											$option_points += $option_value_query->row['points'];
										} elseif ($option_value_query->row['points_prefix'] == '-') {
											$option_points -= $option_value_query->row['points'];
										}

										if ($option_value_query->row['weight_prefix'] == '+') {
											$option_weight += $option_value_query->row['weight'];
										} elseif ($option_value_query->row['weight_prefix'] == '-') {
											$option_weight -= $option_value_query->row['weight'];
										}

										if ($option_value_query->row['subtract'] && (!$option_value_query->row['quantity'] || ($option_value_query->row['quantity'] < $product['quantity']))) {
											$stock = false;
										}

										$option_data[] = array(
											'product_option_id'       => $option['product_option_id'],
											'product_option_value_id' => $option['value'],
											'option_id'               => $option_query->row['option_id'],
											'option_value_id'         => $option_value_query->row['option_value_id'],
											'name'                    => $option_query->row['name'],
											'value'                   => $option_value_query->row['name'],
											'type'                    => $option_query->row['type'],
											'quantity'                => $option_value_query->row['quantity'],
											'subtract'                => $option_value_query->row['subtract'],
											'price'                   => $option_value_query->row['price'],
											'price_prefix'            => $option_value_query->row['price_prefix'],
											'points'                  => $option_value_query->row['points'],
											'points_prefix'           => $option_value_query->row['points_prefix'],
											'weight'                  => $option_value_query->row['weight'],
											'weight_prefix'           => $option_value_query->row['weight_prefix']
										);
									}
								} elseif ($option_query->row['type'] == 'checkbox' && is_array($option['value'])) {
									foreach ($option['value'] as $product_option_value_id) {
										$option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

										if ($option_value_query->num_rows) {
											if ($option_value_query->row['price_prefix'] == '+') {
												$option_price += $option_value_query->row['price'];
											} elseif ($option_value_query->row['price_prefix'] == '-') {
												$option_price -= $option_value_query->row['price'];
											}

											if ($option_value_query->row['points_prefix'] == '+') {
												$option_points += $option_value_query->row['points'];
											} elseif ($option_value_query->row['points_prefix'] == '-') {
												$option_points -= $option_value_query->row['points'];
											}

											if ($option_value_query->row['weight_prefix'] == '+') {
												$option_weight += $option_value_query->row['weight'];
											} elseif ($option_value_query->row['weight_prefix'] == '-') {
												$option_weight -= $option_value_query->row['weight'];
											}

											if ($option_value_query->row['subtract'] && (!$option_value_query->row['quantity'] || ($option_value_query->row['quantity'] < $product['quantity']))) {
												$stock = false;
											}

											$option_data[] = array(
												'product_option_id'       => $option['product_option_id'],
												'product_option_value_id' => $product_option_value_id,
												'option_id'               => $option_query->row['option_id'],
												'option_value_id'         => $option_value_query->row['option_value_id'],
												'name'                    => $option_query->row['name'],
												'value'                   => $option_value_query->row['name'],
												'type'                    => $option_query->row['type'],
												'quantity'                => $option_value_query->row['quantity'],
												'subtract'                => $option_value_query->row['subtract'],
												'price'                   => $option_value_query->row['price'],
												'price_prefix'            => $option_value_query->row['price_prefix'],
												'points'                  => $option_value_query->row['points'],
												'points_prefix'           => $option_value_query->row['points_prefix'],
												'weight'                  => $option_value_query->row['weight'],
												'weight_prefix'           => $option_value_query->row['weight_prefix']
											);
										}
									}
								} elseif ($option_query->row['type'] == 'text' || $option_query->row['type'] == 'textarea' || $option_query->row['type'] == 'file' || $option_query->row['type'] == 'date' || $option_query->row['type'] == 'datetime' || $option_query->row['type'] == 'time') {
									$option_data[] = array(
										'product_option_id'       => $option['product_option_id'],
										'product_option_value_id' => '',
										'option_id'               => $option_query->row['option_id'],
										'option_value_id'         => '',
										'name'                    => $option_query->row['name'],
										'value'                   => $option['value'],
										'type'                    => $option_query->row['type'],
										'quantity'                => '',
										'subtract'                => '',
										'price'                   => '',
										'price_prefix'            => '',
										'points'                  => '',
										'points_prefix'           => '',
										'weight'                  => '',
										'weight_prefix'           => ''
									);
								}
							}
						}
					}

				$product_info = $this->model_wkpos_product->getProduct($product['product_id']);

				// Downloads
				$download_data = array();

				$download_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_download p2d LEFT JOIN " . DB_PREFIX . "download d ON (p2d.download_id = d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE p2d.product_id = '" . (int)$product['product_id'] . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

				foreach ($download_query->rows as $download) {
					$download_data[] = array(
						'download_id' => $download['download_id'],
						'name'        => $download['name'],
						'filename'    => $download['filename'],
						'mask'        => $download['mask']
					);
				}

				if ($product['product_id'] == 0) {
					$product_info['model'] = $product['model'];
					$product_info['subtract'] = 0;
					$product_info['reward'] = 0;
				}

				// for different currency
				if (isset($product['custom'])) {
					if ($this->request->post['currency'] == $this->config->get('config_currency')) {
						$price = $product['uf'];
					} else {
						$price = $this->currency->convert($product['uf'], $this->request->post['currency'], $this->config->get('config_currency'));
					}
				} else {
					if ($product_info['special']) {
						$price = $product_info['special'];
					} else {
						$price = $product_info['price'];
					}
					$price += $option_price;
				}

				$total_price = $price * $product['quantity'];

				$order_data['products'][] = array(
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'model'      => $product_info['model'],
					'option'     => $option_data,
					'download'   => $download_data,
					'quantity'   => $product['quantity'],
					'subtract'   => $product_info['subtract'],
					'price'      => $price,
					'total'      => $total_price,
					'tax'        => 0,
					'reward'     => $product_info['reward'] * $product['quantity']
				);
				$subtotal += $total_price;
			}
		}

		// Sub-total
		$order_data['totals'][] = array(
			'code'  => 'sub_total',
			'title' => 'Sub-Total',
			'value' => $subtotal,
			'sort_order' => 0
		);

		// assuming subtotal as initial total
		$total = $subtotal;

		// POS Tax
		if ($this->config->get('wkpos_tax_status') && isset($this->request->post['tax']) && ($this->request->post['tax'] > 0)) {
			if ($this->request->post['currency'] == $this->config->get('config_currency')) {
				$tax = $this->request->post['tax'];
			} else {
				$tax = $this->currency->convert($this->request->post['tax'], $this->request->post['currency'], $this->config->get('config_currency'));
			}

			$order_data['totals'][] = array(
				'code'  => 'tax',
				'title' => 'Tax',
				'value' => $tax,
				'sort_order' => 1
			);
			$total += $tax;
		}

		// POS Discount
		if (isset($this->request->post['discount']) && isset($this->request->post['discount']['discount']) && ($this->request->post['discount']['discount'] > 0)) {
			if ($this->request->post['currency'] == $this->config->get('config_currency')) {
				$discount = $this->request->post['discount']['discount'];
			} else {
				$discount = $this->currency->convert($this->request->post['discount']['discount'], $this->request->post['currency'], $this->config->get('config_currency'));
			}

			$order_data['totals'][] = array(
				'code'  => 'wkpos_discount',
				'title' => $this->request->post['discount']['name'],
				'value' => -$discount,
				'sort_order' => 2
			);
			$total -= $discount;
		}

		// Coupon
		if (isset($this->request->post['coupon']) && isset($this->request->post['coupon']['discount']) && $this->request->post['coupon']['discount']) {
			if ($this->request->post['currency'] == $this->config->get('config_currency')) {
				$coupon_value = $this->request->post['coupon']['discount'];
			} else {
				$coupon_value = $this->currency->convert($this->request->post['coupon']['discount'], $this->request->post['currency'], $this->config->get('config_currency'));
			}

			$order_data['totals'][] = array(
				'code'  => 'coupon',
				'title' => 'Coupon (' . $this->request->post['coupon']['coupon'] . ')',
				'value' => -$coupon_value,
				'sort_order' => 3
			);
			$total -= $coupon_value;
		}

		// total
		$order_data['totals'][] = array(
			'code'  => 'total',
			'title' => 'Total',
			'value' => $total,
			'sort_order' => 4
		);

		// Leaving Gift Voucher for future update
		$order_data['vouchers'] = array();

		$order_data['comment'] = '';
		$order_data['total'] = $total;

		$order_data['affiliate_id'] = 0;
		$order_data['commission'] = 0;
		$order_data['marketing_id'] = 0;
		$order_data['tracking'] = '';

		$order_data['language_id'] = $this->config->get('config_language_id');
		$order_data['currency_id'] = $this->currency->getId($this->request->post['currency']);
		$order_data['currency_code'] = $this->request->post['currency'];
		$order_data['currency_value'] = $this->currency->getValue($this->request->post['currency']);
		$order_data['ip'] = $this->request->server['REMOTE_ADDR'];

		if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
			$order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
			$order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
		} else {
			$order_data['forwarded_ip'] = '';
		}

		if (isset($this->request->server['HTTP_USER_AGENT'])) {
			$order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
		} else {
			$order_data['user_agent'] = '';
		}

		if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
			$order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
		} else {
			$order_data['accept_language'] = '';
		}

		$this->load->model('checkout/order');

		if (isset($this->request->post['user_id']) && $this->request->post['user_id']) {
			$query = $this->model_wkpos_user->getUser((int)$this->request->post['user_id'], 1);
			$outlet_id = $query['outlet_id'];
			if ($this->config->get('wkpos_email_agent')) {
				$this->session->data['wkpos_cashier_mail'] = $query['email'];
			}
		} else {
			$outlet_id = 0;
		}

		$order_id = $this->model_checkout_order->addOrder($order_data);
		$this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $this->request->post['order_note']);
		$this->model_wkpos_order->decreasePOSQuantity($order_id, $outlet_id);

		if (isset($coupon_value)) {
			$coupon_data = array(
				'order_id'    => $order_id,
				'customer_id' => $order_data['customer_id'],
				'value'       => -$coupon_value
			);
			$this->model_wkpos_wkpos->confirmCoupon($coupon_data, $this->request->post['coupon']['coupon']);
		}

		$pos_order_data = array(
			'user_id'  => $this->request->post['user_id'],
			'order_id' => $order_id,
			'note'     => $this->request->post['order_note'],
			'txn_id'   => isset($this->request->post['txn_id']) ? $this->request->post['txn_id'] : 0
			);

		$this->model_wkpos_order->addUserOrders($pos_order_data);

		$json['success'] = sprintf($this->language->get('text_success_order'), $order_id);
		$json['order_id'] = $order_id;

		if (isset($this->request->post['offline'])) {
			$json['success'] = $this->language->get('text_success_sync');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function applyCoupon() {
		$this->load->language('extension/total/coupon');

		$json = array();

		$this->load->model('wkpos/wkpos');

		unset($this->session->data['coupon']);
		unset($this->session->data['customer_id']);
		$this->cart->clear();

		if (isset($this->request->post['coupon'])) {
			$coupon = $this->request->post['coupon'];
		} else {
			$coupon = '';
		}

		$coupon_info = $this->model_wkpos_wkpos->getCoupon($coupon, $this->request->post);

		if (empty($this->request->post['coupon'])) {
			$json['error'] = $this->language->get('error_empty');

			unset($this->session->data['coupon']);
		} elseif ($coupon_info) {
			$json['success'] = $this->language->get('text_success');

			$json['coupon'] = $coupon_info;
		} else {
			$json['error'] = $this->language->get('error_coupon');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
