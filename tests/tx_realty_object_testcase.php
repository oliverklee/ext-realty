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

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_testingFramework.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_configurationProxy.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_templatehelper.php');

class tx_realty_object_testcase extends tx_phpunit_testcase {
	private $fixture;
	private $testingFramework;
	private $templateHelper;

	private $objectUid = 0;	
	private $pageId = 0;
	private $otherPageId = 0;
	private static $objectNumber = '100000';
	private static $otherObjectNumber = '100001';
	
	public function setUp() {
		$this->fixture = new tx_realty_object_child(true);
		$this->templateHelper = t3lib_div::makeInstance(
			'tx_oelib_templatehelper'
		);
 		$this->templateHelper->init();
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->createDummyRecords();

		$this->fixture->setRequiredFields(array());
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForRealtyObjectsAndImages',
				$this->pageId
			);
	}

	public function tearDown() {
		$this->cleanUp();
		unset($this->fixture);
		unset($this->templateHelper);
		unset($this->testingFramework);
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
				array('uid' => $this->objectUid),
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
				array('object_number' => self::$objectNumber),
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
		$result = $this->fixture->loadDatabaseEntry($this->objectUid);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.$this->objectUid
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

	public function testLoadRealtyObjectIfAValidArrayIsGiven() {
		$this->fixture->loadRealtyObject(array('title' => 'foo'));

		$this->assertEquals(
			'foo',
			$this->fixture->getProperty('title')
		);
	}

	public function testLoadRealtyObjectIfAnArrayWithNonZeroUidIsGiven() {
		try {
			$this->fixture->loadRealtyObject(array('uid' => 1234));
		} catch (Exception $expected) {
			return;
		}

		// Fails the test if the expected exception was not raised above.
		$this->fail('The expected exception was not caught!');
	}

	public function testLoadRealtyObjectIfAnArrayWithZeroUidIsGiven() {
		try {
			$this->fixture->loadRealtyObject(array('uid' => 0));
		} catch (Exception $expected) {
			return;
		}

		// Fails the test if the expected exception was not raised above.
		$this->fail('The expected exception was not caught!');
	}

	public function testCreateNewDatabaseEntryIfAValidArrayIsGiven() {
		$this->fixture->createNewDatabaseEntry(
			array('object_number' => self::$otherObjectNumber)
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="'.self::$otherObjectNumber.'"'
					.$this->templateHelper->enableFields('tx_realty_objects')
			)
		);
	}

	public function testCreateNewDatabaseEntryIfAnArrayWithNonZeroUidIsGiven() {
		try {
			$this->fixture->createNewDatabaseEntry(array('uid' => 1234));
		} catch (Exception $expected) {
			return;
		}

		// Fails the test if the expected exception was not raised above.
		$this->fail('The expected exception was not caught!');
	}

	public function testCreateNewDatabaseEntryIfAnArrayWithZeroUidIsGiven() {
		try {
			$this->fixture->createNewDatabaseEntry(array('uid' => 0));
		} catch (Exception $expected) {
			return;
		}

		// Fails the test if the expected exception was not raised above.
		$this->fail('The expected exception was not caught!');
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
			'uid='.$this->objectUid
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
			'uid='.$this->objectUid
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
			'uid='.$this->objectUid
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

	public function testWriteToDatabaseUpdatesDatabaseEntryWhenUidAlreadyExistsInDb() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('title', 'new title');

		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'title',
			'tx_realty_objects',
			'object_number="'.self::$objectNumber.'"'
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
				'object_number' => self::$objectNumber
			)
		);

		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'title',
			'tx_realty_objects',
			'uid='.$this->objectUid
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
		$this->fixture->setRequiredFields(array('city'));
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$otherObjectNumber,
				'title' => 'new entry',
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

	public function testWriteToDatabaseCreatesNewDatabaseEntry() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="'.(self::$otherObjectNumber).'"'
					.$this->templateHelper->enableFields('tx_realty_objects')
			)
		);
	}

	public function testWriteToDatabaseCreatesNewRealtyRecordWithRealtyRecordPid() {
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$otherObjectNumber
			)
		);
		$this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_objects',
			'object_number='.self::$otherObjectNumber
				.$this->templateHelper->enableFields('tx_realty_objects')
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => $this->pageId),
			$result
		);
	}

	public function testWriteToDatabaseCreatesNewCityRecordWithAuxiliaryRecordPid() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForAuxiliaryRecords',
				$this->otherPageId
			);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_cities',
			'title="foo"'
				.$this->templateHelper->enableFields('tx_realty_cities')
		);

		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => $this->otherPageId),
			$result
		);
	}

	public function testWriteToDatabaseCreatesNewCityRecordWithRealtyRecordPidIfAuxiliaryRecordPidNotSet() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger('pidForAuxiliaryRecords', 0);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_cities',
			'title="foo"'
				.$this->templateHelper->enableFields('tx_realty_cities')
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => $this->pageId),
			$result
		);
	}

	public function testGetPropertyWithNonExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('foo')
		);
	}

	public function testGetPropertyWithExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			$this->objectUid,
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
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');

		$this->assertEquals(
			'foo',
			$this->fixture->getProperty('city')
		);
	}

	public function testSetPropertyWhenValueOfBoolean() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('pets', true);

		$this->assertEquals(
			true,
			$this->fixture->getProperty('pets')
		);
	}

	public function testSetPropertyWhenValueIsNumber() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('zip', 100);

		$this->assertEquals(
			100,
			$this->fixture->getProperty('zip')
		);
	}

	public function testSetPropertyWhenKeyNotExists() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('foo', 'bar');

		$this->assertEquals(
			'',
			$this->fixture->getProperty('foo')
		);
	}

	public function testSetPropertyDoesNotSetTheValueWhenTheValuesTypeIsInvalid() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('pets', array('bar'));

		$this->assertEquals(
			$this->objectUid,
			$this->fixture->getProperty('uid')
		);
	}

	public function testSetPropertyThrowsAnExeptionIfTheKeyToSetIsUid() {
		$this->fixture->loadRealtyObject($this->objectUid);
		
		try {
			$this->fixture->setProperty('uid', 12345);
		} catch (Exception $expected) {
			return;
		}

		// Fails the test if the expected exception was not raised above.
		$this->fail('The expected exception was not caught!');
	}

	public function testIsRealtyObjectDataEmptyReturnsFalseIfObjectLoaded() {
		$this->fixture->loadRealtyObject($this->objectUid);
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
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			array(),
			$this->fixture->checkMissingColumnNames()
		);
	}

	public function testCheckMissingColumnNamesIfSomeDbFieldsNotExistInRealtyObjectData() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'bar',
				'object_number' => $this->objectUid,
			)
		);
		$result = $this->fixture->checkMissingColumnNames();

		$this->assertNotContains(
			'object_number',
			$result
		);
		$this->assertNotContains(
			'title',
			$result
		);
	}

	public function testCheckMissigColumnNamesIfNoRealtyObjectIsLoaded() {
		$this->assertEquals(
			array_keys(
				$GLOBALS['TYPO3_DB']->admin_get_fields('tx_realty_objects')
			),
			$this->fixture->checkMissingColumnNames()
		);
	}

	public function testDeleteSurplusFieldsDeletesNothingIfThereAreNone() {
		$this->fixture->loadRealtyObject($this->objectUid);
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
				'object_number' => self::$objectNumber,
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
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			array(),
			$this->fixture->checkForRequiredFields()
		);
	}

	public function testCheckForRequiredFieldsIfAllFieldsAreSet() {
		$this->fixture->loadRealtyObject($this->objectUid);
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

	public function testCheckForRequiredFieldsIfOneRequriredFieldIsMissing() {
		$this->fixture->loadRealtyObject(array('title' => 'foo'));
		$this->fixture->setRequiredFields(array('object_number'));

		$this->assertContains(
			'object_number',
			$this->fixture->checkForRequiredFields()
		);
	}

	public function testPrepareInsertionAndInsertRelationsWritesUidOfInsertedPropertyToRealtyObjectData() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->prepareInsertionAndInsertRelations(
			'pets',
			'tx_realty_cities'
		);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'tx_realty_cities',
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
			$this->fixture->getProperty('city'),
			$result['uid']
		);
	}

	public function testPrepareInsertionAndInsertRelationsReturnsEmptyStringIfPropertyNotExists() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'',
			$this->fixture->prepareInsertionAndInsertRelations(
				'pets',
				'tx_realty_pets'
			)
		);
	}

	public function testInsertImageEntriesInsertsAndLinksNewEntry() {
		$this->fixture->loadRealtyObject($this->objectUid);
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
			'tx_realty_objects_images_mm.uid_local='.$this->objectUid
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
		$this->fixture->loadRealtyObject($this->objectUid);
		$image = array(
			array(
				'caption' => 'foo',
				'image' => 'bar'
			)
		);
		$this->fixture->insertImageEntries($image);

		$this->fixture->loadRealtyObject($this->objectUid);
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
			'tx_realty_objects_images_mm.uid_local='.$this->objectUid
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
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->insertImageEntries(
			array(
				array(
					'caption' => 'foo',
					'image' => 'bar'
				)
			)
		);

		$this->fixture->setProperty('deleted', 1);
		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_realty_images.deleted',
			'tx_realty_objects_images_mm, tx_realty_images',
			'tx_realty_objects_images_mm.uid_local='.$this->objectUid
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
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->insertImageEntries(
			array(
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
			)
		);

		$this->fixture->setProperty('deleted', 1);
		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_realty_images.deleted',
			'tx_realty_objects_images_mm, tx_realty_images',
			'tx_realty_objects_images_mm.uid_local='.$this->objectUid
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

	public function testWriteToDatabaseInsertsCorrectPageIdForNewRecord() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_objects',
			'object_number="'.self::$otherObjectNumber.'"'
				.$this->templateHelper->enableFields('tx_realty_objects')
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => $this->pageId),
			$result
		);
	}

	public function testUpdatingAnExistingRecordDoesNotChangeThePadeId() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('title', 'new title');

		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForRealtyObjectsAndImages',
				$this->otherPageId
			);
		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			'tx_realty_objects',
			'object_number="'.self::$objectNumber.'"'
				.$this->templateHelper->enableFields('tx_realty_objects')
		);
		if (!$dbResult) {
			$this->fail('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail('The database result was empty.');
		}

		$this->assertEquals(
			array('pid' => $this->pageId),
			$result
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testCreateANewRealtyRecordAlthoughTheSameRecordWasSetToDeletedInTheDatabase() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => self::$otherObjectNumber,
				'deleted' => 1,
			)
		);

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number='.self::$otherObjectNumber.' AND uid!='.$uid
					.$this->templateHelper->enableFields('tx_realty_objects')
			)
		);
	}

	public function testDeleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsSetExplicitly() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('deleted', 1);
		$this->fixture->writeToDatabase();

		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$objectNumber,
				'deleted' => 0
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number='.self::$objectNumber.' AND uid!='.$this->objectUid
					.$this->templateHelper->enableFields('tx_realty_objects')
			)
		);
	}

	public function testDeleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsNotSetExplicitly() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('deleted', 1);
		$this->fixture->writeToDatabase();

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number='.self::$objectNumber.' AND uid!='.$this->objectUid
					.$this->templateHelper->enableFields('tx_realty_objects')
			)
		);
	}

	public function testInsertDeleteAndReinsertAnImageRecord() {
		$imageUid = $this->testingFramework->createRecord(
			'tx_realty_images',
			array(
				'caption' => 'foo',
				'image' => 'bar',
				'deleted' => 1,
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->insertImageEntries(
			array(
				array(
					'caption' => 'foo',
					'image' => 'bar'
				)
			)
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_images',
				'caption="foo" AND image="bar" AND uid!='.$imageUid
					.$this->templateHelper->enableFields('tx_realty_images')
			)
		);
	}

	public function testInsertDeleteAutomaticallyAndReinsertAnImageRecord() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('deleted', 1);

		$image = array(
			array(
				'caption' => 'foo',
				'image' => 'bar'
			)
		);
		$this->fixture->insertImageEntries($image);

		$result = $this->testingFramework->getAssociativeDatabaseResult(
			$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'tx_realty_images',
				'caption="foo" AND image="bar"'
					.$this->templateHelper->enableFields('tx_realty_images')
			)		
		);

		// deletes the image
		$this->fixture->writeToDatabase();
		$this->fixture->insertImageEntries($image);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_images',
				'caption="foo" AND image="bar" AND uid!='.$result['uid']
					.$this->templateHelper->enableFields('tx_realty_images')
			)
		);
	}

	public function testRecreateAnAuxiliaryRecord() {
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities',
			array(
				'title' => 'foo',
				'deleted' => 1,
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_cities',
				'title="foo" AND uid!='.$cityUid
					.$this->templateHelper->enableFields('tx_realty_cities')
			)
		);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy system folders and realty objects in the DB.
	 */
	private function createDummyRecords() {
		$this->pageId = $this->testingFramework->createSystemFolder();
		$this->otherPageId = $this->testingFramework->createSystemFolder();
		$this->objectUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => 'foo',
				'object_number' => self::$objectNumber,
				'pid' => $this->pageId
			)
		);
	}
	
	/**
	 * Cleans up the tables in which dummy records are created during the tests.
	 */
	private function cleanUp() {
		foreach (array(
			'tx_realty_objects',
			'tx_realty_objects_images_mm',
			'tx_realty_images',
			'tx_realty_cities'
		) as $table) {
			$this->testingFramework->markTableAsDirty($table);
		}
		$this->testingFramework->cleanUp();
		
		// Inserting images causes an entry to 'sys_refindex' which is currently
		// not cleaned up automatically by the testing framework.
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'sys_refindex',
			'ref_string = "uploads/tx_realty/bar"'
		);
	}
}

?>
