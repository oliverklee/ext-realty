<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the tx_realty_Mapper_District class in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_District_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var tx_realty_Mapper_District
	 */
	private $fixture;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');

		$this->fixture = new tx_realty_Mapper_District();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	//////////////////////////
	// Tests concerning find
	//////////////////////////

	/**
	 * @test
	 */
	public function findWithUidReturnsDistrictInstance() {
		$this->assertTrue(
			$this->fixture->find(1) instanceof tx_realty_Model_District
		);
	}

	/**
	 * @test
	 */
	public function findWithUidOfExistingRecordReturnsRecordAsModel() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('title' => 'Bad Godesberg')
		);

		$this->assertEquals(
			'Bad Godesberg',
			$this->fixture->find($uid)->getTitle()
		);
	}


	////////////////////////////
	// Tests for the relations
	////////////////////////////

	/**
	 * @test
	 */
	public function getCityReturnsCityFromRelation() {
		$city = tx_oelib_MapperRegistry::get('tx_realty_Mapper_City')
			->getNewGhost();

		$model = $this->fixture->getLoadedTestingModel(
			array('city' => $city->getUid())
		);

		$this->assertSame(
			$city,
			$model->getCity()
		);
	}


	//////////////////////////////////////
	// Tests concerning findAllByCityUid
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function findAllByCityUidWithZeroUidFindsDistrictWithoutCity() {
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts'
		);

		$this->assertTrue(
			$this->fixture->findAllByCityUid(0)->hasUid($districtUid)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidWithZeroUidNotFindsDistrictWithSetCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$this->assertFalse(
			$this->fixture->findAllByCityUid(0)->hasUid($districtUid)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidFindsDistrictWithThatCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$this->assertTrue(
			$this->fixture->findAllByCityUid($cityUid)->hasUid($districtUid)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidCanFindTwoDistrictsWithThatCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid1 = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);
		$districtUid2 = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$result = $this->fixture->findAllByCityUid($cityUid);
		$this->assertTrue(
			$result->hasUid($districtUid1)
		);
		$this->assertTrue(
			$result->hasUid($districtUid2)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidNotFindsDistrictWithoutCity() {
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts'
		);

		$this->assertFalse(
			$this->fixture->findAllByCityUid(1)->hasUid($districtUid)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidNotFindsDistrictWithOtherCity() {
		$otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $otherCityUid)
		);

		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');

		$this->assertFalse(
			$this->fixture->findAllByCityUid($cityUid)->hasUid($districtUid)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidOrdersResultsByTitle() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');

		$this->testingFramework->createRecord(
			'tx_realty_districts',
			array('city'=> $cityUid, 'title' => 'Xen District')
		);
		$districtUid2 = $this->testingFramework->createRecord(
			'tx_realty_districts',
			array('city'=> $cityUid, 'title' => 'Another District')
		);

		$this->assertEquals(
			$districtUid2,
			$this->fixture->findAllByCityUid($cityUid)->first()->getUid()
		);
	}


	//////////////////////////////////////////////////
	// Tests concerning findAllByCityUidOrUnassigned
	//////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function findAllByCityUidOrUnassignedWithZeroUidFindsDistrictWithoutCity() {
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts'
		);

		$this->assertTrue(
			$this->fixture->findAllByCityUidOrUnassigned(0)->hasUid($districtUid)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidOrUnassignedWithZeroUidNotFindsDistrictWithSetCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$this->assertFalse(
			$this->fixture->findAllByCityUidOrUnassigned(0)->hasUid($districtUid)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidOrUnassignedFindsDistrictWithThatCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$this->assertTrue(
			$this->fixture->findAllByCityUidOrUnassigned($cityUid)->hasUid($districtUid)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidOrUnassignedCanFindTwoDistrictsWithThatCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid1 = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);
		$districtUid2 = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$result = $this->fixture->findAllByCityUidOrUnassigned($cityUid);
		$this->assertTrue(
			$result->hasUid($districtUid1)
		);
		$this->assertTrue(
			$result->hasUid($districtUid2)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidOrUnassignedFindsDistrictWithoutCity() {
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts'
		);

		$this->assertTrue(
			$this->fixture->findAllByCityUidOrUnassigned(1)->hasUid($districtUid)
		);
	}

	/**
	 * @test
	 */
	public function findAllByCityUidOrUnassignedNotFindsDistrictWithOtherCity() {
		$otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $otherCityUid)
		);

		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');

		$this->assertFalse(
			$this->fixture->findAllByCityUidOrUnassigned($cityUid)->hasUid($districtUid)
		);
	}
}
?>