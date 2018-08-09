<?php

class ControllerExtensionPaymentApirone extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/apirone');
		$this->load->model('extension/payment/apirone');

		$chkinputthash = $this->model_extension_payment_apirone->check_tx();
		if (!array_key_exists('input_thash', $chkinputthash->row)){
			if($chkinputthash->num_rows != 0){
				$this->model_extension_payment_apirone->update_to_v2();
        	} else{
        		$this->model_extension_payment_apirone->delete_tx_table();
        		$this->model_extension_payment_apirone->install_tx_table();
        	}
		}

		//$this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('apirone', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');

		$data['entry_merchant'] = $this->language->get('entry_merchant');

		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_pending_status'] = $this->language->get('entry_pending_status');

		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_merchantname'] = $this->language->get('entry_merchantname');
		$data['entry_test_mode'] = $this->language->get('entry_test_mode');
		$data['entry_confirmation'] = $this->language->get('entry_confirmation');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchant'])) {
			$data['error_merchant'] = $this->error['merchant'];
		} else {
			$data['error_merchant'] = '';
		}


		$data['breadcrumbs'] = array();

		$data['confirmations'] = array(1,2,3,4,5,6);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/apirone', 'token=' . $this->session->data['token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/apirone', 'token=' . $this->session->data['token'], true);

		$data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true);
		$secret = $this->config->get('apirone_secret');


		if ($this->config->get('apirone_secret') == null) {
			$data['apirone_secret'] = $secret = md5(time().$this->session->data['token']);
		} else {
			$data['apirone_secret'] = $this->config->get('apirone_secret');
		}

		if (isset($this->request->post['apirone_merchant'])) {
			$data['apirone_merchant'] = $this->request->post['apirone_merchant'];
		} else {
			$data['apirone_merchant'] = $this->config->get('apirone_merchant');
		}

		if (isset($this->request->post['apirone_order_status_id'])) {
			$data['apirone_order_status_id'] = $this->request->post['apirone_order_status_id'];
		} else {
			$data['apirone_order_status_id'] = $this->config->get('apirone_order_status_id');
		}
			
		if (isset($this->request->post['apirone_pending_status_id'])) {
			$data['apirone_pending_status_id'] = $this->request->post['apirone_pending_status_id'];
		} else {
			$data['apirone_pending_status_id'] = $this->config->get('apirone_pending_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		if (isset($this->request->post['apirone_geo_zone_id'])) {
			$data['apirone_geo_zone_id'] = $this->request->post['apirone_geo_zone_id'];
		} else {
			$data['apirone_geo_zone_id'] = $this->config->get('apirone_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['apirone_test_mode'])) {
			$data['apirone_test_mode'] = $this->request->post['apirone_test_mode'];
		} else {
			$data['apirone_test_mode'] = $this->config->get('apirone_test_mode');
		}
		if (isset($this->request->post['apirone_confirmation'])) {
			$data['apirone_confirmation'] = $this->request->post['apirone_confirmation'];
		} else {
			$data['apirone_confirmation'] = $this->config->get('apirone_confirmation');
		}

		if (isset($this->request->post['apirone_status'])) {
			$data['apirone_status'] = $this->request->post['apirone_status'];
		} else {
			$data['apirone_status'] = $this->config->get('apirone_status');
		}

		if (isset($this->request->post['apirone_sort_order'])) {
			$data['apirone_sort_order'] = $this->request->post['apirone_sort_order'];
		} else {
			$data['apirone_sort_order'] = $this->config->get('apirone_sort_order');
		}

		if (isset($this->request->post['apirone_merchantname'])) {
			$data['apirone_merchantname'] = $this->request->post['apirone_merchantname'];
		} else {
			$data['apirone_merchantname'] = $this->config->get('apirone_merchantname');
		}

		if (isset($this->request->post['apirone_sort_canceled'])) {
			$data['apirone_sort_canceled'] = $this->request->post['apirone_sort_canceled'];
		} else {
			$data['apirone_sort_canceled'] = $this->config->get('apirone_sort_canceled');
		}
		
		if (isset($this->request->post['apirone_sort_pending'])) {
			$data['apirone_sort_pending'] = $this->request->post['apirone_sort_pending'];
		} else {
			$data['apirone_sort_pending'] = $this->config->get('apirone_sort_pending');
		}

		echo $this->config->get('apirone_sort_pending');
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/apirone', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/apirone')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['apirone_merchant']) {
			$this->error['merchant'] = $this->language->get('error_merchant');
		}

		return !$this->error;
	}

	public function install() {
		$this->load->model('extension/payment/apirone');
		$this->load->model('setting/setting');
		$data = array('apirone_test_mode' => '0', 'apirone_pending_status_id' => '1', 'apirone_order_status_id' => '5');
		$this->model_setting_setting->editSetting('apirone', $data);		
		$this->model_extension_payment_apirone->install_tx_table();
		$this->model_extension_payment_apirone->install_sales_table();
	}

	public function uninstall() {
		$this->load->model('setting/setting');
		$this->load->model('extension/payment/apirone');
		$data = array();
		$this->model_setting_setting->editSetting('apirone', $data);	
		$this->model_extension_payment_apirone->delete_tx_table();
		$this->model_extension_payment_apirone->delete_sales_table();
	}
}