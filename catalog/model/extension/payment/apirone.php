<?php
class ModelExtensionPaymentApirone extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/apirone');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('apirone_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('apirone_total') > 0 && $this->config->get('apirone_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('apirone_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'apirone',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('apirone_sort_order')
			);
		}

		return $method_data;
	}

	public function abf_getSales($order_id, $address = NULL) {

			if (is_null($address)) {
				$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "apirone_sale` WHERE `order_id` = '" . (int)$order_id . "'"); 
			} else {
				$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "apirone_sale` WHERE `order_id` = '" . (int)$order_id . "' AND `address` = '" . (string)$address . "'");
			}

			if ($qry->num_rows) {
				$order = $qry->row;
				$order['transactions'] = $this->abf_getTransactions($order_id);
				return $order;
			} else {
				return false;
			}
	}

	public function abf_getTransactions($order_id) {
		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "apirone_transactions` WHERE `order_id` = '" . (int)$order_id . "'");

		if ($qry->num_rows) {
			return $qry->rows;
		} else {
			return false;
		}
	}

	public function abf_updateTransaction($where_input_thash, $where_paid, $confirmations, $thash = NULL, $where_order_id = NULL, $where_thash = 'empty') {
		if (is_null($thash) || is_null($where_order_id)) {

		$this->db->query("UPDATE `" . DB_PREFIX . "apirone_transactions` SET `time` = NOW(), `confirmations` = '" . (int)$confirmations . "' WHERE `paid` = '" . (int)$where_paid . "'AND `thash` = '" . (string)$thash  . "'AND `input_thash` = '" .(string)$where_input_thash .  "'");

		} else{

		$this->db->query("UPDATE `" . DB_PREFIX . "apirone_transactions` SET `thash` = '" . (string)$thash . "', `time` = NOW(), `confirmations` = '" . (int)$confirmations . "' WHERE `order_id` = '" . (int)$where_order_id . "'AND `paid` = '" . (int)$where_paid . "'AND `thash` = '" . (string)$where_thash . "'AND `input_thash` = '" . (string)$where_input_thash .  "'");

		}
	}

	public function abf_addSale($order_id, $address) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "apirone_sale` SET `order_id` = '" . (int)$order_id . "', `time` = NOW(), `address` = '" . $this->db->escape($address) . "'");
	}

	public function abf_addTransaction($order_id, $thash, $input_thash, $paid, $confirmations) {

		$this->db->query("DELETE FROM `" . DB_PREFIX . "apirone_transactions` WHERE `input_thash` = '" . $this->db->escape($input_thash) . "'");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "apirone_transactions` SET `order_id` = '" . (int)$order_id . "', `time` = NOW(), `thash` = '" . $this->db->escape($thash)  . "', `input_thash` = '" . $this->db->escape($input_thash)  .  "', `paid` = '" . $this->db->escape($paid) . "', `confirmations` = '" . (int)$confirmations . "'");
	}

}