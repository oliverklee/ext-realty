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
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_mailerFactory.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_templatehelper.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_translator.php');
require_once(t3lib_extMgm::extPath('realty') . 'tests/fixtures/class.tx_realty_openImmoImportChild.php');

/**
 * Unit tests for the tx_realty_openImmoImport class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_openImmoImport_testcase extends tx_phpunit_testcase {
	/** instance to be tested */
	private $fixture;
	/** instance of tx_oelib_testingFramework */
	private $testingFramework;
	/** instance of tx_oelib_templatehelper */
	private $templateHelper;
	/** instance of tx_oelib_configurationProxy */
	private $globalConfiguration;
	/** instance of tx_realty_translator */
	private $translator;

	/** PID of the system folder where imported records will be stored */
	private $systemFolderPid;
	/** path to the import folder */
	private $importFolder;

	/** whether an import folder has been created */
	private $testImportFolderExists = false;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->systemFolderPid = $this->testingFramework->createSystemFolder();
		$this->importFolder = PATH_site . 'typo3temp/tx_realty_fixtures/';

		$this->globalConfiguration= tx_oelib_configurationProxy::getInstance('realty');

		tx_oelib_mailerFactory::getInstance()->enableTestMode();

		$this->templateHelper= t3lib_div::makeInstance('tx_oelib_templatehelper');
		$this->templateHelper->init();

		$this->translator= t3lib_div::makeInstance('tx_realty_translator');

		$this->fixture = new tx_realty_openImmoImportChild(true);
		$this->setupStaticConditions();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();
		tx_oelib_mailerFactory::getInstance()->discardInstance();
		unset(
			$this->fixture,
			$this->translator,
			$this->templateHelper,
			$this->testingFramework
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
		$this->globalConfiguration->setConfigurationValueString(
			'emailAddress', 'default-address@valid-email.org'
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyErrors', false
		);
		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema', $this->importFolder . 'schema.xsd'
		);
		$this->globalConfiguration->setConfigurationValueString(
			'importFolder', $this->importFolder
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'deleteZipsAfterImport', true
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'notifyContactPersons', true
		);
		$this->globalConfiguration->setConfigurationValueInteger(
			'pidForRealtyObjectsAndImages', $this->systemFolderPid
		);
		$this->globalConfiguration->setConfigurationValueString(
			'pidsForRealtyObjectsAndImagesByFileName', ''
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', false
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyImportForRegisteredFrontEndUsers', false
		);
		$this->globalConfiguration->setConfigurationValueString(
			'allowedFrontEndUserGroups', ''
		);
	}

	/**
	 * Disables the XML validation.
	 */
	private function disableValidation() {
		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema', ''
		);
	}

	/**
	 * Copies a file or a folder from the extension's tests/fixtures/ folder
	 * into the temporary test import folder.
	 *
	 * @param	string		File or folder to copy. Must be a relative path to
	 * 						existent files within the tests/fixtures/ folder.
	 * 						Leave empty to create an empty import folder.
	 * @param string new file name in case it should be different from
	 *               the original one, may be empty
	 */
	private function copyTestFileIntoImportFolder($fileName, $newFileName = '') {
		// creates an import folder if there is none
		if (!is_dir($this->importFolder)) {
			t3lib_div::mkdir($this->importFolder);
		}
		$this->testImportFolderExists = true;

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
			tx_realty_openImmoImport::rmdir($this->importFolder, true);
			$this->testImportFolderExists = false;
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
			$this->fixture->getPathsOfZipsToExtract($this->importFolder)
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

	public function testExtractZipIfOneZipToExtract() {
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
		$this->copyTestFileIntoImportFolder('bar-bar.zip');
		$this->fixture->extractZip($this->importFolder . 'bar-bar.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'bar-bar.zip')
		);
	}

	public function testGetPathForXmlIfFolderWithoutXmlExists() {
		$this->copyTestFileIntoImportFolder('empty.zip');
		$this->fixture->extractZip($this->importFolder . 'empty.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'empty.zip')
		);
	}

	public function testCopyImagesFromExtractedZipCopiesImagesIntoTheUploadFolder() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'foo.jpg')
		);
		$this->assertTrue(
			file_exists($this->importFolder . 'bar.jpg')
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
		$this->copyTestFileIntoImportFolder('contains-folder.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			is_dir($this->importFolder . 'contains-folder/')
		);
	}

	public function testCleanUpDoesNotRemovesZipWithOneXmlInItIfDeletingZipsIsDisabled() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->globalConfiguration->setConfigurationValueBoolean(
			'deleteZipsAfterImport', false
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	public function testCleanUpRemovesZipWithOneXmlInItIfDeletingZipsIsEnabled() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		// 'deleteZipsAfterImport' is set to true during setUp()
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	public function testCleanUpDoesNotRemoveZipWithoutXmls() {
		$this->copyTestFileIntoImportFolder('empty.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'empty.zip')
		);
	}

	public function testCleanUpDoesNotRemoveZipWithTwoXmls() {
		$this->copyTestFileIntoImportFolder('bar-bar.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'bar-bar.zip')
		);
	}

	public function testCleanUpDoesNotRemoveZipOfUnregisteredOwnerIfOwnerRestrictionIsEnabled() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		// 'deleteZipsAfterImport' is set to true during setUp()
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyImportForRegisteredFrontEndUsers', true
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertTrue(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	public function testCleanUpRemovesZipOfRegisteredOwnerIfOwnerRestrictionIsEnabled() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$this->testingFramework->createFrontendUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'foo')
		);
		// 'deleteZipsAfterImport' is set to true during setUp()
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyImportForRegisteredFrontEndUsers', true
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertFalse(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}


	////////////////////////////////////////////////////////
	// Tests concering loading and importing the XML file.
	////////////////////////////////////////////////////////

	public function testLoadXmlFileIfFolderWithOneXmlExists() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

		$this->assertTrue(
			get_class($this->fixture->getImportedXml()) == 'DOMDocument'
		);
	}

	public function testLoadXmlFileIfXmlIsValid() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

		$this->assertTrue(
			get_class($this->fixture->getImportedXml()) == 'DOMDocument'
		);
	}

	public function testLoadXmlFileIfXmlIsInvalid() {
		$this->copyTestFileIntoImportFolder('bar.zip');
		$this->fixture->extractZip($this->importFolder . 'bar.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'bar.zip');

		$this->assertTrue(
			get_class($this->fixture->getImportedXml()) == 'DOMDocument'
		);
	}

	public function testImportARecordAndImportItAgainAfterContentsHaveChanged() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->disableValidation();
		$this->fixture->importFromZip();
		$result = $this->testingFramework->getAssociativeDatabaseResult(
			$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" AND zip="zip"'
			)
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
				'object_number="'.$objectNumber.'"'
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
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
				'object_number="'.$objectNumber.'"'
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
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
					$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
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
				'hidden' => true,
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
					$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS, 1)
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
		$this->globalConfiguration->setConfigurationValueString(
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
		$this->globalConfiguration->setConfigurationValueString(
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


	//////////////////////////////////////////////////////////////////
	// Tests concerning the restricted import for registered owners.
	//////////////////////////////////////////////////////////////////

	public function testRecordWithAnidThatMatchesAnExistingFeUserIsImportedForEnabledOwnerRestriction() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$feUserUid = $this->testingFramework->createFrontendUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('tx_realty_openimmo_anid' => 'foo')
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyImportForRegisteredFrontEndUsers', true
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
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyImportForRegisteredFrontEndUsers', true
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
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid, array('tx_realty_openimmo_anid' => 'foo')
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyImportForRegisteredFrontEndUsers', true
		);
		$this->globalConfiguration->setConfigurationValueString(
			'allowedFrontEndUserGroups', (string) $feUserGroupUid
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
		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontendUser(
			$feUserGroupUid, array('tx_realty_openimmo_anid' => 'foo')
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyImportForRegisteredFrontEndUsers', true
		);
		$this->globalConfiguration->setConfigurationValueString(
			'allowedFrontEndUserGroups', (string) ($feUserGroupUid + 1)
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
		$this->globalConfiguration->setConfigurationValueString(
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
		$this->globalConfiguration->setConfigurationValueString(
			'emailAddress',
			'default_address@email-address.org'
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'notifyContactPersons',
			false
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
		$this->globalConfiguration->setConfigurationValueString(
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
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyErrors',
			true
		);
		$this->globalConfiguration->setConfigurationValueString(
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

	public function testFrontEndCacheIsClearedAfterImport() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$pageUid = $this->testingFramework->createFrontEndPage();
		$contentUid = $this->testingFramework->createContentElement(
			$pageUid,
			array('list_type' => 'tx_realty_pi1')
		);
		$this->testingFramework->createPageCacheEntry($contentUid);

		$this->fixture->importFromZip();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				'cache_pages',
				'page_id='.$pageUid
			)
		);
	}


	///////////////////////////////////////
	// Tests concerning the log messages.
	///////////////////////////////////////

	public function testImportFromZipReturnsLogMessageNoSchemaFileIfTheSchemaFileWasNotSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema', ''
		);

		$this->assertContains(
			$this->translator->translate('message_no_schema_file'),
			$this->fixture->importFromZip()
		);
	}

	public function testImportFromZipReturnsLogMessageIncorrectSchemaFileIfTheSchemaFilePathWasIncorrect() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema', '/any/not/existing/path'
		);

		$this->assertContains(
			$this->translator->translate('message_invalid_schema_file_path'),
			$this->fixture->importFromZip()
		);
	}

	public function testImportFromZipReturnsLogMessageMissingRequiredFields() {
		$this->copyTestFileIntoImportFolder('email.zip');
		$this->disableValidation();

		$this->assertContains(
			$this->translator->translate('message_fields_required'),
			$this->fixture->importFromZip()
		);
	}

	public function testImportFromZipReturnsLogMessageThatNoRecordWasLoadedForAZipWithNonOpenImmoXml() {
		$this->copyTestFileIntoImportFolder('bar.zip');
		$this->disableValidation();

		$this->assertContains(
			$this->translator->translate('message_object_not_loaded'),
			$this->fixture->importFromZip()
		);
	}

	public function testImportFromZipReturnsMessageThatTheLogWasSentToTheDefaultAddressIfNoRecordWasLoaded() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->disableValidation();

		$this->assertContains(
			'default-address@valid-email.org',
			$this->fixture->importFromZip()
		);
	}


	//////////////////////////////////////////////////////////////
	// Tests for setting the PID depending on the ZIP file name.
	//////////////////////////////////////////////////////////////

	public function testImportedRecordHasTheConfiguredPidByDefault() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->disableValidation();

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" '
					.'AND pid='.$this->systemFolderPid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordHasTheConfiguredPidIfTheFilenameHasNoMatches() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->disableValidation();

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setConfigurationValueString(
			'pidsForRealtyObjectsAndImagesByFileName', 'nomatch:'.$pid.';'
		);
		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" '
					.'AND pid='.$this->systemFolderPid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidIfTheFilenameMatchesTheOnlyPattern() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setConfigurationValueString(
			'pidsForRealtyObjectsAndImagesByFileName', 'same:'.$pid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" '
					.'AND pid='.$pid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testOverridePidCanMatchTheStartOfAString() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setConfigurationValueString(
			'pidsForRealtyObjectsAndImagesByFileName', '^same:'.$pid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" '
					.'AND pid='.$pid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testOverridePidCanMatchTheEndOfAString() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setConfigurationValueString(
			'pidsForRealtyObjectsAndImagesByFileName', 'name$:'.$pid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" '
					.'AND pid='.$pid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidIfTheFilenameMatchesTheFirstPattern() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setConfigurationValueString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'same:'.$pid.';'
				.'nomatch:'.$this->systemFolderPid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" '
					.'AND pid='.$pid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidIfTheFilenameMatchesTheLastPattern() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setConfigurationValueString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'nomatch:'.$this->systemFolderPid.';'
				.'same:'.$pid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" '
					.'AND pid='.$pid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidIfTheFilenameMatchesTheMiddlePattern() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setConfigurationValueString(
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
				'object_number="bar1234567" '
					.'AND pid='.$pid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImportedRecordOverridesPidStopsAtFirstMatchingPattern() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setConfigurationValueString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'sam:'.$pid.';'
				.'same:'.$this->systemFolderPid.';'
		);

		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="bar1234567" '
					.'AND pid='.$pid
					.$this->templateHelper->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}


	/////////////////////////////////
	// Testing the e-mail contents.
	/////////////////////////////////
	// * Tests for the subject.
	/////////////////////////////

	public function testEmailSubjectIsSetCorrectly() {
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
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('valid-email.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'contact-email-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToDefaultEmailForInvalidContactEmailAndObjectAsContactDataSource() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'default-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToDefaultAddressIfARecordIsNotLoadable() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		$this->assertEquals(
			'default-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testEmailIsSentToOwnersAddressForMatchingAnidAndNoContactEmailProvidedAndOwnerAsContactDataSource() {
		$this->testingFramework->createFrontendUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', true
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
		$this->testingFramework->createFrontendUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', true
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
		$this->testingFramework->createFrontendUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array(
				'tx_realty_openimmo_anid' => 'another-test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', true
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
		$this->testingFramework->createFrontendUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', true
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
		$this->testingFramework->createFrontendUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array(
				'tx_realty_openimmo_anid' => 'another-test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', true
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
		$this->testingFramework->createFrontendUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', true
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
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('label_object_number'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsTheIntroductionMessage() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_introduction'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsTheExplanationMessage() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_OBJECTS);
		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_explanation'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsMessageThatARecordWasNotImportedForMismatchingAnidsAndEnabledOwnerRestriction() {
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyImportForRegisteredFrontEndUsers', true
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		$this->assertContains(
			$this->translator->translate('message_openimmo_anid_not_matches_allowed_fe_user'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}
}
?>