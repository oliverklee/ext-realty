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
 * Test case.
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