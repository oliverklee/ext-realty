<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This class represents a district.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_District extends tx_realty_Model_AbstractTitledModel {
	/**
	 * Gets the city this district is part of.
	 *
	 * @return tx_realty_Model_City this district's city, will be NULL if no
	 *                              city is associated with this district
	 */
	public function getCity() {
		return $this->getAsModel('city');
	}

	/**
	 * Sets this district's city.
	 *
	 * @param tx_realty_Model_City $city the city to set, may be NULL
	 *
	 * @return void
	 */
	public function setCity(tx_realty_Model_City $city = NULL) {
		$this->set('city', $city);
	}
}