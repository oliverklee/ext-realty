<?php

use OliverKlee\PhpUnit\TestCase;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_RealtyObjectTest extends TestCase
{
    /**
     * @var tx_realty_Mapper_RealtyObject
     */
    private $subject = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int
     */
    private $folderUid = 0;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->folderUid = $this->testingFramework->createSystemFolder();
        $this->subject = new tx_realty_Mapper_RealtyObject();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /////////////////////////////////////////
    // Tests concerning the basic functions
    /////////////////////////////////////////

    /**
     * @test
     */
    public function findWithUidOfExistingRecordReturnsRealtyObjectInstance()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['title' => 'foo']
        );

        self::assertInstanceOf(
            tx_realty_Model_RealtyObject::class,
            $this->subject->find($uid)
        );
    }

    /**
     * @test
     */
    public function getOwnerForMappedModelReturnsFrontEndUserInstance()
    {
        $ownerUid = $this->testingFramework->createFrontEndUser();
        $objectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['title' => 'foo', 'owner' => $ownerUid]
        );

        /** @var tx_realty_Model_RealtyObject $model */
        $model = $this->subject->find($objectUid);
        self::assertInstanceOf(
            tx_realty_Model_FrontEndUser::class,
            $model->getOwner()
        );
    }

    /*
     * Tests concerning countByCity
     */

    /**
     * @test
     */
    public function countByCityForNoMatchesReturnsZero()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        /** @var tx_realty_Model_City $city */
        $city = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_City::class)->find($cityUid);

        self::assertSame(0, $this->subject->countByCity($city));
    }

    /**
     * @test
     */
    public function countByCityWithOneMatchReturnsOne()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        /** @var tx_realty_Model_City $city */
        $city = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_City::class)->find($cityUid);

        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );

        self::assertSame(1, $this->subject->countByCity($city));
    }

    /**
     * @test
     */
    public function countByCityWithTwoMatchesReturnsTwo()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        /** @var tx_realty_Model_City $city */
        $city = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_City::class)->find($cityUid);

        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );

        self::assertSame(2, $this->subject->countByCity($city));
    }

    /**
     * @test
     */
    public function countByCityTakesAdditionalWhereClauseIntoAccount()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        /** @var \tx_realty_Model_City $city */
        $city = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_City::class)->find($cityUid);

        $this->testingFramework->createRecord('tx_realty_objects', ['city' => $cityUid, 'title' => 'Studio']);
        $this->testingFramework->createRecord('tx_realty_objects', ['city' => $cityUid, 'title' => 'Shared flat']);

        self::assertSame(1, $this->subject->countByCity($city, 'AND title = "Studio"'));
    }

    /*
     * Tests concerning countByDistrict
     */

    /**
     * @test
     */
    public function countByDistrictForNoMatchesReturnsZero()
    {
        $districtUid = $this->testingFramework->createRecord('tx_realty_districts');
        /** @var tx_realty_Model_District $district */
        $district = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_District::class)->find($districtUid);

        self::assertSame(0, $this->subject->countByDistrict($district));
    }

    /**
     * @test
     */
    public function countByDistrictWithOneMatchReturnsOne()
    {
        $districtUid = $this->testingFramework->createRecord('tx_realty_districts');
        /** @var tx_realty_Model_District $district */
        $district = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_District::class)->find($districtUid);

        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['district' => $districtUid]
        );

        self::assertSame(1, $this->subject->countByDistrict($district));
    }

    /**
     * @test
     */
    public function countByDistrictWithTwoMatchesReturnsTwo()
    {
        $districtUid = $this->testingFramework->createRecord('tx_realty_districts');
        /** @var tx_realty_Model_District $district */
        $district = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_District::class)->find($districtUid);

        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['district' => $districtUid]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['district' => $districtUid]
        );

        self::assertSame(2, $this->subject->countByDistrict($district));
    }

    /**
     * @test
     */
    public function countByDistrictTakesAdditionalWhereClauseIntoAccount()
    {
        $districtUid = $this->testingFramework->createRecord('tx_realty_districts');
        /** @var tx_realty_Model_District $district */
        $district = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_District::class)->find($districtUid);

        $this->testingFramework->createRecord('tx_realty_objects', ['district' => $districtUid, 'title' => 'Studio']);
        $this->testingFramework->createRecord('tx_realty_objects', ['district' => $districtUid, 'title' => 'Room']);

        self::assertSame(1, $this->subject->countByDistrict($district, 'AND title = "Studio"'));
    }

    //////////////////////////////////////////////////////////////
    // Tests concerning findByObjectNumberAndObjectIdAndLanguage
    //////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function findByObjectNumberAndObjectIdAndLanguageForAllParametersEmptyAndExistingMatchNotThrowsException()
    {
        $this->subject->getLoadedTestingModel(['object_number' => '', 'openimmo_obid' => '', 'language' => '']);

        $this->subject->findByObjectNumberAndObjectIdAndLanguage('', '', '');
    }

    /**
     * @test
     */
    public function findByObjectNumberAndObjectIdAndLanguageReturnsRealtyObject()
    {
        $this->subject->getLoadedTestingModel(
            [
                'object_number' => 'FLAT0001',
                'openimmo_obid' => 'abc01234',
                'language' => 'de',
            ]
        );

        self::assertInstanceOf(
            tx_realty_Model_RealtyObject::class,
            $this->subject->findByObjectNumberAndObjectIdAndLanguage(
                'FLAT0001',
                'abc01234',
                'de'
            )
        );
    }

    /**
     * @test
     */
    public function findByObjectNumberAndObjectIdAndLanguageCanFindRealtyObjectWithMatchingDataFromDatabase()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => 'FLAT0001',
                'openimmo_obid' => 'abc01234',
                'language' => 'de',
            ]
        );

        self::assertEquals(
            $uid,
            $this->subject->findByObjectNumberAndObjectIdAndLanguage('FLAT0001', 'abc01234', 'de')->getUid()
        );
    }

    /**
     * @test
     */
    public function findByObjectNumberAndObjectIdAndLanguageNotFindsModelWithDifferentObjectNumber()
    {
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $this->subject->getLoadedTestingModel(
            [
                'object_number' => 'FLAT0001',
                'openimmo_obid' => 'abc01234',
                'language' => 'de',
            ]
        );

        $this->subject->findByObjectNumberAndObjectIdAndLanguage(
            'FLAT0002',
            'abc01234',
            'de'
        );
    }

    /**
     * @test
     */
    public function findByObjectNumberAndObjectIdAndLanguageNotFindsModelWithDifferentObjectId()
    {
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $this->subject->getLoadedTestingModel(
            [
                'object_number' => 'FLAT0001',
                'openimmo_obid' => 'abc01234',
                'language' => 'de',
            ]
        );

        $this->subject->findByObjectNumberAndObjectIdAndLanguage(
            'FLAT0001',
            '9684654651',
            'de'
        );
    }

    /**
     * @test
     */
    public function findByObjectNumberAndObjectIdAndLanguageNotFindsModelWithDifferentObjectLanguage()
    {
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $this->subject->getLoadedTestingModel(
            [
                'object_number' => 'FLAT0001',
                'openimmo_obid' => 'abc01234',
                'language' => 'de',
            ]
        );

        $this->subject->findByObjectNumberAndObjectIdAndLanguage(
            'FLAT0002',
            'abc01234',
            'en'
        );
    }

    /*
     * Tests concerning findByAnidAndPid
     */

    /**
     * @test
     */
    public function findByAnidAndPidForNoMatchesReturnsEmptyList()
    {
        $result = $this->subject->findByAnidAndPid('not-in-database', $this->folderUid);
        self::assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function findByAnidAndPidForNonEmptyAnidIgnoresObjectWithEmptyAnid()
    {
        $this->testingFramework->createRecord('tx_realty_objects', ['pid' => $this->folderUid, 'openimmo_anid' => '']);

        $result = $this->subject->findByAnidAndPid('not-in-database', $this->folderUid);
        self::assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function findByAnidAndPidForEmptyAnidFindsObjectWithEmptyAnid()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => '']
        );

        $result = $this->subject->findByAnidAndPid('', $this->folderUid);
        self::assertSame(1, $result->count());
        /** @var \tx_realty_Model_RealtyObject $firstMatch */
        $firstMatch = $result->first();
        self::assertSame($uid, $firstMatch->getUid());
    }

    /**
     * @test
     */
    public function findByAnidAndPidIgnoresObjectWithOtherAnid()
    {
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => 'other-anid']
        );

        $result = $this->subject->findByAnidAndPid('not-in-database', $this->folderUid);
        self::assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function findByAnidAndPidIgnoresDeletedObjectWithOtherMatchingAnid()
    {
        $anid = 'abc-def-ghi-1234';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => $anid, 'deleted' => 1]
        );

        $result = $this->subject->findByAnidAndPid('not-in-database', $this->folderUid);
        self::assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function findByAnidAndPidFindsObjectWithExactMatchingAnid()
    {
        $anid = 'OABC20017128124930123asd43fer35';
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => $anid]
        );

        $result = $this->subject->findByAnidAndPid($anid, $this->folderUid);
        self::assertSame(1, $result->count());
        /** @var \tx_realty_Model_RealtyObject $firstMatch */
        $firstMatch = $result->first();
        self::assertSame($uid, $firstMatch->getUid());
    }

    /**
     * @test
     */
    public function findByAnidAndPidFindsObjectWithMatchingFirstFourCharactersOfAnid()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => 'OABC20017128124930123asd43fer35']
        );

        $result = $this->subject->findByAnidAndPid('OABC10017128124930123asd43fer35', $this->folderUid);
        self::assertSame(1, $result->count());
        /** @var \tx_realty_Model_RealtyObject $firstMatch */
        $firstMatch = $result->first();
        self::assertSame($uid, $firstMatch->getUid());
    }

    /**
     * @test
     */
    public function findByAnidAndPidNotFindsObjectWithMatchingOnlyFirstThreeCharactersOfAnid()
    {
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => 'OABC20017128124930123asd43fer35']
        );

        $result = $this->subject->findByAnidAndPid('OABD20017128124930123asd43fer35', $this->folderUid);
        self::assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function findByAnidAndPidIgnoresObjectWithDifferentPid()
    {
        $anid = 'OABC20017128124930123asd43fer35';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => $anid]
        );

        $result = $this->subject->findByAnidAndPid($anid, $this->folderUid + 1);
        self::assertTrue($result->isEmpty());
    }

    /*
     * Tests concerning deleteByAnidAndPidWithExceptions
     */

    /**
     * @test
     */
    public function deleteByAnidAndPidWithExceptionsNotDeletesRecordWithNonMatchingAnid()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => 'OABC20017128124930123asd43fer35']
        );

        $anid = 'OABD20017128124930123asd43fer35';
        $result = $this->subject->deleteByAnidAndPidWithExceptions($anid, $this->folderUid, new \Tx_Oelib_List());

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function deleteByAnidAndPidWithExceptionsNotDeletesRecordWithNonMatchingPid()
    {
        $anid = 'OABD20017128124930123asd43fer35';
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => $anid]
        );

        $result = $this->subject->deleteByAnidAndPidWithExceptions($anid, $this->folderUid + 1, new \Tx_Oelib_List());

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function deleteByAnidAndPidWithExceptionsDeletesRecordWithMatchingAnidByFirstFourCharacters()
    {
        $anid = 'OABC20017128124930123asd43fer35';
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => $anid]
        );
        $object = $this->subject->find($uid);

        $result = $this->subject->deleteByAnidAndPidWithExceptions(
            'OABC10017128124930123asd43fer35',
            $this->folderUid,
            new \Tx_Oelib_List()
        );

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 1')
        );
        self::assertSame([$object], $result);
    }

    /**
     * @test
     */
    public function deleteByAnidAndPidWithExceptionsDeletesRecordWithMatchingEmptyAnid()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => '']
        );
        $object = $this->subject->find($uid);

        $result = $this->subject->deleteByAnidAndPidWithExceptions('', $this->folderUid, new \Tx_Oelib_List());

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 1')
        );
        self::assertSame([$object], $result);
    }

    /**
     * @test
     */
    public function deleteByAnidAndPidWithExceptionsNotDeletesMatchingRecordMarkedAsException()
    {
        $anid = 'OABC20017128124930123asd43fer35';
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['pid' => $this->folderUid, 'openimmo_anid' => $anid]
        );
        $exceptions = new \Tx_Oelib_List();
        $exceptions->add($this->subject->find($uid));

        $result = $this->subject->deleteByAnidAndPidWithExceptions($anid, $this->folderUid, $exceptions);

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
        self::assertSame([], $result);
    }
}
