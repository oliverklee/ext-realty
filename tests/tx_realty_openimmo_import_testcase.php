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
 * Unit tests for the tx_realty_openimmo_import class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty').'tests/fixtures/class.tx_realty_openimmo_import_child.php');

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_testingFramework.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_configurationProxy.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_templatehelper.php');

class tx_realty_openimmo_import_testcase extends tx_phpunit_testcase {
	private $fixture;
	private $testingFramework;
	private $globalConfiguration;
	private $templateHelper;

	private static $importFolder = '/tmp/tx_realty_fixtures/';

	public function setUp() {
		// copies the test folder to /tmp/ to avoid changes to the original folder
		if (!is_dir(self::$importFolder)) {
			mkdir(self::$importFolder);
		}
		exec('cp -rf '
			.t3lib_extMgm::extPath('realty')
			.'tests/fixtures/tx_realty_fixtures/ /tmp/'
		);

		$this->fixture = new tx_realty_openimmo_import_child(true);
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->globalConfiguration = tx_oelib_configurationProxy::getInstance('realty');
		$this->templateHelper = t3lib_div::makeInstance(
			'tx_oelib_templatehelper'
		);
 		$this->templateHelper->init();

		$this->setupStaticConditions();
	}

	public function tearDown() {
		$this->cleanUp();
		unset($this->testingFramework);
		unset($this->templateHelper);
		unset($this->fixture);

		// removes the test folder from /tmp/
		exec('rm -rf '.self::$importFolder);
	}

	public function testGetPathsOfZipsToExtract() {
		$this->assertEquals(
			glob(self::$importFolder.'*.zip'),
			$this->fixture->getPathsOfZipsToExtract(self::$importFolder)
		);
	}

	public function testGetNameForExtractionFolder() {
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
		$dirName = $this->fixture->createExtractionFolder(self::$importFolder.'foo.zip');

		$this->assertTrue(
			is_dir(self::$importFolder.'foo/')
		);
		$this->assertEquals(
			self::$importFolder.'foo/',
			$dirName
		);
	}

	public function testCreateExtractionFolderForNonExistingZip() {
		$dirName = $this->fixture->createExtractionFolder(self::$importFolder.'foobar.zip');

		$this->assertFalse(
			is_dir(self::$importFolder.'foobar/')
		);
		$this->assertEquals(
			'',
			$dirName
		);
	}

	public function testExtractZipIfOneZipToExtract() {
		$this->fixture->extractZip(self::$importFolder.'foo.zip');

		$this->assertTrue(
			is_dir(self::$importFolder.'foo/')
		);
	}

	public function testExtractZipIfZipDoesNotExist() {
		$this->fixture->extractZip(self::$importFolder.'foobar.zip');

		$this->assertFalse(
			is_dir(self::$importFolder.'foobar/')
		);
	}

	public function testCleanUpRemovesAFolderCreatedByTheImporter() {
		$this->fixture->createExtractionFolder(self::$importFolder.'foo.zip');
		$this->fixture->cleanUp(self::$importFolder);

		$this->assertFalse(
			file_exists(self::$importFolder.'foo/')
		);
	}

	public function testCleanUpDoesNotRemoveAForeignFolderAlthoughItIsNamedLikeAZipToImport() {
		mkdir(self::$importFolder.'foo/');
		$this->fixture->cleanUp(self::$importFolder);

		$this->assertTrue(
			file_exists(self::$importFolder.'foo/')
		);
	}

	public function testCleanUpDoesNotRemoveContentsIfFileIsGiven() {
		$this->fixture->cleanUp(self::$importFolder.'foo.zip');

		$this->assertTrue(
			file_exists(self::$importFolder.'foo.zip')
		);
	}

	public function testCopyImagesFromExtractedZip() {
		$this->fixture->extractZip(self::$importFolder.'foo.zip');
		$this->fixture->copyImagesFromExtractedZip(
			self::$importFolder.'foo.zip'
		);

		$this->assertTrue(
			file_exists(self::$importFolder.'foo.jpg')
		);
		$this->assertTrue(
			file_exists(self::$importFolder.'bar.jpg')
		);
	}

	public function testGetPathForXmlIfFolderWithOneXmlExists() {
		$this->fixture->extractZip(self::$importFolder.'foo.zip');

		$this->assertEquals(
			self::$importFolder.'foo/foo.xml',
			$this->fixture->getPathForXml(self::$importFolder.'foo.zip')
		);
	}

	public function testGetPathForXmlIfFolderNotExists() {
		$this->assertEquals(
			'',
			$this->fixture->getPathForXml(self::$importFolder.'foo.zip')
		);
	}

	public function testGetPathForXmlIfFolderWithTwoXmlExists() {
		$this->fixture->extractZip(self::$importFolder.'bar-bar.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml(self::$importFolder.'bar-bar.zip')
		);
	}

	public function testGetPathForXmlIfFolderWithoutXmlExists() {
		$this->fixture->extractZip(self::$importFolder.'empty.zip');

		$this->assertEquals(
			'',
			$this->fixture->getPathForXml(self::$importFolder.'empty.zip')
		);
	}

	public function testLoadXmlFileIfFolderWithOneXmlExists() {
		$this->fixture->extractZip(self::$importFolder.'foo.zip');
		$this->fixture->loadXmlFile(self::$importFolder.'foo.zip');

		$this->assertTrue(
			get_class($this->fixture->getImportedXml()) == 'DOMDocument'
		);
	}

	public function testLoadXmlFileIfXmlIsInvalid() {
		$this->fixture->extractZip(self::$importFolder.'bar.zip');
		$this->fixture->loadXmlFile(self::$importFolder.'bar.zip');
		$this->assertNotEquals(
			get_class($this->fixture->getImportedXml()),
			'DOMDocument'
		);
	}

	public function testLoadXmlFileIfXmlIsValid() {
		$this->fixture->extractZip(self::$importFolder.'foo.zip');
		$this->fixture->loadXmlFile(self::$importFolder.'foo.zip');

		$this->assertTrue(
			get_class($this->fixture->getImportedXml()) == 'DOMDocument'
		);
	}

	public function testLoadXmlFileIfXmlIsInvalidButValidationIsIgnored() {
		$this->globalConfiguration->setConfigurationValueBoolean(
			'ignoreValidation',
			true
		);
		$this->fixture->extractZip(self::$importFolder.'foo.zip');
		$this->fixture->loadXmlFile(self::$importFolder.'foo.zip');

		$this->assertTrue(
			get_class($this->fixture->getImportedXml()) == 'DOMDocument'
		);
	}

	public function testWriteToDatabaseIfDomDocumentWhenRequiredFieldsNotGiven() {
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
				'tx_realty_objects',
				'object_number="'.$objectNumber.'"'
					.$this->templateHelper->enableFields('tx_realty_objects')
			)
		);
	}

	public function testWriteToDatabaseIfDomDocumentWhenRequiredFieldsAreGiven() {
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
				'tx_realty_objects',
				'object_number="'.$objectNumber.'"'
					.$this->templateHelper->enableFields('tx_realty_objects')
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

	public function testFindContactEmailsIfEmailNotExists() {
		$this->fixture->extractZip(self::$importFolder.'foo.zip');

		$this->assertEquals(
			array(),
			$this->fixture->findContactEmails(self::$importFolder.'foo.zip')
		);
	}

	public function testFindContactEmailsIfEmailExists() {
		$this->fixture->extractZip(self::$importFolder.'email.zip');

		$this->assertEquals(
			array('bar'),
			$this->fixture->findContactEmails(self::$importFolder.'email.zip')
		);
	}

	public function testClearFeCacheDeletesCachedPage() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$contentUid = $this->testingFramework->createContentElement(
			$pageUid,
			array('list_type' => 'tx_realty_pi1')
		);
		$this->testingFramework->createPageCacheEntry($contentUid);

		$this->fixture->clearFeCache();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				'cache_pages',
				'page_id='.$pageUid
			)
		);
	}

	public function testImportARecordAndImportItAgainAfterContentsHaveChanged() {
		// disables validation
		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema',
			''
		);
		$this->fixture->importFromZip();
		$result = $this->testingFramework->getAssociativeDatabaseResult(
			$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'tx_realty_objects',
				'object_number="bar1234567" AND zip="zip"'
			)
		);

		// overwrites "same-name.zip"
		exec('cp -f '
			.self::$importFolder.'changed-copy-of-same-name/same-name.zip '
			.self::$importFolder.'same-name.zip'
		);
		$this->fixture->importFromZip();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" AND zip="changed zip" '
					.'AND uid='.$result['uid']
			)
		);
	}

	public function testImportFromZipDoesNotWriteImportFolderContentsToDatabase() {
		global $LANG;

		$result = $this->fixture->importFromZip();
		$this->assertNotContains(
			$LANG->getLL('message_written_to_database'),
			$result
		);
	}

	public function testImportFromZipSkipsRecordsIfAFolderNamedLikeTheRecordAlreadyExists() {
		global $LANG;

		mkdir(self::$importFolder.'foo/');
		$result = $this->fixture->importFromZip();

		$this->assertContains(
			$LANG->getLL('message_surplus_folder'),
			$result
		);
		$this->assertTrue(
			is_dir(self::$importFolder.'foo/')
		);
	}

	public function testImportFromZipReturnsLogMessageNoSchemaFileIfTheSchemaFileWasNotSet() {
		global $LANG;

		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema',
			''
		);

		$result = $this->fixture->importFromZip();
		$this->assertContains(
			$LANG->getLL('message_no_schema_file'),
			$result
		);
	}

	public function testImportFromZipReturnsLogMessageIncorrectSchemaFileIfTheSchemaFilePathWasIncorrect() {
		global $LANG;

		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema',
			'/any/not/existing/path'
		);

		$result = $this->fixture->importFromZip();
		$this->assertContains(
			$LANG->getLL('message_invalid_schema_file_path'),
			$result
		);
	}

	public function testImportFromZipReturnsLogMessageNoSchemaFileIfNoSchemaFileWasSet() {
		global $LANG;

		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema',
			''
		);

		$result = $this->fixture->importFromZip();
		$this->assertContains(
			$LANG->getLL('message_no_schema_file'),
			$result
		);
	}

	public function testImportFromZipReturnsLogMessageMissingRequiredFields() {
		global $LANG;

		// validation needs to be disabled for this test
		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema',
			''
		);

		$result = $this->fixture->importFromZip();
		$this->assertContains(
			$LANG->getLL('message_fields_required'),
			$result
		);
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
		$this->fixture->setUploadDirectory(self::$importFolder);
		// avoids sending e-mails
		$this->globalConfiguration->setConfigurationValueString(
			'emailAddress',
			''
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'ignoreValidation',
			false
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'onlyErrors',
			false
		);
		$this->globalConfiguration->setConfigurationValueString(
			'openImmoSchema',
			self::$importFolder.'schema.xsd'
		);
		$this->globalConfiguration->setConfigurationValueString(
			'importFolder',
			self::$importFolder
		);
		$this->globalConfiguration->setConfigurationValueBoolean(
			'notifyContactPersons',
			true
		);
	}

	/**
	 * Cleans up the tables in which dummy records are created during the tests.
	 */
	private function cleanUp() {
		foreach (array('tx_realty_objects', 'tx_realty_house_types') as $table) {
			$this->testingFramework->markTableAsDirty($table);
		}
		$this->testingFramework->cleanUp();
	}
}

?>
