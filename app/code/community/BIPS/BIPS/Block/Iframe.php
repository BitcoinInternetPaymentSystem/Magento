<?php
	class BIPS_BIPS_Block_Iframe extends Mage_Checkout_Block_Onepage_Payment
	{
		protected function _construct()
		{      
			$this->setTemplate('BIPS/iframe.phtml');
			parent::_construct();
		}
		
		// create an invoice and return the url so that iframe.phtml can display it
		public function GetIframeUrl()
		{
			if (Mage::registry('customer_save_observer_executed')){
				return $this; //this method has already been executed once in this request (see comment below)
			}			
			
			if (!Mage::getStoreConfig('payment/BIPS/onsite'))
				return 'disabled';

			$quote = $this->getQuote();

			$quoteId = $quote->getId();
			if (Mage::getModel('BIPS/ipn')->GetQuotePaid($quote->getId()))
				return 'paid'; // quote's already paid, so don't show the iframe

			Mage::getSingleton('core/session', array('name'=>'frontend'));
			$session = Mage::getSingleton('checkout/session');

/*
			$item_name = '';

			foreach ($session->getQuote()->getAllItems() as $item)
			{
				$item_name .= 'SKU:' . $item->getSku() . ', ';
				$item_name .= $item->getName() . ', ';
				$item_name .= 'Qty:' . $item->getQty() . ' - ';
			}
*/			

			$ch = curl_init();
			curl_setopt_array($ch, array(
			CURLOPT_URL => 'https://bips.me/api/v1/invoice',
			CURLOPT_USERPWD => Mage::getStoreConfig('payment/BIPS/BIPS_apikey'),
			CURLOPT_POSTFIELDS => 'price=' . number_format($quote->getGrandTotal(), 2, '.', '') . '&currency=' . $quote->getQuoteCurrencyCode() . '&item=' . $quoteId . '&custom=' . json_encode(array('quoteId' => $quoteId, 'returnurl' => rawurlencode(Mage::getUrl('customer/account')))),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC));
			$redirect = curl_exec($ch);
			curl_close($ch);
			
			Mage::register('customer_save_observer_executed',true); 

			return $redirect . '/iframe';
		}
	}
?>
