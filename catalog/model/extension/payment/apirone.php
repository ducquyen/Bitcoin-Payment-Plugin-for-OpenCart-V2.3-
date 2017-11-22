<?php
class ModelExtensionPaymentApirone extends Model {
	public function getMethod($address)	{
		$this->load->language('extension/payment/apirone');
			return array(
				'code'	   => 'apirone',
				'title'	  => $this->language->get('text_title'),
				'terms'	  => '',
				'sort_order' => ''
			);
	}
}