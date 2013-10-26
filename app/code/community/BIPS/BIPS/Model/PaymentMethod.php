<?php
	/**
	* Our test CC module adapter
	*/
	class BIPS_BIPS_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
	{
		/**
		* unique internal payment method identifier
		*
		* @var string [a-z0-9_]
		*/
		protected $_code = 'BIPS';
	 
		/**
		 * Here are examples of flags that will determine functionality availability
		 * of this module to be used by frontend and backend.
		 *
		 * @see all flags and their defaults in Mage_Payment_Model_Method_Abstract
		 *
		 * It is possible to have a custom dynamic logic by overloading
		 * public function can* for each flag respectively
		 */
		 
		/**
		 * Is this payment method a gateway (online auth/charge) ?
		 */
		protected $_isGateway               = true;
	 
		/**
		 * Can authorize online?
		 */
		protected $_canAuthorize            = true;
	 
		/**
		 * Can capture funds online?
		 */
		protected $_canCapture              = false;
	 
		/**
		 * Can capture partial amounts online?
		 */
		protected $_canCapturePartial       = false;
	 
		/**
		 * Can refund online?
		 */
		protected $_canRefund               = false;
	 
		/**
		 * Can void transactions online?
		 */
		protected $_canVoid                 = false;
	 
		/**
		 * Can use this payment method in administration panel?
		 */
		protected $_canUseInternal          = false;
	 
		/**
		 * Can show this payment method as an option on checkout payment page?
		 */
		protected $_canUseCheckout          = true;
	 
		/**
		 * Is this payment method suitable for multi-shipping checkout?
		 */
		protected $_canUseForMultishipping  = true;
	 
		/**
		 * Can save credit card information for future processing?
		 */
		protected $_canSaveCc = false;
		
		//protected $_formBlockType = 'BIPS/form';
		//protected $_infoBlockType = 'BIPS/info';
		
		public function canUseCheckout()
		{		
			$BIPS_apikey = Mage::getStoreConfig('payment/BIPS/BIPS_apikey');
			if (!$BIPS_apikey or !strlen($BIPS_apikey))
			{
				Mage::log('BIPS/BIPS: API key not entered');
				return false;
			}

			$BIPS_secret = Mage::getStoreConfig('payment/BIPS/BIPS_secret');
			if (!$BIPS_secret or !strlen($BIPS_secret))
			{
				Mage::log('BIPS/BIPS: IPN secret not entered');
				return false;
			}
			
			return $this->_canUseCheckout;
		}

		public function authorize(Varien_Object $payment, $amount) 
		{
			if (!Mage::getStoreConfig('payment/BIPS/onsite'))
				return $this->CreateInvoiceAndRedirect($payment, $amount);
			else
				return $this->CheckForPayment($payment);
		}
		
		function CheckForPayment($payment)
		{
			$quoteId = $payment->getOrder()->getQuoteId();
			$ipn = Mage::getModel('BIPS/ipn');
			if (!$ipn->GetQuotePaid($quoteId))
			{
				Mage::throwException("Order not paid for. Please pay first and then Place your Order.");
			}
			
			return $this;
		}
		
		function CreateInvoiceAndRedirect($payment, $amount)
		{
			$order = $payment->getOrder();
			$orderId = $order->getIncrementId();  

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
			CURLOPT_POSTFIELDS => 'price=' . number_format($amount, 2, '.', '') . '&currency=' . $order->getBaseCurrencyCode() . '&item=' . $orderId . '&custom=' . json_encode(array('orderId' => $orderId, 'returnurl' => rawurlencode(Mage::getUrl('customer/account')))),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC));
			$redirect = curl_exec($ch);
			curl_close($ch);

			$payment->setIsTransactionPending(true); // status will be PAYMENT_REVIEW instead of PROCESSING

			$invoiceId = Mage::getModel('sales/order_invoice_api')->create($orderId, array());
			Mage::getSingleton('customer/session')->setRedirectUrl($redirect);

			return $this;
		}

		public function getOrderPlaceRedirectUrl()
		{
			if (Mage::getStoreConfig('payment/BIPS/onsite'))
				return '';
			else
				return Mage::getSingleton('customer/session')->getRedirectUrl();
		}
	}
?>
