<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Bernd Schönbach <bernd@oliverklee.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Unit tests for the tx_realty_Model_FrontEndUser class in the 'realty'
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Model_FrontEndUser_testcase extends tx_phpunit_testcase {
	/** @var frontEndUserModel */
	private $fixture;

	/** @var tx_oelib_testingFramework */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->fixture = new tx_realty_Model_FrontEndUser();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}

	public function testFixtureIsInstanceOfOelibFrontEndUser() {
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
	 * @param integer UID of the owner of the realty object, must be >= 0
	 *
	 * @return integer the UID of the created object record, will be > 0
	 */
	private function createObject($ownerUid = 0) {
		return $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
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

	public function testCreateObjectCreatesObjectInDatabase() {
		$createdObjectUid = $this->createObject();

		$this->assertTrue(
			$this->testingFramework->existsRecordWithUid(
				REALTY_TABLE_OBJECTS, $createdObjectUid
			)
		);
	}

	public function testCreateObjectReturnsPositiveUid() {
		$createdObjectUid = $this->createObject();

		$this->assertGreaterThan(
			0,
			$createdObjectUid
		);
	}

	public function testCreateObjectCreatesObjectRecordWithGivenOwnerUid() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->createObject($userUid);

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'title="foo" and owner=' . $userUid
			)
		);
	}

	public function testCreateObjectCanCreateTwoObjectRecords() {
		$this->createObject();
		$this->createObject();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'title="foo" and owner=0'
			)
		);
	}


	////////////////////////////////////////////////////
	// Tests concerning getTotalNumberOfAllowedObjects
	////////////////////////////////////////////////////

	public function testGetTotalNumberOfAllowedObjectsForUserWithNoMaximumObjectsSetReturnsZero() {
		$this->fixture->setData(array());

		$this->assertEquals(
			0,
			$this->fixture->getTotalNumberOfAllowedObjects()
		);
	}

	public function testGetTotalNumberOfAllowedObjectsForUserWithNonZeroMaximumObjectsReturnsMaximumObjectsValue() {
		$this->fixture->setData(array('tx_realty_maximum_objects' => 42));

		$this->assertEquals(
			42,
			$this->fixture->getTotalNumberOfAllowedObjects()
		);
	}


	//////////////////////////////////////////////////////
	// Tests concerning the getNumberOfObjects function.
	//////////////////////////////////////////////////////

	public function testGetNumberOfObjectsForUserWithNoObjectsReturnsZero() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(array('uid' => $userUid));

		$this->assertEquals(
			0,
			$this->fixture->getNumberOfObjects()
		);
	}

	public function testGetNumberOfObjectsForUserWithOneObjectReturnsOne() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(array('uid' => $userUid));
		$this->createObject($userUid);

		$this->assertEquals(
			1,
			$this->fixture->getNumberOfObjects()
		);
	}

	public function testGetNumberOfObjectsForUserWithTwoObjectReturnsTwo() {
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

	public function testGetObjectsLeftToEnterForUserWithNoObjectsAndNoMaximumNumberOfObjectsReturnsZero() {
		$userUid = $this->testingFramework->createFrontEndUser();
		$this->fixture->setData(array('uid' => $userUid));
		$this->fixture->getNumberOfObjects();

		$this->assertEquals(
			0,
			$this->fixture->getObjectsLeftToEnter()
		);
	}

	public function testGetObjectsLeftToEnterForUserWithOneObjectAndLimitSetToOneObjectReturnsZero() {
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

	public function testGetObjectsLeftToEnterForUserWithTwoObjectsAndLimitSetToOneObjectReturnsZero() {
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

	public function testGetObjectsLeftToEnterForUserWithNoObjectsAndLimitSetToTwoReturnsTwo() {
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

	public function testGetObjectsLeftToEnterForUserWithOneObjectAndLimitSetToTwoReturnsOne() {
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

	public function testCanAddNewObjectsForUserWithMaximumObjectsSetToZeroReturnsTrue() {
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

	public function testCanAddNewObjectsForUserWithOneObjectAndMaximumObjectsSetToZeroReturnsTrue() {
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

	public function testCanAddNewObjectsForUserWithOneObjectAndMaximumObjectsSetToTwoReturnsTrue() {
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

	public function testCanAddNewObjectsForUserWithTwoObjectsAndMaximumObjectsSetToOneReturnsFalse() {
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

	public function testCanAddNewObjectsForUserWithOneObjectAndMaximumObjectsSetToOneReturnsFalse() {
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

	public function testCanAddNewObjectsDoesNotRecalculateObjectLimit() {
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

	public function testCanAddNewObjectsAfterResetObjectsHaveBeenCalculatedIsCalledRecalculatesObjectLimit() {
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
}
?>