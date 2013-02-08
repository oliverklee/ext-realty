<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Oliver Klee (typo3-coding@oliverklee.de)
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
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_DistrictTest extends tx_phpunit_testcase {
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


	////////////////////////////////
	// Tests concerning findByName
	////////////////////////////////

	/**
	 * @test
	 */
	public function findByNameForEmptyValueThrowsException() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'$value must not be empty.'
		);

		$this->fixture->findByName('');
	}

	/**
	 * @test
	 */
	public function findByNameCanFindModelFromCache() {
		$model = $this->fixture->getLoadedTestingModel(
			array('title' => 'Kleinwurzeling')
		);

		$this->assertSame(
			$model,
			$this->fixture->findByName('Kleinwurzeling')
		);
	}

	/**
	 * @test
	 */
	public function findByNameCanLoadModelFromDatabase() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('title' => 'Kleinwurzeling')
		);

		$this->assertEquals(
			$uid,
			$this->fixture->findByName('Kleinwurzeling')->getUid()
		);
	}

	/**
	 * @test
	 */
	public function findByNameForInexistentNameThrowsException() {
		$this->setExpectedException('tx_oelib_Exception_NotFound');

		$this->fixture->findByName('Hupflingen');
	}


	//////////////////////////////////////////
	// Tests concerning findByNameAndCityUid
	//////////////////////////////////////////

	/**
	 * @test
	 */
	public function findByNameAndCityUidForEmptyNameThrowsException() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'$districtName must not be empty.'
		);

		$this->fixture->findByNameAndCityUid('', 42);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidForNegativeCityUidThrowsException() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'$cityUid must be >= 0.'
		);

		$this->fixture->findByNameAndCityUid('Kreuzberg', -1);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidForZeroCityUidNotThrowsException() {
		$this->fixture->getLoadedTestingModel(
			array(
				'title' => 'Kreuzberg',
				'city' => 0,
			)
		);

		$this->fixture->findByNameAndCityUid('Kreuzberg', 0);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidReturnsDistrict() {
		$this->fixture->getLoadedTestingModel(
			array(
				'title' => 'Kreuzberg',
				'city' => 0,
			)
		);

		$this->assertTrue(
			$this->fixture->findByNameAndCityUid('Kreuzberg', 0)
				instanceof tx_realty_Model_District
		);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidCanFindDistrictWithThatNameAndCityFromDatabase() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts',
			array(
				'title' => 'Kreuzberg',
				'city' => $cityUid,
			)
		);

		$this->assertEquals(
			$districtUid,
			$this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid)->getUid()
		);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidNotFindsDistrictWithThatNameAndOtherCityFromDatabase() {
		$this->setExpectedException(
			'tx_oelib_Exception_NotFound'
		);

		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$this->testingFramework->createRecord(
			'tx_realty_districts',
			array(
				'title' => 'Kreuzberg',
				'city' => $otherCityUid,
			)
		);

		$this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidNotFindsDistrictWithThatNameAndInexistentCityFromDatabase() {
		$this->setExpectedException(
			'tx_oelib_Exception_NotFound'
		);

		$this->testingFramework->createRecord(
			'tx_realty_districts',
			array(
				'title' => 'Kreuzberg',
				'city' => 0,
			)
		);
		$cityUid = $this->testingFramework->getAutoIncrement('tx_realty_cities');

		$this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidNotFindsDistrictWithOtherNameAndMatchingCityFromDatabase() {
		$this->setExpectedException(
			'tx_oelib_Exception_NotFound'
		);

		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$this->testingFramework->createRecord(
			'tx_realty_districts',
			array(
				'title' => 'Neukölln',
				'city' => $cityUid,
			)
		);

		$this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidNotFindsDistrictWithOtherNameAndOtherCityFromDatabase() {
		$this->setExpectedException(
			'tx_oelib_Exception_NotFound'
		);

		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$this->testingFramework->createRecord(
			'tx_realty_districts',
			array(
				'title' => 'Neukölln',
				'city' => $otherCityUid,
			)
		);

		$this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidCanFindDistrictWithThatNameAndCityFromCache() {
		$cityUid = tx_oelib_MapperRegistry::get('tx_realty_Mapper_city')
			->getNewGhost()->getUid();
		$district = $this->fixture->getLoadedTestingModel(
			array(
				'title' => 'Kreuzberg',
				'city' => $cityUid,
			)
		);

		$this->assertEquals(
			$district,
			$this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid)
		);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidNotFindsDistrictWithThatNameAndOtherCityFromCache() {
		$this->setExpectedException(
			'tx_oelib_Exception_NotFound'
		);

		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$this->fixture->getLoadedTestingModel(
			array(
				'title' => 'Kreuzberg',
				'city' => $otherCityUid,
			)
		);

		$this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidNotFindsDistrictWithOtherNameMatchingCityFromCache() {
		$this->setExpectedException(
			'tx_oelib_Exception_NotFound'
		);

		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$this->fixture->getLoadedTestingModel(
			array(
				'title' => 'Neukölln',
				'city' => $cityUid,
			)
		);

		$this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
	}

	/**
	 * @test
	 */
	public function findByNameAndCityUidNotFindsDistrictWithOtherNameAndOtherCityFromCache() {
		$this->setExpectedException(
			'tx_oelib_Exception_NotFound'
		);

		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$this->fixture->getLoadedTestingModel(
			array(
				'title' => 'Neukölln',
				'city' => $otherCityUid,
			)
		);

		$this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
	}
}
?>