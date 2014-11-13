<?php
/**
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Model_RealtyObjectTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Model_RealtyObjectChild
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var tx_oelib_templatehelper
	 */
	private $templateHelper;

	/**
	 * @var integer UID of a dummy realty object
	 */
	private $objectUid = 0;
	/**
	 * @var integer page UID of a dummy FE page
	 */
	private $pageUid = 0;
	/**
	 * @var integer page UID of another dummy FE page
	 */
	private $otherPageUid = 0;
	/**
	 * @var string object number of a dummy realty object
	 */
	private static $objectNumber = '100000';
	/**
	 * @var string object number of a dummy realty object
	 */
	private static $otherObjectNumber = '100001';

	/**
	 * @var array
	 */
	private $configurationVariablesBackup = array();

	/**
	 * @var integer static_info_tables UID of Germany
	 */
	const DE = 54;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->createDummyRecords();

		tx_oelib_MapperRegistry::getInstance()
			->activateTestingMode($this->testingFramework);

		$this->templateHelper = $this->getMock(
			'tx_oelib_templatehelper', array('hasConfValueString', 'getConfValueString')
		);

		$this->fixture = new tx_realty_Model_RealtyObjectChild(TRUE);

		$this->fixture->setRequiredFields(array());
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForRealtyObjectsAndImages', $this->pageUid);

		$this->configurationVariablesBackup = $GLOBALS['TYPO3_CONF_VARS'];
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'jpg,tif,tiff,pdf,png,ps,gif';
	}

	public function tearDown() {
		$GLOBALS['TYPO3_CONF_VARS'] = $this->configurationVariablesBackup;

		$this->cleanUpDatabase();

		unset($this->fixture, $this->templateHelper, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy system folders and realty objects in the DB.
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	private function cleanUpDatabase() {
		// Inserting images causes an entry to 'sys_refindex' which is currently
		// not cleaned up automatically by the testing framework.
		if (in_array(
			REALTY_TABLE_IMAGES, $this->testingFramework->getListOfDirtyTables()
		)) {
			tx_oelib_db::delete(
				'sys_refindex', 'ref_string = "' . tx_realty_Model_Image::UPLOAD_FOLDER . 'bar"'
			);
		}

		$this->testingFramework->cleanUp();
	}

	/**
	 * Loads a realty object into the fixture and sets the owner of this object.
	 *
	 * @param integer $ownerSource
	 *        the source of the owner data for the object,
	 *        must be REALTY_CONTACT_FROM_OWNER_ACCOUNT or REALTY_CONTACT_FROM_REALTY_OBJECT
	 * @param array $userData
	 *        additional data which should be stored into the owners data, may be empty
	 * @param array $additionalObjectData
	 *        additional data which should be stored into the object, may be empty
	 *
	 * @return void
	 */
	private function loadRealtyObjectAndSetOwner(
		$ownerSource,
		array $userData = array() ,
		array $additionalObjectData = array()
	) {
		$objectData = array_merge(
			$additionalObjectData,
			array(
				'contact_data_source' => $ownerSource,
				'owner' =>
					tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
						->getLoadedTestingModel($userData)->getUid(),
			)
		);

		$this->fixture->loadRealtyObject($objectData);
	}


	///////////////////////////////
	// Testing the realty object.
	///////////////////////////////

	/**
	 * @test
	 */
	public function recordExistsInDatabaseIfNoExistingObjectNumberGiven() {
		$this->assertFalse(
			$this->fixture->recordExistsInDatabase(
				array('object_number' => '99999')
			)
		);
	}

	/**
	 * @test
	 */
	public function recordExistsInDatabaseIfExistingObjectNumberGiven() {
		$this->assertTrue(
			$this->fixture->recordExistsInDatabase(
				array('object_number' => self::$objectNumber)
			)
		);
	}

	/**
	 * @test
	 */
	public function loadDatabaseEntryWithValidUid() {
		$this->assertEquals(
			tx_oelib_db::selectSingle(
				'*', REALTY_TABLE_OBJECTS, 'uid = ' . $this->objectUid
			),
			$this->fixture->loadDatabaseEntry($this->objectUid)
		);
	}

	/**
	 * @test
	 */
	public function loadDatabaseEntryWithInvalidUid() {
		$this->assertEquals(
			array(),
			$this->fixture->loadDatabaseEntry('99999')
		);
	}

	/**
	 * @test
	 */
	public function loadDatabaseEntryOfAnNonHiddenObjectIfOnlyVisibleAreAllowed() {
		$this->fixture->loadRealtyObject($this->objectUid, FALSE);
		$this->assertEquals(
			tx_oelib_db::selectSingle(
				'*', REALTY_TABLE_OBJECTS, 'uid = ' . $this->objectUid
			),
			$this->fixture->loadDatabaseEntry($this->objectUid)
		);
	}

	/**
	 * @test
	 */
	public function loadDatabaseEntryDoesNotLoadAHiddenObjectIfOnlyVisibleAreAllowed() {
		$this->fixture->loadRealtyObject($this->objectUid, FALSE);
		$uid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('hidden' => 1)
		);
		$this->assertEquals(
			array(),
			$this->fixture->loadDatabaseEntry($uid)
		);
	}

	/**
	 * @test
	 */
	public function loadDatabaseEntryLoadsAHiddenObjectIfHiddenAreAllowed() {
		$this->fixture->loadRealtyObject($this->objectUid, TRUE);
		$uid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('hidden' => 1)
		);
		$this->assertEquals(
			tx_oelib_db::selectSingle(
				'*', REALTY_TABLE_OBJECTS, 'uid = ' . $uid
			),
			$this->fixture->loadDatabaseEntry($uid)
		);
	}

	/**
	 * @test
	 */
	public function getDataTypeWhenArrayGiven() {
		$this->assertEquals(
			'array',
			$this->fixture->getDataType(array('foo'))
		);
	}

	/**
	 * @test
	 */
	public function loadRealtyObjectWithValidArraySetDataForGetProperty() {
		$this->fixture->loadRealtyObject(array('title' => 'foo'));

		$this->assertEquals(
			'foo',
			$this->fixture->getProperty('title')
		);
	}

	/**
	 * @test
	 */
	public function loadRealtyObjectFromAnArrayWithNonZeroUidIsAllowed() {
		$this->fixture->loadRealtyObject(array('uid' => 1234));
	}

	/**
	 * @test
	 */
	public function loadRealtyObjectFromArrayWithZeroUidIsAllowed() {
		$this->fixture->loadRealtyObject(array('uid' => 0));
	}

	/**
	 * @test
	 */
	public function loadHiddenRealtyObjectIfHiddenObjectsAreNotAllowed() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->objectUid, array('hidden' => 1)
		);
		$this->fixture->loadRealtyObject($this->objectUid, FALSE);

		$this->assertTrue(
			$this->fixture->isEmpty()
		);
	}

	/**
	 * @test
	 */
	public function loadHiddenRealtyObjectIfHidddenObjectsAreAllowed() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->objectUid, array('hidden' => 1)
		);
		$this->fixture->loadRealtyObject($this->objectUid, TRUE);

		$this->assertFalse(
			$this->fixture->isEmpty()
		);
	}

	/**
	 * @test
	 */
	public function createNewDatabaseEntryIfAValidArrayIsGiven() {
		$this->fixture->createNewDatabaseEntry(
			array('object_number' => self::$otherObjectNumber)
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$otherObjectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function createNewDatabaseEntryForArrayWithNonZeroUidThrowsException() {
		$this->fixture->createNewDatabaseEntry(array('uid' => 1234));
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function createNewDatabaseEntryForArrayWithZeroUidThrowsException() {
		$this->fixture->createNewDatabaseEntry(array('uid' => 0));
	}

	/**
	 * @test
	 */
	public function getDataTypeWhenIntegerGiven() {
		$this->assertEquals(
			'uid',
			$this->fixture->getDataType(1)
		);
	}

	/**
	 * @test
	 */
	public function setDataSetsTheRealtyObjectsTitle() {
		$this->fixture->setData(array('title' => 'foo'));

		$this->assertEquals(
			'foo',
			$this->fixture->getTitle()
		);
	}

	/**
	 * Test concerning the title
	 */

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsEmptyString() {
		$this->fixture->setData(array());
		$this->assertSame(
			'',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getTitleReturnsTitle() {
		$title = 'A very nice house indeed.';
		$this->fixture->setData(array('title' => $title));

		$this->assertSame(
			$title,
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$this->fixture->setData(array());
		$this->fixture->setTitle('foo bar');

		$this->assertSame(
			'foo bar',
			$this->fixture->getTitle()
		);
	}


	////////////////////////////////
	// Tests concerning the images
	////////////////////////////////

	/**
	 * @test
	 */
	public function loadRealtyObjectByUidAlsoLoadsImages() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'foo',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataSetsTheImageDataForImageFromDatabase() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid
			)
		);
		$this->fixture->setData(array('uid' => $this->objectUid, 'images' => 1));

		$this->assertEquals(
			'foo',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataSetsImagePositionForImageFromDatabase() {
		$this->testingFramework->createRecord(
			'tx_realty_images',
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid,
				'position' => 4,
			)
		);
		$this->fixture->setData(array('uid' => $this->objectUid, 'images' => 1));

		$this->assertEquals(
			4,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function setDataSetsTheImageDataForImageFromArray() {
		$this->fixture->setData(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(
					array('caption' => 'test', 'image' => 'test.jpg')
				)
			)
		);

		$this->assertEquals(
			'test',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataWithDocumentAndImageSetsTheDataForImagesFromArray() {
		$this->fixture->setData(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(
					array('caption' => 'test image', 'image' => 'test.jpg')
				),
				'documents' => array(
					array('title' => 'test document', 'filename' => 'test.pdf')
				),
			)
		);

		$this->assertEquals(
			'test image',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getImagesReturnsTheCurrentObjectsImagesOrderedBySorting() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 2)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'second',
				'image' => 'second.jpg',
				'object' => $this->objectUid,
				'sorting' => 2,
			)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'first',
				'image' => 'first.jpg',
				'object' => $this->objectUid,
				'sorting' => 1,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$titles = array();
		foreach ($this->fixture->getImages() as $image) {
			$titles[] = $image->getTitle();
		}
		$this->assertEquals(
			array('first', 'second'),
			$titles
		);
	}

	/**
	 * @test
	 */
	public function getImagesReturnsTheCurrentObjectsImagesWithoutPdf() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 2)
		);
		$this->testingFramework->createRecord(
			'tx_realty_images',
			array(
				'caption' => 'pdf',
				'image' => 'foo.pdf',
				'object' => $this->objectUid,
				'sorting' => 1,
			)
		);
		$this->testingFramework->createRecord(
			'tx_realty_images',
			array(
				'caption' => 'jpg',
				'image' => 'foo.jpg',
				'object' => $this->objectUid,
				'sorting' => 2,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertSame(
			'jpg',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getImagesReturnsTheCurrentObjectsImagesWithoutPs() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 2)
		);
		$this->testingFramework->createRecord(
			'tx_realty_images',
			array(
				'caption' => 'ps',
				'image' => 'foo.ps',
				'object' => $this->objectUid,
				'sorting' => 1,
			)
		);
		$this->testingFramework->createRecord(
			'tx_realty_images',
			array(
				'caption' => 'jpg',
				'image' => 'foo.jpg',
				'object' => $this->objectUid,
				'sorting' => 2,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertSame(
			'jpg',
			$this->fixture->getImages()->first()->getTitle()
		);
	}


	///////////////////////////////////
	// Tests concerning the documents
	///////////////////////////////////

	/**
	 * @test
	 */
	public function loadRealtyObjectByUidLoadsDocuments() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 1)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => 'foo.pdf',
				'object' => $this->objectUid,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'foo',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataSetsTheDataForDocumentFromDatabase() {
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => 'foo.pdf',
				'object' => $this->objectUid,
			)
		);
		$this->fixture->setData(
			array('uid' => $this->objectUid, 'documents' => 1)
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataSetsTheDataForDocumentFromArray() {
		$this->fixture->setData(
			array(
				'object_number' => self::$otherObjectNumber,
				'documents' => array(
					array('title' => 'test', 'filename' => 'test.pdf')
				),
			)
		);

		$this->assertEquals(
			'test',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataWithDocumentAndImageSetsTheDataForDocumentFromArray() {
		$this->fixture->setData(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(
					array('caption' => 'test image', 'image' => 'test.jpg')
				),
				'documents' => array(
					array('title' => 'test document', 'filename' => 'test.pdf')
				),
			)
		);

		$this->assertEquals(
			'test document',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getDocumentsReturnsTheCurrentObjectsDocumentsOrderedBySorting() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 2)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'second',
				'filename' => 'second.pdf',
				'object' => $this->objectUid,
				'sorting' => 2,
			)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'first',
				'filename' => 'first.pdf',
				'object' => $this->objectUid,
				'sorting' => 1,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$titles = array();
		foreach ($this->fixture->getDocuments() as $document) {
			$titles[] = $document->getTitle();
		}
		$this->assertEquals(
			array('first', 'second'),
			$titles
		);
	}


	/////////////////////////////////////
	// Tests concerning writeToDatabase
	/////////////////////////////////////

	/**
	 * @test
	 */
	public function writeToDatabaseUpdatesEntryIfUidExistsInDb() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('title', 'new title');
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="new title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseUpdatesEntryIfObjectMatchesObjectNumberLanguageAndObidOfADbEntry() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndObidExistOfADbEntryButNotLanguage() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndLanguageExistOfADbEntryButNotObid() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndObidOfADbEntryAndLanguageIsEmpty() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseUpdatesEntryIfObjectMatchesObjectNumberOfADbEntryAndNoLanguageAndNoObidAreSet() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseUpdatesEntryIfObjectMatchesObjectNumberAndObidOfADbEntryAndNoLanguageIsSet() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseUpdatesEntryIfObjectMatchesObjectNumberAndLanguageOfADbEntryAndNoObidIsSet() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewEntryIfObjectNumberButNoLanguageExistsInTheDbAndLanguageIsSet() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewEntryIfObjectNumberButNoObidExistsInTheDbAndObidIsSet() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewEntryIfObjectNumberButObidExistsInTheDbAndObidIsSet() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewEntryIfObjectMatchesLanguageAndObidOfADbEntryButNotObjectNumber() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseReturnsRequiredFieldsMessageIfTheRequiredFieldsAreNotSet() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseReturnsObjectNotLoadedMessageIfTheCurrentObjectIsEmpty() {
		$this->fixture->loadRealtyObject(array());

		$this->assertEquals(
			'message_object_not_loaded',
			$this->fixture->writeToDatabase()
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewDatabaseEntry() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . (self::$otherObjectNumber) . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewDatabaseEntryForObjectWithQuotedData() {
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => '"' . self::$otherObjectNumber . '"',
				'openimmo_obid' => '"foo"',
				'title' => '"bar"'
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS, 'uid=' . $this->fixture->getUid()
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewRealtyRecordWithRealtyRecordPid() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->pageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_OBJECTS,
				'object_number = ' . self::$otherObjectNumber .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseCanOverrideDefaultPidForNewRecords() {
		$systemFolderPid = $this->testingFramework->createSystemFolder();

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase($systemFolderPid);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$otherObjectNumber .
					' AND pid=' . $systemFolderPid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseUpdatesAndCannotOverrideDefaultPid() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewCityRecordWithAuxiliaryRecordPid() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->otherPageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_CITIES,
				'title = "foo"' .
					tx_oelib_db::enableFields(REALTY_TABLE_CITIES)
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewCityRecordWithRealtyRecordPidIfAuxiliaryRecordPidNotSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', 0);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->pageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_CITIES,
				'title = "foo"' .
					tx_oelib_db::enableFields(REALTY_TABLE_CITIES)
			)
		);
	}

	/**
	 * @test
	 */
	public function getPropertyWithNonExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('foo')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyWithExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->set('city', 'foo');

		$this->assertEquals(
			'foo',
			$this->fixture->getProperty('city')
		);
	}

	/**
	 * @test
	 */
	public function setPropertyWhenKeyExists() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');

		$this->assertEquals(
			'foo',
			$this->fixture->getProperty('city')
		);
	}

	/**
	 * @test
	 */
	public function setPropertyWhenValueOfBoolean() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('pets', TRUE);

		$this->assertEquals(
			TRUE,
			$this->fixture->getProperty('pets')
		);
	}

	/**
	 * @test
	 */
	public function setPropertyWhenValueIsNumber() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('zip', 100);

		$this->assertEquals(
			100,
			$this->fixture->getProperty('zip')
		);
	}

	/**
	 * @test
	 */
	public function setPropertyWhenKeyNotExists() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('foo', 'bar');

		$this->assertEquals(
			'',
			$this->fixture->getProperty('foo')
		);
	}

	/**
	 * @test
	 */
	public function setPropertyDoesNotSetTheValueWhenTheValuesTypeIsInvalid() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('pets', array('bar'));

		$this->assertEquals(
			$this->objectUid,
			$this->fixture->getUid()
		);
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function setPropertyKeySetToUidThrowsException() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->fixture->setProperty('uid', 12345);
	}

	/**
	 * @test
	 */
	public function isEmptyWithObjectLoadedReturnsFalse() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->assertFalse(
			$this->fixture->isEmpty()
		);
	}

	/**
	 * @test
	 */
	public function isEmptyWithNoObjectLoadedReturnsTrue() {
		$this->assertTrue(
			$this->fixture->isEmpty()
		);
	}

	/**
	 * @test
	 */
	public function checkForRequiredFieldsIfNoFieldsAreRequired() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			array(),
			$this->fixture->checkForRequiredFields()
		);
	}

	/**
	 * @test
	 */
	public function checkForRequiredFieldsIfAllFieldsAreSet() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setRequiredFields(
			array(
				'title',
				'object_number'
			)
		);

		$this->assertEquals(
			array(),
			$this->fixture->checkForRequiredFields()
		);
	}

	/**
	 * @test
	 */
	public function checkForRequiredFieldsIfOneRequriredFieldIsMissing() {
		$this->fixture->loadRealtyObject(array('title' => 'foo'));
		$this->fixture->setRequiredFields(array('object_number'));

		$this->assertContains(
			'object_number',
			$this->fixture->checkForRequiredFields()
		);
	}

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsWritesUidOfInsertedPropertyToRealtyObjectData() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertTrue(
			$this->fixture->getProperty('city') > 0
		);
	}

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsInsertsPropertyIntoItsTable() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsInsertsPropertyWithQuotesInTitleIntoItsTable() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo "bar"');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsCreatesRelationToAlreadyExistingPropertyWithMatchingPid() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid);
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

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsCreatesRelationToAlreadyExistingPropertyWithMismatchingPid() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid + 1);
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

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertDoesNotUpdateThePidOfAnAlreadyExistingPropertyForMismatchingPids() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid + 1);
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

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsDoesNotCreateARecordForAnInteger() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', '12345');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsDoesNotCreateARecordForZeroPropertyFromTheDatabase() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsDoesNotCreateARecordForZeroPropertyFromLoadedArray() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber, 'city' => 0)
		);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsReturnsZeroForEmptyPropertyFetchedFromLoadedArray() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber, 'city' => '')
		);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	/**
	 * @test
	 */
	public function prepareInsertionAndInsertRelationsReturnsZeroIfThePropertyNotExists() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber)
		);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordInsertsNewEntryWithParentUid() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('image' => 'foo.jpg'),
			tx_oelib_db::selectSingle(
				'image',
				REALTY_TABLE_IMAGES,
				'object = ' . $this->objectUid
			)
		);
	}

	/**
	 * @test
	 */
	public function insertImageEntriesInsertsNewImageWithCaptionWithQuotationMarks() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo "bar"', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('image' => 'foo.jpg'),
			tx_oelib_db::selectSingle(
				'image',
				REALTY_TABLE_IMAGES,
				'object = ' . $this->objectUid
			)
		);
	}

	/**
	 * @test
	 */
	public function insertImageEntriesInsertsImageWithEmptyTitleIfNoTitleIsSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('caption' => '', 'image' => 'foo.jpg'),
			tx_oelib_db::selectSingle(
				'caption, image',
				REALTY_TABLE_IMAGES,
				'object = ' . $this->objectUid
			)
		);
	}

	/**
	 * @test
	 */
	public function deleteFromDatabaseRemovesRelatedImage() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();
		$this->fixture->setToDeleted();
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES, 'deleted = 1'
			)
		);
		$this->assertEquals(
			'message_deleted_flag_causes_deletion',
			$message
		);
	}

	/**
	 * @test
	 */
	public function deleteFromDatabaseRemovesSeveralRelatedImages() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo1', 'foo1.jpg');
		$this->fixture->addImageRecord('foo2', 'foo2.jpg');
		$this->fixture->addImageRecord('foo3', 'foo3.jpg');
		$this->fixture->writeToDatabase();
		$this->fixture->setToDeleted();
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			3,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES, 'deleted = 1'
			)
		);
		$this->assertEquals(
			'message_deleted_flag_causes_deletion',
			$message
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseInsertsCorrectPageUidForNewRecord() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->pageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_OBJECTS,
				'object_number = "' . self::$otherObjectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseInsertsCorrectPageUidForNewRecordIfOverridePidIsSet() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase($this->otherPageUid);

		$this->assertEquals(
			array('pid' => $this->otherPageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_OBJECTS,
				'object_number = "' . self::$otherObjectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 */
	public function imagesReceiveTheCorrectPageUidIfOverridePidIsSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(array('caption' => 'foo', 'image' => 'bar.jpg'))
			)
		);
		$this->fixture->writeToDatabase($this->otherPageUid);

		$this->assertEquals(
			array('pid' => $this->otherPageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_IMAGES,
				'is_dummy_record = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function updatingAnExistingRecordDoesNotChangeThePageUid() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('title', 'new title');

		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForRealtyObjectsAndImages', $this->otherPageUid);
		$message = $this->fixture->writeToDatabase();

		$result = tx_oelib_db::selectSingle(
			'pid',
			REALTY_TABLE_OBJECTS,
			'object_number = "' . self::$objectNumber . '"' .
				tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
		);

		$this->assertEquals(
			array('pid' => $this->pageUid),
			$result
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	/**
	 * @test
	 */
	public function createANewRealtyRecordAlthoughTheSameRecordWasSetToDeletedInTheDatabase() {
		$uid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => self::$otherObjectNumber,
				'deleted' => 1,
			)
		);

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber), TRUE
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$otherObjectNumber .
					' AND uid!=' . $uid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseDeletesAnExistingNonHiddenRealtyRecordIfTheDeletedFlagIsSet() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setToDeleted();
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseDeletesAnExistingHiddenRealtyRecordIfTheDeletedFlagIsSet() {
		$this->fixture->loadRealtyObject($this->objectUid, TRUE);
		$this->fixture->setProperty('hidden', 1);
		$this->fixture->writeToDatabase();

		$this->fixture->setToDeleted();
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS, 1)
			)
		);
	}

	/**
	 * @test
	 */
	public function deleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsSetExplicitly() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setToDeleted();
		$this->fixture->writeToDatabase();

		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);
		$realtyObject->setRequiredFields(array());
		$realtyObject->loadRealtyObject(
			array('object_number' => self::$objectNumber, 'deleted' => 0), TRUE
		);
		$realtyObject->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber .
					' AND uid!=' . $this->objectUid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 */
	public function deleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsNotSetExplicitly() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setToDeleted();
		$this->fixture->writeToDatabase();

		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);
		$realtyObject->setRequiredFields(array());
		$realtyObject->loadRealtyObject(
			array('object_number' => self::$objectNumber), TRUE
		);
		$realtyObject->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber .
					' AND uid!=' . $this->objectUid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 */
	public function loadingAnExistingRecordWithAnImageAndWritingItToTheDatabaseDoesNotDuplicateTheImage() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('object' => $this->objectUid, 'image' => 'test.jpg')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->writeToDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES, 'deleted = 0 AND image="test.jpg"'
			)
		);
	}

	/**
	 * @test
	 */
	public function loadingAnExistingRecordWithAnImageByArrayAndWritingItWithAnotherImageToTheDatabaseDeletesTheExistingImage() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('object' => $this->objectUid, 'image' => 'test.jpg')
		);
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$objectNumber,
				'images' => array(
					array('caption' => 'test', 'image' => 'test2.jpg')
				)
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES, 'deleted = 1 AND image="test.jpg"'
			)
		);
	}

	/**
	 * @test
	 */
	public function importRecordWithImageThatAlreadyExistsForAnotherRecordDoesNotChangeTheOriginalObjectUid() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'object' => $this->objectUid,
				'image' => 'test.jpg',
				'caption' => 'test',
			)
		);
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(
					array('caption' => 'test', 'image' => 'test.jpg')
				)
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES,
				'object=' . $this->objectUid . ' AND image="test.jpg"'
			)
		);
	}

	/**
	 * @test
	 */
	public function recreateAnAuxiliaryRecord() {
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
				'title="foo" AND uid!=' . $cityUid .
					tx_oelib_db::enableFields(REALTY_TABLE_CITIES)
			)
		);
	}


	////////////////////////////////////
	// Tests concerning addImageRecord
	////////////////////////////////////

	/**
	 * @test
	 */
	public function addImageRecordForLoadedObject() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');

		$this->assertEquals(
			'foo',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordForLoadedObjectReturnsKeyWhereTheRecordIsStored() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			0,
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException BadMethodCallException
	 */
	public function addImageRecordForNoObjectLoadedThrowsException() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->addImageRecord('foo', 'foo.jpg');
	}

	/**
	 * @test
	 */
	public function addImagesRecordsUpdatesTheNumberOfCurrentlyAppendedImagesForTheRealtyObject() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo1', 'foo1.jpg');
		$this->fixture->addImageRecord('foo2', 'foo2.jpg');
		$this->fixture->addImageRecord('foo3', 'foo3.jpg');

		$this->assertEquals(
			3,
			$this->fixture->getProperty('images')
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordByDefaultSetsPositionToZero() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');

		$this->assertEquals(
			0,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordCanSetPositionZero() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg', 0);

		$this->assertEquals(
			0,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordCanSetPositionOne() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg', 1);

		$this->assertEquals(
			1,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordCanSetPositionFour() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg', 4);

		$this->assertEquals(
			4,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordByDefaultSetsEmptyThumbnailFileName() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');

		$this->assertEquals(
			'',
			$this->fixture->getImages()->first()->getThumbnailFileName()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordCanSetNonEmptyThumbnailFileName() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg', 0, 'bar.jpg');

		$this->assertEquals(
			'bar.jpg',
			$this->fixture->getImages()->first()->getThumbnailFileName()
		);
	}


	//////////////////////////////////////////////
	// Tests concerning markImageRecordAsDeleted
	//////////////////////////////////////////////

	/**
	 * @test
	 */
	public function markImageRecordAsDeletedUpdatesTheNumberOfCurrentlyAppendedImagesForTheRealtyObject() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo1', 'foo1.jpg');
		$this->fixture->addImageRecord('foo2', 'foo2.jpg');
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);

		$this->assertEquals(
			2,
			$this->fixture->getProperty('images')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException BadMethodCallException
	 */
	public function markImageRecordAsDeletedForNoObjectLoadedThrowsException() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException tx_oelib_Exception_NotFound
	 */
	public function markImageRecordAsDeletedForNonExistingRecordThrowsException() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg') + 1
		);
	}


	/////////////////////////////////////////////////
	// Tests concerning writeToDatabase with images
	/////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function writeToDatabaseMarksImageRecordToDeleteAsDeleted() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$imageUid = $this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid
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

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewImageRecordIfTheSameRecordExistsButIsDeleted() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid,
				'deleted' => 1,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES, 'image = "foo.jpg"'
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseDeletesExistingImageFromTheFileSystem() {
		$fileName = $this->testingFramework->createDummyFile('foo.jpg');
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => basename($fileName),
				'object' => $this->objectUid
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(0);
		$this->fixture->writeToDatabase();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseNotAddsImageRecordWithDeletedFlagSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES, 'deleted = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function importANewRecordWithImagesAndTheDeletedFlagBeingSetReturnsMarkedAsDeletedMessageKey() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject(
			array('object_number' => 'foo-bar', 'deleted' => 1)
		);
		$this->fixture->addImageRecord('foo', 'foo.jpg');

		$this->assertEquals(
			'message_deleted_flag_set',
			$this->fixture->writeToDatabase()
		);
	}


	/////////////////////////////////
	// Tests concerning addDocument
	/////////////////////////////////

	/**
	 * @test
	 */
	public function numberOfAppendedDocumentsInitiallyIsZero() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('documents')
		);
	}

	/**
	 * @test
	 */
	public function addDocumentMakesDocumentAvailableViaGetDocuments() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addDocument('foo', 'foo.pdf');

		$this->assertEquals(
			'foo',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function addDocumentForFirstDocumentsReturnsZeroIndex() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			0,
			$this->fixture->addDocument('foo', 'foo.pdf')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException BadMethodCallException
	 */
	public function addDocumentForNoObjectLoadedThrowsException() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->addDocument('foo', 'foo.pdf');
	}

	/**
	 * @test
	 */
	public function addDocumentUpdatesTheNumberOfAppendedDocuments() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addDocument('foo1', 'foo1.pdf');
		$this->fixture->addDocument('foo2', 'foo2.pdf');
		$this->fixture->addDocument('foo3', 'foo3.pdf');

		$this->assertEquals(
			3,
			$this->fixture->getProperty('documents')
		);
	}


	////////////////////////////////////
	// Tests concerning deleteDocument
	////////////////////////////////////

	/**
	 * @test
	 */
	public function deleteDocumentUpdatesTheNumberOfAppendedDocuments() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addDocument('foo1', 'foo1.pdf');
		$this->fixture->addDocument('foo2', 'foo2.pdf');
		$this->fixture->deleteDocument(
			$this->fixture->addDocument('foo', 'foo.pdf')
		);

		$this->assertEquals(
			2,
			$this->fixture->getProperty('documents')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException BadMethodCallException
	 */
	public function deleteDocumentForNoObjectLoadedThrowsException() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->deleteDocument(
			$this->fixture->addDocument('foo', 'foo.pdf')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException tx_oelib_Exception_NotFound
	 */
	public function deleteDocumentForNonExistingRecordThrowsException() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);
		$documentKey = $this->fixture->addDocument('foo', 'foo.pdf') + 1;

		$this->fixture->deleteDocument($documentKey);
	}


	////////////////////////////////////////////////////
	// Tests concerning writeToDatabase with documents
	////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function writeToDatabaseMarksDocumentRecordToDeleteAsDeleted() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 1)
		);
		$documentUid = $this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => 'foo.pdf',
				'object' => $this->objectUid
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->deleteDocument(0);
		$this->fixture->writeToDatabase();

		$this->assertTrue(
			$this->testingFramework->existsRecord(
				'tx_realty_documents',
				'uid = ' . $documentUid . ' AND deleted = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewDocumentRecordIfTheSameRecordExistsButIsDeleted() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 1)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => 'foo.pdf',
				'object' => $this->objectUid,
				'deleted' => 1,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addDocument('foo', 'foo.pdf');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_documents', 'filename = "foo.pdf" AND deleted = 0'
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseDeletesExistingDocumentFromFileSystem() {
		$fileName = $this->testingFramework->createDummyFile('foo.pdf');
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 1)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => basename($fileName),
				'object' => $this->objectUid,
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->deleteDocument(0);
		$this->fixture->writeToDatabase();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseNotAddsDeletedDocumentRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->deleteDocument(
			$this->fixture->addDocument('foo', 'foo.pdf')
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				'tx_realty_documents', 'deleted = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function importANewRecordWithDocumentsAndTheDeletedFlagBeingSetReturnsMarkedAsDeletedMessageKey() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject(
			array('object_number' => 'foo-bar', 'deleted' => 1)
		);
		$this->fixture->addDocument('foo', 'foo.pdf');

		$this->assertEquals(
			'message_deleted_flag_set',
			$this->fixture->writeToDatabase()
		);
	}


	/////////////////////////////////////
	// Tests for processing owner data.
	/////////////////////////////////////

	/**
	 * @test
	 */
	public function uidOfFeUserWithMatchingAnidIsAddedAsOwnerForExistingObjectIfAddingTheOwnerIsAllowed() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			$feUserUid,
			$this->fixture->getProperty('owner')
		);
	}

	/**
	 * @test
	 */
	public function uidOfFeUserWithMatchingAnidIsAddedAsOwnerForNewObjectIfAddingTheOwnerIsAllowed() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject(array('openimmo_anid' => 'test anid'));
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			$feUserUid,
			$this->fixture->getProperty('owner')
		);
	}

	/**
	 * @test
	 */
	public function uidOfFeUserWithMatchingAnidIsNotAddedAsOwnerIfThisIsForbidden() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, FALSE);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('owner')
		);
	}

	/**
	 * @test
	 */
	public function noOwnerIsAddedForRealtyRecordWithoutOpenImmoAnid() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('owner')
		);
	}

	/**
	 * @test
	 */
	public function ownerIsNotChangedAlthoughTheAnidOfARecordIsUpdatedAndDoesNotMatchAnymore() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid 1')
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid 1');
		$this->fixture->writeToDatabase(0, TRUE);
		$this->fixture->setProperty('openimmo_anid', 'test anid 2');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			$feUserUid,
			$this->fixture->getProperty('owner')
		);
		$this->assertEquals(
			'test anid 2',
			$this->fixture->getProperty('openimmo_anid')
		);
	}

	/**
	 * @test
	 */
	public function ownerIsNotChangedAlthoughTheAnidOfARecordIsUpdatedAndMatchesAnotherFeUser() {
		$feUserGroup = $this->testingFramework->createFrontEndUserGroup();
		$uidOfFeUserOne = $this->testingFramework->createFrontEndUser(
			$feUserGroup, array('tx_realty_openimmo_anid' => 'test anid 1')
		);
		$this->testingFramework->createFrontEndUser(
			$feUserGroup, array('tx_realty_openimmo_anid' => 'test anid 2')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid 1');
		$this->fixture->writeToDatabase(0, TRUE);
		$this->fixture->setProperty('openimmo_anid', 'test anid 2');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			$uidOfFeUserOne,
			$this->fixture->getProperty('owner')
		);
		$this->assertEquals(
			'test anid 2',
			$this->fixture->getProperty('openimmo_anid')
		);
	}

	/**
	 * @test
	 */
	public function useFeUserDataFlagIsSetIfThisOptionIsEnabledByConfiguration() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsBoolean(
				'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
			);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			1,
			$this->fixture->getProperty('contact_data_source')
		);
	}

	/**
	 * @test
	 */
	public function useFeUserDataFlagIsNotSetIfThisOptionIsDisabledByConfiguration() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsBoolean(
				'useFrontEndUserDataAsContactDataForImportedRecords', FALSE
			);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('contact_data_source')
		);
	}

	/**
	 * @test
	 */
	public function useFeUserDataFlagIsNotSetIfNoOwnerWasSetAlthoughOptionIsEnabledByConfiguration() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsBoolean(
				'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
			);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('contact_data_source')
		);
	}


	/*
	 * Test concerning the show_address field
	 */

	/**
	 * @test
	 */
	public function getShowAddressInitiallyReturnsFalse() {
		$this->fixture->setData(array());

		$this->assertFalse(
			$this->fixture->getShowAddress()
		);
	}

	/**
	 * @test
	 */
	public function getShowAddressReturnsShowAddress() {
		$this->fixture->setData(array('show_address' => TRUE));

		$this->assertTrue(
			$this->fixture->getShowAddress()
		);
	}

	/**
	 * @test
	 */
	public function setShowAddressSetsShowAddress() {
		$this->fixture->setData(array());
		$this->fixture->setShowAddress(TRUE);
		$this->assertSame(
			TRUE,
			$this->fixture->getShowAddress()
		);
	}


	/*
	 * Tests concerning getGeoAddress and hasGeoAddress
	 */

	/**
	 * @test
	 */
	public function getGeoAddressForNoAddressDataReturnsEmptyString() {
		$this->fixture->loadRealtyObject(array(
			'street' => '',
			'zip' => '',
			'city' => 0,
			'country' => 0,
		));

		$this->assertSame(
			'',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForCityOnlyReturnsCityName() {
		$this->fixture->loadRealtyObject(array(
			'street' => '',
			'zip' => '',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => 0,
		));

		$this->assertSame(
			'Bonn',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForZipCodeOnlyReturnsEmptyString() {
		$this->fixture->loadRealtyObject(array(
			'street' => '',
			'zip' => '53111',
			'city' => 0,
			'country' => 0,
		));

		$this->assertSame(
			'',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForZipCodeAndCityReturnsZipCodeAndCity() {
		$this->fixture->loadRealtyObject(array(
			'street' => '',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => 0,
		));

		$this->assertSame(
			'53111 Bonn',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForStreetAndZipCodeAndCityReturnsStreetAndZipCodeAndCity() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => 0,
			'show_address' => 1,
		));

		$this->assertSame(
			'Am Hof 1, 53111 Bonn',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForOnlyStreetReturnsEmptyString() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '',
			'city' => 0,
			'country' => 0,
			'show_address' => 1,
		));

		$this->assertSame(
			'',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForStreetAndZipCodeReturnsEmptyString() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => 0,
			'country' => 0,
			'show_address' => 1,
		));

		$this->assertSame(
			'',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForStreetAndCityReturnsStreetAndCity() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => 0,
			'show_address' => 1,
		));

		$this->assertSame(
			'Am Hof 1, Bonn',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForStreetAndZipCodeAndCityAndCountryReturnsStreetAndZipCodeAndCityAndCountry() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
			'show_address' => 1,
		));

		$this->assertSame(
			'Am Hof 1, 53111 Bonn, DE',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForCityAndCountryReturnsCityAndCountry() {
		$this->fixture->loadRealtyObject(array(
			'street' => '',
			'zip' => '',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
		));

		$this->assertSame(
			'Bonn, DE',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function getGeoAddressForOnlyCountryReturnsEmptyString() {
		$this->fixture->loadRealtyObject(array(
			'street' => '',
			'zip' => '',
			'city' => 0,
			'country' => self::DE,
		));

		$this->assertSame(
			'',
			$this->fixture->getGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoAddressForNoAddressDataReturnsFalse() {
		$this->fixture->loadRealtyObject(array(
			'street' => '',
			'zip' => '',
			'city' => 0,
			'country' => 0,
		));

		$this->assertFalse(
			$this->fixture->hasGeoAddress()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoAddressForFullAddressReturnsTrue() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
			'show_address' => 1,
		));

		$this->assertTrue(
			$this->fixture->hasGeoAddress()
		);
	}


	////////////////////////////
	// Tests concerning getUid
	////////////////////////////

	/**
	 * @test
	 */
	public function getUidReturnsZeroForObjectWithoutUid() {
		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);

		$this->assertEquals(
			0,
			$realtyObject->getUid()
		);
	}

	/**
	 * @test
	 */
	public function getUidReturnsCurrentUidForObjectWithUid() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			$this->objectUid,
			$this->fixture->getUid()
		);
	}


	//////////////////////////////
	// Tests concerning getTitle
	//////////////////////////////

	/**
	 * @test
	 */
	public function getTitleReturnsEmptyStringForObjectWithoutTitle() {
		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);
		$realtyObject->loadRealtyObject(0);

		$this->assertEquals(
			'',
			$realtyObject->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getTitleReturnsFullTitleForObjectWithTitle() {
		$this->fixture->loadRealtyObject(
			array('title' => 'foo title filltext-filltext-filltext-filltext')
		);

		$this->assertEquals(
			'foo title filltext-filltext-filltext-filltext',
			$this->fixture->getTitle()
		);
	}


	/////////////////////////////////////
	// Tests concerning getCroppedTitle
	/////////////////////////////////////

	/**
	 * @test
	 */
	public function getCroppedTitleReturnsEmptyStringForObjectWithoutTitle() {
		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);
		$realtyObject->loadRealtyObject(0);

		$this->assertEquals(
			'',
			$realtyObject->getCroppedTitle()
		);
	}

	/**
	 * @test
	 */
	public function getCroppedTitleReturnsFullShortTitleForObjectWithTitle() {
		$this->fixture->loadRealtyObject(
			array('title' => '12345678901234567890123456789012')
		);

		$this->assertEquals(
			'12345678901234567890123456789012',
			$this->fixture->getCroppedTitle()
		);
	}

	/**
	 * @test
	 */
	public function getCroppedTitleReturnsLongTitleCroppedAtDefaultCropSize() {
		$this->fixture->loadRealtyObject(
			array('title' => '123456789012345678901234567890123')
		);

		$this->assertEquals(
			'12345678901234567890123456789012…',
			$this->fixture->getCroppedTitle()
		);
	}

	/**
	 * @test
	 */
	public function getCroppedTitleReturnsLongTitleCroppedAtGivenCropSize() {
		$this->fixture->loadRealtyObject(
			array('title' => '123456789012345678901234567890123')
		);

		$this->assertEquals(
			'1234567890…',
			$this->fixture->getCroppedTitle(10)
		);
	}

	/**
	 * @test
	 */
	public function getCroppedTitleWithZeroGivenReturnsLongTitleCroppedAtDefaultLength() {
		$this->fixture->loadRealtyObject(
			array('title' => '123456789012345678901234567890123')
		);

		$this->assertEquals(
			'12345678901234567890123456789012…',
			$this->fixture->getCroppedTitle(0)
		);
	}

	/**
	 * @test
	 */
	public function getCroppedTitleWithStringGivenReturnsLongTitleCroppedAtDefaultLength() {
		$this->fixture->loadRealtyObject(
			array('title' => '123456789012345678901234567890123')
		);

		$this->assertEquals(
			'12345678901234567890123456789012…',
			$this->fixture->getCroppedTitle('foo')
		);
	}


	/////////////////////////////////////////////
	// Tests concerning getForeignPropertyField
	/////////////////////////////////////////////

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function getForeignPropertyFieldForNonAllowedFieldThrowsException() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->getForeignPropertyField('floor');
	}

	/**
	 * @test
	 */
	public function getForeignPropertyFieldReturnsNonNumericFieldContentForAllowedField() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'test city');

		$this->assertEquals(
			'test city',
			$this->fixture->getForeignPropertyField('city')
		);
	}

	/**
	 * @test
	 */
	public function getForeignPropertyFieldReturnsEmptyStringIfThereIsNoPropertySetForAllowedField() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'',
			$this->fixture->getForeignPropertyField('city')
		);
	}

	/**
	 * @test
	 */
	public function getForeignPropertyFieldReturnsACitysTitle() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('title' => 'foo')
		);
		$this->fixture->setProperty('city', $cityUid);

		$this->assertEquals(
			'foo',
			$this->fixture->getForeignPropertyField('city')
		);
	}

	/**
	 * @test
	 */
	public function getForeignPropertyFieldReturnsADistrictsTitle() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$districtUid = $this->testingFramework->createRecord(
			REALTY_TABLE_DISTRICTS, array('title' => 'foo')
		);
		$this->fixture->setProperty('district', $districtUid);

		$this->assertEquals(
			'foo',
			$this->fixture->getForeignPropertyField('district')
		);
	}

	/**
	 * @test
	 */
	public function getForeignPropertyFieldReturnsACountrysShortLocalName() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('country', self::DE);

		$this->assertEquals(
			'Deutschland',
			$this->fixture->getForeignPropertyField('country', 'cn_short_local')
		);
	}


	//////////////////////////////////////
	// Tests concerning getAddressAsHtml
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function getAddressAsHtmlReturnsFormattedPartlyAddressIfAllDataProvidedAndShowAddressFalse() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'12345 Test Town District<br />Deutschland',
			$this->fixture->getAddressAsHtml()
		);
	}

	/**
	 * @test
	 */
	public function getAddressAsHtmlReturnsFormattedCompleteAddressIfAllDataProvidedAndShowAddressTrue() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'Main Street<br />12345 Test Town District<br />Deutschland',
			$this->fixture->getAddressAsHtml()
		);
	}

	/**
	 * @test
	 */
	public function getAddressAsHtmlReturnsFormattedAddressForAllDataButCountryProvidedAndShowAddressTrue() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
		));

		$this->assertEquals(
			'Main Street<br />12345 Test Town District',
			$this->fixture->getAddressAsHtml()
		);
	}

	/**
	 * @test
	 */
	public function getAddressAsHtmlReturnsFormattedAddressForAllDataButStreetProvidedAndShowAddressTrue() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'12345 Test Town District<br />Deutschland',
			$this->fixture->getAddressAsHtml()
		);
	}

	/**
	 * @test
	 */
	public function getAddressAsHtmlReturnsFormattedAddressForOnlyStreetProvidedAndShowAddressTrue() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1, 'street' => 'Main Street',
		));

		$this->assertEquals(
			'Main Street<br />',
			$this->fixture->getAddressAsHtml()
		);
	}


	////////////////////////////////////////////
	// Tests concerning getAddressAsSingleLine
	////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getAddressAsSingleLineForShowAddressFalseReturnsAddressWithoutStreet() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'12345 Test Town District, Deutschland',
			$this->fixture->getAddressAsSingleLine()
		);
	}

	/**
	 * @test
	 */
	public function getAddressAsSingleLineForShowAddressTrueReturnsCompleteAddress() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'Main Street, 12345 Test Town District, Deutschland',
			$this->fixture->getAddressAsSingleLine()
		);
	}

	/**
	 * @test
	 */
	public function getAddressAsSingleLineForNoCountrySetAndShowAddressTrueReturnsAddressWithoutCountry() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
		));

		$this->assertEquals(
			'Main Street, 12345 Test Town District',
			$this->fixture->getAddressAsSingleLine()
		);
	}

	/**
	 * @test
	 */
	public function getAddressAsSingleLineForNoStreetSetAndShowAddressTrueReturnsAddressWithoutStreet() {
			$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'12345 Test Town District, Deutschland',
			$this->fixture->getAddressAsSingleLine()
		);
	}

	/**
	 * @test
	 */
	public function getAddressAsSingleLineForShowAddressTrueReturnsCompleteAddressWithoutHtmlTags() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertNotContains(
			'<',
			$this->fixture->getAddressAsSingleLine()
		);
	}


	/////////////////////////////
	// Tests for isAllowedKey()
	/////////////////////////////

	/**
	 * @test
	 */
	public function isAllowedKeyReturnsTrueForRealtyObjectField() {
		$this->assertTrue(
			$this->fixture->isAllowedKey('title')
		);
	}

	/**
	 * @test
	 */
	public function isAllowedKeyReturnsFalseForNonRealtyObjectField() {
		$this->assertFalse(
			$this->fixture->isAllowedKey('foo')
		);
	}

	/**
	 * @test
	 */
	public function isAllowedKeyReturnsFalseForEmptyKey() {
		$this->assertFalse(
			$this->fixture->isAllowedKey('')
		);
	}


	//////////////////////////////
	// Tests concerning getOwner
	//////////////////////////////

	/**
	 * @test
	 */
	public function getOwnerForObjectWithOwnerReturnsFrontEndUserModel() {
		$this->fixture->loadRealtyObject(
			array(
				'owner' => $this->testingFramework->createFrontEndUser()
			)
		);

		$this->assertTrue(
			$this->fixture->getOwner() instanceof tx_realty_Model_FrontEndUser
		);
	}


	////////////////////////////////////////////
	// Tests concerning the owner data getters
	////////////////////////////////////////////

	////////////////////////////////////
	// Tests concerning getContactName
	////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactNameForOwnerFromObjectAndWithoutNameReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactName()
		);
	}

	/**
	 * @test
	 */
	public function getContactNameForOwnerFromFeUserWithNameReturnsOwnerName() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('name' => 'foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getContactName()
		);
	}

	/**
	 * @test
	 */
	public function getContactNameForOwnerFromObjectWithNameReturnsOwnerName() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT, array(),
			array('contact_person' => 'foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getContactName()
		);
	}


	////////////////////////////////////////////
	// Tests concerning getContactEMailAddress
	////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactEMailAddressForOwnerFromFeUserAndWithoutEMailAddressReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactEMailAddress()
		);
	}

	/**
	 * @test
	 */
	public function getContactEMailAddressForOwnerFromObjectAndWithoutEMailAddressReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactEMailAddress()
		);
	}

	/**
	 * @test
	 */
	public function getContactEMailAddressForOwnerFromFeUserWithEMailAddressReturnsEMailAddress() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('email' => 'foo@bar.com')
		);

		$this->assertEquals(
			'foo@bar.com',
			$this->fixture->getContactEMailAddress()
		);
	}

	/**
	 * @test
	 */
	public function getContactEMailAddressForOwnerFromObjectWithContactEMailAddressReturnsContactEMailAddress() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT, array(),
			array('contact_email' => 'bar@foo.com')
		);

		$this->assertEquals(
			'bar@foo.com',
			$this->fixture->getContactEMailAddress()
		);
	}


	////////////////////////////////////
	// Tests concerning getContactCity
	////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactCityForOwnerFromFeUserAndWithoutCityReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactCity()
		);
	}

	/**
	 * @test
	 */
	public function getContactCityForOwnerFromObjectReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactCity()
		);
	}

	/**
	 * @test
	 */
	public function getContactCityForOwnerFromFeUserWithCityReturnsCity() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('city' => 'footown')
		);

		$this->assertEquals(
			'footown',
			$this->fixture->getContactCity()
		);
	}


	//////////////////////////////////////
	// Tests concerning getContactStreet
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactStreetForOwnerFromFeUserAndWithoutStreetReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactStreet()
		);
	}

	/**
	 * @test
	 */
	public function getContactStreetForOwnerFromObjectReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactStreet()
		);
	}

	/**
	 * @test
	 */
	public function getContactStreetForOwnerFromFeUserWithStreetReturnsStreet() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('address' => 'foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getContactStreet()
		);
	}


	///////////////////////////////////
	// Tests concerning getContactZip
	///////////////////////////////////

	/**
	 * @test
	 */
	public function getContactZipForOwnerFromFeUserAndWithoutZipReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactZip()
		);
	}

	/**
	 * @test
	 */
	public function getContactZipForOwnerFromObjectReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactZip()
		);
	}

	/**
	 * @test
	 */
	public function getContactZipForOwnerFromFeUserWithZipReturnsZip() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('zip' => '12345')
		);

		$this->assertEquals(
			'12345',
			$this->fixture->getContactZip()
		);
	}


	////////////////////////////////////////
	// Tests concerning getContactHomepage
	////////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactHomepageForOwnerFromFeUserAndWithoutHomepageReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactHomepage()
		);
	}

	/**
	 * @test
	 */
	public function getContactHomepageForOwnerFromObjectReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactHomepage()
		);
	}

	/**
	 * @test
	 */
	public function getContactHomepageForOwnerFromFeUserWithHomepageReturnsHomepage() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('www' => 'www.foo.de')
		);

		$this->assertEquals(
			'www.foo.de',
			$this->fixture->getContactHomepage()
		);
	}


	///////////////////////////////////////////
	// Tests concerning getContactPhoneNumber
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactPhoneNumberForOwnerFromFeUserAndWithoutPhoneNumberReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumberForOwnerFromObjectAndWithoutPhoneNumberReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumberForOwnerFromFeUserWithPhoneNumberReturnsPhoneNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('telephone' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumberForOwnerFromObjectWithDirectExtensionPhoneNumberReturnsThisNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT,
			array(),
			array('phone_direct_extension' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumberForOwnerFromObjectWithSwitchboardAndWithoutDirectExtensionPhoneNumberReturnsSwitchboardNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT,
			array(),
			array('phone_switchboard' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumberForOwnerFromObjectWithSwitchboardAndDirectExtensionPhoneNumberReturnsDirectExtensionNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT,
			array(),
			array(
				'phone_switchboard' => '123456',
				'phone_direct_extension' => '654321'
			)
		);

		$this->assertEquals(
			'654321',
			$this->fixture->getContactPhoneNumber()
		);
	}


	///////////////////////////////////////////
	// Tests concerning getContactSwitchboard
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactSwitchboardForNoSwitchboardSetReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT, array(), array()
		);

		$this->assertEquals(
			'',
			$this->fixture->getContactSwitchboard()
		);
	}

	/**
	 * @test
	 */
	public function getContactSwitchboardForSwitchboardSetReturnsSwitchboardNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT,
			array(),
			array('phone_switchboard' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactSwitchboard()
		);
	}


	///////////////////////////////////////////////
	// Tests concerning getContactDirectExtension
	///////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactDirectExtensionForNoDirectExtensionSetReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT, array(), array()
		);

		$this->assertEquals(
			'',
			$this->fixture->getContactDirectExtension()
		);
	}

	/**
	 * @test
	 */
	public function getContactDirectExtensionForDirectExtensionSetReturnsDirectExtensionNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT,
			array(),
			array('phone_direct_extension' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactDirectExtension()
		);
	}


	////////////////////////////////
	// Tests concerning the status
	////////////////////////////////

	/**
	 * @test
	 */
	public function getStatusForNoStatusSetReturnsVacant() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(), array()
		);

		$this->assertEquals(
			tx_realty_Model_RealtyObject::STATUS_VACANT,
			$this->fixture->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function getStatusForStatusSetReturnsStatus() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_RENTED)
		);

		$this->assertEquals(
			tx_realty_Model_RealtyObject::STATUS_RENTED,
			$this->fixture->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function isRentedOrSoldForStatusVacantReturnsFalse() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_VACANT)
		);

		$this->assertFalse(
			$this->fixture->isRentedOrSold()
		);
	}

	/**
	 * @test
	 */
	public function isRentedOrSoldForStatusReservedReturnsFalse() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_RESERVED)
		);

		$this->assertFalse(
			$this->fixture->isRentedOrSold()
		);
	}

	/**
	 * @test
	 */
	public function isRentedOrSoldForStatusSoldReturnsTrue() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_SOLD)
		);

		$this->assertTrue(
			$this->fixture->isRentedOrSold()
		);
	}

	/**
	 * @test
	 */
	public function isRentedOrSoldForStatusRentedReturnsTrue() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_RENTED)
		);

		$this->assertTrue(
			$this->fixture->isRentedOrSold()
		);
	}


	/*
	 * Tests concerning the address
	 */

	/**
	 * @test
	 */
	public function getStreetForEmptyStreetReturnsEmptyString() {
		$this->fixture->setData(array());

		$this->assertEquals(
			'',
			$this->fixture->getStreet()
		);
	}

	/**
	 * @test
	 */
	public function getStreetForNonEmptyStreetReturnsStreet() {
		$this->fixture->setData(array('street' => 'foo'));

		$this->assertSame(
			'foo',
			$this->fixture->getStreet()
		);
	}

	/**
	 * @test
	 */
	public function hasStreetForEmptyStreetReturnsFalse() {
		$this->fixture->setData(array('street' => ''));

		$this->assertFalse(
			$this->fixture->hasStreet()
		);
	}

	/**
	 * @test
	 */
	public function hasStreetForNonEmptyStreetReturnsTrue() {
		$this->fixture->setData(array('street' => 'foo'));

		$this->assertTrue(
			$this->fixture->hasStreet()
		);
	}

	/**
	 * @test
	 */
	public function getZipForEmptyZipReturnsEmptyString() {
		$this->fixture->setData(array());

		$this->assertSame(
			'',
			$this->fixture->getZip()
		);
	}

	/**
	 * @test
	 */
	public function setStreetSetsStreet() {
		$this->fixture->setData(array());
		$this->fixture->setStreet('bar');

		$this->assertSame(
			'bar',
			$this->fixture->getStreet()
		);
	}

	/**
	 * @test
	 */
	public function getZipForNonEmptyZipReturnsZip() {
		$this->fixture->setData(array('zip' => '12345'));

		$this->assertSame(
			'12345',
			$this->fixture->getZip()
		);
	}

	/**
	 * @test
	 */
	public function setZipSetsZip() {
		$this->fixture->setData(array());
		$zip = '16432';
		$this->fixture->setZip($zip);

		$this->assertSame(
			$zip,
			$this->fixture->getZip()
		);
	}

	/**
	 * @test
	 */
	public function hasZipForEmptyZipReturnsFalse() {
		$this->fixture->setData(array('zip' => ''));

		$this->assertFalse(
			$this->fixture->hasZip()
		);
	}

	/**
	 * @test
	 */
	public function hasZipForNonEmptyZipReturnsTrue() {
		$this->fixture->setData(array('zip' => '12345'));

		$this->assertTrue(
			$this->fixture->hasZip()
		);
	}

	/**
	 * @test
	 */
	public function getCityForNoCityReturnsNull() {
		$this->fixture->setData(array());

		$this->assertNull(
			$this->fixture->getCity()
		);
	}

	/**
	 * @test
	 */
	public function getCityForExistingCityReturnsCity() {
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => 'Berlin')
		);
		$this->fixture->setData(array('city' => $cityUid));
		$city = tx_oelib_MapperRegistry::get('tx_realty_Mapper_City')->find($cityUid);

		$this->assertSame(
			$city,
			$this->fixture->getCity()
		);
	}

	/**
	 * @test
	 */
	public function hasCityForNoCityReturnsFalse() {
		$this->fixture->setData(array());

		$this->assertFalse(
			$this->fixture->hasCity()
		);
	}

	/**
	 * @test
	 */
	public function hasCityForExistingCityReturnsTrue() {
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => 'Berlin')
		);
		$this->fixture->setData(array('city' => $cityUid));

		$this->assertTrue(
			$this->fixture->hasCity()
		);
	}

	/**
	 * @test
	 */
	public function getCountryForNoCountryReturnsNull() {
		$this->fixture->setData(array());

		$this->assertNull(
			$this->fixture->getCountry()
		);
	}

	/**
	 * @test
	 */
	public function getCountryForExistingCountryReturnsCountry() {
		$this->fixture->setData(array('country' => self::DE));
		$country = tx_oelib_MapperRegistry::get('tx_oelib_Mapper_Country')->find(self::DE);

		$this->assertSame(
			$country,
			$this->fixture->getCountry()
		);
	}

	/**
	 * @test
	 */
	public function hasCountryForNoCountryReturnsFalse() {
		$this->fixture->setData(array());

		$this->assertFalse(
			$this->fixture->hasCountry()
		);
	}

	/**
	 * @test
	 */
	public function hasCountryForExistingCountryReturnsTrue() {
		$this->fixture->setData(array('country' => self::DE));

		$this->assertTrue(
			$this->fixture->hasCountry()
		);
	}


	/*
	 * Tests concerning the geo coordinates
	/*

	/**
	 * @test
	 */
	public function getGeoCoordinatesForHasCoordinatesReturnsLatitudeAndLongitude() {
		$this->fixture->setData(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
				'has_coordinates' => TRUE,
			)
		);

		$this->assertSame(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			),
			$this->fixture->getGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function getGeoCoordinatesForNotHasCoordinatesReturnsEmptyArray() {
		$this->fixture->setData(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
				'has_coordinates' => FALSE,
			)
		);

		$this->assertSame(
			array(),
			$this->fixture->getGeoCoordinates()
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setGeoCoordinatesWithoutLatitudeThrowsException() {
		$this->fixture->setGeoCoordinates(array('longitude' => 42.0));
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setGeoCoordinatesWithoutLongitudeThrowsException() {
		$this->fixture->setGeoCoordinates(array('latitude' => -42.7));
	}

	/**
	 * @test
	 */
	public function setGeoCoordinatesSetsCoordinates() {
		$this->fixture->setData(array());

		$this->fixture->setGeoCoordinates(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			)
		);

		$this->assertSame(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			),
			$this->fixture->getGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function setGeoCoordinatesSetsHasCoordinatesToTrue() {
		$this->fixture->setData(array('has_coordinates' => FALSE));

		$this->fixture->setGeoCoordinates(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			)
		);

		$this->assertTrue(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function setGeoCoordinatesSetsHasGeoErrorToFalse() {
		$this->fixture->setData(array('coordinates_problem' => TRUE));

		$this->fixture->setGeoCoordinates(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			)
		);

		$this->assertFalse(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoCoordinatesForHasCoordinatesTrueReturnsTrue() {
		$this->fixture->setData(array('has_coordinates' => TRUE));

		$this->assertTrue(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoCoordinatesForHasCoordinatesFalseReturnsFalse() {
		$this->fixture->setData(array('has_coordinates' => FALSE));

		$this->assertFalse(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function clearGeoCoordinatesSetsHasCoordinatesToFalse() {
		$this->fixture->setData(array('has_coordinates' => TRUE));

		$this->fixture->clearGeoCoordinates();

		$this->assertFalse(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoErrorForProblemTrueReturnsTrue() {
		$this->fixture->setData(array('coordinates_problem' => TRUE));

		$this->assertTrue(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoErrorForProblemFalseReturnsFalse() {
		$this->fixture->setData(array('coordinates_problem' => FALSE));

		$this->assertFalse(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function setGeoErrorSetsGeoErrorToTrue() {
		$this->fixture->setData(array('coordinates_problem' => FALSE));

		$this->fixture->setGeoError();

		$this->assertTrue(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function clearGeoErrorSetsGeoErrorToFalse() {
		$this->fixture->setData(array('coordinates_problem' => TRUE));

		$this->fixture->clearGeoError();

		$this->assertFalse(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function getDistanceToTheSeaInitiallyReturnsZero() {
		$this->fixture->setData(array());

		$this->assertSame(
			0,
			$this->fixture->getDistanceToTheSea()
		);
	}

	/**
	 * @test
	 */
	public function getDistanceToTheSeaReturnsDistanceToTheSea() {
		$distance = 42;

		$this->fixture->setData(array('distance_to_the_sea' => $distance));

		$this->assertSame(
			$distance,
			$this->fixture->getDistanceToTheSea()
		);
	}

	/**
	 * @test
	 */
	public function hasDistanceToTheSeaForZeroReturnsFalse() {
		$this->fixture->setData(array('distance_to_the_sea' => 0));

		$this->assertFalse(
			$this->fixture->hasDistanceToTheSea()
		);
	}

	/**
	 * @test
	 */
	public function hasDistanceToTheSeaForPositiveNumberReturnsTrue() {
		$this->fixture->setData(array('distance_to_the_sea' => 9));

		$this->assertTrue(
			$this->fixture->hasDistanceToTheSea()
		);
	}

	/**
	 * @test
	 */
	public function setDistanceToTheSeaSetsDistanceToTheSea() {
		$distance = 9;

		$this->fixture->setData(array());
		$this->fixture->setDistanceToTheSea($distance);

		$this->assertSame(
			$distance,
			$this->fixture->getDistanceToTheSea()
		);
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function setDistanceToTheSeaWithNegativeNumberThrowsException() {
		$this->fixture->setData(array());
		$this->fixture->setDistanceToTheSea(-1);
	}
}