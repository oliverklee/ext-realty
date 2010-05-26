<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2010 Saskia Metzler <saskia@merlin.owl.de>
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
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_translator.php');
require_once(t3lib_extMgm::extPath('realty') . 'tests/fixtures/class.tx_realty_openImmoImportChild.php');

/**
 * Unit tests for the tx_realty_openImmoImport class in the 'realty' extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_openImmoImport_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_openImmoImport instance to be tested
	 */
	private $fixture;
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;
	/**
	 * @var tx_oelib_configurationProxy
	 */
	private $globalConfiguration;
	/**
	 * @var tx_realty_translator
	 */
	private $translator;

	/**
	 * @var integer PID of the system folder where imported records will
	 *              be stored
	 */
	private $systemFolderPid = 0;
	/**
	 * @var string path to the import folder
	 */
	private $importFolder = '';

	/**
	 * @var boolean whether an import folder has been created
	 */
	private $testImportFolderExists = FALSE;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->systemFolderPid = $this->testingFramework->createSystemFolder();
		$this->importFolder = PATH_site . 'typo3temp/tx_realty_fixtures/';

		$this->globalConfiguration= tx_oelib_configurationProxy::getInstance('realty');

		tx_oelib_mailerFactory::getInstance()->enableTestMode();

		$this->translator = tx_oelib_ObjectFactory::make('tx_realty_translator');

		$this->fixture = new tx_realty_openImmoImportChild(TRUE);
		$this->setupStaticConditions();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();

		unset(
			$this->fixture, $this->translator, $this->testingFramework,
			$this->globalConfiguration
		);
		$this->deleteTestImportFolder();
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Sets the global configuration values which need to be static during the
	 * tests.
	 */
	private function setupStaticConditions() {
		// avoids using the extension's real upload folder
		$this->fixture->setUploadDirectory($this->importFolder);

		// TYPO3 default configuration
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
			= 'gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai';

		$this->globalConfiguration->setAsString(
			'emailAddress', 'default-address@valid-email.org'
		);
		$this->globalConfiguration->setAsBoolean(
			'onlyErrors', FALSE
		);
		$this->globalConfiguration->setAsString(
			'openImmoSchema', $this->importFolder . 'schema.xsd'
		);
		$this->globalConfiguration->setAsString(
			'importFolder', $this->importFolder
		);
		$this->globalConfiguration->setAsBoolean(
			'deleteZipsAfterImport', TRUE
		);
		$this->globalConfiguration->setAsBoolean(
			'notifyContactPersons', TRUE
		);
		$this->globalConfiguration->setAsInteger(
			'pidForRealtyObjectsAndImages', $this->systemFolderPid
		);
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName', ''
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', FALSE
		);
		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', FALSE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', ''
		);
	}

	/**
	 * Disables the XML validation.
	 */
	private function disableValidation() {
		$this->globalConfiguration->setAsString('openImmoSchema', '');
	}

	/**
	 * Copies a file or a folder from the extension's tests/fixtures/ folder
	 * into the temporary test import folder.
	 *
	 * @param string File or folder to copy. Must be a relative path to
	 *               existent files within the tests/fixtures/ folder.
	 *               Leave empty to create an empty import folder.
	 * @param string new file name in case it should be different from
	 *               the original one, may be empty
	 */
	private function copyTestFileIntoImportFolder($fileName, $newFileName = '') {
		// creates an import folder if there is none
		if (!is_dir($this->importFolder)) {
			t3lib_div::mkdir($this->importFolder);
		}
		$this->testImportFolderExists = TRUE;

		if ($fileName != '') {
			copy(
				t3lib_extMgm::extPath('realty') .
					'tests/fixtures/tx_realty_fixtures/' . $fileName,
				$this->importFolder .
					(($newFileName != '') ? $newFileName : basename($fileName))
			);
		}
	}

	/**
	 * Deletes the test import folder if it has been created during the tests.
	 * Otherwise does nothing.
	 */
	private function deleteTestImportFolder() {
		if ($this->testImportFolderExists) {
			tx_realty_openImmoImport::rmdir($this->importFolder, TRUE);
			$this->testImportFolderExists = FALSE;
		}
	}

	/**
	 * Checks if the ZIPArchive class is available. If it is not available, the
	 * current test will be marked as skipped.
	 */
	private function checkForZipArchive() {
		if (!in_array('zip', get_loaded_extensions())) {
			$this->markTestSkipped(
				'This PHP installation does not provide the ZIPArchive class.'
			);
		}
	}


	/////////////////////////////////////////
	// Tests concerning the ZIP extraction.
	/////////////////////////////////////////

	public function testGetPathsOfZipsToExtract() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->copyTestFileIntoImportFolder('bar.zip');

		$this->assertEquals(
			glob($this->importFolder . '*.zip'),
			array_values(
				$this->fixture->getPathsOfZipsToExtract($this->importFolder)
			)
		);
	}

	public function testGetNameForExtractionFolder() {
		$this->copyTestFileIntoImportFolder('bar.zip');

		$this->assertEquals(
			'bar/',
			$this->fixture->getNameForExtractionFolder('bar.zip')
		);
	}

	public function testUnifyPathDoesNotChangeCorrectPath() {
		$this->assertEquals(
			'correct/path/',
			$this->fixture->unifyPath('correct/path/')
		);
	}

	public function testUnifyPathTrimsAndAddsNecessarySlash() {
		$this->assertEquals(
			'incorrect/path/',
			$this->fixture->unifyPath('incorrect/path')
		);
	}

	public function testCreateExtractionFolderForExistingZip() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$dirName = $this->fixture->createExtractionFolder(
			$this->importFolder . 'foo.zip'
		);

		$this->assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
		$this->assertEquals(
			$this->importFolder . 'foo/',
			$dirName
		);
	}

	public function testCreateExtractionFolderForNonExistingZip() {
		$this->copyTestFileIntoImportFolder('');
		$dirName = $this->fixture->createExtractionFolder(
			$this->importFolder . 'foobar.zip'
		);

		$this->assertFalse(
			is_dir($this->importFolder . 'foobar/')
		);
		$this->assertEquals(
			'',
			$dirName
		);
	}

	public function testExtractZipIfOneZipToExtractExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');

		$this->assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
	}

	public function testExtractZipIfZipDoesNotExist() {
		$this->copyTestFileIntoImportFolder('');
		$this->fixture->extractZip($this->importFolder . 'foobar.zip');

		$this->assertFalse(
			is_dir($this->importFolder . 'foobar/')
		);
	}

	public function testGetPathForXmlIfFolderWithOneXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');

		$this->assertEquals(
			$this->importFolder . 'foo/foo.xml',
			$this->fixture->getPathForXml($this->importFolder . 'foo.zip')
		);
	}

	public function testGetPathForXmlIfFolderNotExists() {
		$this->copyTestFileIntoImportFolder('foo.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'foo.zip')
		);
	}

	public function testGetPathForXmlIfFolderWithTwoXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar-bar.zip');
		$this->fixture->extractZip($this->importFolder . 'bar-bar.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'bar-bar.zip')
		);
	}

	public function testGetPathForXmlIfFolderWithoutXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('empty.zip');
		$this->fixture->extractZip($this->importFolder . 'empty.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'empty.zip')
		);
	}

	public function testCopyImagesFromExtractedZipCopiesImagesIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'foo.jpg')
		);
		$this->assertTrue(
			file_exists($this->importFolder . 'bar.jpg')
		);
	}

	public function testCopyImagesFromExtractedZipCopiesImagesWithUppercasedExtensionsIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo-uppercased.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'foo.JPG')
		);
	}

	public function testCopyImagesFromExtractedZipTwiceCopiesImagesUniquelyNamedIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->copyTestFileIntoImportFolder('foo.zip', 'foo2.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'foo.jpg')
		);
		$this->assertTrue(
			file_exists($this->importFolder . 'foo_00.jpg')
		);
	}

	public function testCopyImagesFromExtractedZipCopiesImagesForRealtyRecord() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'foo.jpg')
		);
		$this->assertTrue(
			file_exists($this->importFolder . 'bar.jpg')
		);
	}

	public function testCopyImagesFromExtractedZipNotCopiesImagesForRecordWithDeletionFlagSet() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo-deleted.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			file_exists($this->importFolder . 'foo.jpg')
		);
		$this->assertFalse(
			file_exists($this->importFolder . 'bar.jpg')
		);
	}


	////////////////////////////////
	// Tests concerning cleanUp().
	////////////////////////////////

	public function testCleanUpRemovesAFolderCreatedByTheImporter() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->createExtractionFolder($this->importFolder . 'foo.zip');
		$this->fixture->cleanUp($this->importFolder);

		$this->assertFalse(
			is_dir($this->importFolder . 'foo/')
		);
	}

	public function testCleanUpDoesNotRemoveAForeignFolderAlthoughItIsNamedLikeAZipToImport() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		t3lib_div::mkdir($this->importFolder . 'foo/');
		$this->fixture->cleanUp($this->importFolder);

		$this->assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
	}

	public function testCleanUpDoesNotRemoveZipThatIsNotMarkedAsDeletable() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->cleanUp($this->importFolder . 'foo.zip');

		$this->assertTrue(
			file_exists($this->importFolder . 'foo.zip')
		);
	}

	public function testCleanUpRemovesCreatedFolderAlthoughTheExtractedArchiveContainsAFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('contains-folder.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			is_dir($this->importFolder . 'contains-folder/')
		);
	}

	public function testCleanUpDoesNotRemovesZipWithOneXmlInItIfDeletingZipsIsDisabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->globalConfiguration->setAsBoolean('deleteZipsAfterImport', FALSE);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	public function testCleanUpRemovesZipWithOneXmlInItIfDeletingZipsIsEnabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		// 'deleteZipsAfterImport' is set to TRUE during setUp()
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	public function testCleanUpDoesNotRemoveZipWithoutXmls() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('empty.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'empty.zip')
		);
	}

	public function testCleanUpDoesNotRemoveZipWithTwoXmls() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar-bar.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'bar-bar.zip')
		);
	}

	public function testCleanUpRemovesZipFileInASubFolderOfTheImportFolder() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		// just to ensure the import folder exists
		$this->copyTestFileIntoImportFolder('empty.zip');
		// copyTestFileIntoImportFolder() cannot copy folders
		t3lib_div::mkdir($this->importFolder . 'changed-copy-of-same-name/');
		copy(
			t3lib_extMgm::extPath('realty') . 'tests/fixtures/tx_realty_fixtures/' .
				'changed-copy-of-same-name/same-name.zip',
			$this->importFolder . 'changed-copy-of-same-name/same-name.zip'
		);

		$this->fixture->importFromZip();

		$this->assertFalse(
			file_exists($this->importFolder . 'changed-copy-of-same-name/same-name.zip')
		);
	}

	public function testCleanUpDoesNotRemoveZipOfUnregisteredOwnerIfOwnerRestrictionIsEnabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		// 'deleteZipsAfterImport' is set to TRUE during setUp()
		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	public function testCleanUpRemovesZipOfRegisteredOwnerIfOwnerRestrictionIsEnabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->testingFramework->createFrontendUser(
			'', array('tx_realty_openimmo_anid' => 'foo')
		);
		// 'deleteZipsAfterImport' is set to TRUE during setUp()
		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	public function testCleanUpDoesNotRemoveZipIfOwnerWhichHasReachedObjectLimitDuringImport() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$this->testingFramework->createFrontendUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
			)
		);

		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);

		$this->copyTestFileIntoImportFolder('two-objects.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'two-objects.zip')
		);
	}

	public function testCleanUpDoesNotRemoveIfZipOwnerWhichHasNoObjectsLeftToEnter() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array('owner' => $feUserUid)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('two-objects.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'two-objects.zip')
		);
	}


	////////////////////////////////////////////////////////
	// Tests concering loading and importing the XML file.
	////////////////////////////////////////////////////////

	public function testLoadXmlFileIfFolderWithOneXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

		$this->assertEquals(
			'DOMDocument',
			get_class($this->fixture->getImportedXml())
		);
	}

	public function testLoadXmlFileIfXmlIsValid() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

		$this->assertEquals(
			'DOMDocument',
			get_class($this->fixture->getImportedXml())
		);
	}

	public function testLoadXmlFileIfXmlIsInvalid() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar.zip');
		$this->fixture->extractZip($this->importFolder . 'bar.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'bar.zip');

		$this->assertEquals(
			'DOMDocument',
			get_class($this->fixture->getImportedXml())
		);
	}

	public function testImportARecordAndImportItAgainAfterContentsHaveChanged() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->disableValidation();
		$this->fixture->importFromZip();
		$result = tx_oelib_db::selectSingle(
			'uid',
			REALTY_TABLE_OBJECTS,
			'object_number = "bar1234567" AND zip = "zip"'
		);

		// overwrites "same-name.zip" in the import folder
		$this->copyTestFileIntoImportFolder('changed-copy-of-same-name/same-name.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" AND zip="changed zip" '
					.'AND uid='.$result['uid']
			)
		);
	}

	public function testImportFromZipSkipsRecordsIfAFolderNamedLikeTheRecordAlreadyExists() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('foo.zip');
		t3lib_div::mkdir($this->importFolder . 'foo/');
		$result = $this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_surplus_folder'),
			$result
		);
		$this->assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
	}

	public function testImportFromZipImportsFromZipFileInASubFolderOfTheImportFolder() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		// just to ensure the import folder exists
		$this->copyTestFileIntoImportFolder('empty.zip');
		// copyTestFileIntoImportFolder() cannot copy folders
		t3lib_div::mkdir($this->importFolder . 'changed-copy-of-same-name/');
		copy(
			t3lib_extMgm::extPath('realty') . 'tests/fixtures/tx_realty_fixtures/' .
				'changed-copy-of-same-name/same-name.zip',
			$this->importFolder . 'changed-copy-of-same-name/same-name.zip'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" AND zip="changed zip" '
			)
		);
	}

	public function testRecordIsNotWrittenToTheDatabaseIfTheRequiredFieldsAreNotSet() {
		$this->testingFramework->markTableAsDirty(
			REALTY_TABLE_OBJECTS.','.REALTY_TABLE_HOUSE_TYPES
		);

		$objectNumber = 'bar1234567';
		$dummyDocument = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>foobar</strasse>'
							.'<plz>bar</plz>'
						.'</geo>'
						.'<freitexte>'
							.'<lage>foo</lage>'
						.'</freitexte>'
						.'<verwaltung_techn>'
							.'<objektnr_extern>'.$objectNumber.'</objektnr_extern>'
						.'</verwaltung_techn>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . $objectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testRecordIsWrittenToTheDatabaseIfRequiredFieldsAreSet() {
		$this->testingFramework->markTableAsDirty(
			REALTY_TABLE_OBJECTS.','.REALTY_TABLE_HOUSE_TYPES
		);

		$objectNumber = 'bar1234567';
		$dummyDocument = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<objektkategorie>'
							.'<nutzungsart WOHNEN="1"/>'
							.'<vermarktungsart KAUF="1"/>'
							.'<objektart><zimmer/></objektart>'
						.'</objektkategorie>'
						.'<geo>'
							.'<plz>bar</plz>'
						.'</geo>'
						.'<kontaktperson>'
							.'<name>bar</name>'
							.'<email_zentrale>bar</email_zentrale>'
						.'</kontaktperson>'
						.'<verwaltung_techn>'
							.'<openimmo_obid>foo</openimmo_obid>'
							.'<aktion/>'
							.'<objektnr_extern>'.$objectNumber.'</objektnr_extern>'
						.'</verwaltung_techn>'
					.'</immobilie>'
					.'<openimmo_anid>foo</openimmo_anid>'
					.'<firma>bar</firma>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . $objectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testExistingNonHiddenRecordCanBeSetToDeletedInTheDatabase() {
		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array('object_number' => $objectNumber, 'openimmo_obid' => $objectId)
		);
		$dummyDocument = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
							'<email_zentrale>bar</email_zentrale>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>' . $objectId . '</openimmo_obid>' .
							'<aktion aktionart="DELETE" />' .
							'<objektnr_extern>' . $objectNumber . '</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . $objectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testExistingHiddenRecordCanBeSetToDeletedInTheDatabase() {
		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
				'hidden' => TRUE,
			)
		);
		$dummyDocument = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
							'<email_zentrale>bar</email_zentrale>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>' . $objectId . '</openimmo_obid>' .
							'<aktion aktionart="DELETE" />' .
							'<objektnr_extern>' . $objectNumber . '</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . $objectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS, 1)
			)
		);
	}

	public function testEnsureContactEmailNotChangesAddressIfValidAddressIsSet() {
		$this->fixture->loadRealtyObject(
			array('contact_email' => 'foo-valid@email-address.org')
		);
		$this->fixture->ensureContactEmail();

		$this->assertEquals(
			'foo-valid@email-address.org',
			$this->fixture->getContactEmailFromRealtyObject()
		);
	}

	public function testEnsureContactEmailSetsDefaultAddressIfEmptyAddressSet() {
		$this->globalConfiguration->setAsString(
			'emailAddress',
			'default_address@email-address.org'
		);
		$this->fixture->loadRealtyObject(array('contact_email' => ''));
		$this->fixture->ensureContactEmail();

		$this->assertEquals(
			'default_address@email-address.org',
			$this->fixture->getContactEmailFromRealtyObject()
		);
	}

	public function testEnsureContactEmailSetsDefaultAddressIfInvalidAddressIsSet() {
		$this->globalConfiguration->setAsString(
			'emailAddress',
			'default_address@email-address.org'
		);
		$this->fixture->loadRealtyObject(array('contact_email' => 'foo'));
		$this->fixture->ensureContactEmail();

		$this->assertEquals(
			'default_address@email-address.org',
			$this->fixture->getContactEmailFromRealtyObject()
		);
	}

	public function testImportStoresZipsWithLeadingZeroesIntoDb() {
		$this->testingFramework->markTableAsDirty(
			REALTY_TABLE_OBJECTS . ',' . REALTY_TABLE_HOUSE_TYPES
		);

		$objectNumber = 'bar1234567';
		$dummyDocument = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>01234</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
							'<email_zentrale>bar</email_zentrale>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<aktion/>' .
							'<objektnr_extern>' .
								$objectNumber .
							'</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . $objectNumber . '" AND zip="01234"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportStoresNumberOfRoomsWithDecimalsIntoDb() {
		$this->testingFramework->markTableAsDirty(
			REALTY_TABLE_OBJECTS . ',' . REALTY_TABLE_HOUSE_TYPES
		);

		$objectNumber = 'bar1234567';
		$dummyDocument = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<flaechen>' .
							'<anzahl_zimmer>1.25</anzahl_zimmer>' .
						'</flaechen>' .
						'<geo>' .
							'<plz>01234</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>' .
								$objectNumber .
							'</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . $objectNumber . '" AND ' .
					'number_of_rooms = 1.25' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportUtf8FileWithCorrectUmlauts() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('charset-UTF8.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'openimmo_anid="test-anid-with-umlaut-ü"'
			)
		);
	}

	public function testImportUtf8FileWithUtf8AsDefaultEncodingAndNoXmlPrologueWithCorrectUmlauts() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('charset-UTF8-default.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'openimmo_anid="test-anid-with-umlaut-ü"'
			)
		);
	}

	public function testImpotIso8859_1FileWithCorrectUmlauts() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('charset-ISO8859-1.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'openimmo_anid="test-anid-with-umlaut-ü"'
			)
		);
	}


	//////////////////////////////////////////////////////////////////
	// Tests concerning the restricted import for registered owners.
	//////////////////////////////////////////////////////////////////

	public function testRecordWithAnidThatMatchesAnExistingFeUserIsImportedForEnabledOwnerRestriction() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$feUserUid = $this->testingFramework->createFrontendUser(
			'',	array('tx_realty_openimmo_anid' => 'foo')
		);
		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'openimmo_anid="foo" AND owner=' . $feUserUid
			)
		);
	}

	public function testRecordWithAnidThatDoesNotMatchAnExistingFeUserIsNotImportedForEnabledOwnerRestriction() {
		$this->checkForZipArchive();

		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'openimmo_anid="foo"'
			)
		);
	}

	public function testRecordWithAnidThatMatchesAnExistingFeUserInAnAllowedGroupIsImportedForEnabledOwnerAndGroupRestriction() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid, array('tx_realty_openimmo_anid' => 'foo')
		);
		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'openimmo_anid="foo" AND owner=' . $feUserUid
			)
		);
	}

	public function testRecordWithAnidThatMatchesAnExistingFeUserInAForbiddenGroupIsNotImportedForEnabledOwnerAndGroupRestriction() {
		$this->checkForZipArchive();

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid, array('tx_realty_openimmo_anid' => 'foo')
		);
		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid + 1
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'openimmo_anid="foo" AND owner=' . $feUserUid
			)
		);
	}


	////////////////////////////////////////////////
	// Tests concerning the object limit for users
	////////////////////////////////////////////////

	public function testWriteToDatabaseForUserWithObjectLimitReachedDoesNotImportAnyFurtherRecords() {
		$this->testingFramework->markTableAsDirty(
			REALTY_TABLE_OBJECTS . ',' . REALTY_TABLE_HOUSE_TYPES
		);

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array('owner' => $feUserUid)
		);

		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);

		$singleObject = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar1234567</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($singleObject);
		$this->fixture->writeToDatabase($records[0]);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'owner =' . $feUserUid
			)
		);
	}

	public function testWriteToDatabaseForUserWithObjectLimitNotReachedDoesImportRecords() {
		$this->testingFramework->markTableAsDirty(
			REALTY_TABLE_OBJECTS . ',' . REALTY_TABLE_HOUSE_TYPES
		);

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 42,
			)
		);

		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);

		$multipleRecords = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar1234567</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar2345678</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($multipleRecords);
		$this->fixture->writeToDatabase($records[0]);
		$this->fixture->writeToDatabase($records[1]);

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'owner =' . $feUserUid
			)
		);
	}

	public function testWriteToDatabaseForUserWithoutObjectLimitDoesImportRecord() {
		$this->testingFramework->markTableAsDirty(
			REALTY_TABLE_OBJECTS . ',' . REALTY_TABLE_HOUSE_TYPES
		);
		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid, array('tx_realty_openimmo_anid' => 'foo')
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);

		$singleObject = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar1234567</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($singleObject);
		$this->fixture->writeToDatabase($records[0]);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'owner =' . $feUserUid
			)
		);
	}

	public function testWriteToDatabaseForUserWithOneObjectLeftToLimitImportsOnlyOneRecord() {
		$this->testingFramework->markTableAsDirty(
			REALTY_TABLE_OBJECTS . ',' . REALTY_TABLE_HOUSE_TYPES
		);

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
			)
		);

		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);

		$multipleRecords = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar1234567</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar2345678</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($multipleRecords);
		$this->fixture->writeToDatabase($records[0]);
		$this->fixture->writeToDatabase($records[1]);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'owner =' . $feUserUid
			)
		);
	}

	public function testImportFromZipForUserWithObjectLimitReachedReturnsObjectLimitReachedErrorMessage() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->testingFramework->createFrontEndUserGroup();
		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
				'username' => 'fooBar',
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->copyTestFileIntoImportFolder('two-objects.zip');

		$this->assertContains(
			sprintf(
				$this->translator->translate('message_object_limit_reached'),
				'fooBar', $feUserUid, 1
			),
			$this->fixture->importFromZip()
		);
	}


	////////////////////////////////////////////////////////////////////
	// Tests concerning the preparation of e-mails containing the log.
	////////////////////////////////////////////////////////////////////

	public function testPrepareEmailsReturnsEmptyArrayWhenEmptyArrayGiven() {
		$emailData = array();

		$this->assertEquals(
			array(),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsReturnsEmptyArrayWhenInvalidArrayGiven() {
		$emailData = array('invalid' => 'array');

		$this->assertEquals(
			array(),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsFillsEmptyEmailFieldWithDefaultAddressIfNotifyContactPersonsIsEnabled() {
		$this->globalConfiguration->setAsString(
			'emailAddress',
			'default_address@email-address.org'
		);

		$emailData = array(
			array(
				'recipient' => '',
				'objectNumber' => 'foo',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			)
		);

		$this->assertEquals(
			array(
				'default_address@email-address.org' => array(
					array('foo' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsReplacesNonEmptyEmailAddressIfNotifyContactPersonsIsDisabled() {
		$this->globalConfiguration->setAsString(
			'emailAddress',
			'default_address@email-address.org'
		);
		$this->globalConfiguration->setAsBoolean(
			'notifyContactPersons',
			FALSE
		);
		$emailData = array(
			array(
				'recipient' => 'foo-valid@email-address.org',
				'objectNumber' => 'foo',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			)
		);

		$this->assertEquals(
			array(
				'default_address@email-address.org' => array(
					array('foo' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsUsesLogEntryIfOnlyErrorsIsDisabled() {
		$this->globalConfiguration->setAsString(
			'emailAddress',
			'default_address@email-address.org'
		);

		$emailData = array(
			array(
				'recipient' => '',
				'objectNumber' => 'foo',
				'logEntry' => 'log entry',
				'errorLog' => 'error log'
			)
		);

		$this->assertEquals(
			array(
				'default_address@email-address.org' => array(
					array('foo' => 'log entry')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsUsesLogEntryIfOnlyErrorsIsEnabled() {
		$this->globalConfiguration->setAsBoolean('onlyErrors', TRUE);
		$this->globalConfiguration->setAsString(
			'emailAddress',
			'default_address@email-address.org'
		);

		$emailData = array(
			array(
				'recipient' => '',
				'objectNumber' => 'foo',
				'logEntry' => 'log entry',
				'errorLog' => 'error log'
			)
		);

		$this->assertEquals(
			array(
				'default_address@email-address.org' => array(
					array('foo' => 'error log')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsFillsEmptyObjectNumberFieldWithWrapper() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => '',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			)
		);

		$this->assertEquals(
			array(
				'foo' => array(
					array('------' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsSortsMessagesForOneRecepientWhichHaveTheSameObjectNumber() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			),
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => 'foo',
				'errorLog' => 'foo'
			),
		);

		$this->assertEquals(
			array(
				'foo' => array(
					array('number' => 'bar'),
					array('number' => 'foo')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsSortsMessagesForTwoRecepientWhichHaveTheSameObjectNumber() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => 'foo',
				'errorLog' => 'foo'
			),
			array(
				'recipient' => 'bar',
				'objectNumber' => 'number',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			),
		);

		$this->assertEquals(
			array(
				'foo' => array(
					array('number' => 'foo')
				),
				'bar' => array(
					array('number' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsSnipsObjectNumbersWithNothingToReport() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			),
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => '',
				'errorLog' => ''
			)
		);

		$this->assertEquals(
			array(
				'foo' => array(
					array('number' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	public function testPrepareEmailsSnipsRecipientWhoDoesNotReceiveMessages() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => '',
				'errorLog' => ''
			),
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => '',
				'errorLog' => ''
			),
		);

		$this->assertEquals(
			array(),
			$this->fixture->prepareEmails($emailData)
		);
	}


	/////////////////////////////////
	// Test for clearing the cache.
	/////////////////////////////////

	public function testImportFromZipClearsFrontEndCacheAfterImport() {
		if (t3lib_div::int_from_ver(TYPO3_version) < 4003000) {
			$this->markTestSkipped(
				'This test is not applicable for TYPO3 versions lower than 4.3.'
			);
		} elseif (!TYPO3_UseCachingFramework) {
			$this->markTestSkipped(
				'This test is not applicable if the caching framework is disabled.'
			);
		}

		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('foo.zip');
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createContentElement(
			$pageUid, array('list_type' => 'realty_pi1')
		);

		$cachePages = $this->getMock(
			't3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'flushByTags'),
			array(), '', FALSE
		);
		$cachePages->expects($this->once())->method('getIdentifier')
			->will($this->returnValue('cache_pages')
		);
		$cachePages->expects($this->atLeastOnce())->method('flushByTags');

		$GLOBALS['typo3CacheManager'] = new t3lib_cache_Manager();
		$GLOBALS['typo3CacheManager']->registerCache($cachePages);

		$this->fixture->importFromZip();

		$GLOBALS['typo3CacheManager'] = null;
		$cachePages = null;
	}


	///////////////////////////////////////
	// Tests concerning the log messages.
	///////////////////////////////////////

	public function testImportFromZipReturnsLogMessageNoSchemaFileIfTheSchemaFileWasNotSet() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->globalConfiguration->setAsString('openImmoSchema', '');

		$this->assertContains(
			$this->translator->translate('message_no_schema_file'),
			$this->fixture->importFromZip()
		);
	}

	public function testImportFromZipReturnsLogMessageIncorrectSchemaFileIfTheSchemaFilePathWasIncorrect() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->globalConfiguration->setAsString(
			'openImmoSchema', '/any/not/existing/path'
		);

		$this->assertContains(
			$this->translator->translate('message_invalid_schema_file_path'),
			$this->fixture->importFromZip()
		);
	}

	public function testImportFromZipReturnsLogMessageMissingRequiredFields() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->disableValidation();

		$this->assertContains(
			$this->translator->translate('message_fields_required'),
			$this->fixture->importFromZip()
		);
	}

	public function testImportFromZipReturnsLogMessageThatNoRecordWasLoadedForAZipWithNonOpenImmoXml() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar.zip');
		$this->disableValidation();

		$this->assertContains(
			$this->translator->translate('message_object_not_loaded'),
			$this->fixture->importFromZip()
		);
	}

	public function testImportFromZipReturnsMessageThatTheLogWasSentToTheDefaultAddressIfNoRecordWasLoaded() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->disableValidation();

		$this->assertContains(
			'default-address@valid-email.org',
			$this->fixture->importFromZip()
		);
	}

	public function test_ImportFromZip_ForNonExistingImportFolder_ReturnsFolderNotExistingErrorMessage() {
		$this->checkForZipArchive();

		$path = '/any/not/existing/import-path/';
		$this->globalConfiguration->setAsString(
			'importFolder', $path
		);

		$this->assertContains(
			sprintf(
				$this->translator->translate(
					'message_import_directory_not_existing'
				),
				$path,
				get_current_user()
			),
			$this->fixture->importFromZip()
		);
	}

	public function test_ImportFromZip_ForNonExistingUploadFolder_ReturnsFolderNotExistingErrorMessage() {
		$this->checkForZipArchive();
		$this->copyTestFileIntoImportFolder('foo.zip');

		$path = '/any/not/existing/upload-path/';
		$this->fixture->setUploadDirectory($path);

		$this->assertContains(
			sprintf(
				$this->translator->translate(
					'message_upload_directory_not_existing'
				),
				$path
			),
			$this->fixture->importFromZip()
		);
	}


	//////////////////////////////////////////////////////////////
	// Tests for setting the PID depending on the ZIP file name.
	//////////////////////////////////////////////////////////////

	public function testImportedRecordHasTheConfiguredPidByDefault() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->disableValidation();

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" ' .
					'AND pid=' . $this->systemFolderPid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordHasTheConfiguredPidIfTheFilenameHasNoMatches() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->disableValidation();

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName', 'nomatch:'.$pid.';'
		);
		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" ' .
					'AND pid=' . $this->systemFolderPid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidIfTheFilenameMatchesTheOnlyPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName', 'same:'.$pid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" ' .
					'AND pid=' . $pid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testOverridePidCanMatchTheStartOfAString() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName', '^same:'.$pid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" ' .
					'AND pid=' . $pid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testOverridePidCanMatchTheEndOfAString() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName', 'name$:'.$pid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" ' .
					'AND pid=' . $pid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidIfTheFilenameMatchesTheFirstPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'same:'.$pid.';'
				.'nomatch:'.$this->systemFolderPid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" ' .
					'AND pid=' . $pid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidIfTheFilenameMatchesTheLastPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'nomatch:'.$this->systemFolderPid.';'
				.'same:'.$pid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" ' .
					'AND pid=' . $pid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidIfTheFilenameMatchesTheMiddlePattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'nomatch1:'.$this->systemFolderPid.';'
				.'same:'.$pid.';'
				.'nomatch2:'.$this->systemFolderPid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" ' .
					'AND pid=' . $pid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidStopsAtFirstMatchingPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'sam:'.$pid.';'
				.'same:'.$this->systemFolderPid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" ' .
					'AND pid=' . $pid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}


	/////////////////////////////////
	// Testing the e-mail contents.
	/////////////////////////////////
	// * Tests for the subject.
	/////////////////////////////

	public function testEmailSubjectIsSetCorrectly() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			$this->translator->translate('label_subject_openImmo_import'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastSubject()
		);
	}


	//////////////////////////////////////
	// * Tests concerning the recipient.
	//////////////////////////////////////

	public function testEmailIsSentToContactEmailForValidContactEmailAndObjectAsContactDataSource() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('valid-email.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'contact-email-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToDefaultEmailForInvalidContactEmailAndObjectAsContactDataSource() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'default-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToDefaultAddressIfARecordIsNotLoadable() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'default-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToOwnersAddressForMatchingAnidAndNoContactEmailProvidedAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontendUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('with-openimmo-anid.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'owner-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToOwnersAddressForMatchingAnidAndSetContactEmailAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontendUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('with-email-and-openimmo-anid.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'owner-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToContactAddressForNonMatchingAnidAndSetContactEmailAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontendUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'another-test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('with-email-and-openimmo-anid.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'contact-email-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToContactAddressForNoAnidAndSetContactEmailAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontendUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('valid-email.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'contact-email-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToDefaultAddressForNonMatchingAnidAndNoContactEmailAndOwnerContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontendUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'another-test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('with-openimmo-anid.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'default-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToDefaultAddressForNeitherAnidNorContactEmailProvidedAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontendUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'default-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}


	///////////////////////////////////
	// * Testing the e-mail contents.
	///////////////////////////////////

	public function testSentEmailContainsTheObjectNumberLabel() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('label_object_number'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsTheIntroductionMessage() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_introduction'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsTheExplanationMessage() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_explanation'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsMessageThatARecordWasNotImportedForMismatchingAnidsAndEnabledOwnerRestriction() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_openimmo_anid_not_matches_allowed_fe_user'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailForUserWhoReachedHisObjectLimitContainsMessageThatRecordWasNotImported() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid, array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
				'username' => 'fooBar',
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);

		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('two-objects.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			sprintf(
				$this->translator->translate('message_object_limit_reached'),
				'fooBar', $feUserUid, 1
			),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}
}
?>