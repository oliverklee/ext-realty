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
	}

	public function tearDown() {
		unset($this->fixture);

		// remove test folder from /tmp/
		exec('rm -rf '.REALTY_IMPORT_FOLDER);
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

	public function testExtractZipsIfOneZipToExtract() {
		$this->fixture->extractZips(array(REALTY_IMPORT_FOLDER.'foo.zip'));

		$this->assertTrue(
			is_dir(REALTY_IMPORT_FOLDER.'foo/')
		);
	}

	public function testExtractZipsIfTwoZipsToExtract() {
		$this->fixture->extractZips(array(
			REALTY_IMPORT_FOLDER.'bar.zip',
			REALTY_IMPORT_FOLDER.'foo.zip'
		));

		$this->assertTrue(
			is_dir(REALTY_IMPORT_FOLDER.'foo/')
		);
		$this->assertTrue(
			is_dir(REALTY_IMPORT_FOLDER.'bar/')
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
}

?>
