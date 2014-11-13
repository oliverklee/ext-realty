<?php
/**
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
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_BackEnd_TcaTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Tca
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->fixture = new tx_realty_Tca();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	/////////////////////////////////////////
	// Tests concerning getDistrictsForCity
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function getDistrictsForCitySetsItems() {
		$result = $this->fixture->getDistrictsForCity(
			array('row' => array('city' => 0))
		);

		$this->assertTrue(
			isset($result['items'])
		);
	}

	/**
	 * @test
	 */
	public function getDistrictsForCityContainsEmptyOption() {
		$result = $this->fixture->getDistrictsForCity(
			array('row' => array('city' => 0))
		);

		$this->assertTrue(
			in_array(array('', 0), $result['items'])
		);
	}

	/**
	 * @test
	 */
	public function getDistrictsForCityReturnsDistrictsForCityOrUnassigned() {
		$city = new tx_realty_Model_District();
		$city->setData(array('uid' => 2, 'title' => 'Kreuzberg'));
		$cities = new tx_oelib_List();
		$cities->add($city);

		$mapper = $this->getMock(
			'tx_realty_Mapper_District', array('findAllByCityUidOrUnassigned')
		);
		$mapper->expects($this->once())
			->method('findAllByCityUidOrUnassigned')->with(42)
			->will($this->returnValue($cities));
		tx_oelib_MapperRegistry::set('tx_realty_Mapper_District', $mapper);

		$result = $this->fixture->getDistrictsForCity(
			array('row' => array('city' => 42))
		);

		$this->assertTrue(
			in_array(array('Kreuzberg', 2), $result['items'])
		);
	}
}