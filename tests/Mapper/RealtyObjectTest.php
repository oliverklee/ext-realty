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
 * Unit tests for the tx_realty_Mapper_RealtyObject class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_RealtyObjectTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Mapper_RealtyObject
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->fixture = new tx_realty_Mapper_RealtyObject();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();

		unset($this->fixture, $this->testingFramework);
	}


	/////////////////////////////////////////
	// Tests concerning the basic functions
	/////////////////////////////////////////

	public function testFindWithUidOfExistingRecordReturnsRealtyObjectInstance() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_objects', array('title' => 'foo')
		);

		$this->assertTrue(
			$this->fixture->find($uid) instanceof tx_realty_Model_RealtyObject
		);
	}

	public function testGetOwnerForMappedModelReturnsFrontEndUserInstance() {
		$ownerUid = $this->testingFramework->createFrontEndUser();
		$objectUid = $this->testingFramework->createRecord(
			'tx_realty_objects', array('title' => 'foo', 'owner' => $ownerUid)
		);

		$this->assertTrue(
			$this->fixture->find($objectUid)->getOwner()
				instanceof tx_realty_Model_FrontEndUser
		);
	}


	/////////////////////////////////
	// Tests concerning countByCity
	/////////////////////////////////

	/**
	 * @test
	 */
	public function countByCityForNoMatchesReturnsZero() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$city = tx_oelib_MapperRegistry::get('tx_realty_Mapper_City')
			->find($cityUid);

		$this->assertEquals(
			0,
			$this->fixture->countByCity($city)
		);
	}

	/**
	 * @test
	 */
	public function countByCityWithOneMatchReturnsOne() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$city = tx_oelib_MapperRegistry::get('tx_realty_Mapper_City')
			->find($cityUid);

		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);

		$this->assertEquals(
			1,
			$this->fixture->countByCity($city)
		);
	}

	/**
	 * @test
	 */
	public function countByCityWithTwoMatchesReturnsTwo() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$city = tx_oelib_MapperRegistry::get('tx_realty_Mapper_City')
			->find($cityUid);

		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);

		$this->assertEquals(
			2,
			$this->fixture->countByCity($city)
		);
	}


	/////////////////////////////////////
	// Tests concerning countByDistrict
	/////////////////////////////////////

	/**
	 * @test
	 */
	public function countByDistrictForNoMatchesReturnsZero() {
		$districtUid = $this->testingFramework->createRecord('tx_realty_districts');
		$district = tx_oelib_MapperRegistry::get('tx_realty_Mapper_District')
			->find($districtUid);

		$this->assertEquals(
			0,
			$this->fixture->countByDistrict($district)
		);
	}

	/**
	 * @test
	 */
	public function countByDistrictWithOneMatchReturnsOne() {
		$districtUid = $this->testingFramework->createRecord('tx_realty_districts');
		$district = tx_oelib_MapperRegistry::get('tx_realty_Mapper_District')
			->find($districtUid);

		$this->testingFramework->createRecord(
			'tx_realty_objects', array('district' => $districtUid)
		);

		$this->assertEquals(
			1,
			$this->fixture->countByDistrict($district)
		);
	}

	/**
	 * @test
	 */
	public function countByDistrictWithTwoMatchesReturnsTwo() {
		$districtUid = $this->testingFramework->createRecord('tx_realty_districts');
		$district = tx_oelib_MapperRegistry::get('tx_realty_Mapper_District')
			->find($districtUid);

		$this->testingFramework->createRecord(
			'tx_realty_objects', array('district' => $districtUid)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('district' => $districtUid)
		);

		$this->assertEquals(
			2,
			$this->fixture->countByDistrict($district)
		);
	}


	//////////////////////////////////////////////////////////////
	// Tests concerning findByObjectNumberAndObjectIdAndLanguage
	//////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function findByObjectNumberAndObjectIdAndLanguageForAllParametersEmptyNotThrowsException() {
		$district = $this->fixture->getLoadedTestingModel(array());

		$this->fixture->findByObjectNumberAndObjectIdAndLanguage('', '', '');
	}

	/**
	 * @test
	 */
	public function findByObjectNumberAndObjectIdAndLanguageReturnsRealtyObject() {
		$this->fixture->getLoadedTestingModel(array(
			'object_number' => 'FLAT0001',
			'openimmo_obid' => 'abc01234',
			'language' => 'de',
		));

		$this->assertTrue(
			$this->fixture->findByObjectNumberAndObjectIdAndLanguage(
				'FLAT0001', 'abc01234', 'de'
			) instanceof tx_realty_Model_RealtyObject
		);
	}

	/**
	 * @test
	 */
	public function findByObjectNumberAndObjectIdAndLanguageCanFindRealtyObjectWithMatchingDataFromDatabase() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => 'FLAT0001',
				'openimmo_obid' => 'abc01234',
				'language' => 'de',
			)
		);

		$this->assertEquals(
			$uid,
			$this->fixture->findByObjectNumberAndObjectIdAndLanguage(
				'FLAT0001', 'abc01234', 'de'
			)->getUid()
		);
	}

	/**
	 * @test
	 *
	 * @expectedException tx_oelib_Exception_NotFound
	 */
	public function findByObjectNumberAndObjectIdAndLanguageNotFindsModelWithDifferentObjectNumber() {
		$this->fixture->getLoadedTestingModel(array(
			'object_number' => 'FLAT0001',
			'openimmo_obid' => 'abc01234',
			'language' => 'de',
		));

		$this->fixture->findByObjectNumberAndObjectIdAndLanguage(
			'FLAT0002', 'abc01234', 'de'
		);
	}

	/**
	 * @test
	 *
	 * @expectedException tx_oelib_Exception_NotFound
	 */
	public function findByObjectNumberAndObjectIdAndLanguageNotFindsModelWithDifferentObjectId() {
		$this->fixture->getLoadedTestingModel(array(
			'object_number' => 'FLAT0001',
			'openimmo_obid' => 'abc01234',
			'language' => 'de',
		));

		$this->fixture->findByObjectNumberAndObjectIdAndLanguage(
			'FLAT0001', '9684654651', 'de'
		);
	}

	/**
	 * @test
	 *
	 * @expectedException tx_oelib_Exception_NotFound
	 */
	public function findByObjectNumberAndObjectIdAndLanguageNotFindsModelWithDifferentObjectLanguage() {
		$this->fixture->getLoadedTestingModel(array(
			'object_number' => 'FLAT0001',
			'openimmo_obid' => 'abc01234',
			'language' => 'de',
		));

		$this->fixture->findByObjectNumberAndObjectIdAndLanguage(
			'FLAT0002', 'abc01234', 'en'
		);
	}
}
?>