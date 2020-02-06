<?php
/**
 * The Royal Mail Shipping Method. This class requires the following config
 * settings either in <defaults> in config.xml or set via the admin interface
 * before this class is first used:
 * 
 * <pre>
    &lt;default&gt;
        &lt;carriers&gt;
            &lt;gareth_royalmail&gt;
                &lt;active&gt;1&lt;/active&gt;
                &lt;sort_order&gt;10&lt;/sort_order&gt;
                &lt;model&gt;gareth_royalmail/carrier&lt;/model&gt;
                &lt;title&gt;RoyalMail&lt;/title&gt;
                &lt;sallowspecific&gt;1&lt;/sallowspecific&gt;
                &lt;specificcountry&gt;UK&lt;/specificcountry&gt;
            &lt;/gareth_royalmail&gt;
        &lt;/carriers&gt;
    &lt;/default&gt;
   </pre>
 *
 * Note:
 * Magento will check the <sallowspecific> and <specificcountry> config settings
 * but not whether we are enabled. We must check:
 * <code>$this->getConfigFlag('active')</code> ourselves.
 * 
 * Note:
 * An exception in a carrier during checkout will cause  Magento to default to
 * the unchanged cart page (instead of showing blank or stacktrace).
 * 
 * TODO deal with parent/child products - currently they are totally ignored
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
     * Given an array of Mage_Sales_Model_Quote_Item items, returns the array:
     * <code>
     * array($total_volume, $total_weight, $max_length, $max_width, $max_depth)
     * </code>
     * 
     * which is the total volume and weight of all the items in the request,
     * counting for the quantity of each item.
     * 
     * @param array $all_items
     * @return array
     */
    protected function calculateTotalVolumeAndWeight($all_items)
    {
    	
    	/** @var Gareth_RoyalMail_Helper_Config $config */
    	$config = Mage::helper('gareth_royalmail/config');
    	
    	$default_length = $config->getDefaultLength();
    	$default_width = $config->getDefaultWidth();
    	$default_depth = $config->getDefaultDepth();
    	$default_weight = $config->getDefaultWeight();
    	
    	$total_weight = 0;
    	$total_volume = 0;
    	$max_length = 0;
    	$max_width = 0;
    	$max_depth = 0;
    	/* @var Mage_Sales_Model_Quote_Item $item */
    	foreach ($all_items as $item)
    	{
    		// Notes:
    		//  * Virtual items are not shipped so don't include
    		//  * IGNORE parent/child products for the moment
    		//  * If a 'free shipping' item (e.g. due address) then pretend it
    		//    does not exist for weight/dimensions/price calcs
    		if ($item->getProduct()->isVirtual() || $item->getHasChildren() || $item->getParentItem() || $item->getFreeShipping())
    		{
    			continue;
    		}
    		
    		// must load full product to get EAVs (e.g. is_organic) loaded
    		// otherwise they will be null
    		$id = $item->getProduct()->getId();
    		$full_product = Mage::getModel('catalog/product')->load($id);
    		
    		$product_quantity = $item->getTotalQty();
    		
    		// NB getWeight() returns the weight of one product
    		$item_weight = $item->getWeight();
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
    	
    	Mage::log('RoyalMail: total volume: '.$total_volume, null, 'gareth.log');
    	Mage::log('RoyalMail: total weight: '.$total_weight, null, 'gareth.log');
    	return array($total_volume, $total_weight, $max_length, $max_width, $max_depth);
    }
    
    /**
     * Returns available shipping rates for this carrier
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
    	Mage::log('Gareth_RoyalMail_Model_Carrier::collectRates called', Zend_Log::INFO, 'gareth.log');
    	
    	/** Check we are enabled */
    	if (!$this->getConfigFlag('active'))
    	{
    		return false;
    	} 
    	
    	// inspect all items for total weight/volume and max width/height/depth
    	list($total_volume, $total_weight, $max_length, $max_width, $max_depth) = $this->calculateTotalVolumeAndWeight($request->getAllItems());
    	
    	/** @var Gareth_RoyalMail_Helper_Rates $rates */
    	$rates = Mage::helper('gareth_royalmail/rates');
    	
		/** @var Mage_Shipping_Model_Rate_Result $result */
        $result = Mage::getModel('shipping/rate_result');
        
        foreach ($rates->getMethodsForCriteria($max_length, $max_width, $max_depth, (int)ceil($total_volume), $total_weight) as $rate)
        {
        	Mage::log(" -> $rate[0] / $rate[1] = £$rate[2]", Zend_Log::DEBUG, 'gareth.log');
        	
        	/** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        	$mage_rate = Mage::getModel('shipping/rate_result_method');
        	$mage_rate->setCarrier($this->_code);
        	$mage_rate->setCarrierTitle($this->getConfigData('title'));
        	$mage_rate->setMethod($rate[0]);
        	$mage_rate->setMethodTitle($rate[1]);
        	
        	$shipping_cost = $rate[2];
        	$shipping_cost = $this->getFinalPriceWithHandlingFee($shipping_cost);
        	$mage_rate->setCost($shipping_cost);
        	$mage_rate->setPrice($shipping_cost);
        	
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
    	Mage::log('Gareth_RoyalMail_Model_Carrier::getAllowedMethods called', Zend_Log::INFO, 'gareth.log');
    	
    	/** @var Gareth_RoyalMail_Helper_Rates $shippingRates */
    	$shippingRates = Mage::helper('gareth_royalmail/rates');
    	
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
    	}
    	else
    	{
    		//Mage::log('product '.$property_name.': '.$property_value, null, null, true);
    	}
    	return $property_value;
    }
}