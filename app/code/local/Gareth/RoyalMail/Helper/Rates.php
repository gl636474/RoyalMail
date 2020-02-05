<?php
/**
 * @author gareth
 *
 * TODO remove some debug logs from all files. Change others to NOTICE.
 * TODO Log to 'gareth.log' in all files.
 */
class Gareth_RoyalMail_Helper_Rates extends
    Mage_Core_Helper_Abstract
{
	/**
	 * Returns the human-readable and internal names of all the
	 * carrier method types (e.g. First Class Signed For).
	 * 
	 * @return array( id => array(name, internal_name))
	 */
	public function getNames()
	{
		return array(
				1=>array('First Class', 'rm1stclass'),	
				2=>array('Second Class', 'rm2ndclass'),
				3=>array('First Class Signed For', 'rm1stclasssigned'),
				4=>array('Second Class Signed For', 'rm2ndclasssigned'),
		);
	}
	
	/**
	 * Returns the maximum sizes for a price. The volume is just the
	 * dimentions multiplied together. All dimentions in cm or cm3.
	 * 
	 * @return array( id => array(name, max_length, max_width, max_depth, max_volume, internal_name)
	 */
	public function getSizes()
	{
		return array(
				1=>array('Letter', 24, 16.5, 0.5, 198, 'letter'),
				2=>array('Large Letter', 35.3, 25, 2.5, 2206, 'largeletter'),
				3=>array('Small Parcel', 45, 35, 16, 25200, 'smallparcel'),
				4=>array('Medium Parcel', 61, 46, 46, 129076, 'mediumparcel'),
		);
	}
	
	/**
	 * Returns the human-readable and internal names of the 
	 * various insurance limit categories. None of the carrier
	 * methods have all of these - most only have one.
	 * 
	 * @return array( id => array(name, internal_name) )
	 */
	public function getInsuranceLimits()
	{
		return array(
				1=>array('£20 insurance', '20'),
				2=>array('£50 insurance', '50'),
				3=>array('£100 insurance', '100'),
				4=>array('£200 insurance', '200'),
		);
	}
	
	/**
	 * Returns the table of shipping rates. The 'name_lookup' is
	 * a foreign key to the table returned by getShippingRateNames(). The 
	 * size_lookup a foreign key to the table returned by getShippingRateSizes().
	 * The insurance_lookup a foreign key to the table returned by 
	 * getShippingRateInsuranceLimits().
	 * 
	 * @return array of name_lookup => array(array(size_lookup, insurance_lookup, weight_limit_kg, price))
	 */
	public function getPrices()
	{
		$return = array(
				// First Class
				1 => array(
						array(1, 1, 0.100, 0.65),
						array(2, 1, 0.100, 0.98),
						array(2, 1, 0.250, 1.30),
						array(2, 1, 0.500, 1.74),
						array(2, 1, 0.750, 2.52),
						array(3, 1, 1.000, 3.40),
						array(3, 1, 2.000, 5.50),
						array(4, 1, 1.000, 5.70),
						array(4, 1, 2.000, 8.95),
						array(4, 1, 5.000, 15.85),
						array(4, 1, 10.000, 21.90),
						array(4, 1, 20.000, 33.40),
				),
				// Second Class
				2 => array(
						array(1, 1, 0.100, 0.56),
						array(2, 1, 0.100, 0.76),
						array(2, 1, 0.250, 1.22),
						array(2, 1, 0.500, 1.58),
						array(2, 1, 0.750, 2.14),
						array(3, 1, 1.000, 2.90),
						array(3, 1, 2.000, 2.90),
						array(4, 1, 1.000, 5.00),
						array(4, 1, 2.000, 5.00),
						array(4, 1, 5.000, 13.75),
						array(4, 1, 10.000, 20.25),
						array(4, 1, 20.000, 28.55),
				),
				// First Class Signed For
				3 => array(
						array(1, 2, 0.100, 1.75),
						array(2, 2, 0.100, 1.86),
						array(2, 2, 0.250, 2.32),
						array(2, 2, 0.500, 2.68),
						array(2, 2, 0.750, 3.24),
						array(3, 2, 1.000, 4.40),
						array(3, 2, 2.000, 6.50),
						array(4, 2, 1.000, 6.70),
						array(4, 2, 2.000, 9.95),
						array(4, 2, 5.000, 16.85),
						array(4, 2, 10.000, 22.90),
						array(4, 2, 20.000, 34.40),
				),				
				// Second Class Signed For
				4 => array(
						array(1, 2, 0.100, 1.66),
						array(2, 2, 0.100, 1.86),
						array(2, 2, 0.250, 2.32),
						array(2, 2, 0.500, 2.68),
						array(2, 2, 0.750, 3.24),
						array(3, 2, 1.000, 3.90),
						array(3, 2, 2.000, 3.90),
						array(4, 2, 1.000, 6.00),
						array(4, 2, 2.000, 6.00),
						array(4, 2, 5.000, 14.75),
						array(4, 2, 10.000, 21.25),
						array(4, 2, 20.000, 29.55),
				),				
		);
		return $return;
	}
	
	/**
	 * Returns the internal name of the method for the given 
	 * method class name (e.g. 1st/2nd-signed-for), parcel size
	 * and insurance category.
	 * 
	 * @param int $name_id the ID delivery class name
	 * @param int $size_id the ID of the parcel size category
	 * @param int $insurance_id the IS of the insurance class
	 * @return string the internal name
	 */
	public function getInternalMethodName($name_id, $size_id, $insurance_id)
	{
		$internal_name = $this->getNames()[$name_id][1];
		$internal_size = $this->getSizes()[$size_id][5];
		$internal_insurance = $this->getInsuranceLimits()[$insurance_id][1];
		
		$internal_method_name = $internal_name.$internal_size.$internal_insurance;
	
		return $internal_method_name;
	}
	
	/**
	 * Returns the human-readable name of the method for the given
	 * method class name (e.g. 1st/2nd-signed-for), parcel size
	 * and insurance category.
	 *
	 * @param int $name_id the ID delivery class name
	 * @param int $size_id the ID of the parcel size category
	 * @param int $insurance_id the IS of the insurance class
	 * @return string the internal name
	 */
	public function getMethodName($name_id, $size_id, $insurance_id)
	{		
		$name = $this->getNames()[$name_id][0];
		$size = $this->getSizes()[$size_id][0];
		$insurance = $this->getInsuranceLimits()[$insurance_id][0];
		
		$method_name = $name.' '.$size.' ('.$insurance.')';
		
		return $method_name;
	}
	
	/**
	 * Returns all the method names.
	 * 
	 * @return array(internal_name => name)
	 */
	public function getAllMethodNames()
	{
		$methodNames = array();
		$prices = $this->getPrices();
		foreach ($prices as $deliveryMethodLookup => $details)
		{
			// $details = array(array(size_lookup, insurance_lookup, weight_limit_kg, price))
			
			foreach ($details as $detail)
			{
				$size = $detail[0];
				$insurance = $detail[1];
				
				$methodName = $this->getMethodName($deliveryMethodLookup, $size, $insurance);
				$internameName = $this->getInternalMethodName($deliveryMethodLookup, $size, $insurance);
				
				$methodNames[$internalName] = $methodName;
			}
		}
		return $methodNames;
	}
	
	/**
	 * @param float $length
	 * @param float $width
	 * @param float $depth
	 * @param int $volume
	 * @param float $weight
	 * @return array(array(internal_method_name, method_name, cost))
	 */
	public function getMethodsForCriteria($length, $width, $depth, $volume, $weight)
	{
		$this->sortArgs($length, $width, $depth);
		Mage::log('Getting RoyalMail methods for ('.$length.'x'.$width.'x'.$depth.'cm '.$volume.'cm3 '.$weight.'kg)', null, 'gareth.log', true);
		
		$methods = array();
		$prices = $this->getPrices();
		
		foreach ($prices as $deliveryMethodLookup => $details)
		{
			// $details = array(array(size_lookup, insurance_lookup, weight_limit_kg, price))
			
			foreach ($details as $detail)
			{
				$sizeLimitsLookup = $detail[0];
				$sizeLimits = $this->getSizes()[$sizeLimitsLookup];
				$maxLength = $sizeLimits[1];
				$maxWidth = $sizeLimits[2];
				$maxDepth = $sizeLimits[3];
				$maxVolume = $sizeLimits[4];
				
				$maxWeight = $detail[2];
				
				$insuranceLookup = $detail[1];
				$methodName = $this->getMethodName($deliveryMethodLookup, $sizeLimitsLookup, $insuranceLookup);
				$internalName = $this->getInternalMethodName($deliveryMethodLookup, $sizeLimitsLookup, $insuranceLookup);
				
				//Mage::log('Comparing to '.$internalName.' '.$maxLength.'x'.$maxWidth.'x'.$maxDepth.'cm '.$maxWeight.'kg', null, 'gareth.log', true);
				
				if ($length <= $maxLength && $width <= $maxWidth && $depth <= $maxDepth && $weight <= $maxWeight)
				{
					$cost = $detail[3];
					$methods[] = array($internalName, $methodName, $cost);
					//Mage::log('   Selected '.$methodName.' at £'.$cost, null, 'gareth.log', true);
					break;
				}
			}
		}
		return $methods;		
	}
	
	/**
	 * Sorts the values of the given args from highest to lowest. All args are
	 * passed by reference and will be modified by this function.
	 * 
	 * $a = 5;
	 * $b = 2;
	 * $c = 3;
	 * sortArgs($a, $b, $c);
	 * // Now $a = 5; $b = 3; $c = 2;
	 */
	protected function sortArgs(&$a, &$b, &$c)
	{
		$args_array = [$a, $b, $c];
		sort($args_array);
		$a = $args_array[2];
		$b = $args_array[1];
		$c = $args_array[0];
	}
}