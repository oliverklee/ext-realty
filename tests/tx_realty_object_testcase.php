<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2008 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('realty').'tests/fixtures/class.tx_realty_object_child.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_configurationProxy.php');

define('TX_REALTY_OBJECT_UID_1', 100000);
define('TX_REALTY_OBJECT_NUMBER_1', '100000');
define('TX_REALTY_PID_1', 100000);
define('TX_REALTY_PID_2', 100001);

class tx_realty_object_testcase extends tx_phpunit_testcase {
	private $fixture;

	public function setUp() {
		$this->fixture = new tx_realty_object_child();
		$this->increaseAutoIncrement();
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForRealtyObjectsAndImages',
				TX_REALTY_PID_1
			);
		$this->createDummyObject();
	}

	public function tearDown() {
		unset($this->fixture);
		$this->deleteDummyEntries();
		$this->resetAutoIncrement();
	}

	public function testRecordExistsInDatabaseIfNoExistingUidGiven() {
		$this->assertFalse(
			$this->fixture->recordExistsInDatabase(
				array('uid' => '99999'),
				'any_alternative_key'
			)
		);
	}

	public function testRecordExistsInDatabaseIfExistingUidGiven() {
		$this->assertTrue(
			$this->fixture->recordExistsInDatabase(
				array('uid' => TX_REALTY_OBJECT_UID_1),
				'any_alternative_key'
			)
		);
	}

	public function testRecordExistsInDatabaseIfNoExistingObjectNumberGiven() {
		$this->assertFalse(
			$this->fixture->recordExistsInDatabase(
				array('object_number' => '99999'),
				'object_number'
			)
		);
	}

	public function testRecordExistsInDatabaseIfExistingObjectNumberGiven() {
		$this->assertTrue(
			$this->fixture->recordExistsInDatabase(
				array('object_number' => TX_REALTY_OBJECT_NUMBER_1),
				'object_number'
			)
		);
	}

	public function testRecordExistsInDatabaseIfKeyNotExistsInGivenDataArray() {
		$this->assertFalse(
			$this->fixture->recordExistsInDatabase(
				array('key' => 'value'),
				'other_key'
			)
		);
	}

	public function testLoadDatabaseEntryWithValidUid() {
		$result = $this->fixture->loadDatabaseEntry(TX_REALTY_OBJECT_UID_1);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
			);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$expectedResult = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$expectedResult) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			$expectedResult,
			$result
		);
	}

	public function testLoadDatabaseEntryWithInvalidUid() {
		$this->assertEquals(
			array(),
			$this->fixture->loadDatabaseEntry('99999')
		);
	}

	public function testGetDataTypeWhenArrayGiven() {
		$this->assertEquals(
			'array',
			$this->fixture->getDataType(array('foo'))
		);
	}

	public function testGetDataTypeWhenIntegerGiven() {
		$this->assertEquals(
			'uid',
			$this->fixture->getDataType(1)
		);
	}

	public function testGetDataTypeWhenDatabaseResultGiven() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
		);

		$this->assertEquals(
			'dbResult',
			$this->fixture->getDataType($dbResult)
		);
	}

	public function testFetchDatabaseResultFromValidStream() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$expectedResult = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$expectedResult) {
			$this->fail('The database result was empty.');
		}

		// dbResult can be fetched only once, so the query is needed again.
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
		);
		$resultToCheck = $this->fixture->fetchDatabaseResult($dbResult);

		$this->assertEquals(
			$expectedResult,
			$resultToCheck
		);
	}

	public function testFetchDatabaseResultIfDbResultIsFalse() {
		$dbResult = false;
		$resultToCheck = $this->fixture->fetchDatabaseResult($dbResult);

		$this->assertEquals(
			array(),
			$resultToCheck
		);
	}

	public function testWriteToDatabaseUpdatesDatabaseEntryWhenUidExistsInDb() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'uid' => TX_REALTY_OBJECT_UID_1,
				'object_number' => TX_REALTY_OBJECT_NUMBER_1
			)
		);

		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'title',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('title' => 'new title'),
			$result
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseUpdatesDatabaseEntryWhenObjectNumberExistsInDb() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => TX_REALTY_OBJECT_NUMBER_1
			)
		);

		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'title',
			'tx_realty_objects',
			'uid='.TX_REALTY_OBJECT_UID_1
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('title' => 'new title'),
			$result
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseReturnsRequiredFieldsMessageIfTheRequiredFieldsAreNotSet() {
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => (TX_REALTY_OBJECT_NUMBER_1 + 1),
				'title' => 'new entry',
				'uid' => (TX_REALTY_OBJECT_UID_1 + 1)
			)
		);

		$this->assertEquals(
			'message_fields_required',
			$this->fixture->writeToDatabase()
		);
	}

	public function testWriteToDatabaseReturnsObjectNotLoadedMessageIfTheCurrentObjectIsEmpty() {
		$this->fixture->loadRealtyObject(array());

		$this->assertEquals(
			'message_object_not_loaded',
			$this->fixture->writeToDatabase()
		);
	}

	public function testCreateNewDatabaseEntry() {
		$this->fixture->createNewDatabaseEntry(
			array(
				'object_number' => (TX_REALTY_OBJECT_NUMBER_1 + 1),
				'title' => 'new entry',
				'uid' => (TX_REALTY_OBJECT_UID_1 + 1)
			)
		);

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'title',
			'tx_realty_objects',
			'uid='.(TX_REALTY_OBJECT_UID_1 + 1)
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('title' => 'new entry'),
			$result
		);
	}

	public function testCreateNewDatabaseEntryCreatesRealtyRecordWithRealtyRecordsPid() {
		$this->fixture->createNewDatabaseEntry(
			array(
				'object_number' => (TX_REALTY_OBJECT_NUMBER_1 + 1),
				'uid' => (TX_REALTY_OBJECT_UID_1 + 1)
			)
		);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_objects',
			'uid='.(TX_REALTY_OBJECT_UID_1 + 1)
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => TX_REALTY_PID_1),
			$result
		);
	}

	public function testCreateNewDatabaseEntryCreatesPetsRecordWithAuxiliaryRecordsPid() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForAuxiliaryRecords',
				TX_REALTY_PID_2
			);

		$this->fixture->createNewDatabaseEntry(
			array(
				'uid' => TX_REALTY_OBJECT_UID_1
			),
			'tx_realty_pets'

		);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_pets',
			'uid='.TX_REALTY_OBJECT_UID_1
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => (TX_REALTY_PID_2)),
			$result
		);
	}

	public function testCreateNewDatabaseEntryCreatesPetsRecordWithRealtyRecordsPidIfAuxiliaryRecordPidNotSet() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger('pidForAuxiliaryRecords', 0);

		$this->fixture->createNewDatabaseEntry(
			array(
				'uid' => TX_REALTY_OBJECT_UID_1
			),
			'tx_realty_pets'

		);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_pets',
			'uid='.TX_REALTY_OBJECT_UID_1
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => TX_REALTY_PID_1),
			$result
		);
	}

	public function testGetPropertyWithNonExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('foo')
		);
	}

	public function testGetPropertyWithExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);

		$this->assertEquals(
			TX_REALTY_OBJECT_UID_1,
			$this->fixture->getProperty('uid')
		);
	}

	public function testGetPropertyWithExistingKeyWhenNoObjectLoaded() {
		$this->assertEquals(
			'',
			$this->fixture->getProperty('uid')
		);
	}

	public function testSetPropertyWhenKeyExists() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('uid', 'foo');

		$this->assertEquals(
			'foo',
			$this->fixture->getProperty('uid')
		);
	}

	public function testSetPropertyWhenValueOfBoolean() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('uid', true);

		$this->assertEquals(
			true,
			$this->fixture->getProperty('uid')
		);
	}

	public function testSetPropertyWhenValueIsNumber() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('uid', 100);

		$this->assertEquals(
			100,
			$this->fixture->getProperty('uid')
		);
	}

	public function testSetPropertyWhenKeyNotExists() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('foo', 'bar');

		$this->assertEquals(
			'',
			$this->fixture->getProperty('foo')
		);
	}

	public function testSetPropertyWhenValueOfArrayNotSetsValue() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);
		$this->fixture->setProperty('uid', array('bar'));

		$this->assertEquals(
			TX_REALTY_OBJECT_UID_1,
			$this->fixture->getProperty('uid')
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
			array(),
			$this->fixture->checkMissingColumnNames()
		);
	}

	public function testCheckMissingColumnNamesIfSomeDbFieldsNotExistInRealtyObjectData() {
		$this->fixture->loadRealtyObject(array('uid' => TX_REALTY_OBJECT_UID_1));
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
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array_keys($result),
			$this->fixture->checkMissingColumnNames()
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
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'bar',
				'uid' => TX_REALTY_OBJECT_UID_1,
				'foobar' => 'foobar'
			)
		);
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
			array(),
			$this->fixture->checkForRequiredFields()
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
			array(),
			$this->fixture->checkForRequiredFields()
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
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			$this->fixture->getProperty('pets'),
			$result['uid']
		);
	}

	public function testPrepareInsertionAndInsertRelationsReturnsEmptyStringIfPropertyNotExists() {
		$this->fixture->loadRealtyObject(TX_REALTY_OBJECT_UID_1);

		$this->assertEquals(
			'',
			$this->fixture->prepareInsertionAndInsertRelations(
				'pets',
				'tx_realty_pets'
			)
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
			'tx_realty_images.caption, tx_realty_images.image',
			'tx_realty_objects_images_mm, tx_realty_images',
			'tx_realty_objects_images_mm.uid_local='.TX_REALTY_OBJECT_UID_1
				.' AND tx_realty_images.uid=tx_realty_objects_images_mm.uid_foreign'
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			$image[0],
			$result
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
			'tx_realty_images.caption, tx_realty_images.image',
			'tx_realty_objects_images_mm, tx_realty_images',
			'tx_realty_objects_images_mm.uid_local='.TX_REALTY_OBJECT_UID_1
				.' AND tx_realty_images.uid=tx_realty_objects_images_mm.uid_foreign'
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			$imageNew[0],
			$result
		);
	}
	public function testDeleteFromDatabaseRemovesRelatedImage() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'bar',
				'uid' => TX_REALTY_OBJECT_UID_1,
				'deleted' => 1
			)
		);
		$image = array(
			array(
				'caption' => 'foo',
				'image' => 'bar'
			)
		);

		$this->fixture->insertImageEntries($image);
		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_realty_images.deleted',
			'tx_realty_objects_images_mm, tx_realty_images',
			'tx_realty_objects_images_mm.uid_local='.TX_REALTY_OBJECT_UID_1
				.' AND tx_realty_images.uid=tx_realty_objects_images_mm.uid_foreign'
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('deleted' => 1),
			$result
		);
		$this->assertEquals(
			'message_deleted_flag_set',
			$message
		);
	}

	public function testDeleteFromDatabaseRemovesSeveralRelatedImages() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'bar',
				'uid' => TX_REALTY_OBJECT_UID_1,
				'deleted' => 1
			)
		);
		$images = array(
			array(
				'caption' => 'foo1',
				'image' => 'bar1'
			),
			array(
				'caption' => 'foo2',
				'image' => 'bar2'
			),
			array(
				'caption' => 'foo3',
				'image' => 'bar3'
			)
		);

		$this->fixture->insertImageEntries($images);
		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_realty_images.deleted',
			'tx_realty_objects_images_mm, tx_realty_images',
			'tx_realty_objects_images_mm.uid_local='.TX_REALTY_OBJECT_UID_1
				.' AND tx_realty_images.uid=tx_realty_objects_images_mm.uid_foreign'
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))	{
			$result[] = $row['deleted'];
		}
		if (empty($result)) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array(1, 1, 1),
			$result
		);
		$this->assertEquals(
			'message_deleted_flag_set',
			$message
		);
	}

	public function testCreateNewDatabaseEntryInsertsCorrectPageIdForNewRecord() {
		$this->fixture->createNewDatabaseEntry(
			array(
				'title' => 'bar',
				'uid' => (TX_REALTY_OBJECT_UID_1 + 2),
			)
		);

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_objects',
			'uid='.(TX_REALTY_OBJECT_UID_1 + 2)
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => TX_REALTY_PID_1),
			$result
		);
	}

	public function testUpdatingAnExistingRecordDoesNotChangeThePadeId() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'uid' => TX_REALTY_OBJECT_UID_1,
				'object_number' => TX_REALTY_OBJECT_NUMBER_1
			)
		);

		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForRealtyObjectsAndImages',
				TX_REALTY_PID_2
			);
		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_objects',
			'uid='.(TX_REALTY_OBJECT_UID_1)
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => TX_REALTY_PID_1),
			$result
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testDeletingAnExistingRecordDoesNotChangeThePageId() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'bar',
				'uid' => TX_REALTY_OBJECT_UID_1,
				'deleted' => 1
			)
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForRealtyObjectsAndImages',
				TX_REALTY_PID_2
			);
		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_objects',
			'uid='.(TX_REALTY_OBJECT_UID_1)
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => TX_REALTY_PID_1),
			$result
		);
		$this->assertEquals(
			'message_deleted_flag_set',
			$message
		);
	}

	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy realty objects in the DB.
	 */
	private function createDummyObject() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_realty_objects',
			array(
				'uid' => TX_REALTY_OBJECT_UID_1,
				'title' => 'foo',
				'object_number' => TX_REALTY_OBJECT_NUMBER_1,
				'pid' => tx_oelib_configurationProxy::getInstance('realty')->
					getConfigurationValueInteger('pidForRealtyObjectsAndImages')

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
