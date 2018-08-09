<?php

class ModelExtensionPaymentApirone extends Model {

	public function install_tx_table() {
	$this->db->query("
    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "apirone_transactions` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        paid bigint DEFAULT '0' NOT NULL,
        confirmations int DEFAULT '0' NOT NULL,
        thash text NOT NULL,
        input_thash text NOT NULL,
        order_id int DEFAULT '0' NOT NULL,
        PRIMARY KEY  (id)
    ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
	}

    public function install_sales_table() {
    $this->db->query("
    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "apirone_sale` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        address text NOT NULL,
        order_id int DEFAULT '0' NOT NULL,
        PRIMARY KEY  (id)
    ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }

	public function delete_sales_table() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "apirone_sale`;");
	}   

    public function delete_tx_table(){
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "apirone_transactions`;");
    }

    public function check_tx() {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "apirone_transactions` LIMIT 1");
    }

    public function update_to_v2() {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "apirone_transactions` ADD `input_thash` text NOT NULL AFTER thash;");
    }
}