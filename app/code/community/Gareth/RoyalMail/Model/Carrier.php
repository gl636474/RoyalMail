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
    	/** Check we are enabled */
    	if (!$this->getConfigFlag('active'))
    	{
    		return false;
    	} 
    	
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

    		// $item is Mage_Sales_Model_Quote_Item
    		// must load full product to get EAVs (e.g. is_organic) loaded
    		// otherwise they will be null
    		$id = $item->getProduct()->getId();
    		$full_product = Mage::getModel('catalog/product')->load($id);
    		Mage::log('product: '.$full_product->getName(), null, null, true);
    		
    		$product_quantity = $item->getTotalQty();
    		Mage::log('quantity: '.$product_quantity);
    		
    		// NB getWeight() returns the weight of one product
    		$item_weight = $item->getWeight();
    		Mage::log('weight: '.$item_weight.'kg');
    		$item_weight *= $product_quantity;
    		$total_weight += $item_weight;
    		    		
    		$product_length = $this->getPropertyValue($full_product, 'package_height', $default_length);
    		$max_length = max($max_length, $product_length);
    		
    		$product_width= $this->getPropertyValue($full_product, 'package_width', $default_width);
    		$max_width = max($max_width, $product_width);
    		
    		$product_depth= $this->getPropertyValue($full_product, 'package_depth', $default_depth);
    		$max_depth= max($max_depth, $product_depth);
    	
    		// NB getPackagdDepth() etc. returns the depth of one product
    		$product_volume = $product_depth * $product_length * $product_width;
    		$item_volume = $product_volume * $product_quantity;
    		$total_volume += $item_volume;
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
    
    /**
     * Returns the specified property of the specified product (which should
     * be fully loaded). Returns the specified default if the property does not
     * exist or is null.
     */
    protected function getPropertyValue($full_product, $property_name, $default)
    {
    	$property_value = $full_product->getData($property_name);
    	if (is_null($property_value))
    	{
    		$property_value= $default;
    		Mage::log('product '.$property_name.': using default '.$property_value, null, null, true);
    	}
    	else
    	{
    		Mage::log('product '.$property_name.': '.$property_value, null, null, true);
    	}
    	return $property_value;
    }
}