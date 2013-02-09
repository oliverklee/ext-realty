<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Saskia Metzler <saskia@merlin.owl.de>
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
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_Service_FakeGoogleMapsLookupTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_tests_fixtures_FakeGoogleMapsLookup
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

	/**
	 * @var float latitude
	 */
	const LATITUDE = 50.7;

	/**
	 * @var float longitude
	 */
	const LONGITUDE = 7.1;

	public function setUp() {
		$this->fixture = new tx_realty_tests_fixtures_FakeGoogleMapsLookup();
	}

	public function tearDown() {
		$this->fixture->__destruct();
		unset($this->fixture);
	}


	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function classIsSubclassOfRealLookUp() {
		$this->assertTrue(
			$this->fixture instanceof tx_realty_googleMapsLookup
		);
	}


	/////////////////////
	// Tests for lookUp
	/////////////////////

	/**
	 * @test
	 */
	public function lookUpForAllParametersEmptyAndCoordinatesGivenReturnsEmptyArray() {
		$this->fixture->setCoordinates(self::LATITUDE, self::LONGITUDE);

		$this->assertEquals(
			array(),
			$this->fixture->lookUp('', '', '', 0)
		);
	}

	/**
	 * @test
	 */
	public function lookUpForNoParametersGivenAndCoordinatesGivenReturnsEmptyArray() {
		$this->fixture->setCoordinates(self::LATITUDE, self::LONGITUDE);

		$this->assertEquals(
			array(),
			$this->fixture->lookUp()
		);
	}

	/**
	 * @test
	 */
	public function lookUpForAllParametersEmptyAndCoordinatesNotGivenReturnsEmptyArray() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp('', '', '', 0)
		);
	}

	/**
	 * @test
	 */
	public function lookUpForNoParametersGivenAndCoordinatesNotGivenReturnsEmptyArray() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp()
		);
	}

	/**
	 * @test
	 */
	public function lookUpForOnlyCountryCodeGivenAndCoordinatesGivenReturnsEmptyArray() {
		$this->fixture->setCoordinates(self::LATITUDE, self::LONGITUDE);

		$this->assertEquals(
			array(),
			$this->fixture->lookUp('', '', '', self::DE)
		);
	}

	/**
	 * @test
	 */
	public function lookUpForOnlyStreetGivenAndCoordinatesGivenReturnsEmptyArray() {
		$this->fixture->setCoordinates(self::LATITUDE, self::LONGITUDE);

		$this->assertEquals(
			array(),
			$this->fixture->lookUp('Am Hof 1', '', '', 0)
		);
	}

	/**
	 * @test
	 */
	public function lookUpForOnlyStreetAndCountryCodeGivenAndCoordinatesGivenReturnsEmptyArray() {
		$this->fixture->setCoordinates(self::LATITUDE, self::LONGITUDE);

		$this->assertEquals(
			array(),
			$this->fixture->lookUp('Am Hof 1', '', '', self::DE)
		);
	}

	/**
	 * @test
	 */
	public function lookUpForGarbageAddressReturnsFakedCoordinates() {
		$this->fixture->setCoordinates(self::LATITUDE, self::LONGITUDE);

		$this->assertEquals(
			array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE),
			$this->fixture->lookUp(
				'asdfas', '11111', 'ljkasfda', self::DE
			)
		);
	}

	/**
	 * @test
	 */
	public function lookUpForFullGermanAddressReturnsFakedCoordinates() {
		$this->fixture->setCoordinates(self::LATITUDE, self::LONGITUDE);

		$this->assertEquals(
			array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE),
			$this->fixture->lookUp(
				'Am Hof 1', '53113', 'Bonn', self::DE
			)
		);
	}

	/**
	 * @test
	 */
	public function lookUpForFullUSAddressReturnsFakedCoordinates() {
		$this->fixture->setCoordinates(self::LATITUDE, self::LONGITUDE);

		$this->assertEquals(
			array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE),
			$this->fixture->lookUp(
				'1600 Amphitheatre', '94043', 'Mountain View', 'US'
			)
		);
	}

	/**
	 * @test
	 */
	public function lookUpForFullGermanAddressReturnsAndNoFakedCoordinatesReturnsEmptyArray() {
		$this->assertEquals(
			array(),
			$this->fixture->lookUp(
				'Am Hof 1', '53113', 'Bonn', self::DE
			)
		);
	}

	/**
	 * @test
	 */
	public function lookUpForFullGermanAddressReturnsAndResetFakedCoordinatesReturnsEmptyArray() {
		$this->fixture->setCoordinates(self::LATITUDE, self::LONGITUDE);
		$this->fixture->clearCoordinates();

		$this->assertEquals(
			array(),
			$this->fixture->lookUp(
				'Am Hof 1', '53113', 'Bonn', self::DE
			)
		);
	}
}
?>