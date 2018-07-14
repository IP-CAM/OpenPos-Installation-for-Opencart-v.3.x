<?php
class ModelWkposUserGroup extends Model {
	/**
	 * Adds a user group for POS
	 * @param int $data returns the user group id
	 */
	public function addUserGroup($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "wkpos_user_group SET name = '" . $this->db->escape($data['name']) . "'");
	
		return $this->db->getLastId();
	}

	/**
	 * edits a user group
	 * @param  int $user_group_id contains the user group id
	 * @param  array $data          contains the form data for user group
	 * @return null                none
	 */
	public function editUserGroup($user_group_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "wkpos_user_group SET name = '" . $this->db->escape($data['name']) . "' WHERE user_group_id = '" . (int)$user_group_id . "'");
	}

	/**
	 * Deletes a user group
	 * @param  int $user_group_id contains the user group id
	 * @return null                none
	 */
	public function deleteUserGroup($user_group_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "wkpos_user_group WHERE user_group_id = '" . (int)$user_group_id . "'");
	}

	/**
	 * Fetches the content of user group
	 * @param  int $user_group_id contains the user group id
	 * @return array                returns the content of a user group
	 */
	public function getUserGroup($user_group_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "wkpos_user_group WHERE user_group_id = '" . (int)$user_group_id . "'");

		$user_group = array(
			'name'       => $query->row['name'],
		);

		return $user_group;
	}

	/**
	 * Fetches all user groups
	 * @param  array  $data contains the filter data
	 * @return array       return the user groups
	 */
	public function getUserGroups($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "wkpos_user_group";

		$sql .= " ORDER BY name";

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
	 * Fetches the total number of user groups
	 * @return int contains the total number of user groups
	 */
	public function getTotalUserGroups() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "wkpos_user_group");

		return $query->row['total'];
	}

	// public function addPermission($user_group_id, $type, $route) {
	// 	$user_group_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "wkpos_user_group WHERE user_group_id = '" . (int)$user_group_id . "'");

	// 	if ($user_group_query->num_rows) {
	// 		$data = json_decode($user_group_query->row['permission'], true);

	// 		$data[$type][] = $route;

	// 		$this->db->query("UPDATE " . DB_PREFIX . "wkpos_user_group SET permission = '" . $this->db->escape(json_encode($data)) . "' WHERE user_group_id = '" . (int)$user_group_id . "'");
	// 	}
	// }

	// public function removePermission($user_group_id, $type, $route) {
	// 	$user_group_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "wkpos_user_group WHERE user_group_id = '" . (int)$user_group_id . "'");

	// 	if ($user_group_query->num_rows) {
	// 		$data = json_decode($user_group_query->row['permission'], true);

	// 		$data[$type] = array_diff($data[$type], array($route));

	// 		$this->db->query("UPDATE " . DB_PREFIX . "wkpos_user_group SET permission = '" . $this->db->escape(json_encode($data)) . "' WHERE user_group_id = '" . (int)$user_group_id . "'");
	// 	}
	// }
}