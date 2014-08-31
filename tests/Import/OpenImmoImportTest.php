<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2014 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Import_OpenImmoImportTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_openImmoImport
	 */
	private $fixture = NULL;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework = NULL;

	/**
	 * @var tx_oelib_configurationProxy
	 */
	private $globalConfiguration = NULL;

	/**
	 * @var tx_realty_translator
	 */
	private $translator = NULL;

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

	/**
	 * @var t3lib_cache_Manager
	 */
	private $cacheManager = NULL;

	/**
	 * backup of $GLOBALS['TYPO3_CONF_VARS']['GFX']
	 *
	 * @var array
	 */
	private $graphicsConfigurationBackup;

	/**
	 * @var t3lib_mail_Message
	 */
	private $message = NULL;

	protected function setUp() {
		$this->graphicsConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['GFX'];

		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->systemFolderPid = $this->testingFramework->createSystemFolder();
		$this->importFolder = PATH_site . 'typo3temp/tx_realty_fixtures/';

		tx_oelib_MapperRegistry::getInstance()->activateTestingMode($this->testingFramework);

		$this->globalConfiguration = tx_oelib_configurationProxy::getInstance('realty');

		$this->translator = new tx_realty_translator();
		$this->cacheManager = $GLOBALS['typo3CacheManager'];

		$this->fixture = new tx_realty_openImmoImportChild(TRUE);
		$this->setupStaticConditions();

		$this->message = $this->getMock('t3lib_mail_Message', array('send', '__destruct'));
		t3lib_div::addInstance('t3lib_mail_Message', $this->message);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
		$this->deleteTestImportFolder();
		t3lib_div::purgeInstances();

		$GLOBALS['typo3CacheManager'] = $this->cacheManager;
		$GLOBALS['TYPO3_CONF_VARS']['GFX'] = $this->graphicsConfigurationBackup;

		unset(
			$this->fixture, $this->translator, $this->testingFramework, $this->message,
			$this->globalConfiguration, $this->cacheManager, $this->graphicsConfigurationBackup
		);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Sets the global configuration values which need to be static during the
	 * tests.
	 *
	 * @return void
	 */
	private function setupStaticConditions() {
		// avoids using the extension's real upload folder
		$this->fixture->setUploadDirectory($this->importFolder);

		// TYPO3 default configuration
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
			= 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai';

		$this->globalConfiguration->setAsString(
			'emailAddress', 'default-address@example.org'
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
		$this->globalConfiguration->setAsString(
			'emailTemplate',
			'EXT:realty/lib/tx_realty_emailNotification.tmpl'
		);
	}

	/**
	 * Disables the XML validation.
	 *
	 * @return void
	 */
	private function disableValidation() {
		$this->globalConfiguration->setAsString('openImmoSchema', '');
	}

	/**
	 * Copies a file or a folder from the extension's tests/fixtures/ folder
	 * into the temporary test import folder.
	 *
	 * @param string $fileName
	 *        File or folder to copy. Must be a relative path to existent files within the tests/fixtures/ folder.
	 *        Leave empty to create an empty import folder.
	 * @param string $newFileName
	 *        new file name in case it should be different from the original one, may be empty
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	private function deleteTestImportFolder() {
		if ($this->testImportFolderExists) {
			t3lib_div::rmdir($this->importFolder, TRUE);
			$this->testImportFolderExists = FALSE;
		}
	}

	/**
	 * Checks if the ZIPArchive class is available. If it is not available, the
	 * current test will be marked as skipped.
	 *
	 * @return void
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

	/**
	 * @test
	 */
	public function getPathsOfZipsToExtract() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->copyTestFileIntoImportFolder('bar.zip');

		$this->assertEquals(
			glob($this->importFolder . '*.zip'),
			array_values(
				$this->fixture->getPathsOfZipsToExtract($this->importFolder)
			)
		);
	}

	/**
	 * @test
	 */
	public function getNameForExtractionFolder() {
		$this->copyTestFileIntoImportFolder('bar.zip');

		$this->assertEquals(
			'bar/',
			$this->fixture->getNameForExtractionFolder('bar.zip')
		);
	}

	/**
	 * @test
	 */
	public function unifyPathDoesNotChangeCorrectPath() {
		$this->assertEquals(
			'correct/path/',
			$this->fixture->unifyPath('correct/path/')
		);
	}

	/**
	 * @test
	 */
	public function unifyPathTrimsAndAddsNecessarySlash() {
		$this->assertEquals(
			'incorrect/path/',
			$this->fixture->unifyPath('incorrect/path')
		);
	}

	/**
	 * @test
	 */
	public function createExtractionFolderForExistingZip() {
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

	/**
	 * @test
	 */
	public function createExtractionFolderForNonExistingZip() {
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

	/**
	 * @test
	 */
	public function extractZipIfOneZipToExtractExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');

		$this->assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
	}

	/**
	 * @test
	 */
	public function extractZipIfZipDoesNotExist() {
		$this->copyTestFileIntoImportFolder('');
		$this->fixture->extractZip($this->importFolder . 'foobar.zip');

		$this->assertFalse(
			is_dir($this->importFolder . 'foobar/')
		);
	}

	/**
	 * @test
	 */
	public function getPathForXmlIfFolderWithOneXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');

		$this->assertEquals(
			$this->importFolder . 'foo/foo.xml',
			$this->fixture->getPathForXml($this->importFolder . 'foo.zip')
		);
	}

	/**
	 * @test
	 */
	public function getPathForXmlIfFolderNotExists() {
		$this->copyTestFileIntoImportFolder('foo.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'foo.zip')
		);
	}

	/**
	 * @test
	 */
	public function getPathForXmlIfFolderWithTwoXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar-bar.zip');
		$this->fixture->extractZip($this->importFolder . 'bar-bar.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'bar-bar.zip')
		);
	}

	/**
	 * @test
	 */
	public function getPathForXmlIfFolderWithoutXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('empty.zip');
		$this->fixture->extractZip($this->importFolder . 'empty.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'empty.zip')
		);
	}


	////////////////////////////////////////////////////////////
	// Tests concerning copyImagesAndDocumentsFromExtractedZip
	////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipCopiesJpgImagesIntoTheUploadFolder() {
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

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipCopiesPdfFilesIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('pdf.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'foo.pdf')
		);
	}

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipNotCopiesPsFilesIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('ps.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			file_exists($this->importFolder . 'foo.ps')
		);
	}

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipCopiesJpgImagesWithUppercasedExtensionsIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo-uppercased.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'foo.JPG')
		);
	}

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipTwiceCopiesImagesUniquelyNamedIntoTheUploadFolder() {
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

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipCopiesImagesForRealtyRecord() {
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

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipNotCopiesImagesForRecordWithDeletionFlagSet() {
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

	/**
	 * @test
	 */
	public function cleanUpRemovesAFolderCreatedByTheImporter() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->createExtractionFolder($this->importFolder . 'foo.zip');
		$this->fixture->cleanUp($this->importFolder);

		$this->assertFalse(
			is_dir($this->importFolder . 'foo/')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveAForeignFolderAlthoughItIsNamedLikeAZipToImport() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		t3lib_div::mkdir($this->importFolder . 'foo/');
		$this->fixture->cleanUp($this->importFolder);

		$this->assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipThatIsNotMarkedAsDeletable() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->cleanUp($this->importFolder . 'foo.zip');

		$this->assertTrue(
			file_exists($this->importFolder . 'foo.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpRemovesCreatedFolderAlthoughTheExtractedArchiveContainsAFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('contains-folder.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			is_dir($this->importFolder . 'contains-folder/')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemovesZipWithOneXmlInItIfDeletingZipsIsDisabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

		$this->globalConfiguration->setAsBoolean('deleteZipsAfterImport', FALSE);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpRemovesZipWithOneXmlInItIfDeletingZipsIsEnabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

		// 'deleteZipsAfterImport' is set to TRUE during setUp()
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipWithoutXmls() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('empty.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'empty.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipWithTwoXmls() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar-bar.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'bar-bar.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpRemovesZipFileInASubFolderOfTheImportFolder() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipOfUnregisteredOwnerIfOwnerRestrictionIsEnabled() {
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

	/**
	 * @test
	 */
	public function cleanUpRemovesZipOfRegisteredOwnerIfOwnerRestrictionIsEnabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipIfOwnerWhichHasReachedObjectLimitDuringImport() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveIfZipOwnerWhichHasNoObjectsLeftToEnter() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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
	// Tests concerning loading and importing the XML file.
	////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function loadXmlFileIfFolderWithOneXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

		$this->assertEquals(
			'DOMDocument',
			get_class($this->fixture->getImportedXml())
		);
	}

	/**
	 * @test
	 */
	public function loadXmlFileIfXmlIsValid() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

		$this->assertEquals(
			'DOMDocument',
			get_class($this->fixture->getImportedXml())
		);
	}

	/**
	 * @test
	 */
	public function loadXmlFileIfXmlIsInvalid() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar.zip');
		$this->fixture->extractZip($this->importFolder . 'bar.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'bar.zip');

		$this->assertEquals(
			'DOMDocument',
			get_class($this->fixture->getImportedXml())
		);
	}

	/**
	 * @test
	 */
	public function importARecordAndImportItAgainAfterContentsHaveChanged() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function importFromZipSkipsRecordsIfAFolderNamedLikeTheRecordAlreadyExists() {
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

	/**
	 * @test
	 */
	public function importFromZipImportsFromZipFileInASubFolderOfTheImportFolder() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function recordIsNotWrittenToTheDatabaseIfTheRequiredFieldsAreNotSet() {
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

	/**
	 * @test
	 */
	public function recordIsWrittenToTheDatabaseIfRequiredFieldsAreSet() {
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

	/**
	 * @test
	 */
	public function existingNonHiddenRecordCanBeSetToDeletedInTheDatabase() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function existingHiddenRecordCanBeSetToDeletedInTheDatabase() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function ensureContactEmailNotChangesAddressIfValidAddressIsSet() {
		$this->fixture->loadRealtyObject(
			array('contact_email' => 'foo-valid@email-address.org')
		);
		$this->fixture->ensureContactEmail();

		$this->assertEquals(
			'foo-valid@email-address.org',
			$this->fixture->getContactEmailFromRealtyObject()
		);
	}

	/**
	 * @test
	 */
	public function ensureContactEmailSetsDefaultAddressIfEmptyAddressSet() {
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

	/**
	 * @test
	 */
	public function ensureContactEmailSetsDefaultAddressIfInvalidAddressIsSet() {
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

	/**
	 * @test
	 */
	public function importStoresZipsWithLeadingZeroesIntoDb() {
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

	/**
	 * @test
	 */
	public function importStoresNumberOfRoomsWithDecimalsIntoDb() {
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

	/**
	 * @test
	 */
	public function importUtf8FileWithCorrectUmlauts() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

		$this->copyTestFileIntoImportFolder('charset-UTF8.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'openimmo_anid="test-anid-with-umlaut-ü"'
			)
		);
	}

	/**
	 * @test
	 */
	public function importUtf8FileWithUtf8AsDefaultEncodingAndNoXmlPrologueWithCorrectUmlauts() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

		$this->copyTestFileIntoImportFolder('charset-UTF8-default.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'openimmo_anid="test-anid-with-umlaut-ü"'
			)
		);
	}

	/**
	 * @test
	 */
	public function importIso88591FileWithCorrectUmlauts() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function recordWithAnidThatMatchesAnExistingFeUserIsImportedForEnabledOwnerRestriction() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function recordWithAnidThatDoesNotMatchAnExistingFeUserIsNotImportedForEnabledOwnerRestriction() {
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

	/**
	 * @test
	 */
	public function recordWithAnidThatMatchesAnExistingFeUserInAnAllowedGroupIsImportedForEnabledOwnerAndGroupRestriction() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function recordWithAnidThatMatchesAnExistingFeUserInAForbiddenGroupIsNotImportedForEnabledOwnerAndGroupRestriction() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseForUserWithObjectLimitReachedDoesNotImportAnyFurtherRecords() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseForUserWithObjectLimitNotReachedDoesImportRecords() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseForUserWithoutObjectLimitDoesImportRecord() {
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

	/**
	 * @test
	 */
	public function writeToDatabaseForUserWithOneObjectLeftToLimitImportsOnlyOneRecord() {
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

	/**
	 * @test
	 */
	public function importFromZipForUserWithObjectLimitReachedReturnsObjectLimitReachedErrorMessage() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function prepareEmailsReturnsEmptyArrayWhenEmptyArrayGiven() {
		$emailData = array();

		$this->assertEquals(
			array(),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsReturnsEmptyArrayWhenInvalidArrayGiven() {
		$emailData = array('invalid' => 'array');

		$this->assertEquals(
			array(),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsFillsEmptyEmailFieldWithDefaultAddressIfNotifyContactPersonsIsEnabled() {
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

	/**
	 * @test
	 */
	public function prepareEmailsReplacesNonEmptyEmailAddressIfNotifyContactPersonsIsDisabled() {
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

	/**
	 * @test
	 */
	public function prepareEmailsUsesLogEntryIfOnlyErrorsIsDisabled() {
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

	/**
	 * @test
	 */
	public function prepareEmailsUsesLogEntryIfOnlyErrorsIsEnabled() {
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

	/**
	 * @test
	 */
	public function prepareEmailsFillsEmptyObjectNumberFieldWithWrapper() {
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

	/**
	 * @test
	 */
	public function prepareEmailsSortsMessagesForOneRecepientWhichHaveTheSameObjectNumber() {
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

	/**
	 * @test
	 */
	public function prepareEmailsSortsMessagesForTwoRecepientWhichHaveTheSameObjectNumber() {
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

	/**
	 * @test
	 */
	public function prepareEmailsSnipsObjectNumbersWithNothingToReport() {
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

	/**
	 * @test
	 */
	public function prepareEmailsSnipsRecipientWhoDoesNotReceiveMessages() {
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

	/**
	 * @test
	 */
	public function importFromZipClearsFrontEndCacheAfterImportInOldTypo3() {
		if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 4006000) {
			$this->markTestSkipped('This test is not applicable for TYPO3 >= 4.6.');
		}
		if (!TYPO3_UseCachingFramework) {
			$this->markTestSkipped('This test is not applicable if the caching framework is disabled.');
		}

		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('foo.zip');
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createContentElement($pageUid, array('list_type' => 'realty_pi1'));

		$cacheFrontEnd = $this->getMock(
			't3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'flushByTags'),
			array(), '', FALSE
		);
		$cacheFrontEnd->expects($this->once())->method('getIdentifier')->will($this->returnValue('cache_pages'));
		$cacheFrontEnd->expects($this->atLeastOnce())->method('flushByTags');

		$GLOBALS['typo3CacheManager'] = new t3lib_cache_Manager();
		$GLOBALS['typo3CacheManager']->registerCache($cacheFrontEnd);

		$this->fixture->importFromZip();

		$GLOBALS['typo3CacheManager'] = NULL;
	}

	/**
	 * @test
	 */
	public function importFromZipClearsFrontEndCacheAfterImport() {
		if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) < 4006000) {
			$this->markTestSkipped('This test is not applicable for TYPO3 < 4.6.');
		}

		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('foo.zip');
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createContentElement($pageUid, array('list_type' => 'realty_pi1'));

		/** @var $cacheFrontEnd t3lib_cache_frontend_AbstractFrontend|PHPUnit_Framework_MockObject_MockObject */
		$cacheFrontEnd = $this->getMock(
			't3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'getBackend'),
			array(), '', FALSE
		);
		$cacheFrontEnd->expects($this->once())->method('getIdentifier')->will($this->returnValue('cache_pages'));
		/** @var $cacheBackEnd t3lib_cache_backend_Backend|PHPUnit_Framework_MockObject_MockObject */
		$cacheBackEnd = $this->getMock('t3lib_cache_backend_Backend');
		$cacheFrontEnd->expects($this->any())->method('getBackend')->will($this->returnValue($cacheBackEnd));
		$cacheBackEnd->expects($this->atLeastOnce())->method('flushByTag');

		$GLOBALS['typo3CacheManager'] = new t3lib_cache_Manager();
		$GLOBALS['typo3CacheManager']->registerCache($cacheFrontEnd);

		$this->fixture->importFromZip();

		$GLOBALS['typo3CacheManager'] = NULL;
	}


	///////////////////////////////////////
	// Tests concerning the log messages.
	///////////////////////////////////////

	/**
	 * @test
	 */
	public function importFromZipReturnsLogMessageNoSchemaFileIfTheSchemaFileWasNotSet() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->globalConfiguration->setAsString('openImmoSchema', '');

		$this->assertContains(
			$this->translator->translate('message_no_schema_file'),
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipReturnsLogMessageIncorrectSchemaFileIfTheSchemaFilePathWasIncorrect() {
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

	/**
	 * @test
	 */
	public function importFromZipReturnsLogMessageMissingRequiredFields() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->disableValidation();

		$this->assertContains(
			$this->translator->translate('message_fields_required'),
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipReturnsLogMessageThatNoRecordWasLoadedForZipWithNonOpenImmoXml() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar.zip');
		$this->disableValidation();

		$this->assertContains(
			$this->translator->translate('message_object_not_loaded'),
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipReturnsMessageThatTheLogWasSentToTheDefaultAddressIfNoRecordWasLoaded() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->disableValidation();

		$this->assertContains(
			'default-address@example.org',
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipForNonExistingImportFolderReturnsFolderNotExistingErrorMessage() {
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

	/**
	 * @test
	 */
	public function importFromZipForNonExistingUploadFolderReturnsFolderNotExistingErrorMessage() {
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

	/**
	 * @test
	 */
	public function importedRecordHasTheConfiguredPidByDefault() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function importedRecordHasTheConfiguredPidIfTheFilenameHasNoMatches() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function importedRecordOverridesPidIfTheFilenameMatchesTheOnlyPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function overridePidCanMatchTheStartOfAString() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function overridePidCanMatchTheEndOfAString() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function importedRecordOverridesPidIfTheFilenameMatchesTheFirstPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function importedRecordOverridesPidIfTheFilenameMatchesTheLastPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function importedRecordOverridesPidIfTheFilenameMatchesTheMiddlePattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function importedRecordOverridesPidStopsAtFirstMatchingPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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

	/**
	 * @test
	 */
	public function emailSubjectIsSetCorrectly() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertSame(
			$this->translator->translate('label_subject_openImmo_import'),
			$this->message->getSubject()
		);
	}


	//////////////////////////////////////
	// * Tests concerning the recipient.
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function emailIsSentToContactEmailForValidContactEmailAndObjectAsContactDataSource() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('valid-email.zip');
		$this->fixture->importFromZip();

		$this->assertArrayHasKey(
			'contact-email-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToDefaultEmailForInvalidContactEmailAndObjectAsContactDataSource() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertArrayHasKey(
			'default-address@example.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToDefaultAddressIfARecordIsNotLoadable() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		$this->assertArrayHasKey(
			'default-address@example.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToOwnersAddressForMatchingAnidAndNoContactEmailProvidedAndOwnerAsContactDataSource() {
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

		$this->assertArrayHasKey(
			'owner-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToOwnersAddressForMatchingAnidAndSetContactEmailAndOwnerAsContactDataSource() {
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

		$this->assertArrayHasKey(
			'owner-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToContactAddressForNonMatchingAnidAndSetContactEmailAndOwnerAsContactDataSource() {
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

		$this->assertArrayHasKey(
			'contact-email-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToContactAddressForNoAnidAndSetContactEmailAndOwnerAsContactDataSource() {
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

		$this->assertArrayHasKey(
			'contact-email-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToDefaultAddressForNonMatchingAnidAndNoContactEmailAndOwnerContactDataSource() {
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

		$this->assertArrayHasKey(
			'default-address@example.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToDefaultAddressForNeitherAnidNorContactEmailProvidedAndOwnerAsContactDataSource() {
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

		$this->assertArrayHasKey(
			'default-address@example.org',
			$this->message->getTo()
		);
	}


	///////////////////////////////////
	// * Testing the e-mail contents.
	///////////////////////////////////

	/**
	 * @test
	 */
	public function sentEmailContainsTheObjectNumberLabel() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('label_object_number'),
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsTheIntroductionMessage() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_introduction'),
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsTheExplanationMessage() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_explanation'),
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsMessageThatARecordWasNotImportedForMismatchingAnidsAndEnabledOwnerRestriction() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_openimmo_anid_not_matches_allowed_fe_user'),
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailForUserWhoReachedHisObjectLimitContainsMessageThatRecordWasNotImported() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_HOUSE_TYPES);

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
			$this->message->getBody()
		);
	}
}