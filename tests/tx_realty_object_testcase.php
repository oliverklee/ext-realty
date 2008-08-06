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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_testingFramework.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_configurationProxy.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_templatehelper.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'tests/fixtures/class.tx_realty_objectChild.php');

/**
 * Unit tests for the tx_realty_object class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_object_testcase extends tx_phpunit_testcase {
	/** @var	tx_realty_objectChild */
	private $fixture;
	/** @var	tx_oelib_testingFramework */
	private $testingFramework;
	/** @var	tx_oelib_templatehelper */
	private $templateHelper;

	/** UID of a dummy realty object */
	private $objectUid = 0;
	/** page UID of a dummy FE page */
	private $pageUid = 0;
	/** page UID of another dummy FE page */
	private $otherPageUid = 0;
	/** object number of a dummy realty object */
	private static $objectNumber = '100000';
	/** object number of a dummy realty object */
	private static $otherObjectNumber = '100001';

	public function setUp() {
		$this->fixture = new tx_realty_objectChild(true);
		$this->templateHelper = new tx_oelib_templatehelper();

		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->createDummyRecords();

		$this->fixture->setRequiredFields(array());
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForRealtyObjectsAndImages',
				$this->pageUid
			);
	}

	public function tearDown() {
		$this->cleanUpDatabase();
		unset($this->fixture, $this->templateHelper, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy system folders and realty objects in the DB.
	 */
	private function createDummyRecords() {
		$this->pageUid = $this->testingFramework->createSystemFolder();
		$this->otherPageUid = $this->testingFramework->createSystemFolder();
		$this->objectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'foo',
				'object_number' => self::$objectNumber,
				'pid' => $this->pageUid,
				'language' => 'foo',
				'openimmo_obid' => 'test-obid',
			)
		);
	}

	/**
	 * Cleans up the tables in which dummy records are created during the tests.
	 */
	private function cleanUpDatabase() {
		// Inserting images causes an entry to 'sys_refindex' which is currently
		// not cleaned up automatically by the testing framework.
		if (in_array(
			REALTY_TABLE_IMAGES, $this->testingFramework->getListOfDirtyTables()
		)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'sys_refindex', 'ref_string = "uploads/tx_realty/bar"'
			);
		}

		$this->testingFramework->cleanUp();
	}


	///////////////////////////////
	// Testing the realty object.
	///////////////////////////////

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
		$this->assertEquals(
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*', REALTY_TABLE_OBJECTS, 'uid='.$this->objectUid
				)
			),
			$this->fixture->loadDatabaseEntry($this->objectUid)
		);
	}

	public function testLoadDatabaseEntryWithInvalidUid() {
		$this->assertEquals(
			array(),
			$this->fixture->loadDatabaseEntry('99999')
		);
	}

	public function testLoadDatabaseEntryOfAnNonHiddenObjectIfOnlyVisibleAreAllowed() {
		$this->fixture->loadRealtyObject($this->objectUid, false);
		$this->assertEquals(
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*', REALTY_TABLE_OBJECTS, 'uid='.$this->objectUid
				)
			),
			$this->fixture->loadDatabaseEntry($this->objectUid)
		);
	}

	public function testLoadDatabaseEntryDoesNotLoadAHiddenObjectIfOnlyVisibleAreAllowed() {
		$this->fixture->loadRealtyObject($this->objectUid, false);
		$uid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('hidden' => 1)
		);
		$this->assertEquals(
			array(),
			$this->fixture->loadDatabaseEntry($uid)
		);
	}

	public function testLoadDatabaseEntryLoadsAHiddenObjectIfHiddenAreAllowed() {
		$this->fixture->loadRealtyObject($this->objectUid, true);
		$uid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('hidden' => 1)
		);
		$this->assertEquals(
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*', REALTY_TABLE_OBJECTS, 'uid='.$uid
				)
			),
			$this->fixture->loadDatabaseEntry($uid)
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

	public function testLoadRealtyObjectFromAnArrayWithNonZeroUidIsAllowed() {
 		$this->fixture->loadRealtyObject(array('uid' => 1234));
 	}

	public function testLoadRealtyObjectFromArrayWithZeroUidIsAllowed() {
		$this->fixture->loadRealtyObject(array('uid' => 0));
	}

	public function testLoadHiddenRealtyObjectIfHiddenObjectsAreNotAllowed() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->objectUid, array('hidden' => 1)
		);
		$this->fixture->loadRealtyObject($this->objectUid, false);

		$this->assertTrue(
			$this->fixture->isRealtyObjectDataEmpty()
		);
	}

	public function testLoadHiddenRealtyObjectIfHidddenObjectsAreAllowed() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->objectUid, array('hidden' => 1)
		);
		$this->fixture->loadRealtyObject($this->objectUid, true);

		$this->assertFalse(
			$this->fixture->isRealtyObjectDataEmpty()
		);
	}

	public function testCreateNewDatabaseEntryIfAValidArrayIsGiven() {
		$this->fixture->createNewDatabaseEntry(
			array('object_number' => self::$otherObjectNumber)
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="'.self::$otherObjectNumber.'"'
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testCreateNewDatabaseEntryIfAnArrayWithNonZeroUidIsGiven() {
		$this->setExpectedException(
			'Exception', 'The column "uid" must not be set in $realtyData.'
		);
		$this->fixture->createNewDatabaseEntry(array('uid' => 1234));
	}

	public function testCreateNewDatabaseEntryIfAnArrayWithZeroUidIsGiven() {
		$this->setExpectedException(
			'Exception', 'The column "uid" must not be set in $realtyData.'
		);
		$this->fixture->createNewDatabaseEntry(array('uid' => 0));
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
			REALTY_TABLE_OBJECTS,
			'uid='.$this->objectUid
		);

		$this->assertEquals(
			'dbResult',
			$this->fixture->getDataType($dbResult)
		);
	}

	public function testFetchDatabaseResultFromValidStream() {
		$expectedResult = $this->testingFramework->getAssociativeDatabaseResult(
			$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				REALTY_TABLE_OBJECTS,
				'uid='.$this->objectUid
			)
		);

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			REALTY_TABLE_OBJECTS,
			'uid='.$this->objectUid
		);
		$resultToCheck = $this->fixture->fetchDatabaseResult($dbResult);

		$this->assertEquals(
			$expectedResult,
			$resultToCheck
		);
	}

	public function testFetchDatabaseResultIfDbResultIsFalse() {
		$this->setExpectedException('Exception', DATABASE_QUERY_ERROR);
		$this->fixture->fetchDatabaseResult(false);
	}

	public function testLoadRealtyObjectByUidAlsoLoadsImages() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'realty_object_uid' => $this->objectUid
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			array(
				array('caption' => 'foo', 'image' => 'foo.jpg')
			),
			$this->fixture->getAllImageData()
		);
	}

	public function testLoadRealtyObjectByDatabaseResultAlsoLoadsImages() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'realty_object_uid' => $this->objectUid
			)
		);
		$this->fixture->loadRealtyObject(
			$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*', REALTY_TABLE_OBJECTS, 'uid=' . $this->objectUid
			)
		);

		$this->assertEquals(
			array(
				array('caption' => 'foo', 'image' => 'foo.jpg')
			),
			$this->fixture->getAllImageData()
		);
	}

	public function testWriteToDatabaseUpdatesEntryIfUidExistsInDb() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('title', 'new title');
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="'.self::$objectNumber.'" AND title="new title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseUpdatesEntryIfObjectMatchesObjectNumberLanguageAndObidOfADbEntry() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => 'foo',
				'openimmo_obid' => 'test-obid',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="new title"'
			)
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndObidExistOfADbEntryButNotLanguage() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => 'bar',
				'openimmo_obid' => 'test-obid',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber
			)
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndLanguageExistOfADbEntryButNotObid() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => 'foo',
				'openimmo_obid' => 'another-test-obid',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber
			)
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndObidOfADbEntryAndLanguageIsEmpty() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => '',
				'openimmo_obid' => 'test-obid',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber
			)
		);
	}

	public function testWriteToDatabaseUpdatesEntryIfObjectMatchesObjectNumberOfADbEntryAndNoLanguageAndNoObidAreSet() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid . ' AND title="new title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseUpdatesEntryIfObjectMatchesObjectNumberAndObidOfADbEntryAndNoLanguageIsSet() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'openimmo_obid' => 'test-obid',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid . ' AND title="new title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseUpdatesEntryIfObjectMatchesObjectNumberAndLanguageOfADbEntryAndNoObidIsSet() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => 'foo',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid . ' AND title="new title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectNumberButNoLanguageExistsInTheDbAndLanguageIsSet() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
			)
		);
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
				'language' => 'bar',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="this is a title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectNumberButNoObidExistsInTheDbAndObidIsSet() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
			)
		);
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
				'openimmo_obid' => 'another-test-obid',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="this is a title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectNumberButObidExistsInTheDbAndObidIsSet() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
			)
		);
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
				'openimmo_obid' => 'another-test-obid',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="this is a title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectMatchesLanguageAndObidOfADbEntryButNotObjectNumber() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$otherObjectNumber,
				'openimmo_obid' => 'test-obid',
				'language' => 'foo',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'language="foo" AND openimmo_obid="test-obid"'
			)
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
				REALTY_TABLE_OBJECTS,
				'object_number="'.(self::$otherObjectNumber).'"'
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseCreatesNewRealtyRecordWithRealtyRecordPid() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->pageUid),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid',
					REALTY_TABLE_OBJECTS,
					'object_number='.self::$otherObjectNumber
						.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
				)
			)
		);
	}

	public function testWriteToDatabaseCanOverrideDefaultPidForNewRecords() {
		$systemFolderPid = $this->testingFramework->createSystemFolder();

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase($systemFolderPid);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number='.self::$otherObjectNumber
				.' AND pid='.$systemFolderPid
				.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseUpdatesAndCannotOverrideDefaultPid() {
		$systemFolderPid = $this->testingFramework->createSystemFolder();
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber)
		);
		$this->fixture->writeToDatabase($systemFolderPid);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid='.$this->objectUid
				.' AND pid='.$this->pageUid
			)
		);
	}

	public function testWriteToDatabaseCreatesNewCityRecordWithAuxiliaryRecordPid() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForAuxiliaryRecords',
				$this->otherPageUid
			);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->otherPageUid),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid',
					REALTY_TABLE_CITIES,
					'title="foo"'
						.$this->templateHelper->enableFields(REALTY_TABLE_CITIES)
				)
			)
		);
	}

	public function testWriteToDatabaseCreatesNewCityRecordWithRealtyRecordPidIfAuxiliaryRecordPidNotSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger('pidForAuxiliaryRecords', 0);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->pageUid),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid',
					REALTY_TABLE_CITIES,
					'title="foo"'
						.$this->templateHelper->enableFields(REALTY_TABLE_CITIES)
				)
			)
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

		$this->setExpectedException('Exception', 'The key must not be "uid".');
		$this->fixture->setProperty('uid', 12345);
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
				$GLOBALS['TYPO3_DB']->admin_get_fields(REALTY_TABLE_OBJECTS)
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
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertTrue(
			$this->fixture->getProperty('city') > 0
		);
	}

	public function testPrepareInsertionAndInsertRelationsInsertsPropertyIntoItsTable() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				 REALTY_TABLE_CITIES, 'is_dummy_record=1'
			)
		);
	}

	public function testPrepareInsertionAndInsertRelationsCreatesRelationToAlreadyExistingPropertyWithMatchingPid() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForAuxiliaryRecords', $this->otherPageUid
			);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => 'test city', 'pid' => $this->otherPageUid)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'test city');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			$cityUid,
			$this->fixture->getProperty('city')
		);
	}

	public function testPrepareInsertionAndInsertRelationsCreatesRelationToAlreadyExistingPropertyWithMismatchingPid() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForAuxiliaryRecords', ($this->otherPageUid +1)
			);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => 'test city', 'pid' => $this->otherPageUid)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'test city');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			$cityUid,
			$this->fixture->getProperty('city')
		);
	}

	public function testPrepareInsertionAndInsertDoesNotUpdateThePidOfAnAlreadyExistingPropertyForMismatchingPids() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForAuxiliaryRecords', ($this->otherPageUid +1)
			);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => 'test city', 'pid' => $this->otherPageUid)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'test city');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				 REALTY_TABLE_CITIES,
				'uid=' . $cityUid . ' AND pid='. $this->otherPageUid
			)
		);
	}

	public function testPrepareInsertionAndInsertRelationsDoesNotCreateARecordForAnInteger() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', '12345');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				 REALTY_TABLE_CITIES, 'is_dummy_record=1'
			)
		);
	}

	public function testPrepareInsertionAndInsertRelationsDoesNotCreateARecordForZeroPropertyFromTheDatabase() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				 REALTY_TABLE_CITIES, 'is_dummy_record=1'
			)
		);
	}

	public function testPrepareInsertionAndInsertRelationsDoesNotCreateARecordForZeroPropertyFromLoadedArray() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber, 'city' => 0)
		);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				 REALTY_TABLE_CITIES, 'is_dummy_record=1'
			)
		);
	}

	public function testPrepareInsertionAndInsertRelationsReturnsZeroForEmptyPropertyFetchedFromLoadedArray() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber, 'city' => '')
		);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				 REALTY_TABLE_CITIES, 'is_dummy_record=1'
			)
		);
	}

	public function testPrepareInsertionAndInsertRelationsReturnsZeroIfThePropertyNotExists() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber)
		);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				 REALTY_TABLE_CITIES, 'is_dummy_record=1'
			)
		);
	}

	public function testInsertImageEntriesInsertsNewEntryWithParentUid() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('image' => 'foo.jpg'),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'image',
					REALTY_TABLE_IMAGES,
					'realty_object_uid=' . $this->objectUid
				)
			)
		);
	}

	public function testInsertImageEntriesUpdatesExistingEntry() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();
		$this->fixture->addImageRecord('new caption', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('caption' => 'new caption', 'image' => 'foo.jpg'),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'caption, image',
					REALTY_TABLE_IMAGES,
					'realty_object_uid=' . $this->objectUid
				)
			)
		);
		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'image="foo.jpg" AND is_dummy_record=1'
			)
		);
	}

	public function testInsertImageEntriesDoesNotUpdateAnExistingEntryIfTheNewTitleIsEmpty() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();
		$this->fixture->addImageRecord('', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('caption' => 'foo', 'image' => 'foo.jpg'),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'caption, image',
					REALTY_TABLE_IMAGES,
					'realty_object_uid=' . $this->objectUid
				)
			)
		);
		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'image="foo.jpg" AND is_dummy_record=1'
			)
		);
	}

	public function testInsertImageEntriesInsertsNewImageWithFileNameAsTitleIfNoTitleIsSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('caption' => 'foo.jpg', 'image' => 'foo.jpg'),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'caption, image',
					REALTY_TABLE_IMAGES,
					'realty_object_uid=' . $this->objectUid
				)
			)
		);
	}

	public function testDeleteFromDatabaseRemovesRelatedImage() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();
		$this->fixture->setProperty('deleted', 1);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'deleted=1 AND is_dummy_record=1'
			)
		);
		$this->assertEquals(
			'message_deleted_flag_set',
			$message
		);
	}

	public function testDeleteFromDatabaseRemovesSeveralRelatedImages() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo1', 'foo1.jpg');
		$this->fixture->addImageRecord('foo2', 'foo2.jpg');
		$this->fixture->addImageRecord('foo3', 'foo3.jpg');
		$this->fixture->writeToDatabase();
		$this->fixture->setProperty('deleted', 1);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			3,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'deleted=1 AND is_dummy_record=1'
			)
		);
		$this->assertEquals(
			'message_deleted_flag_set',
			$message
		);
	}

	public function testWriteToDatabaseInsertsCorrectPageUidForNewRecord() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->pageUid),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid',
					REALTY_TABLE_OBJECTS,
					'object_number="'.self::$otherObjectNumber.'"'
						.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
				)
			)
		);
	}

	public function testWriteToDatabaseInsertsCorrectPageUidForNewRecordIfOverridePidIsSet() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase($this->otherPageUid);

		$this->assertEquals(
			array('pid' => $this->otherPageUid),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid',
					REALTY_TABLE_OBJECTS,
					'object_number="'.self::$otherObjectNumber.'"'
						.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
				)
			)
		);
	}

	public function testImagesReceiveTheCorrectPageUidIfOverridePidIsSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(array('caption' => 'foo', 'image' => 'bar'))
			)
		);
		$this->fixture->writeToDatabase($this->otherPageUid);

		$this->assertEquals(
			array('pid' => $this->otherPageUid),
			$this->testingFramework->getAssociativeDatabaseResult(
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid',
					REALTY_TABLE_IMAGES,
					'is_dummy_record=1'
				)
			)
		);
	}

	public function testUpdatingAnExistingRecordDoesNotChangeThePageUid() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('title', 'new title');

		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueInteger(
				'pidForRealtyObjectsAndImages',
				$this->otherPageUid
			);
		$message = $this->fixture->writeToDatabase();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid',
			REALTY_TABLE_OBJECTS,
			'object_number="'.self::$objectNumber.'"'
				.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
		);
		if (!$dbResult) {
			$this->fail(DATABASE_QUERY_ERROR);
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$result) {
			$this->fail(DATABASE_RESULT_ERROR);
		}

		$this->assertEquals(
			array('pid' => $this->pageUid),
			$result
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testCreateANewRealtyRecordAlthoughTheSameRecordWasSetToDeletedInTheDatabase() {
		$uid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => self::$otherObjectNumber,
				'deleted' => 1,
			)
		);

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber), true
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number='.self::$otherObjectNumber.' AND uid!='.$uid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseDeletesAnExistingNonHiddenRealtyRecordIfTheDeletedFlagIsSet() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('deleted', 1);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid .
					$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseDeletesAnExistingHiddenRealtyRecordIfTheDeletedFlagIsSet() {
		$this->fixture->loadRealtyObject($this->objectUid, true);
		$this->fixture->setProperty('hidden', 1);
		$this->fixture->writeToDatabase();

		$this->fixture->setProperty('deleted', 1);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid .
					$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS, 1)
			)
		);
	}

	public function testDeleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsSetExplicitly() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('deleted', 1);
		$this->fixture->writeToDatabase();

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber, 'deleted' => 0), true
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number='.self::$objectNumber.' AND uid!='.$this->objectUid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testDeleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsNotSetExplicitly() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('deleted', 1);
		$this->fixture->writeToDatabase();

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber), true
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number='.self::$objectNumber.' AND uid!='.$this->objectUid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testRecreateAnAuxiliaryRecord() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
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
				REALTY_TABLE_CITIES,
				'title="foo" AND uid!='.$cityUid
					.$this->templateHelper->enableFields(REALTY_TABLE_CITIES)
			)
		);
	}

	public function testAddImageRecordForLoadedObject() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');

		$this->assertEquals(
			array(
				array('caption' => 'foo', 'image' => 'foo.jpg')
			),
			$this->fixture->getAllImageData()
		);
	}

	public function testAddImageRecordForLoadedObjectReturnsKeyWhereTheRecordIsStored() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			0,
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);
	}

	public function testAddImageRecordIfNoObjectIsLoaded() {
		$this->setExpectedException(
			'Exception',
			'A realty record must be loaded before images can be appended.'
		);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
	}

	public function testMarkImageRecordAsDeleted() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);

		$this->assertEquals(
			array(
				array('caption' => 'foo', 'image' => 'foo.jpg', 'deleted' => 1)
			),
			$this->fixture->getAllImageData()
		);
	}

	public function testMarkImageRecordAsDeletedIfNoObjectIsLoaded() {
		$this->setExpectedException(
			'Exception',
			'A realty record must be loaded before images can be appended.'
		);
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);
	}

	public function testMarkImageRecordAsDeletedForNonExistingRecord() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->setExpectedException(
			'Exception',
			'The image record does not exist.'
		);
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg') + 1
		);
	}

	public function testWriteToDatabaseMarksImageRecordToDeleteAsDeleted() {
		$imageUid = $this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'realty_object_uid' => $this->objectUid
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(0);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'uid='.$imageUid.' AND deleted=1'
			)
		);
	}

	public function testWriteToDatabaseCreatesNewImageRecordIfTheSameRecordExistsButIsDeleted() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'realty_object_uid' => $this->objectUid,
				'deleted' => 1,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'image="foo.jpg" AND is_dummy_record=1'
			)
		);
	}

	public function testWriteToDatabaseNotAddsImageRecordWithDeletedFlagSet() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'is_dummy_record=1 AND deleted=1'
			)
		);
	}


	/////////////////////////////////////
	// Tests for processing owner data.
	/////////////////////////////////////

	public function testUidOfFeUserWithMatchingAnidIsAddedAsOwnerForExistingObjectIfAddingTheOwnerIsAllowed() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, true);

		$this->assertEquals(
			$feUserUid,
			$this->fixture->getProperty('owner')
		);
	}

	public function testUidOfFeUserWithMatchingAnidIsAddedAsOwnerForNewObjectIfAddingTheOwnerIsAllowed() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject(array('openimmo_anid' => 'test anid'));
		$this->fixture->writeToDatabase(0, true);

		$this->assertEquals(
			$feUserUid,
			$this->fixture->getProperty('owner')
		);
	}

	public function testUidOfFeUserWithMatchingAnidIsNotAddedAsOwnerIfThisIsForbidden() {
		$this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, false);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('owner')
		);
	}

	public function testNoOwnerIsAddedForARealtyRecordWithoutOpenImmoAnid() {
		$this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->writeToDatabase(0, true);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('owner')
		);
	}

	public function testOwnerIsNotChangedAlthoughTheAnidOfARecordIsUpdatedAndDoesNotMatchAnymore() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'test anid 1')
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid 1');
		$this->fixture->writeToDatabase(0, true);
		$this->fixture->setProperty('openimmo_anid', 'test anid 2');
		$this->fixture->writeToDatabase(0, true);

		$this->assertEquals(
			$feUserUid,
			$this->fixture->getProperty('owner')
		);
		$this->assertEquals(
			'test anid 2',
			$this->fixture->getProperty('openimmo_anid')
		);
	}

	public function testOwnerIsNotChangedAlthoughTheAnidOfARecordIsUpdatedAndMatchesAnotherFeUser() {
		$feUserGroup = $this->testingFramework->createFrontEndUserGroup();
		$uidOfFeUserOne = $this->testingFramework->createFrontEndUser(
			$feUserGroup, array('tx_realty_openimmo_anid' => 'test anid 1')
		);
		$this->testingFramework->createFrontEndUser(
			$feUserGroup, array('tx_realty_openimmo_anid' => 'test anid 2')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid 1');
		$this->fixture->writeToDatabase(0, true);
		$this->fixture->setProperty('openimmo_anid', 'test anid 2');
		$this->fixture->writeToDatabase(0, true);

		$this->assertEquals(
			$uidOfFeUserOne,
			$this->fixture->getProperty('owner')
		);
		$this->assertEquals(
			'test anid 2',
			$this->fixture->getProperty('openimmo_anid')
		);
	}

	public function testUseFeUserDataFlagIsSetIfThisOptionIsEnabledByConfiguration() {
		$this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'test anid')
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueBoolean(
				'useFrontEndUserDataAsContactDataForImportedRecords', true
			);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, true);

		$this->assertEquals(
			1,
			$this->fixture->getProperty('contact_data_source')
		);
	}

	public function testUseFeUserDataFlagIsNotSetIfThisOptionIsDisabledByConfiguration() {
		$this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'test anid')
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueBoolean(
				'useFrontEndUserDataAsContactDataForImportedRecords', false
			);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, true);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('contact_data_source')
		);
	}

	public function testUseFeUserDataFlagIsNotSetIfNoOwerWasSetAlthoughOptionIsEnabledByConfiguration() {
		$this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'test anid')
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueBoolean(
				'useFrontEndUserDataAsContactDataForImportedRecords', true
			);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->writeToDatabase(0, true);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('contact_data_source')
		);
	}
}
?>