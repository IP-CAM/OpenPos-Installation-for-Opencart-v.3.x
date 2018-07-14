<?php
class ModelWkposUser extends Model {
  /**
  * used for login purposes
  * @param  string $username contains the username of a user
  * @param  string $password contains the password of a user
  * @return array           return the details of a user or false
  */
  public function login($username, $password) {
    $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_user wu LEFT JOIN " . DB_PREFIX . "wkpos_outlet wo ON (wu.outlet_id = wo.outlet_id) WHERE wu.username = '" . $this->db->escape($username) . "' AND (wu.password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape($password) . "'))))) OR wu.password = '" . $this->db->escape(md5($password)) . "') AND wu.status = '1' AND wo.status = '1' ");

    if ($user_query->num_rows) {
      $this->session->data['user_login_id'] = $user_query->row['user_id'];
      $this->session->data['wkpos_outlet'] = $user_query->row['outlet_id'];

      $data['name'] = $user_query->row['firstname'] . ' ' . $user_query->row['lastname'];

      $this->load->model('tool/image');

      if ($user_query->row['image']) {
        $data['image'] = $this->model_tool_image->resize($user_query->row['image'], 100, 100);
      } else {
        $data['image'] = $this->model_tool_image->resize('no_image.png', 100, 100);
      }

      $data['user_id'] = $user_query->row['user_id'];
      $data['firstname'] = $user_query->row['firstname'];
      $data['lastname'] = $user_query->row['lastname'];
      $data['email'] = $user_query->row['email'];
      $data['username'] = $user_query->row['username'];

      $outlet_query = $this->db->query("SELECT name FROM " . DB_PREFIX . "wkpos_outlet WHERE outlet_id = '" . (int)$user_query->row['outlet_id'] . "'");

      $data['group_name'] = $outlet_query->row['name'];

      return $data;
    } else {
      return false;
    }
  }
  /**
  * fetches the details of a user using its user id
  * @param  integer $user_id contains the user's id
  * @return array          returns the data of a user or false
  */
  public function getUser($user_id, $user_only = '') {
    $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_user WHERE user_id = '" . $user_id . "'");

    if ($user_query->num_rows) {
      $data = $user_query->row;
      $data['name'] = $user_query->row['firstname'] . ' ' . $user_query->row['lastname'];

      if (!$user_only) {
        $this->load->model('tool/image');

        if ($user_query->row['image']) {
          $data['image'] = $this->model_tool_image->resize($user_query->row['image'], 100, 100);
        } else {
          $data['image'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        }

        $outlet_query = $this->db->query("SELECT name FROM " . DB_PREFIX . "wkpos_outlet WHERE outlet_id = '" . (int)$user_query->row['outlet_id'] . "'");

        $data['group_name'] = $outlet_query->row['name'];
      }

      return $data;
    } else {
      return false;
    }
  }
  /**
  * finds whether a user exist with the same email
  * @param  string $email contains the email of a user
  * @return integer        returns the number of user with same email address
  */
  public function getUsersByEmail($email) {
    $sql = "SELECT count(*) as total FROM " . DB_PREFIX . "wkpos_user WHERE email = '" . $email . "' AND user_id != '" . $this->session->data['user_login_id'] . "'";
    $query = $this->db->query($sql);

    return $query->row['total'];
  }
  /**
  * checks the current password of a user
  * @param  string $pwd contains the current password of a user
  * @return boolean      returns true or false
  */
  public function checkPreviousPwd($pwd) {
    $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_user WHERE (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape($pwd) . "'))))) OR password = '" . $this->db->escape(md5($pwd)) . "') AND status = '1' AND user_id = '" . $this->session->data['user_login_id'] . "'");

    if ($user_query->num_rows) {
      return true;
    } else {
      return false;
    }
  }
  /**
  * updated the details of a user
  * @param  array $data contains the data of a user
  * @return null       none
  */
  public function updateProfile($data) {
    $this->db->query("UPDATE " . DB_PREFIX . "wkpos_user SET firstname = '" . $data['firstname'] . "', lastname = '" . $data['lastname'] . "', email = '" . $data['account_email'] . "', username = '" . $data['username'] . "', salt = '" . $this->db->escape($salt = token(9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($data['account_npwd'])))) . "' WHERE user_id = '" . $this->session->data['user_login_id'] . "'");
  }
  /**
   * validates the user id exists in the session
   * @return array|boolean returns the details of the user if exists else false
   */
	public function checkUserLogin() {
		if (isset($this->session->data['user_login_id']) && $this->session->data['user_login_id']) {
			$check_user = $this->db->query("SELECT * FROM " . DB_PREFIX . "wkpos_user WHERE user_id = '" . $this->session->data['user_login_id'] . "'")->row;
			return $check_user;
		} else {
			return false;
		}
	}
}
