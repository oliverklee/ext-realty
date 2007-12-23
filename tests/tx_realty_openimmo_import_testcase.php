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
 * Unit tests for the tx_realty_openimmo_import class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty')
	.'tests/fixtures/class.tx_realty_openimmo_import_child.php');

define('REALTY_IMPORT_FOLDER', '/tmp/tx_realty_fixtures/');
define('DUMMY_PAGE_UID', 100000);
define('DUMMY_PAGE_CONTENT_UID', 100001);

class tx_realty_openimmo_import_testcase extends tx_phpunit_testcase {
	private $fixture;

	public function setUp() {
		// copy test folder to /tmp/ to avoid changes to the original folder
		if (!is_dir(REALTY_IMPORT_FOLDER)) {
			mkdir(REALTY_IMPORT_FOLDER);
		}
		exec('cp -rf '
			.t3lib_extMgm::extPath('realty')
			.'/tests/fixtures/tx_realty_fixtures/ /tmp/'
		);
		$this->fixture = new tx_realty_openimmo_import_child();

		$this->createDummyPages();
	}

	public function tearDown() {
		unset($this->fixture);

		$this->deleteDummyPages();
		$this->resetAutoIncrement();

		// remove test folder from /tmp/
		exec('rm -rf '.REALTY_IMPORT_FOLDER);

		// deletes dummy records
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_realty_objects',
			'zip = "bar" AND object_number = "foo"'
		);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_realty_house_types',
			'title = "foo"'
		);
	}

	public function testGetPathsOfZipsToExtract() {
		$this->assertEquals(
			$this->fixture->getPathsOfZipsToExtract(REALTY_IMPORT_FOLDER),
			glob(REALTY_IMPORT_FOLDER.'*.zip')
		);
	}

	public function testGetNameForExtractionFolder() {
		$this->assertEquals(
			$this->fixture->getNameForExtractionFolder('bar.zip'),
			'bar/'
		);
	}

	public function testUnifyImportPathDoesNotChangeCorrectPath() {
		$this->assertEquals(
			$this->fixture->unifyImportPath('correct/path/'),
			'correct/path/'
		);
	}

	public function testUnifyImportPathTrimsAndAddsNescessarySlash() {
		$this->assertEquals(
			$this->fixture->unifyImportPath(' incorrect/path '),
			'incorrect/path/'
		);
	}

	public function testCreateExtractionFolderForExistingZip() {
		$dirName = $this->fixture->createExtractionFolder(REALTY_IMPORT_FOLDER.'foo.zip');

		$this->assertTrue(
			is_dir(REALTY_IMPORT_FOLDER.'foo/')
		);
		$this->assertEquals(
			REALTY_IMPORT_FOLDER.'foo/',
			$dirName
		);
	}

	public function testCreateExtractionFolderForNonExistingZip() {
		$dirName = $this->fixture->createExtractionFolder(REALTY_IMPORT_FOLDER.'foobar.zip');

		$this->assertFalse(
			is_dir(REALTY_IMPORT_FOLDER.'foobar/')
		);
		$this->assertEquals(
			'',
			$dirName
		);
	}

	public function testExtractZipIfOneZipToExtract() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'foo.zip');

		$this->assertTrue(
			is_dir(REALTY_IMPORT_FOLDER.'foo/')
		);
	}

	public function testExtractZipIfZipDoesNotExist() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'foobar.zip');

		$this->assertFalse(
			is_dir(REALTY_IMPORT_FOLDER.'foobar/')
		);
	}

	public function testCleanUpDeletesImportFolder() {
		$this->fixture->createExtractionFolder(REALTY_IMPORT_FOLDER.'foo.zip');
		$this->fixture->cleanUp(REALTY_IMPORT_FOLDER);

		$this->assertFalse(
			file_exists(REALTY_IMPORT_FOLDER.'foo/')
		);
	}

	public function testCleanUpDoesNotRemoveContentsIfFileIsGiven() {
		$this->fixture->cleanUp(REALTY_IMPORT_FOLDER.'foo.zip');

		$this->assertTrue(
			file_exists(REALTY_IMPORT_FOLDER.'foo.zip')
		);
	}

	public function testCopyImagesFromExtractedZip() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'foo.zip');
		$this->fixture->copyImagesFromExtractedZip(
			REALTY_IMPORT_FOLDER.'foo.zip',
			'../../'.REALTY_IMPORT_FOLDER
		);

		$this->assertTrue(
			file_exists(REALTY_IMPORT_FOLDER.'foo.jpg')
		);
		$this->assertTrue(
			file_exists(REALTY_IMPORT_FOLDER.'bar.jpg')
		);
	}

	public function testGetPathForXmlIfFolderWithOneXmlExists() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'foo.zip');

		$this->assertEquals(
			$this->fixture->getPathForXml(REALTY_IMPORT_FOLDER.'foo.zip'),
			REALTY_IMPORT_FOLDER.'foo/foo.xml'
		);
	}

	public function testGetPathForXmlIfFolderNotExists() {
		$this->assertEquals(
			$this->fixture->getPathForXml(REALTY_IMPORT_FOLDER.'foo.zip'),
			''
		);
	}

	public function testGetPathForXmlIfFolderWithTwoXmlExists() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'bar-bar.zip');

		$this->assertEquals(
			$this->fixture->getPathForXml(REALTY_IMPORT_FOLDER.'bar-bar.zip'),
			''
		);
	}

	public function testGetPathForXmlIfFolderWithoutXmlExists() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'empty.zip');

		$this->assertEquals(
			$this->fixture->getPathForXml(REALTY_IMPORT_FOLDER.'empty.zip'),
			''
		);
	}

	public function testLoadXmlFileIfFolderWithOneXmlExists() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'foo.zip');
		$this->fixture->loadXmlFile(REALTY_IMPORT_FOLDER.'foo.zip');

		$this->assertTrue(
			get_class($this->fixture->getImportedXml()) == 'DOMDocument'
		);
	}

	public function testLoadXmlFileIfXmlIsInvalid() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'bar.zip');
		$this->fixture->setSchemaFile(REALTY_IMPORT_FOLDER.'schema.xsd');
		$this->fixture->loadXmlFile(REALTY_IMPORT_FOLDER.'bar.zip');
		$this->assertNotEquals(
			get_class($this->fixture->getImportedXml()),
			'DOMDocument'
		);
	}

	public function testLoadXmlFileIfXmlIsValid() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'foo.zip');
		$this->fixture->setSchemaFile(REALTY_IMPORT_FOLDER.'schema.xsd');
		$this->fixture->loadXmlFile(REALTY_IMPORT_FOLDER.'foo.zip');

		$this->assertTrue(
			get_class($this->fixture->getImportedXml()) == 'DOMDocument'
		);
	}

	public function testWriteToDatabaseIfDomDocumentWhenRequiredFieldsNotGiven() {
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
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		$result = array();
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'street, zip, location',
			'tx_realty_objects',
			''
		);
		if ($dbResult) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))	{
				$result[] = $row;
			}
		}

		$this->assertFalse(
			in_array(
				array(
					'street' => 'foobar',
					'zip' => 'bar',
					'location' => 'foo'
				),
				$result
			)
		);
	}

	public function testWriteToDatabaseIfDomDocumentWhenRequiredFieldsAreGiven() {
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
							.'<objektnr_extern>foo</objektnr_extern>'
						.'</verwaltung_techn>'
					.'</immobilie>'
					.'<openimmo_anid>foo</openimmo_anid>'
					.'<firma>bar</firma>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		$result = array();
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'zip = "bar" AND object_number = "foo"'
		);
		if ($dbResult) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))	{
				$result[] = $row;
			}
		}

		$this->assertTrue(
			is_array($result[0])
		);

		$difference = array_diff(
			$this->fixture->getRequiredFields(),
			array_keys($result[0]));
		$this->assertTrue(
			empty($difference)
		);
	}

	public function testEnsureContactEmailNotChangesAddressIfValidAddressIsSet() {
		$this->fixture->loadRealtyObject(array('contact_email' => 'foo-valid@email-address.org'));
		$this->fixture->ensureContactEmail();

		$this->assertEquals(
			$this->fixture->getContactEmailFromRealtyObject(),
			'foo-valid@email-address.org'
		);
	}

	public function testEnsureContactEmailSetsDefaultAddressIfEmptyAddressSet() {
		$this->fixture->loadRealtyObject(array('contact_email' => ''));
		$this->fixture->ensureContactEmail();

		$this->assertEquals(
			$this->fixture->defaultEmailAddress(),
			$this->fixture->getContactEmailFromRealtyObject()
		);
	}

	public function testEnsureContactEmailSetsDefaultAddressIfInvalidAddressIsSet() {
		$this->fixture->loadRealtyObject(array('contact_email' => 'foo'));
		$this->fixture->ensureContactEmail();

		$this->assertEquals(
			$this->fixture->defaultEmailAddress(),
			$this->fixture->getContactEmailFromRealtyObject()
		);
	}

	public function testPrepareEmailsReturnsEmptyArrayWhenEmptyArrayGiven() {
		$emailData = array();

		$this->assertEquals(
			$this->fixture->prepareEmails($emailData),
			array()
		);
	}

	public function testPrepareEmailsReturnsEmptyArrayWhenInvalidArrayGiven() {
		$emailData = array('invalid' => 'array');

		$this->assertEquals(
			$this->fixture->prepareEmails($emailData),
			array()
		);
	}

	public function testPrepareEmailsFillsEmptyEmailFieldWithDefaultAddress() {
		$emailData = array(
			array(
				'recipient' => '',
				'objectNumber' => 'foo',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			)
		);

		$this->assertEquals(
			$this->fixture->prepareEmails($emailData),
			array(
				$this->fixture->defaultEmailAddress() => array(
					array('foo' => 'bar')
				)
			)
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
			$this->fixture->prepareEmails($emailData),
			array(
				'foo' => array(
					array('------' => 'bar')
				)
			)
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
			$this->fixture->prepareEmails($emailData),
			array(
				'foo' => array(
					array('number' => 'bar'),
					array('number' => 'foo')
				)
			)
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
			$this->fixture->prepareEmails($emailData),
			array(
				'foo' => array(
					array('number' => 'foo')
				),
				'bar' => array(
					array('number' => 'bar')
				)
			)
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
			$this->fixture->prepareEmails($emailData),
			array(
				'foo' => array(
					array('number' => 'bar')
				)
			)
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
			$this->fixture->prepareEmails($emailData),
			array()
		);
	}

	public function testFindContactEmailsIfEmailNotExists() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'foo.zip');

		$this->assertTrue(
			$this->fixture->isRealtyObjectDataEmpty()
		);
		$this->assertEquals(
			array(),
			$this->fixture->findContactEmails(REALTY_IMPORT_FOLDER.'foo.zip')
		);
	}

	public function testFindContactEmailsIfEmailExists() {
		$this->fixture->extractZip(REALTY_IMPORT_FOLDER.'email.zip');

		$this->assertEquals(
			array('bar'),
			$this->fixture->findContactEmails(REALTY_IMPORT_FOLDER.'email.zip')
		);
	}

	public function testCreateDummyCachePage() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'page_id',
			'cache_pages',
			''
		);
		$allPageIds = array();
		if ($dbResult) {
			while ($dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
				$allPageIds[] = $dbResultRow['page_id'];
			}
		}

		$this->assertTrue(
			in_array(DUMMY_PAGE_UID, $allPageIds)
		);
	}

	public function testClearFeCacheDeletesCashedPage() {
		$this->fixture->clearFeCache();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'page_id',
			'cache_pages',
			''
		);
		$allPageIds = array();
		if ($dbResult) {
			while ($dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
				$allPageIds[] = $dbResultRow['page_id'];
			}
		}

		$this->assertFalse(
			in_array(DUMMY_PAGE_UID, $allPageIds)
		);
	}

	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy pages.
	 */
	private function createDummyPages() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'pages',
			array('uid' => DUMMY_PAGE_UID)
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tt_content',
			array(
				'uid' => DUMMY_CONTENT_PAGE_UID,
				'pid' => DUMMY_PAGE_UID,
				'list_type' => 'realty_pi1'
			)
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'cache_pages',
			array(
				'page_id' => DUMMY_PAGE_UID
			)
		);
	}

	/**
	 * Deletes dummy pages.
	 */
	private function deleteDummyPages() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'pages',
			'uid ='.DUMMY_PAGE_UID
		);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tt_content',
			'uid ='.DUMMY_PAGE_CONTENT_UID
		);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'cache_pages',
			'page_id ='.DUMMY_PAGE_UID
		);
	}

	/**
	 * Resets the auto increment value for the tables 'pages' and 'tt_content'
	 * to the highest existing UID + 1. This is required to leave the table in
	 * the same status that it had before adding dummy records.
	 */
	private function resetAutoIncrement() {
		foreach (array('pages', 'tt_content') as $table) {
			$dbResult = $GLOBALS['TYPO3_DB']->sql_query(
				'SELECT MAX(uid) AS uid FROM '.$table.';'
			);
			if ($dbResult) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
				if ($row) {
					$newAutoIncrementValue = $row['uid'] + 1;
					$GLOBALS['TYPO3_DB']->sql_query(
						'ALTER TABLE '.$table.' AUTO_INCREMENT='.$newAutoIncrementValue.';'
					);
				}
			}
		}
	}
}

?>
