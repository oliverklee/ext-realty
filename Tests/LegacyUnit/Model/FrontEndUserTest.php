<?php

use OliverKlee\PhpUnit\TestCase;

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Model_FrontEndUserTest extends TestCase
{
    /**
     * @var tx_realty_Model_FrontEndUser
     */
    private $subject = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->subject = new tx_realty_Model_FrontEndUser();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /**
     * @test
     */
    public function fixtureIsInstanceOfOelibFrontEndUser()
    {
        self::assertInstanceOf(Tx_Oelib_Model_FrontEndUser::class, $this->subject);
    }

    ///////////////////////
    // Utility functions.
    ///////////////////////

    /**
     * Creates a realty object record.
     *
     * @param int $ownerUid UID of the owner of the realty object, must be >= 0
     *
     * @return int the UID of the created object record, will be > 0
     */
    private function createObject($ownerUid = 0)
    {
        return $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'foo',
                'language' => 'foo',
                'openimmo_obid' => 'test-obid',
                'owner' => $ownerUid,
            ]
        );
    }

    ///////////////////////////////////////
    // Tests concerning createDummyObject
    ///////////////////////////////////////

    /**
     * @test
     */
    public function createObjectCreatesObjectInDatabase()
    {
        $createdObjectUid = $this->createObject();

        self::assertTrue(
            $this->testingFramework->existsRecordWithUid(
                'tx_realty_objects',
                $createdObjectUid
            )
        );
    }

    /**
     * @test
     */
    public function createObjectReturnsPositiveUid()
    {
        $createdObjectUid = $this->createObject();

        self::assertGreaterThan(
            0,
            $createdObjectUid
        );
    }

    /**
     * @test
     */
    public function createObjectCreatesObjectRecordWithGivenOwnerUid()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->createObject($userUid);

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_objects',
                'title="foo" and owner=' . $userUid
            )
        );
    }

    /**
     * @test
     */
    public function createObjectCanCreateTwoObjectRecords()
    {
        $this->createObject();
        $this->createObject();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'title="foo" and owner=0'
            )
        );
    }

    ////////////////////////////////////////////////////
    // Tests concerning getTotalNumberOfAllowedObjects
    ////////////////////////////////////////////////////

    /**
     * @test
     */
    public function getTotalNumberOfAllowedObjectsForUserWithNoMaximumObjectsSetReturnsZero()
    {
        $this->subject->setData([]);

        self::assertEquals(
            0,
            $this->subject->getTotalNumberOfAllowedObjects()
        );
    }

    /**
     * @test
     */
    public function getTotalNumberOfAllowedObjectsForUserWithNonZeroMaximumObjectsReturnsMaximumObjectsValue()
    {
        $this->subject->setData(['tx_realty_maximum_objects' => 42]);

        self::assertEquals(
            42,
            $this->subject->getTotalNumberOfAllowedObjects()
        );
    }

    //////////////////////////////////////////////////////
    // Tests concerning the getNumberOfObjects function.
    //////////////////////////////////////////////////////

    /**
     * @test
     */
    public function getNumberOfObjectsForUserWithNoObjectsReturnsZero()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(['uid' => $userUid]);

        self::assertEquals(
            0,
            $this->subject->getNumberOfObjects()
        );
    }

    /**
     * @test
     */
    public function getNumberOfObjectsForUserWithOneObjectReturnsOne()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(['uid' => $userUid]);
        $this->createObject($userUid);

        self::assertEquals(
            1,
            $this->subject->getNumberOfObjects()
        );
    }

    /**
     * @test
     */
    public function getNumberOfObjectsForUserWithTwoObjectReturnsTwo()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(['uid' => $userUid]);
        $this->createObject($userUid);
        $this->createObject($userUid);

        self::assertEquals(
            2,
            $this->subject->getNumberOfObjects()
        );
    }

    ///////////////////////////////////////////
    // Tests concerning getObjectsLeftToEnter
    ///////////////////////////////////////////

    /**
     * @test
     */
    public function getObjectsLeftToEnterForUserWithNoObjectsAndNoMaximumNumberOfObjectsReturnsZero()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(['uid' => $userUid]);
        $this->subject->getNumberOfObjects();

        self::assertEquals(
            0,
            $this->subject->getObjectsLeftToEnter()
        );
    }

    /**
     * @test
     */
    public function getObjectsLeftToEnterForUserWithOneObjectAndLimitSetToOneObjectReturnsZero()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 1,
            ]
        );
        $this->createObject($userUid);
        $this->subject->getNumberOfObjects();

        self::assertEquals(
            0,
            $this->subject->getObjectsLeftToEnter()
        );
    }

    /**
     * @test
     */
    public function getObjectsLeftToEnterForUserWithTwoObjectsAndLimitSetToOneObjectReturnsZero()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 1,
            ]
        );
        $this->createObject($userUid);
        $this->createObject($userUid);
        $this->subject->getNumberOfObjects();

        self::assertEquals(
            0,
            $this->subject->getObjectsLeftToEnter()
        );
    }

    /**
     * @test
     */
    public function getObjectsLeftToEnterForUserWithNoObjectsAndLimitSetToTwoReturnsTwo()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 2,
            ]
        );
        $this->subject->getNumberOfObjects();

        self::assertEquals(
            2,
            $this->subject->getObjectsLeftToEnter()
        );
    }

    /**
     * @test
     */
    public function getObjectsLeftToEnterForUserWithOneObjectAndLimitSetToTwoReturnsOne()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 2,
            ]
        );
        $this->createObject($userUid);
        $this->subject->getNumberOfObjects();

        self::assertEquals(
            1,
            $this->subject->getObjectsLeftToEnter()
        );
    }

    //////////////////////////////////////
    // Tests concerning canAddNewObjects
    //////////////////////////////////////

    /**
     * @test
     */
    public function canAddNewObjectsForUserWithMaximumObjectsSetToZeroReturnsTrue()
    {
        $this->subject->setData(
            [
                'uid' => $this->testingFramework->createFrontEndUser(),
                'tx_realty_maximum_objects' => 0,
            ]
        );

        self::assertTrue(
            $this->subject->canAddNewObjects()
        );
    }

    /**
     * @test
     */
    public function canAddNewObjectsForUserWithOneObjectAndMaximumObjectsSetToZeroReturnsTrue()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 0,
            ]
        );
        $this->createObject($userUid);

        self::assertTrue(
            $this->subject->canAddNewObjects()
        );
    }

    /**
     * @test
     */
    public function canAddNewObjectsForUserWithOneObjectAndMaximumObjectsSetToTwoReturnsTrue()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 2,
            ]
        );
        $this->createObject($userUid);

        self::assertTrue(
            $this->subject->canAddNewObjects()
        );
    }

    /**
     * @test
     */
    public function canAddNewObjectsForUserWithTwoObjectsAndMaximumObjectsSetToOneReturnsFalse()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 1,
            ]
        );
        $this->createObject($userUid);
        $this->createObject($userUid);

        self::assertFalse(
            $this->subject->canAddNewObjects()
        );
    }

    /**
     * @test
     */
    public function canAddNewObjectsForUserWithOneObjectAndMaximumObjectsSetToOneReturnsFalse()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 1,
            ]
        );
        $this->createObject($userUid);

        self::assertFalse(
            $this->subject->canAddNewObjects()
        );
    }

    //////////////////////////////////////////////////////////////
    // Tests concerning the calculation of the number of objects
    //////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function canAddNewObjectsDoesNotRecalculateObjectLimit()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 1,
            ]
        );
        $this->subject->canAddNewObjects();
        $this->createObject($userUid);

        self::assertTrue(
            $this->subject->canAddNewObjects()
        );
    }

    /**
     * @test
     */
    public function canAddNewObjectsAfterResetObjectsHaveBeenCalculatedIsCalledRecalculatesObjectLimit()
    {
        $userUid = $this->testingFramework->createFrontEndUser();
        $this->subject->setData(
            [
                'uid' => $userUid,
                'tx_realty_maximum_objects' => 1,
            ]
        );
        $this->subject->canAddNewObjects();
        $this->createObject($userUid);
        $this->subject->resetObjectsHaveBeenCalculated();

        self::assertFalse(
            $this->subject->canAddNewObjects()
        );
    }

    /////////////////////////////////////////////
    // Tests concerning the OpenImmo offerer ID
    /////////////////////////////////////////////

    /**
     * @test
     */
    public function getOpenImmoOffererIdForNoDataReturnsEmptyString()
    {
        $this->subject->setData([]);

        self::assertEquals(
            '',
            $this->subject->getOpenImmoOffererId()
        );
    }

    /**
     * @test
     */
    public function getOpenImmoOffererIdReturnsOpenImmoOffererId()
    {
        $this->subject->setData(
            ['tx_realty_openimmo_anid' => 'some complicated ID']
        );

        self::assertEquals(
            'some complicated ID',
            $this->subject->getOpenImmoOffererId()
        );
    }

    /**
     * @test
     */
    public function hasOpenImmoOffererIdForEmptyIdReturnsFalse()
    {
        $this->subject->setData(
            ['tx_realty_openimmo_anid' => '']
        );

        self::assertFalse(
            $this->subject->hasOpenImmoOffererId()
        );
    }

    /**
     * @test
     */
    public function hasOpenImmoOffererIdForNonEmptyIdReturnsTrue()
    {
        $this->subject->setData(
            ['tx_realty_openimmo_anid' => 'some complicated ID']
        );

        self::assertTrue(
            $this->subject->hasOpenImmoOffererId()
        );
    }
}
