<?php
/***************************************************************
* Copyright notice
*
* (c) 2007 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_object class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty')
	.'tests/fixtures/class.tx_realty_object_child.php');

define('TX_REALTY_OBJECT_UID_1', 100000);
define('TX_REALTY_OBJECT_NUMBER_1', '100000');

class tx_realty_object_testcase extends tx_phpunit_testcase {
	private $fixture;

	private $dummyObjectNew = array(
		'object_number' => '100010',
		'title' => 'bar',
		'uid' => 100010
	);

	private $dummyObjectUpdateWithNumber = array(
		'object_number' => TX_REALTY_OBJECT_NUMBER_1,
		'title' => 'bar',
		'uid' => 100010
	);

	private $dummyObjectUpdateWithUid = array(
		'object_number' => '100010',
		'title' => 'bar',
		'uid' => TX_REALTY_OBJECT_UID_1
	);

	private $dummyObjectWithInvalidField = array(
		'object_number' => '100010',
		'title' => 'bar',
		'uid' => TX_REALTY_OBJECT_UID_1,
		'foobar' => 'foobar'
	);

	private $dummyObjectWitoutObjectNumber = array(
		'title' => 'bar',
		'uid' => TX_REALTY_OBJECT_UID_1
	);

	private $dummyObjectWitoutObjectNumberAndTitle = array(
		'uid' => TX_REALTY_OBJECT_UID_1
	);

	public function setUp() {
		$this->fixture = new tx_realty_object_child();
		$this->increaseAutoIncrement();
		$this->createDummyObjects();
	}

	public function tearDown() {
		unset($this->fixture);
		$this->deleteDummyEntries();
		$this->resetAutoIncrement();
	}

	public function testRecordExistsInDatabaseIfNoExistingUidGiven() {
		$this->assertFalse(
			$this->fixture->recordExistsInDatabase(array('uid' => '99999'))
		);
	}

	public function testRecordExistsInDatabaseIfExistingUidGiven() {
		$this->assertTrue(
			$this->fixture->recordExistsInDatabase(array('uid' => TX_REALTY_OBJECT_UID_1))
		);
	}

	public function testRecordExistsInDatabaseIfNoExistingObjectNumberGiven() {
		$this->assertFalse(
			$this->fixture->recordExistsInDatabase(array('object_number' => '99999'))
		);
	}

	public function testRecordExistsInDatabaseIfExistingObjectNumberGiven() {
		$this->assertTrue(
			$this->fixture->recordExistsInDatabase(array('object_number' => TX_REALTY_OBJECT_NUMBER_1))
		);
	}

	public function testLoadDatabaseEntryWithValidUid() {
		$realResult = $this->fixture->loadDatabaseEntry(TX_REALTY_OBJECT_UID_1);
		$supposedResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
			);
		if ($supposedResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($supposedResult))
		) {
        	$supposedResultArray = $row;
		}

		$this->assertEquals(
			$realResult,
			$supposedResultArray
		);
	}

	public function testLoadDatabaseEntryWithInvalidUid() {
		$this->assertEquals(
			$this->fixture->loadDatabaseEntry('99999'),
			array()
		);
	}

	public function testGetDataTypeWhenArrayGiven() {
		$this->assertEquals(
			$this->fixture->getDataType(array('foo')),
			'array'
		);
	}

	public function testGetDataTypeWhenIntegerGiven() {
		$this->assertEquals(
			$this->fixture->getDataType(1),
			'uid'
		);
	}

	public function testGetDataTypeWhenDatabaseResultGiven() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
		);

		$this->assertEquals(
			$this->fixture->getDataType($dbResult),
			'dbResult'
		);
	}

	public function testFetchDatabaseResultFromValidStream() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
		);
		if ($dbResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
			$supposedResult = $row;
		} else {
			$this->fail('There was an error with the database query.');
		}

		// dbResult can be fetched only once, so the query is needed again.
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
		);
		$resultToCheck = $this->fixture->fetchDatabaseResult($dbResult);

		$this->assertEquals(
			$supposedResult,
			$resultToCheck
		);
	}

	public function testFetchDatabaseResultIfDbResultIsFalse() {
		$dbResult = false;
		$resultToCheck = $this->fixture->fetchDatabaseResult($dbResult);

		$this->assertEquals(
			$resultToCheck,
			array()
		);
	}

	public function testUpdateDatabaseEntryWhenUidExistsInDb() {
		$this->fixture->updateDatabaseEntry($this->dummyObjectUpdateWithUid);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'tx_realty_objects',
			'uid='.$this->dummyObjectNew['uid']
		);

		$this->assertTrue(
			$dbResult !== false
		);
	}

	public function testUpdateDatabaseEntryWhenObjectNumberExistsInDb() {
		$this->fixture->updateDatabaseEntry($this->dummyObjectUpdateWithNumber);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'object_number',
			'tx_realty_objects',
			'object_number='.$this->dummyObjectUpdateWithNumber['object_number']
		);

		$this->assertTrue(
			$dbResult !== false
		);
	}

	public function testCreateNewDatabaseEntry() {
		$this->fixture->createNewDatabaseEntry($this->dummyObjectNew);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'object_number',
			'tx_realty_objects',
			'object_number='.$this->dummyObjectNew['object_number']
		);

		$this->assertTrue(
			$dbResult !== false
		);
	}

	public function testGetPropertyWithNonExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);

		$this->assertEquals(
			$this->fixture->getProperty('foo'),
			''
		);
	}

	public function testGetPropertyWithExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);

		$this->assertEquals(
			$this->fixture->getProperty('uid'),
			TX_REALTY_OBJECT_UID_1
		);
	}

	public function testGetPropertyWithExistingKeyWhenNoObjectLoaded() {
		$this->assertEquals(
			$this->fixture->getProperty('uid'),
			''
		);
	}

	public function testSetPropertyWhenKeyExists() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('uid', 'foo');

		$this->assertEquals(
			$this->fixture->getProperty('uid'),
			'foo'
		);
	}

	public function testSetPropertyWhenValueOfBoolean() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('uid', true);

		$this->assertEquals(
			$this->fixture->getProperty('uid'),
			true
		);
	}

	public function testSetPropertyWhenValueIsNumber() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('uid', 100);

		$this->assertEquals(
			$this->fixture->getProperty('uid'),
			100
		);
	}

	public function testSetPropertyWhenKeyNotExists() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('foo', 'bar');

		$this->assertEquals(
			$this->fixture->getProperty('foo'),
			''
		);
	}

	public function testSetPropertyWhenValueOfArrayNotSetsValue() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('uid', array('bar'));

		$this->assertEquals(
			$this->fixture->getProperty('uid'),
			TX_REALTY_OBJECT_UID_1
		);
	}

	public function testIsRealtyObjectDataEmptyReturnsFalseIfObjectLoaded() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->assertFalse(
			$this->fixture->isRealtyObjectDataEmpty()
		);
	}

	public function testIsRealtyObjectDataEmptyReturnsTrueIfNoObjectLoaded() {
		$this->assertTrue(
			$this->fixture->isRealtyObjectDataEmpty()
		);
	}

	public function testCheckMissingColumnNamesIfAllDbFieldsExistInRealtyObjectData() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);

		$this->assertEquals(
			$this->fixture->checkMissingColumnNames(),
			array()
		);
	}

	public function testCheckMissingColumnNamesIfSomeDbFieldsNotExistInRealtyObjectData() {
		$this->fixture->loadRealtyObject($this->dummyObjectWitoutObjectNumberAndTitle);
		$result = $this->fixture->checkMissingColumnNames();

		$this->assertContains(
			'title',
			$result
		);
		$this->assertContains(
			'object_number',
			$result
		);
	}

	public function testCheckMissigColumnNamesIfNoRealtyObjectIsLoaded() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			''
		);
		if ($dbResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
        	$fieldsInDb = array_keys($row);
		} else {
			$this->fail('There was an error with the database query.');
		}

		$this->assertEquals(
			$this->fixture->checkMissingColumnNames(),
			$fieldsInDb
		);
	}

	public function testDeleteSurplusFieldsDeletesNothingIfThereAreNone() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$realtyObjectBeforeDeleteSurplusFields = $this->fixture->getAllProperties();
		$this->fixture->DeleteSurplusFields();

		$this->assertEquals(
			$realtyObjectBeforeDeleteSurplusFields,
			$this->fixture->getAllProperties()
		);

	}

	public function testDeleteSurplusFieldsDeletesSurplusField() {
		$this->fixture->loadRealtyObject($this->dummyObjectWithInvalidField);
		$this->fixture->DeleteSurplusFields();

		$this->assertNotContains(
			'foobar',
			$this->fixture->getAllProperties()
		);
	}

	public function testCheckForRequiredFieldsIfNoFieldsAreRequired() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setRequiredFields(array());
		$this->assertEquals(
			$this->fixture->checkForRequiredFields(),
			array()
		);
	}

	public function testCheckForRequiredFieldsIfAllFieldsAreSet() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setRequiredFields(
			array(
				'uid',
				'object_number'
			)
		);

		$this->assertEquals(
			$this->fixture->checkForRequiredFields(),
			array()
		);
	}

	public function testCheckForRequiredFieldsIfOneFieldIsMissing() {
		$this->fixture->loadRealtyObject($this->dummyObjectWitoutObjectNumber);
		$this->fixture->setRequiredFields(
			array(
				'uid',
				'object_number',
			)
		);

		$this->assertContains(
			'object_number',
			$this->fixture->checkForRequiredFields()
		);
	}

	public function testPrepareInsertionAndInsertRelationsWritesUidOfInsertedPropertyToRealtyObjectData() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('pets', 'foo');
		$this->fixture->prepareInsertionAndInsertRelations(
			'pets',
			'tx_realty_pets'
		);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'tx_realty_pets',
			'title = "foo"'
		);
		if ($dbResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
			$result = $row['uid'];
		} else {
			$this->fail('There was an error with the database query.');
		}

		$this->assertEquals(
			$result,
			$this->fixture->getProperty('pets')
		);
	}

	public function testPrepareInsertionAndInsertRelationsReturnsEmptyStringIfPropertyNotExists() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);

		$this->assertEquals(
			$this->fixture->prepareInsertionAndInsertRelations(
				'pets',
				'tx_realty_pets'
			),
			''
		);
	}

	public function testInsertImageEntriesInsertsAndLinksNewEntry() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$image = array(
			array(
				'caption' => 'foo',
				'image' => 'bar'
			)
		);

		$this->fixture->insertImageEntries($image);

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_foreign',
			'tx_realty_objects_images_mm',
			'uid_local='.TX_REALTY_OBJECT_UID_1
		);
		if ($dbResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
			$mMresult = $row['uid_foreign'];
		} else {
			$this->fail('There was an error with the database query.');
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'caption, image',
			'tx_realty_images',
			'uid='.$mMresult
		);
		if ($dbResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
			$result = $row;
		} else {
			$this->fail('There was an error with the database query.');
		}

		$this->assertEquals(
			$result,
			$image[0]
		);
	}

	public function testInsertImageEntriesUpdatesExistingEntry() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$image = array(
			array(
				'caption' => 'foo',
				'image' => 'bar'
			)
		);
		$this->fixture->insertImageEntries($image);

		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$imageNew = array(
			array(
				'caption' => 'updated',
				'image' => 'bar'
			)
		);
		$this->fixture->insertImageEntries($imageNew);

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_foreign',
			'tx_realty_objects_images_mm',
			'uid_local='.TX_REALTY_OBJECT_UID_1
		);
		if ($dbResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
			$mMresult = $row['uid_foreign'];
		} else {
			$this->fail('There was an error with the database query.');
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'caption, image',
			'tx_realty_images',
			'uid='.$mMresult
		);
		if ($dbResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
			$result = $row;
		} else {
			$this->fail('There was an error with the database query.');
		}

		$this->assertEquals(
			$result,
			$imageNew[0]
		);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy realty objects in the DB.
	 */
	private function createDummyObjects() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_realty_objects',
			array(
				'uid' => TX_REALTY_OBJECT_UID_1,
				'title' => 'foo',
				'object_number' => '100000'
			)
		);
	}

	/**
	 * Deletes all dummy entries from the DB.
	 */
	private function deleteDummyEntries() {
		foreach (array(
			'tx_realty_objects' => 'uid',
			'tx_realty_pets' => 'uid',
			'tx_realty_images' => 'uid',
			'tx_realty_objects_images_mm' => 'uid_foreign'
		) as $table => $column) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$table,
				$column.' >= '.TX_REALTY_OBJECT_UID_1
			);
		}

		// inserting images causes an entry to 'sys_refindex'
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'sys_refindex',
			'ref_string = "uploads/tx_realty/bar"'
		);
	}

	/**
	 * Increases the  auto increment value for the table 'tx_realty_objects' to
	 * 100000. So records inserted during the tests can be deleted without
	 * touching the non-dummy records.
	 */
	private function increaseAutoIncrement() {
		foreach (array(
			'tx_realty_objects',
			'tx_realty_pets',
			'tx_realty_images'
		) as $table) {
			$GLOBALS['TYPO3_DB']->sql_query(
				'ALTER TABLE '.$table.' AUTO_INCREMENT='.TX_REALTY_OBJECT_UID_1.';'
			);
		}
	}

	/**
	 * Resets the auto increment value for the table 'tx_realty_objects' to the
	 * highest existing UID + 1. This is required to leave the table in the same
	 * status that it had before adding dummy records.
	 */
	private function resetAutoIncrement() {
		foreach (array(
			'tx_realty_objects',
			'tx_realty_pets',
			'tx_realty_images'
		) as $table) {
			$dbResult = $GLOBALS['TYPO3_DB']->sql_query(
				'SELECT MAX(uid) AS uid FROM '.$table.';'
			);
			if ($dbResult
				&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
			) {
				$newAutoIncrementValue = $row['uid'] + 1;
				$GLOBALS['TYPO3_DB']->sql_query(
					'ALTER TABLE '.$table.' AUTO_INCREMENT='
						.$newAutoIncrementValue.';'
				);
			}
		}
	}
}

?>
