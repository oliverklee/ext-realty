<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2011 Oliver Klee <typo3-coding@oliverklee.de>
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_Autoloader.php');

/**
 * Unit tests for the tx_realty_googleMapsLookup class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Service_GoogleMapsLookupTest extends tx_phpunit_testcase {
	/**
	 * @var string a valid Google Maps API key for localhost
	 */
	const GOOGLE_MAPS_API_KEY = 'ABQIAAAAbDm1mvIP78sIsBcIbMgOPRT2yXp_ZAY8_ufC3CFXhHIE1NvwkxTwV0FqSWhHhsXRyGQ_btfZ1hNR7g';

	/**
	 * @var tx_realty_googleMapsLookup
	 */
	private $fixture;

	/**
	 * @var integer static_info_tables UID of Germany
	 */
	const DE = 54;

	/**
	 * @var integer static_info_tables UID of the US
	 */
	const US = 220;

	public function setUp() {
		$this->fixture = $this->getMock(
			'tx_realty_googleMapsLookup',
			array('sendRequest', 'throttle', 'getDefaultCountryUid'),
			array(),
			'',
			FALSE
		);
	}

	public function tearDown() {
		unset($this->fixture);
	}


	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function getInstanceCreatesGoogleMapsLookupInstance() {
		$configuration = $this->getMock(
			'tx_oelib_templatehelper', array('hasConfValueString')
		);
		$configuration->expects($this->any())->method('hasConfValueString')
			->with('googleMapsApiKey', 's_googlemaps')
			->will($this->returnValue(TRUE));

		$this->assertTrue(
			tx_realty_googleMapsLookup::getInstance($configuration)
				instanceof tx_realty_googleMapsLookup
		);

		tx_realty_googleMapsLookup::purgeInstance();
		$configuration->__destruct();
	}

	/**
	 * @test
	 */
	public function constructorThrowsExceptionIfGoogleMapsApiKeyIsMissing() {
		$this->setExpectedException(
			'Exception',
			'The Google Maps API key was missing from the configuration.'
		);

		$configuration = $this->getMock(
			'tx_oelib_templatehelper', array('hasConfValueString')
		);
		$configuration->expects($this->any())->method('hasConfValueString')
			->with('googleMapsApiKey', 's_googlemaps')
			->will($this->returnValue(FALSE));

		tx_realty_googleMapsLookup::getInstance($configuration);

		tx_realty_googleMapsLookup::purgeInstance();
		$configuration->__destruct();
	}


	/////////////////////
	// Tests for lookUp
	/////////////////////

	/**
	 * @test
	 */
	public function lookUpReturnsEmptyArrayIfAllParametersAreEmpty() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('', '', '', '', 0)
		);
	}

	/**
	 * @test
	 */
	public function lookUpReturnsEmptyArrayIfAllParametersAreMissing() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp()
		);
	}

	/**
	 * @test
	 */
	public function lookUpReturnsEmptyArrayIfOnlyTheCountryIsProvided() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('', '', '', '', self::DE)
		);
	}

	/**
	 * @test
	 */
	public function lookUpReturnsEmptyArrayIfOnlyTheStreetIsProvided() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('Am Hof 1', '', '', '', 0)
		);
	}

	/**
	 * @test
	 */
	public function lookUpReturnsEmptyArrayIfOnlyStreetAndCountryAreProvided() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('Am Hof 1', '', '', '', self::DE)
		);
	}

	/**
	 * @test
	 */
	public function lookUpReturnsEmptyArrayForAGarbageAddress() {
		$this->fixture->expects($this->once())->method('sendRequest')
			->with(array('asdfas', '11111 sdgh', 'ljkasfda', 'DE'))
			->will($this->returnValue('602'));

		$this->assertEquals(
			array(),
			$this->fixture->lookUp('asdfas', '11111', 'sdgh', 'ljkasfda', self::DE)
		);
	}

	/**
	 * @test
	 */
	public function lookUpReturnsCorrectCoordinatesForAFullGermanAddress() {
		$fixture = $this->getMock(
			'tx_realty_googleMapsLookup',
			array('throttle', 'getGoogleMapsApiKey'),
			array(),
			'',
			FALSE
		);
		$fixture->expects($this->any())->method('getGoogleMapsApiKey')
			->will($this->returnValue(self::GOOGLE_MAPS_API_KEY));

		$coordinates = $fixture->lookUp(
			'Am Hof 1', '53113', 'Zentrum', 'Bonn', self::DE
		);

		$this->assertEquals(
			50.734343,
			$coordinates['latitude'],
			'', 0.1
		);
		$this->assertEquals(
			7.10211,
			$coordinates['longitude'],
			'', 0.1
		);
	}

	/**
	 * @test
	 */
	public function lookUpSendsRequestForAFullUsAddress() {
		$this->fixture->expects($this->once())->method('sendRequest')
			->with(array('1600 Amphitheatre', '94043', 'Mountain View', 'US'));
		$this->fixture->lookUp(
			'1600 Amphitheatre', '94043', '', 'Mountain View', self::US
		);
	}

	/**
	 * @test
	 */
	public function lookUpSendsRequestForAGermanAddressWithCityMissing() {
		$this->fixture->expects($this->once())->method('sendRequest')
			->with(array('Am Hof 1', '53113 Zentrum', 'DE'));
		$this->fixture->lookUp(
			'Am Hof 1', '53113', 'Zentrum', '', self::DE
		);
	}

	/**
	 * @test
	 */
	public function lookUpSendsRequestForAGermanAddressWithCityAndDistrictMissing() {
		$this->fixture->expects($this->once())->method('sendRequest')
			->with(array('Am Hof 1', '53113', 'DE'));
		$this->fixture->lookUp(
			'Am Hof 1', '53113', '', '', self::DE
		);
	}

	/**
	 * @test
	 */
	public function lookUpSendsRequestForAGermanAddressWithZipMissing() {
		$this->fixture->expects($this->once())->method('sendRequest')
			->with(array('Am Hof 1', 'Zentrum', 'Bonn', 'DE'));
		$this->fixture->lookUp(
			'Am Hof 1', '', 'Zentrum', 'Bonn', self::DE
		);
	}

	/**
	 * @test
	 */
	public function lookUpSendsRequestForAGermanAddressWithZipAndDistrictMissing() {
		$this->fixture->expects($this->once())->method('sendRequest')
			->with(array('Am Hof 1', 'Bonn', 'DE'));
		$this->fixture->lookUp(
			'Am Hof 1', '', '', 'Bonn', self::DE
		);
	}

	/**
	 * @test
	 */
	public function lookUpSendsRequestForAGermanCity() {
		$this->fixture->expects($this->once())->method('sendRequest')
			->with(array('Bonn', 'DE'));
		$this->fixture->lookUp(
			'', '', '', 'Bonn', self::DE
		);
	}

	/**
	 * @test
	 */
	public function lookUpSendsRequestForAGermanZip() {
		$this->fixture->expects($this->once())->method('sendRequest')
			->with(array('53111', 'DE'));
		$this->fixture->lookUp(
			'', '53111', '', '', self::DE
		);
	}

	/**
	 * @test
	 */
	public function lookUpThrottlesRequestsByAtLeast1Dot73Seconds() {
		$fixture = $this->getMock(
			'tx_realty_googleMapsLookup',
			array('sendRequest'),
			array(),
			'',
			FALSE
		);
		$fixture->expects($this->any())->method('sendRequest')
			->will($this->returnValue('200'));

		$startTime = microtime(TRUE);
		$fixture->lookUp('Am Hof 1', '53113', '', 'Bonn', self::DE);
		$fixture->lookUp('Am Hof 1', '53113', '', 'Bonn', self::DE);
		$endTime = microtime(TRUE);

		$this->assertGreaterThan(
			1.73,
			$endTime - $startTime
		);
	}

	/**
	 * @test
	 */
	public function lookUpForNoCountryProvidedCanUsesDefaultCountryFromConfiguration() {
		$this->fixture->expects($this->any())->method('getDefaultCountryUid')
			->will($this->returnValue(self::US));
		$this->fixture->expects($this->once())->method('sendRequest')
			->with(array('Texas', 'US'));

		$this->fixture->lookUp('', '', '', 'Texas', 0);
	}
}
?>