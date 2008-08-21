<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Oliver Klee <typo3-coding@oliverklee.de>
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


require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_templatehelper.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_googleMapsLookup.php');

/**
 * Unit tests for the tx_realty_googleMapsLookup class in the 'realty'
 * extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_googleMapsLookup_testcase extends tx_phpunit_testcase {
	/** @var	string	a valid Google Maps API key for localhost */
	const GOOGLE_MAPS_API_KEY = 'ABQIAAAAbDm1mvIP78sIsBcIbMgOPRT2yXp_ZAY8_ufC3CFXhHIE1NvwkxTwV0FqSWhHhsXRyGQ_btfZ1hNR7g';

	/** @var	tx_realty_googleMapsLookup */
	private $fixture;

	/** @var	integer		static_info_tables UID of Germany */
	const DE = 54;

	/** @var	integer		static_info_tables UID of the US */
	const US = 220;

	/** @var	tx_oelib_templatehelper		plugin configuration */
	private $configuration;

	public function setUp() {
		$this->configuration = new tx_oelib_templatehelper();
		$this->configuration->setConfigurationValue(
			'googleMapsApiKey', self::GOOGLE_MAPS_API_KEY
		);

		$this->fixture = new tx_realty_googleMapsLookup($this->configuration);
	}

	public function tearDown() {
		unset($this->fixture, $this->configuration);
	}


	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////

	public function testClassCanBeInstantiated() {
		$this->assertTrue(
			$this->fixture instanceof tx_realty_googleMapsLookup
		);
	}

	public function testConstructorThrowsExceptionIfGoogleMapsApiKeyIsMissing() {
		$this->setExpectedException(
			'Exception',
			'The Google Maps API key was missing from the configuration.'
		);

		$configuration = new tx_oelib_templatehelper();
		$configuration->init(array());

		new tx_realty_googleMapsLookup($configuration);
	}


	/////////////////////
	// Tests for lookUp
	/////////////////////

	public function testLookUpReturnsEmptyArrayIfAllParametersAreEmpty() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('', '', '', 0)
		);
	}

	public function testLookUpReturnsEmptyArrayIfAllParametersAreMissing() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp()
		);
	}

	public function testLookUpReturnsEmptyArrayIfOnlyTheCountryIsProvided() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('', '', '', self::DE)
		);
	}

	public function testLookUpReturnsEmptyArrayIfOnlyTheStreetIsProvided() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('Am Hof 1', '', '', 0)
		);
	}

	public function testLookUpReturnsEmptyArrayIfOnlyStreetAndCountryAreProvided() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('Am Hof 1', '', '', self::DE)
		);
	}

	public function testLookUpReturnsEmptyArrayForAGarbageAddress() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('asdfas', '11111', 'ljkasfda', self::DE)
		);
	}

	public function testLookUpReturnsCorrectCoordinatesForAFullGermanAddress() {
		$coordinates = $this->fixture->lookUp(
			'Am Hof 1', '53111', 'Bonn', self::DE
		);

		$this->assertEquals(
			50.734343,
			$coordinates['latitude'],
			'', 0.001
		);
		$this->assertEquals(
			7.10211,
			$coordinates['longitude'],
			'', 0.001
		);
	}

	public function testLookUpReturnsCorrectCoordinatesForAFullUsAddress() {
		$coordinates = $this->fixture->lookUp(
			'1600 Amphitheatre', '94043', 'Mountain View', 'US'
		);

		$this->assertEquals(
			37.421972,
			$coordinates['latitude'],
			'', 0.001
		);
		$this->assertEquals(
			-122.084143,
			$coordinates['longitude'],
			'', 0.001
		);
	}

	public function testLookUpReturnsCorrectCoordinatesForAFullAddressWithUmlaut() {
		$coordinates = $this->fixture->lookUp(
			'Münsterplatz 1', '53111', 'Bonn', self::DE
		);

		$this->assertEquals(
			50.733895,
			$coordinates['latitude'],
			'', 0.001
		);
		$this->assertEquals(
			7.099394,
			$coordinates['longitude'],
			'', 0.001
		);
	}

	public function testLookUpReturnsCorrectCoordinatesForAGermanAddressWithCityMissing() {
		$coordinates = $this->fixture->lookUp(
			'Am Hof 1', '53111', '', self::DE
		);

		$this->assertEquals(
			50.734343,
			$coordinates['latitude'],
			'', 0.001
		);
		$this->assertEquals(
			7.10211,
			$coordinates['longitude'],
			'', 0.001
		);
	}

	public function testLookUpReturnsCorrectCoordinatesForAGermanAddressWithZipMissing() {
		$coordinates = $this->fixture->lookUp(
			'Am Hof 1', '', 'Bonn', self::DE
		);

		$this->assertEquals(
			50.734343,
			$coordinates['latitude'],
			'', 0.001
		);
		$this->assertEquals(
			7.10211,
			$coordinates['longitude'],
			'', 0.001
		);
	}

	public function testLookUpReturnsCorrectCoordinatesForAGermanCity() {
		$coordinates = $this->fixture->lookUp(
			'', '', 'Bonn', self::DE
		);

		$this->assertEquals(
			50.732704,
			$coordinates['latitude'],
			'', 0.001
		);
		$this->assertEquals(
			7.096311,
			$coordinates['longitude'],
			'', 0.001
		);
	}

	public function testLookUpReturnsCorrectCoordinatesForAGermanZip() {
		$coordinates = $this->fixture->lookUp(
			'', '53111', '', self::DE
		);

		$this->assertEquals(
			50.741551,
			$coordinates['latitude'],
			'', 0.01
		);
		$this->assertEquals(
			7.101499,
			$coordinates['longitude'],
			'', 0.01
		);
	}

	public function testLookUpCanReturnTheCorrectCoordinatesTwoTimesInARow() {
		$this->assertEquals(
			$this->fixture->lookUp('Am Hof 1', '53111', 'Bonn', self::DE),
			$this->fixture->lookUp('Am Hof 1', '53111', 'Bonn', self::DE)
		);
	}

	public function testLookUpReturnsDifferentCoordinatesForTheSameCityNameInDifferentCountries() {
		$this->assertNotEquals(
			$this->fixture->lookUp('', '', 'Texas', self::DE),
			$this->fixture->lookUp('', '', 'Texas', self::US)
		);
	}

	public function testLookUpCanUseDeAsDefaultCountryFromConfiguration() {
		$this->configuration->setConfigurationValue('defaultCountryUID', self::DE);

		$this->assertEquals(
			$this->fixture->lookUp('', '', 'Texas', self::DE),
			$this->fixture->lookUp('', '', 'Texas', 0)
		);
	}

	public function testLookUpCanUseUsAsDefaultCountryFromConfiguration() {
		$this->configuration->setConfigurationValue('defaultCountryUID', self::US);

		$this->assertEquals(
			$this->fixture->lookUp('', '', 'Texas', self::US),
			$this->fixture->lookUp('', '', 'Texas', 0)
		);
	}
}
?>