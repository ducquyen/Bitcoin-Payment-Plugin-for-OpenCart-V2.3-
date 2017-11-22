<?php

class ControllerExtensionPaymentApirone extends Controller {

	public function index() {
				$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');
		
		$this->load->language('extension/payment/apirone');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$test = $this->config->get('apirone_test_mode');
		//get test mode status
		if ($test){
			$apirone_adr =$this->language->get('test_url');
		}
		else{
			$apirone_adr = $this->language->get('live_url');
		}
			$address = $this->config->get('apirone_merchant'); // Destination Bitcoin address
			$secret = $this->config->get('apirone_secret');
			$currency = $order_info['currency_code'];
			$amount = $this->convert_to_btc($order_info['currency_code'], $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false));
			$refnum = $this->session->data['order_id'];
			//echo $amount;
			$args = array(
				'address' => $address,
				'callback' => urlencode(HTTP_SERVER . 'index.php?route=extension/payment/apirone/callback&secret='. $secret .'&refnum='.$refnum),
			);
			$apirone_create = $apirone_adr . '?method=create&address=' . $args['address'] . '&callback=' . $args['callback'];

			$apironeCurl = curl_init();
			curl_setopt_array($apironeCurl, array(
			    CURLOPT_URL => $apirone_create,
			    CURLOPT_RETURNTRANSFER => 1
			));
			$response_create = curl_exec($apironeCurl);
			curl_close($apironeCurl);
			$response_create = json_decode($response_create, true);
			print_r($response_create);
			echo '<div><ul class="order_details"><li>Please send exactly <strong>'. $amount .' BTC</strong> </li><li>for this address:<strong>'. $response_create['input_address'];
			echo '</strong></li><li><img src="https://bitaps.com/api/qrcode/png/'. urlencode( "bitcoin:".$response_create['input_address']."?amount=".$amount ."&label=Apirone pay" ) .'"></li><li class="apirone_result"></li></ul></div>';


		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		return $this->load->view('extension/payment/apirone', $data);
	}

	private function convert_to_btc($currency, $value){
			$apironeConvertTotalCost = curl_init();
			//$apirone_tobtc = $apirone_adr . 'tobtc?currency='.$args['currency'].'&value='.$args['value'];
			$apirone_tobtc = 'https://blockchain.info/tobtc?currency='.$currency.'&value='.$value;
			curl_setopt_array($apironeConvertTotalCost, array(
			    CURLOPT_URL => $apirone_tobtc,
			    CURLOPT_RETURNTRANSFER => 1
			));
			$response_btc = curl_exec($apironeConvertTotalCost);
			curl_close($apironeConvertTotalCost);
			return $response_btc;
	}

	public function callback() {
		$success_url = $this->url->link('checkout/success');
		$pending_url = $this->url->link('checkout/success');
		$cancel_url = $this->url->link('checkout/checkout', '', true);

   if(
   isset($this->request->get['refnum']) &&
   isset($this->request->get['secret']) &&
   isset($this->request->get['value']) &&
   isset($this->request->get['confirmations']) &&
   isset($this->request->get['input_address']) &&
   isset($this->request->get['transaction_hash'])
	){
		$secret = $this->request->get['secret'];
		$value = $this->request->get['value'];
		$confirmations = $this->request->get['confirmations'];
		$input_address = $this->request->get['input_address'];
		$th = $this->request->get['transaction_hash'];
		$refnum = $this->request->get['refnum'];

		if($secret == $this->config->get('apirone_secret')) {
			$order_id = (int) $refnum;
		} else {
			$order_id = 0;
		};

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);
		$status = -1;
		
		if ($order_info) {
			//$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'));

		if ($confirmations > 1) {
				$response_btc = $this->convert_to_btc($order_info['currency_code'], $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false));
				$notes  = 'Input Address: '. $input_address .', Transaction ID: '.$th .', Order ID: '. $refnum;
				$diff = 'Need ' .$response_btc . ', Transfered '. $value;
				if($response_btc > $value) $notes .= '. User trasfrer not enough money. ('. $diff . ' BTC)';
				if($response_btc < $value) $notes .= '. User trasfrer more money than You need. (' . $diff . ' BTC)';
				$status = 1;
				echo "*ok*";
		} else {
				$notes ='Waitng for payment';
				$status = 2;
		}

		switch($status) {
			case -1:
				echo '';
				break;
			case 0:
				$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('apirone_canceled_status_id'), $notes, true);
				//$this->response->redirect($cancel_url);
				break;
			case 1:
				$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('apirone_order_status_id'), $notes, true);
				//$this->response->redirect($success_url);
				break;
			case 2:
				$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('apirone_pending_status_id'), $notes, true);
				//$this->response->redirect($pending_url);
				break;
			default:
				//$this->response->redirect($cancel_url);
				break;
		}
	
	}
	}
	}

}