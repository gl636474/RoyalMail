<?php
/**
 * Calculates the shipping price given the user selection for 1st/2nd class
 * and signed for/standard. Prices vary according to weight and size:
 * 
 * Letter		100g	24cm x 16.5cm x 0.5cm //unused
 * Large Letter	100g	35.3cm x 25cm x 2.5cm
 * 				250g
 * 				500g
 * 				750g
 * Small Parcel	1kg		45cm x 35cm x 16cm
 * 				2kg
 * Med Parcel	1kg		61cm x 46cm x 46cm
 * 				2kg
 * 				5kg
 * 				10kg
 * 				20kg
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
    	
    	// inspect weight, dimentions and volume
    	$total_weight = 0;
    	$total_volume = 0;
    	$max_length = 0;
    	$max_width = 0;
    	$max_depth = 0;
    	
    	// getAllItems() returns array of Mage_Sales_Model_Quote_Item
    	// one per unique item in the cart.
    	foreach ($request->getAllItems() as $item) {
    		
    		// some info is in the item the rest is in the product
    		//$product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
    		$product = $item->getProduct()->getId();
    		
    		$quantity = $item->getTotalQty();
    		
    		// weight in kg
    		$weight = $item->getWeight();
    		if ($weight == 0) // NB: null == 0
    		{
    			$weight = $config->getDefaultWeight();
    		}
    		$weight = $weight * $quantity;
    		
    		// dimentions in cm
    		$length = $product->getLength();
    		if ($length== 0) // NB: null == 0
    		{
    			$length= $config->getDefaultLength();
    		}
    		$width = $product->getWidth();
    		if ($width == 0) // NB: null == 0
    		{
    			$width = $config->getDefaultWidth();
    		}
    		$depth = $product->getDepth();
    		if ($depth == 0) // NB: null == 0
    		{
    			$depth = $config->getDefaultDepth();
    		}
    		
    		// volume in cm3
    		$volume = $length* $width * $depth * $quantity;
    		
    		$total_weight += $weight;
    		$total_volume += $volume;
    		$max_length = max($max_length, $length);
    		$max_width = max($max_width, $width);
    		$max_depth = max($max_depth, $depth);
    	}
    	
    	/** @var Gareth_RoyalMail_Helper_ShippingRates $shippingRates */
    	$shippingRates = Mage::helper('gareth_royalmail/shippingrates');
    	$availableRates = $shippingRates->getMethodsForCriteria($max_length, $max_width, $max_depth, $total_volume, $total_weight);
    	
    	/** @var Mage_Shipping_Model_Rate_Result $result */
    	$result = Mage::getModel('shipping/rate_result');
    	
    	foreach($availableRates as $availableRate)
    	{
    		$internalName = $availableRate[0];
    		$name = $availableRate[1];
    		$cost = $availableRate[2];
    		
    		/** @var Mage_Shipping_Model_Rate_Result_Method $rate */
    		$rate = Mage::getModel('shipping/rate_result_method');
    		$rate->setCarrier($this->_code);
    		$rate->setCarrierTitle($this->getConfigData('title'));
    		$rate->setMethod($internalName);
    		$rate->setMethodTitle($name);
    		$rate->setCost($cost);
    		$rate->setPrice($cost); // cost + handling fee
    		
    		$result->append($rate);
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
    	$allMethodNames = $shippingRates->getAllMethodNames();
    	
    	// TODO remove any that are not allowed.
    	
    	$msg = "";
    	foreach ($allMethodNames as $internalName => $name)
    	{
    		$msg = $msg.$internalName.'=>'.$name.'\n';
    	}
    	Mage::log($msg, null, null, true);
    	
    	return $allMethodNames;
    }
}