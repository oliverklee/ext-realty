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
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_RealtyObjectTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_Mapper_RealtyObject
	 */
	private $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

	protected function setUp() {
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->fixture = new tx_realty_Mapper_RealtyObject();
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	/////////////////////////////////////////
	// Tests concerning the basic functions
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function findWithUidOfExistingRecordReturnsRealtyObjectInstance() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_objects', array('title' => 'foo')
		);

		$this->assertTrue(
			$this->fixture->find($uid) instanceof tx_realty_Model_RealtyObject
		);
	}

	/**
	 * @test
	 */
	public function getOwnerForMappedModelReturnsFrontEndUserInstance() {
		$ownerUid = $this->testingFramework->createFrontEndUser();
		$objectUid = $this->testingFramework->createRecord(
			'tx_realty_objects', array('title' => 'foo', 'owner' => $ownerUid)
		);

		/** @var tx_realty_Model_RealtyObject $model */
		$model = $this->fixture->find($objectUid);
		$this->assertTrue(
			$model->getOwner() instanceof tx_realty_Model_FrontEndUser
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
		/** @var tx_realty_Model_City $city */
		$city = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_City')->find($cityUid);

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
		/** @var tx_realty_Model_City $city */
		$city = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_City')->find($cityUid);

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
		/** @var tx_realty_Model_City $city */
		$city = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_City')->find($cityUid);

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
		/** @var tx_realty_Model_District $district */
		$district = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_District')->find($districtUid);

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
		/** @var tx_realty_Model_District $district */
		$district = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_District')->find($districtUid);

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
		/** @var tx_realty_Model_District $district */
		$district = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_District')->find($districtUid);

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
	public function findByObjectNumberAndObjectIdAndLanguageForAllParametersEmptyAndExistingMatchNotThrowsException() {
		$this->fixture->getLoadedTestingModel(array('object_number' => '', 'openimmo_obid' => '', 'language' => ''));

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
			$this->fixture->findByObjectNumberAndObjectIdAndLanguage('FLAT0001', 'abc01234', 'de')->getUid()
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

    /*
     * Tests concerning findByAnid
     */

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function findByAnidForEmptyAnidThrowsException()
    {
        $this->fixture->findByAnid('');
    }

    /**
     * @test
     */
    public function findByAnidForNoMatchesReturnsEmptyList()
    {
        $result = $this->fixture->findByAnid('not-in-database');
        self::assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function findByAnidIgnoresObjectWithEmptyAnid()
    {
        $this->testingFramework->createRecord('tx_realty_objects', ['openimmo_anid' => '']);

        $result = $this->fixture->findByAnid('not-in-database');
        self::assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function findByAnidIgnoresObjectWithOtherAnid()
    {
        $this->testingFramework->createRecord('tx_realty_objects', ['openimmo_anid' => 'other-anid']);

        $result = $this->fixture->findByAnid('not-in-database');
        self::assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function findByAnidIgnoresDeletedObjectWithOtherMatchingAnid()
    {
        $anid = 'abc-def-ghi-1234';
        $this->testingFramework->createRecord('tx_realty_objects', ['openimmo_anid' => $anid, 'deleted' => 1]);

        $result = $this->fixture->findByAnid('not-in-database');
        self::assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function findByAnidFindsObjectWithExactMatchingAnid()
    {
        $anid = 'OABC20017128124930123asd43fer35';
        $uid = $this->testingFramework->createRecord('tx_realty_objects', ['openimmo_anid' => $anid]);

        $result = $this->fixture->findByAnid($anid);
        self::assertSame(1, $result->count());
        /** @var \tx_realty_Model_RealtyObject $firstMatch */
        $firstMatch = $result->first();
        self::assertSame($uid, $firstMatch->getUid());
    }

    /**
     * @test
     */
    public function findByAnidFindsObjectWithMatchingFirstFourCharactersOfAnid()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['openimmo_anid' => 'OABC20017128124930123asd43fer35']
        );

        $result = $this->fixture->findByAnid('OABC10017128124930123asd43fer35');
        self::assertSame(1, $result->count());
        /** @var \tx_realty_Model_RealtyObject $firstMatch */
        $firstMatch = $result->first();
        self::assertSame($uid, $firstMatch->getUid());
    }

    /**
     * @test
     */
    public function findByAnidNotFindsObjectWithMatchingOnlyFirstThreeCharactersOfAnid()
    {
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['openimmo_anid' => 'OABC20017128124930123asd43fer35']
        );

        $result = $this->fixture->findByAnid('OABD20017128124930123asd43fer35');
        self::assertTrue($result->isEmpty());
    }
}
