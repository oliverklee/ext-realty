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
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_DistrictTest extends Tx_Phpunit_TestCase
{
    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var tx_realty_Mapper_District
     */
    private $fixture = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');

        $this->fixture = new tx_realty_Mapper_District();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    //////////////////////////
    // Tests concerning find
    //////////////////////////

    /**
     * @test
     */
    public function findWithUidReturnsDistrictInstance()
    {
        self::assertTrue(
            $this->fixture->find(1) instanceof tx_realty_Model_District
        );
    }

    /**
     * @test
     */
    public function findWithUidOfExistingRecordReturnsRecordAsModel()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_districts', array('title' => 'Bad Godesberg')
        );

        /** @var tx_realty_Model_District $model */
        $model = $this->fixture->find($uid);
        self::assertEquals(
            'Bad Godesberg',
            $model->getTitle()
        );
    }

    ////////////////////////////
    // Tests for the relations
    ////////////////////////////

    /**
     * @test
     */
    public function getCityReturnsCityFromRelation()
    {
        /** @var tx_realty_Model_City $city */
        $city = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_City')->getNewGhost();

        /** @var tx_realty_Model_District $district */
        $district = $this->fixture->getLoadedTestingModel(array('city' => $city->getUid()));

        self::assertSame(
            $city,
            $district->getCity()
        );
    }

    //////////////////////////////////////
    // Tests concerning findAllByCityUid
    //////////////////////////////////////

    /**
     * @test
     */
    public function findAllByCityUidWithZeroUidFindsDistrictWithoutCity()
    {
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts'
        );

        self::assertTrue(
            $this->fixture->findAllByCityUid(0)->hasUid($districtUid)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidWithZeroUidNotFindsDistrictWithSetCity()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $cityUid)
        );

        self::assertFalse(
            $this->fixture->findAllByCityUid(0)->hasUid($districtUid)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidFindsDistrictWithThatCity()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $cityUid)
        );

        self::assertTrue(
            $this->fixture->findAllByCityUid($cityUid)->hasUid($districtUid)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidCanFindTwoDistrictsWithThatCity()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid1 = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $cityUid)
        );
        $districtUid2 = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $cityUid)
        );

        $result = $this->fixture->findAllByCityUid($cityUid);
        self::assertTrue(
            $result->hasUid($districtUid1)
        );
        self::assertTrue(
            $result->hasUid($districtUid2)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidNotFindsDistrictWithoutCity()
    {
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts'
        );

        self::assertFalse(
            $this->fixture->findAllByCityUid(1)->hasUid($districtUid)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidNotFindsDistrictWithOtherCity()
    {
        $otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $otherCityUid)
        );

        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');

        self::assertFalse(
            $this->fixture->findAllByCityUid($cityUid)->hasUid($districtUid)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidOrdersResultsByTitle()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');

        $this->testingFramework->createRecord(
            'tx_realty_districts',
            array('city'=> $cityUid, 'title' => 'Xen District')
        );
        $districtUid2 = $this->testingFramework->createRecord(
            'tx_realty_districts',
            array('city'=> $cityUid, 'title' => 'Another District')
        );

        self::assertEquals(
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
    public function findAllByCityUidOrUnassignedWithZeroUidFindsDistrictWithoutCity()
    {
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts'
        );

        self::assertTrue(
            $this->fixture->findAllByCityUidOrUnassigned(0)->hasUid($districtUid)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidOrUnassignedWithZeroUidNotFindsDistrictWithSetCity()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $cityUid)
        );

        self::assertFalse(
            $this->fixture->findAllByCityUidOrUnassigned(0)->hasUid($districtUid)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidOrUnassignedFindsDistrictWithThatCity()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $cityUid)
        );

        self::assertTrue(
            $this->fixture->findAllByCityUidOrUnassigned($cityUid)->hasUid($districtUid)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidOrUnassignedCanFindTwoDistrictsWithThatCity()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid1 = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $cityUid)
        );
        $districtUid2 = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $cityUid)
        );

        $result = $this->fixture->findAllByCityUidOrUnassigned($cityUid);
        self::assertTrue(
            $result->hasUid($districtUid1)
        );
        self::assertTrue(
            $result->hasUid($districtUid2)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidOrUnassignedFindsDistrictWithoutCity()
    {
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts'
        );

        self::assertTrue(
            $this->fixture->findAllByCityUidOrUnassigned(1)->hasUid($districtUid)
        );
    }

    /**
     * @test
     */
    public function findAllByCityUidOrUnassignedNotFindsDistrictWithOtherCity()
    {
        $otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts', array('city' => $otherCityUid)
        );

        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');

        self::assertFalse(
            $this->fixture->findAllByCityUidOrUnassigned($cityUid)->hasUid($districtUid)
        );
    }

    ////////////////////////////////
    // Tests concerning findByName
    ////////////////////////////////

    /**
     * @test
     */
    public function findByNameForEmptyValueThrowsException()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            '$value must not be empty.'
        );

        $this->fixture->findByName('');
    }

    /**
     * @test
     */
    public function findByNameCanFindModelFromCache()
    {
        $model = $this->fixture->getLoadedTestingModel(
            array('title' => 'Kleinwurzeling')
        );

        self::assertSame(
            $model,
            $this->fixture->findByName('Kleinwurzeling')
        );
    }

    /**
     * @test
     */
    public function findByNameCanLoadModelFromDatabase()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_districts', array('title' => 'Kleinwurzeling')
        );

        self::assertEquals(
            $uid,
            $this->fixture->findByName('Kleinwurzeling')->getUid()
        );
    }

    /**
     * @test
     */
    public function findByNameForInexistentNameThrowsException()
    {
        $this->setExpectedException('Tx_Oelib_Exception_NotFound');

        $this->fixture->findByName('Hupflingen');
    }

    //////////////////////////////////////////
    // Tests concerning findByNameAndCityUid
    //////////////////////////////////////////

    /**
     * @test
     */
    public function findByNameAndCityUidForEmptyNameThrowsException()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            '$districtName must not be empty.'
        );

        $this->fixture->findByNameAndCityUid('', 42);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidForNegativeCityUidThrowsException()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            '$cityUid must be >= 0.'
        );

        $this->fixture->findByNameAndCityUid('Kreuzberg', -1);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidForZeroCityUidNotThrowsException()
    {
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
    public function findByNameAndCityUidReturnsDistrict()
    {
        $this->fixture->getLoadedTestingModel(
            array(
                'title' => 'Kreuzberg',
                'city' => 0,
            )
        );

        self::assertTrue(
            $this->fixture->findByNameAndCityUid('Kreuzberg', 0)
                instanceof tx_realty_Model_District
        );
    }

    /**
     * @test
     */
    public function findByNameAndCityUidCanFindDistrictWithThatNameAndCityFromDatabase()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts',
            array(
                'title' => 'Kreuzberg',
                'city' => $cityUid,
            )
        );

        self::assertEquals(
            $districtUid,
            $this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid)->getUid()
        );
    }

    /**
     * @test
     */
    public function findByNameAndCityUidNotFindsDistrictWithThatNameAndOtherCityFromDatabase()
    {
        $this->setExpectedException(
            'Tx_Oelib_Exception_NotFound'
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
    public function findByNameAndCityUidNotFindsDistrictWithThatNameAndInexistentCityFromDatabase()
    {
        $this->setExpectedException(
            'Tx_Oelib_Exception_NotFound'
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
    public function findByNameAndCityUidNotFindsDistrictWithOtherNameAndMatchingCityFromDatabase()
    {
        $this->setExpectedException(
            'Tx_Oelib_Exception_NotFound'
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
    public function findByNameAndCityUidNotFindsDistrictWithOtherNameAndOtherCityFromDatabase()
    {
        $this->setExpectedException(
            'Tx_Oelib_Exception_NotFound'
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
    public function findByNameAndCityUidCanFindDistrictWithThatNameAndCityFromCache()
    {
        $cityUid = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_city')
            ->getNewGhost()->getUid();
        $district = $this->fixture->getLoadedTestingModel(
            array(
                'title' => 'Kreuzberg',
                'city' => $cityUid,
            )
        );

        self::assertEquals(
            $district,
            $this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid)
        );
    }

    /**
     * @test
     */
    public function findByNameAndCityUidNotFindsDistrictWithThatNameAndOtherCityFromCache()
    {
        $this->setExpectedException(
            'Tx_Oelib_Exception_NotFound'
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
    public function findByNameAndCityUidNotFindsDistrictWithOtherNameMatchingCityFromCache()
    {
        $this->setExpectedException(
            'Tx_Oelib_Exception_NotFound'
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
    public function findByNameAndCityUidNotFindsDistrictWithOtherNameAndOtherCityFromCache()
    {
        $this->setExpectedException(
            'Tx_Oelib_Exception_NotFound'
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
