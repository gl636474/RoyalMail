<?php
/**
 * Notes:
 * Loading helper works OK. If there is an error then Magento will default to
 * the cart page (instead of showing blank or stacktrace).
 * 
 * @author gareth
 */
class Gareth_RoyalMail_Model_Carrier
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * Carriers code, as defined in parent class
     *
     * @var string
     */
    protected $_code = 'gareth_royalmail';
    
    /**
     * Returns available shipping rates for this carrier
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
    	/** @var Gareth_RoyalMail_Helper_Config $config */
    	$config = Mage::helper('gareth_royalmail/config');
    	
    	$default_length = $config->getDefaultLength();
    	$default_width = $config->getDefaultWidth();
    	$default_depth = $config->getDefaultDepth();
    	$default_weight = $config->getDefaultWeight();
    	
    	// inspect all items for total weight/volume and max width/height/depth
    	$total_weight = 0;
    	$total_volume = 0;
		$max_length = 0;
    	$max_width = 0;
    	$max_depth = 0;
    	
    	//Mage::log('# items = '.count($request->getAllItems()));
    	foreach ($request->getAllItems() as $item) {

    		// TODO need to times by quantity of the product in this item
    		// getWeight() returns weight of the product even if user specified
    		// quantity of 10
    		$total_weight += $item->getWeight();
    		
    		// must load full product to get EAVs (e.g. is_organic) loaded
    		$full_product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
    		
    		Mage::log('product: '.$full_product->getName(), null, null, true);
    		
    		$product_height = $full_product->getData('height');
    		Mage::log('product height: '.$product_height, null, null, true);
    		if (is_null($product_height))
    		{
    			$product_height = $default_length;
    		}
    		$max_length = max($max_length, $product_height);
    		
    		$product_width = $full_product->getData('width');
    		Mage::log('product width: '.$product_width, null, null, true);
    		if (is_null($product_width))
    		{
    			$product_width = $default_width;
    		}
    		$max_width = max($max_width, $product_width);
    		
    		$product_depth = $full_product->getData('depth');
    		Mage::log('product depth: '.$product_depth, null, null, true);
    		if (is_null($product_depth))
    		{
    			$product_depth =$default_depth;
    		}
    		$max_depth= max($max_depth, $product_depth);
    	
    		// TODO need to times by quantity of the product in this item
    		$product_volume = $product_depth * $product_height * $product_width;
    		$total_volume += $product_volume;
    	}

    	Mage::log('total volume: '.$total_volume, null, null, true);
    	Mage::log('total weight: '.$total_weight, null, null, true);
    	
    	/** @var Gareth_RoyalMail_Helper_Rates $rates */
    	$rates = Mage::helper('gareth_royalmail/rates');
    	
		/** @var Mage_Shipping_Model_Rate_Result $result */
        $result = Mage::getModel('shipping/rate_result');
        
         foreach ($rates->getMethodsForCriteria($max_length, $max_width, $max_depth, (int)ceil($total_volume), $total_weight) as $rate)
        {
        	/** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        	$mage_rate = Mage::getModel('shipping/rate_result_method');
        	$mage_rate->setCarrier($this->_code);
        	$mage_rate->setCarrierTitle($this->getConfigData('title'));
        	$mage_rate->setMethod($rate[0]);
        	$mage_rate->setMethodTitle($rate[1]);
        	$mage_rate->setCost($rate[2]);
        	$mage_rate->setPrice($rate[2]);
        	
        	$result->append($mage_rate);
        }
        return $result;
    }
    
    /**
     * Returns Allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
    	Mage::log('getAllowedMethods', null, null, true);
    	
    	/** @var Gareth_RoyalMail_Helper_ShippingRates $shippingRates */
    	$shippingRates = Mage::helper('gareth_royalmail/shippingrates');
    	
    	return $shippingRates->getAllMethodNames();
    }
}