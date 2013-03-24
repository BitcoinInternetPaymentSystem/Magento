<?php
	class BIPS_BIPS_Model_Ipn extends Mage_Core_Model_Abstract
	
{
		function _construct()
		{
			$this->_init('BIPS/ipn');
			return parent::_construct();
		}
		
		function Record($post)
		{
			return $this
				->setQuoteId(isset($post['quoteId']) ? $post['quoteId'] : NULL)
				->setOrderId(isset($post['orderId']) ? $post['orderId'] : NULL)
				->setInvoice($post['invoice'])
				->setTxid($post['transaction']['hash'])
				->setPrice($post['fiat']['amount'])
				->setStatus($post['status'])
				->setInvoiceTime($post['timestamp'])
				->save();
		}
		
		function GetStatusReceived($quoteId, $statuses)
		{
			$collection = $this->getCollection()->AddFilter('quote_id', $quoteId);
			foreach($collection as $i)
			{
				if (in_array($i->getStatus(), $statuses))
				{
					return true;
				}
			}
					
			return false;
		}
		
		function GetQuotePaid($quoteId)
		{
			return $this->GetStatusReceived($quoteId, array('1'));
		}
	}


?>