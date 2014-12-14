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
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Model_FrontEndUserTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Model_FrontEndUser
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	protected function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->fixture = new tx_realty_Model_FrontEndUser();
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}

	/**
	 * @test
	 */
	public function fixtureIsInstanceOfOelibFrontEndUser() {
		$this->assertTrue(
			$this->fixture instanceof tx_oelib_Model_FrontEndUser
		);
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
	private function createObject($ownerUid = 0) {
		return $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => 'foo',
				'language' => 'foo',
				'openimmo_obid' => 'test-obid',
				'owner' => $ownerUid,
			)
		);
	}


	///////////////////////////////////////
	// Tests concerning createDummyObject
	///////////////////////////////////////

	/**
	 * @test
	 */
	public function createObjectCreatesObjectInDatabase() {
		$createdObjectUid = $this->createObject();

		$this->assertTrue(
			$this->testingFramework->existsRecordWithUid(
				'tx_realty_objects', $createdObjectUid
			)
		);
	}

	/**
	 * @test
	 */
	public function createObjectReturnsPositiveUid() {
		$createdObjectUid = $this->createObject();

		$this->assertGreaterThan(
			0,
			$createdObjectUid
		);
	}

	/**
	 * @test
	 */
	public function createObjectCreatesObjectRecordWithGivenOwnerUid() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->createObject($userUid);

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				'tx_realty_objects',
				'title="foo" and owner=' . $userUid
			)
		);
	}

	/**
	 * @test
	 */
	public function createObjectCanCreateTwoObjectRecords() {
		$this->createObject();
		$this->createObject();

		$this->assertEquals(
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
	public function getTotalNumberOfAllowedObjectsForUserWithNoMaximumObjectsSetReturnsZero() {
		$this->fixture->setData(array());

		$this->assertEquals(
			0,
			$this->fixture->getTotalNumberOfAllowedObjects()
		);
	}

	/**
	 * @test
	 */
	public function getTotalNumberOfAllowedObjectsForUserWithNonZeroMaximumObjectsReturnsMaximumObjectsValue() {
		$this->fixture->setData(array('tx_realty_maximum_objects' => 42));

		$this->assertEquals(
			42,
			$this->fixture->getTotalNumberOfAllowedObjects()
		);
	}


	//////////////////////////////////////////////////////
	// Tests concerning the getNumberOfObjects function.
	//////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getNumberOfObjectsForUserWithNoObjectsReturnsZero() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(array('uid' => $userUid));

		$this->assertEquals(
			0,
			$this->fixture->getNumberOfObjects()
		);
	}

	/**
	 * @test
	 */
	public function getNumberOfObjectsForUserWithOneObjectReturnsOne() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(array('uid' => $userUid));
		$this->createObject($userUid);

		$this->assertEquals(
			1,
			$this->fixture->getNumberOfObjects()
		);
	}

	/**
	 * @test
	 */
	public function getNumberOfObjectsForUserWithTwoObjectReturnsTwo() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(array('uid' => $userUid));
		$this->createObject($userUid);
		$this->createObject($userUid);

		$this->assertEquals(
			2,
			$this->fixture->getNumberOfObjects()
		);
	}


	///////////////////////////////////////////
	// Tests concerning getObjectsLeftToEnter
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getObjectsLeftToEnterForUserWithNoObjectsAndNoMaximumNumberOfObjectsReturnsZero() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(array('uid' => $userUid));
		$this->fixture->getNumberOfObjects();

		$this->assertEquals(
			0,
			$this->fixture->getObjectsLeftToEnter()
		);
	}

	/**
	 * @test
	 */
	public function getObjectsLeftToEnterForUserWithOneObjectAndLimitSetToOneObjectReturnsZero() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->createObject($userUid);
		$this->fixture->getNumberOfObjects();

		$this->assertEquals(
			0,
			$this->fixture->getObjectsLeftToEnter()
		);
	}

	/**
	 * @test
	 */
	public function getObjectsLeftToEnterForUserWithTwoObjectsAndLimitSetToOneObjectReturnsZero() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->createObject($userUid);
		$this->createObject($userUid);
		$this->fixture->getNumberOfObjects();

		$this->assertEquals(
			0,
			$this->fixture->getObjectsLeftToEnter()
		);
	}

	/**
	 * @test
	 */
	public function getObjectsLeftToEnterForUserWithNoObjectsAndLimitSetToTwoReturnsTwo() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 2,
			)
		);
		$this->fixture->getNumberOfObjects();

		$this->assertEquals(
			2,
			$this->fixture->getObjectsLeftToEnter()
		);
	}

	/**
	 * @test
	 */
	public function getObjectsLeftToEnterForUserWithOneObjectAndLimitSetToTwoReturnsOne() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 2,
			)
		);
		$this->createObject($userUid);
		$this->fixture->getNumberOfObjects();

		$this->assertEquals(
			1,
			$this->fixture->getObjectsLeftToEnter()
		);
	}


	//////////////////////////////////////
	// Tests concerning canAddNewObjects
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function canAddNewObjectsForUserWithMaximumObjectsSetToZeroReturnsTrue() {
		$this->fixture->setData(
			array(
				'uid' => $this->testingFramework->createFrontEndUser(),
				'tx_realty_maximum_objects' => 0,
			)
		);

		$this->assertTrue(
			$this->fixture->canAddNewObjects()
		);
	}

	/**
	 * @test
	 */
	public function canAddNewObjectsForUserWithOneObjectAndMaximumObjectsSetToZeroReturnsTrue() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 0,
			)
		);
		$this->createObject($userUid);

		$this->assertTrue(
			$this->fixture->canAddNewObjects()
		);
	}

	/**
	 * @test
	 */
	public function canAddNewObjectsForUserWithOneObjectAndMaximumObjectsSetToTwoReturnsTrue() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 2,
			)
		);
		$this->createObject($userUid);

		$this->assertTrue(
			$this->fixture->canAddNewObjects()
		);
	}

	/**
	 * @test
	 */
	public function canAddNewObjectsForUserWithTwoObjectsAndMaximumObjectsSetToOneReturnsFalse() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->createObject($userUid);
		$this->createObject($userUid);

		$this->assertFalse(
			$this->fixture->canAddNewObjects()
		);
	}

	/**
	 * @test
	 */
	public function canAddNewObjectsForUserWithOneObjectAndMaximumObjectsSetToOneReturnsFalse() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->createObject($userUid);

		$this->assertFalse(
			$this->fixture->canAddNewObjects()
		);
	}


	//////////////////////////////////////////////////////////////
	// Tests concerning the calculation of the number of objects
	//////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function canAddNewObjectsDoesNotRecalculateObjectLimit() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->fixture->canAddNewObjects();
		$this->createObject($userUid);

		$this->assertTrue(
			$this->fixture->canAddNewObjects()
		);
	}

	/**
	 * @test
	 */
	public function canAddNewObjectsAfterResetObjectsHaveBeenCalculatedIsCalledRecalculatesObjectLimit() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(
			array(
				'uid' => $userUid,
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->fixture->canAddNewObjects();
		$this->createObject($userUid);
		$this->fixture->resetObjectsHaveBeenCalculated();

		$this->assertFalse(
			$this->fixture->canAddNewObjects()
		);
	}


	/////////////////////////////////////////////
	// Tests concerning the OpenImmo offerer ID
	/////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getOpenImmoOffererIdForNoDataReturnsEmptyString() {
		$this->fixture->setData(array());

		$this->assertEquals(
			'',
			$this->fixture->getOpenImmoOffererId()
		);
	}

	/**
	 * @test
	 */
	public function getOpenImmoOffererIdReturnsOpenImmoOffererId() {
		$this->fixture->setData(
			array('tx_realty_openimmo_anid' => 'some complicated ID')
		);

		$this->assertEquals(
			'some complicated ID',
			$this->fixture->getOpenImmoOffererId()
		);
	}

	/**
	 * @test
	 */
	public function hasOpenImmoOffererIdForEmptyIdReturnsFalse() {
		$this->fixture->setData(
			array('tx_realty_openimmo_anid' => '')
		);

		$this->assertFalse(
			$this->fixture->hasOpenImmoOffererId()
		);
	}

	/**
	 * @test
	 */
	public function hasOpenImmoOffererIdForNonEmptyIdReturnsTrue() {
		$this->fixture->setData(
			array('tx_realty_openimmo_anid' => 'some complicated ID')
		);

		$this->assertTrue(
			$this->fixture->hasOpenImmoOffererId()
		);
	}
}