<?php

/**
 * @author gareth
 *
 */
class Gareth_RoyalMail_Helper_Config extends
Mage_Core_Helper_Abstract
{
	/**
	 * The parcel length to use in cm when none is provided. 
	 * @return float the default length in cm
	 */
	public function getDefaultLength()
	{
		return 10.0;
	}
	
	/**
	 * The parcel width to use in cm when none is provided.
	 * @return float the default width in cm
	 */
	public function getDefaultWidth()
	{
		return 10.0;
	}
	
	/**
	 * The parcel depth to use in cm when none is provided.
	 * @return float the default depth in cm
	 */
	public function getDefaultDepth()
	{
		return 10.0;
	}
	
	/**
	 * The parcel weight to use in cm when none is provided.
	 * @return float the default weight in kg
	 */
	public function getDefaultWeight()
	{
		return 0.5;
	}
}
	