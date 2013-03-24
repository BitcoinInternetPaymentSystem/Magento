<?php
	// callback controller
	class BIPS_BIPS_IndexController extends Mage_Core_Controller_Front_Action {
	
		// BIPS's IPN lands here
		public function indexAction()
		{
			$BIPS = $_POST;
			$hash = hash('sha512', $BIPS['transaction']['hash'] . Mage::getStoreConfig('payment/BIPS/BIPS_secret'));

			if ($BIPS['hash'] == $hash && $BIPS['status'] == 1)
			{
				// get the order
				if (isset($_POST['custom']['quoteId']))
				{
					$quoteId = $_POST['custom']['quoteId'];
					$order = Mage::getModel('sales/order')->load($quoteId, 'quote_id');
				}
				else
				{
					$orderId = $_POST['custom']['orderId'];
					$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
				}

				// save the ipn so that we can find it when the user clicks "Place Order"
				Mage::getModel('BIPS/ipn')->Record($_POST);

				// update the order if it exists already
				if ($order->getId())
				{
					foreach($order->getInvoiceCollection() as $i)
						$i->pay()->save();
					
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
				}
			}
			else
			{
				Mage::log("BIPS callback error: " . $_POST["invoice"]);
			}
		}
	}
?>