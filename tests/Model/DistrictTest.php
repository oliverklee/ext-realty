<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2012 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the tx_realty_Model_District class in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage  tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_DistrictTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Model_District
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new tx_realty_Model_District();
	}

	public function tearDown() {
		$this->fixture->__destruct();
		unset($this->fixture);
	}


	///////////////////////////////
	// Tests concerning the title
	///////////////////////////////

	/**
	 * @test
	 */
	public function getTitleWithNonEmptyTitleReturnsTitle() {
		$this->fixture->setData(array('title' => 'Bad Godesberg'));

		$this->assertEquals(
			'Bad Godesberg',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$this->fixture->setTitle('Bad Godesberg');

		$this->assertEquals(
			'Bad Godesberg',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleWithEmptyStringThrowsException() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'The parameter $title must not be empty.'
		);

		$this->fixture->setTitle('');
	}


	//////////////////////////////
	// Tests concerning the city
	//////////////////////////////

	/**
	 * @test
	 */
	public function getCityWithCitySetReturnsCity() {
		$city = new tx_realty_Model_City();

		$this->fixture->setData(array('city' => $city));

		$this->assertSame(
			$city,
			$this->fixture->getCity()
		);

		$city->__destruct();
	}

	/**
	 * @test
	 */
	public function getCityReturnsCitySetWithSetCity() {
		$city = new tx_realty_Model_City();

		$this->fixture->setCity($city);

		$this->assertSame(
			$city,
			$this->fixture->getCity()
		);

		$city->__destruct();
	}

	/**
	 * @test
	 */
	public function getCityAfterSetCityWithNullReturnsNull() {
		$this->fixture->setCity(NULL);

		$this->assertNull(
			$this->fixture->getCity()
		);
	}
}
?>