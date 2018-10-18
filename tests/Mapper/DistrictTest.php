<?php

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_DistrictTest extends \Tx_Phpunit_TestCase
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
        self::assertInstanceOf(
            tx_realty_Model_District::class,
            $this->fixture->find(1)
        );
    }

    /**
     * @test
     */
    public function findWithUidOfExistingRecordReturnsRecordAsModel()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['title' => 'Bad Godesberg']
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
        $city = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_City::class)->getNewGhost();

        /** @var tx_realty_Model_District $district */
        $district = $this->fixture->getLoadedTestingModel(['city' => $city->getUid()]);

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
            'tx_realty_districts',
            ['city' => $cityUid]
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
            'tx_realty_districts',
            ['city' => $cityUid]
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
            'tx_realty_districts',
            ['city' => $cityUid]
        );
        $districtUid2 = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['city' => $cityUid]
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
            'tx_realty_districts',
            ['city' => $otherCityUid]
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
            ['city' => $cityUid, 'title' => 'Xen District']
        );
        $districtUid2 = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['city' => $cityUid, 'title' => 'Another District']
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
            'tx_realty_districts',
            ['city' => $cityUid]
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
            'tx_realty_districts',
            ['city' => $cityUid]
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
            'tx_realty_districts',
            ['city' => $cityUid]
        );
        $districtUid2 = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['city' => $cityUid]
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
            'tx_realty_districts',
            ['city' => $otherCityUid]
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
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->findByName('');
    }

    /**
     * @test
     */
    public function findByNameCanFindModelFromCache()
    {
        $model = $this->fixture->getLoadedTestingModel(
            ['title' => 'Kleinwurzeling']
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
            'tx_realty_districts',
            ['title' => 'Kleinwurzeling']
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
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

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
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->findByNameAndCityUid('', 42);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidForNegativeCityUidThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->findByNameAndCityUid('Kreuzberg', -1);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidForZeroCityUidNotThrowsException()
    {
        $this->fixture->getLoadedTestingModel(
            [
                'title' => 'Kreuzberg',
                'city' => 0,
            ]
        );

        $this->fixture->findByNameAndCityUid('Kreuzberg', 0);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidReturnsDistrict()
    {
        $this->fixture->getLoadedTestingModel(
            [
                'title' => 'Kreuzberg',
                'city' => 0,
            ]
        );

        self::assertInstanceOf(
            tx_realty_Model_District::class,
            $this->fixture->findByNameAndCityUid('Kreuzberg', 0)
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
            [
                'title' => 'Kreuzberg',
                'city' => $cityUid,
            ]
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
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $this->testingFramework->createRecord(
            'tx_realty_districts',
            [
                'title' => 'Kreuzberg',
                'city' => $otherCityUid,
            ]
        );

        $this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidNotFindsDistrictWithThatNameAndInexistentCityFromDatabase()
    {
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $this->testingFramework->createRecord(
            'tx_realty_districts',
            [
                'title' => 'Kreuzberg',
                'city' => 0,
            ]
        );
        $cityUid = $this->testingFramework->getAutoIncrement('tx_realty_cities');

        $this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidNotFindsDistrictWithOtherNameAndMatchingCityFromDatabase()
    {
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $this->testingFramework->createRecord(
            'tx_realty_districts',
            [
                'title' => 'Neukölln',
                'city' => $cityUid,
            ]
        );

        $this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidNotFindsDistrictWithOtherNameAndOtherCityFromDatabase()
    {
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $this->testingFramework->createRecord(
            'tx_realty_districts',
            [
                'title' => 'Neukölln',
                'city' => $otherCityUid,
            ]
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
            [
                'title' => 'Kreuzberg',
                'city' => $cityUid,
            ]
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
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $this->fixture->getLoadedTestingModel(
            [
                'title' => 'Kreuzberg',
                'city' => $otherCityUid,
            ]
        );

        $this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidNotFindsDistrictWithOtherNameMatchingCityFromCache()
    {
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $this->fixture->getLoadedTestingModel(
            [
                'title' => 'Neukölln',
                'city' => $cityUid,
            ]
        );

        $this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
    }

    /**
     * @test
     */
    public function findByNameAndCityUidNotFindsDistrictWithOtherNameAndOtherCityFromCache()
    {
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $this->fixture->getLoadedTestingModel(
            [
                'title' => 'Neukölln',
                'city' => $otherCityUid,
            ]
        );

        $this->fixture->findByNameAndCityUid('Kreuzberg', $cityUid);
    }
}
