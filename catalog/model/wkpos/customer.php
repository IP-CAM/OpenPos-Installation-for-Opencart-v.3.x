<?php
class ModelWkposCustomer extends Model {
  /**
  * returns the name and id of all customers
  * @return array returns the array with customer details
  */
  public function getCustomers() {
    $customers = $this->db->query("SELECT customer_id, CONCAT(firstname, ' ', lastname) as name, telephone, email FROM " . DB_PREFIX . "customer")->rows;
    return $customers;
  }
  /**
  * Fetches the address of a customer using the address id
  * @param  integer $address_id contains the address id
  * @return array             returns the array containing the address details of a customer
  */
  public function getAddress($address_id) {
    $address_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "'");

    if ($address_query->num_rows) {
      $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$address_query->row['country_id'] . "'");

      if ($country_query->num_rows) {
        $country = $country_query->row['name'];
        $iso_code_2 = $country_query->row['iso_code_2'];
        $iso_code_3 = $country_query->row['iso_code_3'];
        $address_format = $country_query->row['address_format'];
      } else {
        $country = '';
        $iso_code_2 = '';
        $iso_code_3 = '';
        $address_format = '';
      }

      $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$address_query->row['zone_id'] . "'");

      if ($zone_query->num_rows) {
        $zone = $zone_query->row['name'];
        $zone_code = $zone_query->row['code'];
      } else {
        $zone = '';
        $zone_code = '';
      }

      $address_data = array(
        'address_id'     => $address_query->row['address_id'],
        'firstname'      => $address_query->row['firstname'],
        'lastname'       => $address_query->row['lastname'],
        'company'        => $address_query->row['company'],
        'address_1'      => $address_query->row['address_1'],
        'address_2'      => $address_query->row['address_2'],
        'postcode'       => $address_query->row['postcode'],
        'city'           => $address_query->row['city'],
        'zone_id'        => $address_query->row['zone_id'],
        'zone'           => $zone,
        'zone_code'      => $zone_code,
        'country_id'     => $address_query->row['country_id'],
        'country'        => $country,
        'iso_code_2'     => $iso_code_2,
        'iso_code_3'     => $iso_code_3,
        'address_format' => $address_format,
        'custom_field'   => json_decode($address_query->row['custom_field'], true)
      );

      return $address_data;
    } else {
      return false;
    }
  }
}
