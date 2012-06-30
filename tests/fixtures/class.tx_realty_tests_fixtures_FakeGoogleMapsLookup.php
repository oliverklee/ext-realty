<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2012 Saskia Metzler <saskia@merlin.owl.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class 'tx_realty_tests_fixtures_FakeGoogleMapsLookup' for the 'realty'
 * extension.
 *
 * This class represents a faked service to look up geo coordinates.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_tests_fixtures_FakeGoogleMapsLookup extends tx_realty_googleMapsLookup {
	/**
	 * @var array faked coordinates with the keys "latitude" and "longitude" or
	 *            empty if there are none
	 */
	private $coordinates = array();

	/**
	 * The constructor.
	 */
	public function __construct() {
	}

	/**
	 * Looks up the geo coordinates of an address.
	 *
	 * @param string $street the street of the address, may be empty
	 * @param string $zip the ZIP code of the address, may be empty
	 * @param string $city the city of the address, may be empty
	 *
	 * @return array an array with the geo coordinates using the keys
	 *               'longitude' and 'latitude' or an empty array if no fake
	 *                coordinates have been set
	 */
	public function lookUp($street = '', $zip = '', $city = '') {
		if (($zip . $city) == '') {
			return array();
		}

		return $this->coordinates;
	}

	/**
	 * Sets the coordinates lookUp() is supposed to return.
	 *
	 * @param float $latitude latitude coordinate
	 * @param float $longitude longitude coordinate
	 */
	public function setCoordinates($latitude, $longitude) {
		$this->coordinates = array(
			'latitude' => $latitude, 'longitude' => $longitude,
		);
	}

	/**
	 * Resets the fake coordinates.
	 */
	public function clearCoordinates() {
		$this->coordinates = array();
	}
}
?>