<?php

class ApironeCurl {
  private $logger;
  private static $instance;
 
  /**
 * @param  object  $registry  Registry Object
 */
  public static function get_instance($registry) {
 if (is_null(static::$instance)) {
 static::$instance = new static($registry);
 }
 
 return static::$instance;
  }
 
  /** 
 * @param  object  $registry  Registry Object
 * 
 */
  protected function __construct($registry) {
 // load the "Log" library from the "Registry"
 $this->logger = $registry->get('log');
  }
 
  /**
 * @param  string  $url Url
 * @param  array  $params  Key-value pair
 */
  public function do_request($url, $params=array()) {
 // log the request
 //$this->logger->write("Initiated CURL request for: {$url}");
 
 // init curl object
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $url);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
 // prepare post array if available
 $params_string = '';
 if (is_array($params) && count($params)) {
 foreach($params as $key=>$value) {
 $params_string .= $key.'='.$value.'&';
 }
 rtrim($params_string, '&');
 
 curl_setopt($ch, CURLOPT_POST, count($params));
 curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
 }
 
 // execute request
 $result = curl_exec($ch);
 
 // close connection
 curl_close($ch);
 
 return $result;
  }
}


class ControllerExtensionPaymentApirone extends Controller {

    public function index() {

        $data['button_confirm'] = $this->language->get('button_confirm');
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/apirone');
        $this->load->model('extension/payment/apirone');

        $test = $this->config->get('apirone_test_mode');

        $order_id = $this->session->data['order_id'];
        $order = $this->model_checkout_order->getOrder($order_id);
            
        //get test mode status
        if ($test){
            /*$apirone_adr =$this->language->get('test_url');*/
            $apirone_adr = $this->language->get('live_url');
        }
        else{
            $apirone_adr = $this->language->get('live_url');
        }
            $response_btc = $this->abf_convert_to_btc($order['currency_code'], $order['total'], $order['currency_value']);

            $data['and_pay'] = $this->language->get('and_pay');
            $data['btc'] = $response_btc;

            if ($this->abf_is_valid_for_use($order['currency_code']) && $response_btc > 0) {
                /**
                 * Args for Forward query
                 */           
                
                $sales = $this->model_extension_payment_apirone->abf_getSales($order_id);
                $data['error_message'] = false;
                
                if ($sales == null) {

                    $order_id = $this->session->data['order_id'];
                    $secret = $this->abf_getKey($order_id);

                    $args = array(
                        'address' => $this->config->get('apirone_merchant'),
                        'callback' => urlencode(HTTP_SERVER . 'index.php?route=extension/payment/apirone/callback&secret='. $secret .'&order_id='.$order_id)
                    );
                    $apirone_create = $apirone_adr . '?method=create&address=' . $args['address'] . '&callback=' . $args['callback'];

                    $obj_curl = ApironeCurl::get_instance($this->registry);                
                    $response_create = $obj_curl->do_request( $apirone_create );
                    $response_create = json_decode($response_create, true);
                    if ($response_create['input_address'] != null){
                        $this->model_extension_payment_apirone->abf_addSale($order_id, $response_create['input_address']);
                    } else{
                        $data['error_message'] =  $this->language->get('no_input_address');
                    }
                } else {
                    $response_create['input_address'] = $sales[0]->address;
                }             

                    $this->abf_logger("Request: {$apirone_create} , Response: {$response_btc}");
            } else {
                $data['error_message'] = $this->language->get('not_exchange') . " " . $order['currency_code'] . " ". $this->language->get('to') ." BTC :(";
            }

        $data['refresh_url'] = $this->url->link('checkout/checkout');
        $data['url_redirect'] = $this->url->link('extension/payment/apirone/confirm','order='.$order_id.'&address='. $response_create['input_address']);
        return $this->load->view('extension/payment/apirone', $data);
    }

    public function confirm(){

        $this->load->model('checkout/order');
        $this->load->language('extension/payment/apirone');
        $this->load->model('extension/payment/apirone');

        if($this->config->get('apirone_merchantname') != ''){
            $data['merchantname'] = $this->config->get('apirone_merchantname');
        } else {
            $data['merchantname'] = $this->config->get('config_name');
        }

        $data['please_send'] = $this->language->get('please_send');
        $data['to_address'] = $this->language->get('to_address');
        $data['merchant'] = $this->language->get('merchant');
        $data['amount_to_pay'] = $this->language->get('amount_to_pay');
        $data['arrived_amount'] = $this->language->get('arrived_amount');
        $data['remains_to_pay'] = $this->language->get('remains_to_pay');
        $data['date'] = $this->language->get('date');
        $data['transactions'] = $this->language->get('transactions');
        $data['no_tx_yet'] = $this->language->get('no_tx_yet');
        $data['status'] = $this->language->get('status');
        $data['loading_data'] = $this->language->get('loading_data');
        $data['status'] = $this->language->get('status');
        $data['if_you_unable_complete'] = $this->language->get('if_you_unable_complete');
        $data['you_can_pay_partially'] = $this->language->get('you_can_pay_partially');
        $data['payment_complete'] = $this->language->get('payment_complete');
        $data['tx_in_network'] = $this->language->get('tx_in_network');
        $data['waiting_payment'] = $this->language->get('waiting_payment');
        $data['confirmations_count'] = $this->language->get('confirmations_count');
        $data['thank_you'] = $this->language->get('thank_you');
        $data['go_to_cart'] = $this->language->get('go_to_cart');
        $data['with_uncomfirmed'] = $this->language->get('with_uncomfirmed');

        $safe_order = intval( $this->request->get['order'] );
        if (isset($this->request->get['address'])) {
        $safe_address = $this->request->get['address'];
            if ( strlen( $safe_address ) > 64 ) {
               $safe_address = substr( $safe_address, 0, 64 );
            }
        }
        if ( !isset($safe_address) ) {
            $safe_address = '';
        }

        $test = $this->config->get('apirone_test_mode');

        //get test mode status
        if ($test){
            /*$apirone_adr =$this->language->get('test_url');*/
            $apirone_adr = $this->language->get('live_url');
        }
        else{
            $apirone_adr = $this->language->get('live_url');
        }

        $order_id = $safe_order;
        $input_address = $safe_address;
        $order = $this->model_checkout_order->getOrder($order_id);

        $response_btc = $this->abf_convert_to_btc($order['currency_code'], $order['total'], $order['currency_value']);

        if ($this->abf_is_valid_for_use($order['currency_code']) && $response_btc > 0) {

            $sales = $this->model_extension_payment_apirone->abf_getSales($order_id);
            $data['error_message'] = false;
                
            if ($sales == null) {
                $this->response->redirect($this->url->link('checkout/cart'));
                return;
            } else {
                $response_create['input_address'] = $sales['address'];
            }
            $message = urlencode("bitcoin:" . $response_create['input_address'] . "?amount=" . $response_btc . "&label=Apirone");
            $data['response_btc'] = number_format($response_btc, 8, '.', '');
            $data['message'] = $message;
            $data['input_address'] = $response_create['input_address'];
            $data['order'] = $order_id;
            $data['current_date'] = date('Y-m-d');
            $data['key'] = $input_address;
            $data['order'] = $order_id;

            $data['back_to_cart'] = $this->url->link('checkout/cart');

        } else {
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }   
        if($input_address == $response_create['input_address']){
            $data['script'] = $this->language->get('script');
            $data['style'] =  $this->language->get('style');
            $this->response->setOutput($this->load->view('extension/payment/apirone_invoice', $data));
            return;
        } else {
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }
    }

    private function abf_convert_to_btc($currency, $value, $currency_value){
           if ($currency == 'BTC') {
                return $value * $currency_value;
            } else { 
                if ($currency == 'USD' || $currency == 'EUR' || $currency == 'GBP') {
                    //$apirone_tobtc = $apirone_adr . 'tobtc?currency='.$args['currency'].'&value='.$args['value'];
                    $apirone_tobtc = 'https://apirone.com/api/v1/tobtc?currency='.$currency.'&value='.$value;
                    $obj_curl = ApironeCurl::get_instance($this->registry);
                    $response_btc = $obj_curl->do_request($apirone_tobtc);
                    $response_btc = json_decode($response_btc, true);
                    return round($response_btc * $currency_value, 8);
                } else {
                if($this->abf_is_valid_for_use($currency)){
                    $obj_curl = ApironeCurl::get_instance($this->registry);
                    $response_coinbase = $obj_curl->do_request('https://api.coinbase.com/v2/prices/BTC-'. $currency .'/buy');
                    $response_coinbase = json_decode($response_coinbase, true);
                    $response_coinbase = $response_coinbase['data']['amount'];
                    if (is_numeric($response_coinbase)) {
                       return round(($value  * $currency_value) / $response_coinbase, 8);
                    } else {
                        return 0;
                    }                   
                } else {
                    return 0;
                }
                }     
            }           
    }


        private function abf_is_valid_for_use($currency = NULL)
        {
            if($currency != NULL){
                $check_currency = $currency;
            } else {
                $check_currency = $_SESSION['currency'];
            }
            
            if (!in_array($check_currency, array(
                'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BCH', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BTC', 'BTN', 'BWP', 'BYN', 'BYR', 'BZD', 'CAD', 'CDF', 'CHF', 'CLF', 'CLP', 'CNH', 'CNY', 'COP', 'CRC', 'CUC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EEK', 'EGP', 'ERN', 'ETB', 'ETH', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GGP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'ISK', 'JEP', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LTC', 'LTL', 'LVL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MTL', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'SSP', 'STD', 'SVC', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VEF', 'VND', 'VUV', 'WST', 'XAF', 'XAG', 'XAU', 'XCD', 'XDR', 'XOF', 'XPD', 'XPF', 'XPT', 'YER', 'ZAR', 'ZMK', 'ZMW', 'ZWL'
            ))) {
                return false;
            }
            return true;
        }

        //checks that order has sale
        private function abf_sale_exists($order_id, $input_address)
        {   $this->load->model('extension/payment/apirone');
            $sales = $this->model_extension_payment_apirone->abf_getSales($order_id, $input_address);
            if ($sales['address'] == $input_address) {return true;} else {return false;};
        }

        // function that checks what user complete full payment for order
        private function abf_check_remains($order_id)
        {
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/apirone');
            $order = $this->model_checkout_order->getOrder($order_id);
            $total = $this->abf_convert_to_btc($order['currency_code'], $order['total'], $order['currency_value']);

            $sales = $this->model_extension_payment_apirone->abf_getSales($order_id);
            $transactions = $sales['transactions'];
            $remains = 0;
            $total_paid = 0;
            $total_empty = 0;
            if($transactions != '')
                foreach ($transactions as $transaction) {
                    if ($transaction['thash'] == "empty") $total_empty+=$transaction['paid'];
                    $total_paid+=$transaction['paid'];
                }
            $total_paid/=1E8;
            $total_empty/=1E8;
            $remains = $total - $total_paid;
            $remains_wo_empty = $remains + $total_empty;
            if ($remains_wo_empty > 0) {
                return false;
            } else {
                return true;
            };
        }

        private function abf_logger($message)
        {
            if ($this->config->get('apirone_test_mode')) {
                $this->log->write($message);
            }
        }

        private function abf_remains_to_pay($order_id)
        {   
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/apirone');
            $order = $this->model_checkout_order->getOrder($order_id);

            $sales = $this->model_extension_payment_apirone->abf_getSales($order_id);
            $transactions = $sales['transactions'];

            $total_paid = 0;
            if($transactions != '')
            foreach ($transactions as $transaction) {
                $total_paid+=$transaction['paid'];
            }
            $response_btc = $this->abf_convert_to_btc($order['currency_code'], $order['total'], $order['currency_value']);
            $remains = $response_btc - $total_paid/1E8;
            if($remains < 0) $remains = 0;  
            return $remains;
        }

private function abf_getKey($order_id){
    $key = $this->config->get('apirone_secret');
    return md5($key . $order_id);
}
private function abf_check_data($apirone_order){
    $abf_check_code = 100; //No value
    if (!empty($apirone_order['value'])) {
        $abf_check_code = 101; //No input address
        if (!empty($apirone_order['input_address'])) {
            $abf_check_code = 102; //No order_id
            if (!empty($apirone_order['orderId'])) {
                $abf_check_code = 103; //No secret
                if (!empty($apirone_order['secret'])) {
                    $abf_check_code = 104; //No confirmations
                    if ($apirone_order['confirmations']>=0) {
                            $abf_check_code = 106; //No input transaction hash
                            if (!empty($apirone_order['input_transaction_hash'])) {
                                $abf_check_code = 200; //No transaction hash
                                if (!empty($apirone_order['transaction_hash'])){
                                    $abf_check_code = 201; //All data is ready
                                }                           
                            }                    
                    }                   
                }
            }
        }
    }
    return $abf_check_code;
}
private function abf_transaction_exists($thash, $order_id){
    $this->load->model('extension/payment/apirone');
    $transactions = $this->model_extension_payment_apirone->abf_getTransactions($order_id);
    $flag = false;
    if($transactions != '')
        foreach ($transactions as $transaction) {
        if($thash == $transaction['thash']){
            $flag = true; // same transaction was in DB
            break;
        }
    }
    return $flag;
}
private function abf_input_transaction_exists($input_thash, $order_id){
    $this->load->model('extension/payment/apirone');
    $transactions = $this->model_extension_payment_apirone->abf_getTransactions($order_id);
    $flag = false;
    if($transactions != '')
        foreach ($transactions as $transaction) {
        if($input_thash == $transaction['input_thash']){
            $flag = true; // same transaction was in DB
            break;
        }
    }
    return $flag;
}
private function secret_is_valid($secret, $order_id){
    $flag = false;
    if($secret == $this->abf_getKey($order_id)){
        $flag = true;
    }
    return $flag;
}
private function confirmations_is_ok($confirmations){
    define("ABF_COUNT_CONFIRMATIONS", $this->config->get('apirone_confirmation'));
    $flag = false;
    if($confirmations >= ABF_COUNT_CONFIRMATIONS) {
        $flag = true;
    }
    return $flag;
}

private function abf_validate_data($apirone_order){
    $abf_check_code = 300; //No sale exists
    if ($this->abf_sale_exists($apirone_order['orderId'], $apirone_order['input_address'])) {
        $abf_check_code = 302; //secret is invalid
            if ($this->secret_is_valid($apirone_order['secret'], $apirone_order['orderId'])) {
                $abf_check_code = 400; //validate complete
            }
    }
    return $abf_check_code;
}

private function abf_empty_transaction_hash($apirone_order){
    $this->load->model('extension/payment/apirone');
    if ($this->abf_input_transaction_exists($apirone_order['input_transaction_hash'],$apirone_order['orderId'])) {
        $this->model_extension_payment_apirone->abf_updateTransaction(
            $apirone_order['input_transaction_hash'],
            $apirone_order['value'],
            $apirone_order['confirmations']
        );
        $abf_check_code = 500; //update existing transaction
    } else {
        $this->model_extension_payment_apirone->abf_addTransaction(
            $apirone_order['orderId'],
            'empty',
            $apirone_order['input_transaction_hash'],
            $apirone_order['value'],
            $apirone_order['confirmations']
        );
        $abf_check_code = 501; //insert new transaction in DB without transaction hash
    }
    return $abf_check_code;
}
private function abf_calculate_payamount($apirone_order){
    $this->load->model('extension/payment/apirone');
    $transactions = $this->model_extension_payment_apirone->abf_getTransactions($apirone_order['orderId']);
    $payamount = 0;
    if($transactions != '')
    foreach ($transactions as $transaction) {
        if($transaction['thash'] != 'empty')
            $payamount += $transaction['paid'];
    }
    return $payamount;
}
private function abf_skip_transaction($apirone_order){
    define("ABF_COUNT_CONFIRMATIONS", $this->config->get('apirone_confirmation')); // number of confirmations
    define("ABF_MAX_CONFIRMATIONS", 150); // max confirmations count
    $abf_check_code = NULL;
    if(($apirone_order['confirmations'] >= ABF_MAX_CONFIRMATIONS) && (ABF_MAX_CONFIRMATIONS != 0)) {// if callback's confirmations count more than ABF_MAX_CONFIRMATIONS we answer *ok*
        $abf_check_code="*ok*";
        $this->abf_logger('[Info] Skipped transaction: ' .  $apirone_order['transaction_hash'] . ' with confirmations: ' . $apirone_order['confirmations']);
        };
        return $abf_check_code;
}
private function abf_take_notes($apirone_order){
    $this->load->model('checkout/order');
    $order = $this->model_checkout_order->getOrder($apirone_order['orderId']);
    $response_btc = $this->abf_convert_to_btc($order['currency_code'], $order['total'], $order['currency_value']);
    $payamount = $this->abf_calculate_payamount($apirone_order);
    $notes  = 'Input Address: ' . $apirone_order['input_address'] . '; Transaction Hash: ' . $apirone_order['transaction_hash'] . '; Payment: ' . number_format($apirone_order['value']/1E8, 8, '.', '') . ' BTC; ';
    $notes .= 'Total paid: '.number_format(($payamount)/1E8, 8, '.', '').' BTC; ';
    if (($payamount)/1E8 < $response_btc)
        $notes .= 'User trasfrer not enough money in your shop currency. Waiting for next payment; ';
    if (($payamount)/1E8 > $response_btc)
        $notes .= 'User trasfrer more money than You need in your shop currency; ';
    $notes .= 'Order total: '.$response_btc . ' BTC; ';
    if ($this->abf_check_remains($apirone_order['orderId'])){ //checking that payment is complete, if not enough money on payment it's not completed 
        $notes .= 'Successfully paid.';
    }
    return $notes;
}
private function abf_filled_transaction_hash($apirone_order){
    $this->load->model('extension/payment/apirone');
    $this->load->model('checkout/order');
    $order = $this->model_checkout_order->getOrder($apirone_order['orderId']);
        if($this->abf_transaction_exists($apirone_order['transaction_hash'],$apirone_order['orderId'])){
            $abf_check_code = 600;//update transaction
            $this->model_extension_payment_apirone->abf_updateTransaction(
                $apirone_order['input_transaction_hash'],
                $apirone_order['value'],
                $apirone_order['confirmations'],
                $apirone_order['transaction_hash'],
                $apirone_order['orderId']
            ); 
        } else {
            $abf_check_code = 601; //small confirmations count for update tx
            if ($this->confirmations_is_ok($apirone_order['confirmations'])) {
            $this->model_extension_payment_apirone->abf_addTransaction(
                $apirone_order['orderId'],
                $apirone_order['transaction_hash'],
                $apirone_order['input_transaction_hash'],
                $apirone_order['value'],
                $apirone_order['confirmations']
            );
            $notes = $this->abf_take_notes($apirone_order);
            $abf_check_code = '*ok*';//insert new TX with transaction_hash
            if ($this->abf_check_remains($apirone_order['orderId'])){ //checking that payment is complete, if not enough money on payment is not completed
                $complete_order_status = $this->config->get('apirone_order_status_id');
                $this->model_checkout_order->addOrderHistory($apirone_order['orderId'], $complete_order_status, $notes, true);
            } else{
                $partiallypaid_order_status = $this->config->get('apirone_pending_status_id');
                $this->model_checkout_order->addOrderHistory($apirone_order['orderId'], $partiallypaid_order_status, $notes, true);
            }
        }
    }
    return $abf_check_code;
}

    public function callback(){

    $this->abf_logger('[Info] Callback' . $_SERVER['REQUEST_URI']);
    if (isset($this->request->get['secret'])) {
        $safe_secret = $this->request->get['secret'];
    } else {
        $safe_secret = '';
    }
    if (isset($this->request->get['order_id'])) {
        $safe_order_id = intval( $this->request->get['order_id'] );
    } else {
        $safe_order_id = '';
    }
    if (isset($this->request->get['confirmations'])) {
        $safe_confirmations = intval( $this->request->get['confirmations'] );
    } else {
        $safe_confirmations = '';
    }
    if (isset($this->request->get['value'])) {
        $safe_value = intval( $this->request->get['value'] );
    } else {
        $safe_value = '';
    }
    if (isset($this->request->get['input_address'])) {
        $safe_input_address = $this->request->get['input_address'];
    } else {
        $safe_input_address = '';
    }
    if (isset($this->request->get['transaction_hash'])) {
        $safe_transaction_hash = $this->request->get['transaction_hash'];
    } else {
        $safe_transaction_hash = '';
    }
    if (isset($this->request->get['input_transaction_hash'])) {
        $safe_input_transaction_hash = $this->request->get['input_transaction_hash'];
    } else {
        $safe_input_transaction_hash = '';
    }

    if ( strlen( $safe_secret ) > 32 ) {
        $safe_secret = substr( $safe_secret, 0, 32 );
    }
    if ( $safe_order_id == 'undefined' ) {
         $safe_order_id = '';
    }
    if ( strlen( $safe_order_id ) > 25 ) {
        $safe_order_id = substr( $safe_order_id, 0, 25 );
    }
    if ( strlen( $safe_confirmations ) > 5 ) {
        $safe_confirmations = substr( $safe_confirmations, 0, 5 );
    }
    if ( ! $safe_confirmations ) {
        $safe_confirmations = 0;
    }
    if ( strlen( $safe_value ) > 16 ) {
        $safe_value = substr( $safe_value, 0, 16 );
    }
    if ( ! $safe_value ) {
        $safe_value = '';
    }
    if ( strlen( $safe_input_address ) > 64 ) {
        $safe_input_address = substr( $safe_input_address, 0, 64 );
    }
    if ( ! $safe_input_address ) {
        $safe_input_address = '';
    }
    if ( strlen( $safe_transaction_hash ) > 65 ) {
        $safe_transaction_hash = substr( $safe_transaction_hash, 0, 65 );
    }
    if ( ! $safe_transaction_hash ) {
        $safe_transaction_hash = '';
    }
    if ( strlen( $safe_input_transaction_hash ) > 65 ) {
        $safe_input_transaction_hash = substr( $safe_input_transaction_hash, 0, 65 );
    }
    if ( ! $safe_input_transaction_hash ) {
        $safe_input_transaction_hash = '';
    }
    $apirone_order = array(
        'value' => $safe_value,
        'input_address' => $safe_input_address,
        'orderId' => $safe_order_id, // order id
        'secret' => $safe_secret,
        'confirmations' => $safe_confirmations,
        'input_transaction_hash' => $safe_input_transaction_hash,
        'transaction_hash' => $safe_transaction_hash
    );
    $check_data_score = $this->abf_check_data($apirone_order);
    $abf_api_output = $check_data_score;
    if( $check_data_score >= 200 ){
        $validate_score = $this->abf_validate_data($apirone_order);
        $abf_api_output = $validate_score;
        if ($validate_score == 400) {
            if($check_data_score == 200){
                $data_action_code = $this->abf_empty_transaction_hash($apirone_order);
            }
            if($check_data_score == 201){
                $data_action_code = $this->abf_filled_transaction_hash($apirone_order);
            }
            $abf_api_output = $data_action_code;
        }
    }
    if($this->config->get('apirone_test_mode')) {
        print_r($abf_api_output);//global output
    } else {
        if($abf_api_output === '*ok*') {
            echo '*ok*';   
        } else{
            echo $this->abf_skip_transaction($apirone_order);
        }
    }
    exit;
    }


   public function check_payment(){
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/apirone');

        define("ABF_COUNT_CONFIRMATIONS", $this->config->get('apirone_confirmation')); // number of confirmations

        $safe_order = intval( $this->request->get['order'] );

        if ( $safe_order == 'undefined') {
             $safe_order = '';
        }

        if ( strlen( $safe_order ) > 25 ) {
            $safe_order = substr( $safe_order, 0, 25 );
        }

        $safe_key = $this->request->get['key'];
        if ( strlen( $safe_key ) > 64 ) {
           $safe_key = substr( $safe_key, 0, 64 );
        }
        if ( !isset($safe_key) ) {
            $safe_key = '';
        }

        if (!empty($safe_order) && !empty($safe_key)) {
            $order = $this->model_checkout_order->getOrder($safe_order);
            /*print_r( $order );*/
            if (!empty($safe_order)) {
                $sales = $this->model_extension_payment_apirone->abf_getSales($safe_order);
                $transactions = $sales['transactions'];
            }
            $empty = 0;
            $value = 0;
            $paid_value = 0;

            $payamount = 0;
            $innetwotk_pay = 0;
            $last_transaction = '';
            $confirmed = '';
            $status = 'waiting';
            //print_r($sales);
            $alltransactions = '';
            if($transactions != '')
            foreach ($transactions as $transaction) {
                if($transaction['thash'] == 'empty') {
                            $status = 'innetwork';
                            $innetwotk_pay += $transaction['paid'];
                }
                if($transaction['thash'] != 'empty') 
                    $payamount += $transaction['paid'];      
               //print_r($transaction);

                if ($transaction['thash'] == "empty"){
                    $empty = 1; // has empty value in thash
                    $value = $transaction['paid'];
                } else{
                    $paid_value = $transaction['paid'];
                    $confirmed = $transaction['thash'];
                }
                $alltransactions[] = array('thash' => $transaction['thash'], 'input_thash' => $transaction['input_thash'], 'confirmations' => $transaction['confirmations']);             
            }
            if ($order == '') {
                echo '';
                exit;
            }
            $response_btc = $this->abf_convert_to_btc($order['currency_code'], $order['total'], $order['currency_value']);
            if ($order['order_status_id'] == $this->config->get('apirone_order_status_id') && $this->abf_check_remains($safe_order)) {
                $status = 'complete';
            }
                $remains_to_pay = number_format($this->abf_remains_to_pay($safe_order), 8, '.', '');
                $last_transaction = $confirmed;
                $payamount = number_format($payamount/1E8, 8, '.', '');
                $innetwotk_pay = number_format($innetwotk_pay/1E8, 8, '.', '');
                $response_btc = number_format($response_btc, 8, '.', '');

            if($sales['address'] == $safe_key){
            $ouput = array('total_btc' => $response_btc, 'innetwork_amount' => $innetwotk_pay, 'arrived_amount' => $payamount, 'remains_to_pay' => $remains_to_pay, 'transactions' => $alltransactions, 'status' => $status, 'count_confirmations' => ABF_COUNT_CONFIRMATIONS);
            echo json_encode($ouput);
            } else {
                echo '';
            }

            exit;
        }  
    }
}