<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2011 Saskia Metzler <saskia@merlin.owl.de>
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

/**
 * Unit tests for the tx_realty_cli_ImageCleanUp class in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Cli_ImageCleanUpTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_cli_ImageCleanUp
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var string upload folder name
	 */
	private $uploadFolder = 'uploads/tx_realty_test';

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->setUploadFolderPath(PATH_site);
		tx_oelib_MapperRegistry::getInstance()
			->activateTestingMode($this->testingFramework);

		$this->fixture = new tx_realty_cli_ImageCleanUp();
		$this->uploadFolder = str_replace(
			PATH_site, '',
			$this->testingFramework->createDummyFolder($this->uploadFolder)
		);
		$this->fixture->setTestMode($this->uploadFolder);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	////////////////////////////////
	// Testing checkUploadFolder()
	////////////////////////////////

	/**
	 * @test
	 */
	public function checkUploadFolder_forExistingWritableFolderNotThrowsException() {
		$this->fixture->checkUploadFolder();
	}

	/**
	 * @test
	 */
	public function checkUploadFolder_forNonExistingWritableFolderThrowsException() {
		$this->setExpectedException(
			Exception,
			'The folder ' .  PATH_site . 'uploads_tx_realty_test/no_folder/ ' .
				'with the uploaded realty files does not exist. ' .
				'Please check your configuration and restart the clean-up.'
		);
		$this->fixture->setTestMode('uploads_tx_realty_test/no_folder');
		$this->fixture->checkUploadFolder();
	}


	/////////////////////////////////////////
	// Testing hideUnusedImagesInDatabase()
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function hideUnusedImagesInDatabase_notHidesImageReferencingEnabledRealtyRecord() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('object' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('images' => 1)
			))
		);

		$this->fixture->hideUnusedImagesInDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES, 'hidden = 0'
			)
		);
	}

	/**
	 * @test
	 */
	public function hideUnusedImagesInDatabase_notHidesImageReferencingHiddenRealtyRecord() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('object' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('images' => 1, 'hidden' => 1)
			))
		);

		$this->fixture->hideUnusedImagesInDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES, 'hidden = 0'
			)
		);
	}

	/**
	 * @test
	 */
	public function hideUnusedImagesInDatabase_hidesImageReferencingDeletedRealtyRecord() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('object' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('images' => 1, 'deleted' => 1)
			))
		);

		$this->fixture->hideUnusedImagesInDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES, 'hidden = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function hideUnusedImagesInDatabase_hidesImageReferencingNoRealtyRecord() {
		$this->testingFramework->createRecord(REALTY_TABLE_IMAGES);

		$this->fixture->hideUnusedImagesInDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES, 'hidden = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function hideUnusedImagesInDatabase_hidesTwoImagesReferencingNoRealtyRecords() {
		$this->testingFramework->createRecord(REALTY_TABLE_IMAGES);
		$this->testingFramework->createRecord(REALTY_TABLE_IMAGES);

		$this->fixture->hideUnusedImagesInDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES, 'hidden = 1'
			)
		);
	}


	////////////////////////////////////////
	// Testing deleteUnusedDocumentRecords
	////////////////////////////////////////

	/**
	 * @test
	 */
	public function deleteUnusedDocumentRecordsKeepsDocumentReferencingNonDeletedRealtyRecord() {
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array('object' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('documents' => 1)
			))
		);

		$this->fixture->deleteUnusedDocumentRecords();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				'tx_realty_documents', 'deleted = 0'
			)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedDocumentRecordsKeepsDocumentReferencingHiddenRealtyRecord() {
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array('object' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('documents' => 1, 'hidden' => 1)
			))
		);

		$this->fixture->deleteUnusedDocumentRecords();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				'tx_realty_documents', 'deleted = 0'
			)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedDocumentRecordsDeletesDocumentReferencingDeletedRealtyRecord() {
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array('object' => $this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array('documents' => 1, 'deleted' => 1)
			))
		);

		$this->fixture->deleteUnusedDocumentRecords();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				'tx_realty_documents', 'deleted = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedDocumentRecordsDeletesDocumentReferencingNoRealtyRecord() {
		$this->testingFramework->createRecord('tx_realty_documents');

		$this->fixture->deleteUnusedDocumentRecords();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				'tx_realty_documents', 'deleted = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedDocumentRecordsDeletesTwoDocumentsReferencingNoRealtyRecords() {
		$this->testingFramework->createRecord('tx_realty_documents');
		$this->testingFramework->createRecord('tx_realty_documents');

		$this->fixture->deleteUnusedDocumentRecords();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				'tx_realty_documents', 'deleted = 1'
			)
		);
	}


	//////////////////////////////
	// Testing deleteUnusedFiles
	//////////////////////////////

	/**
	 * @test
	 */
	public function deleteUnusedFilesDeletesImageFileReferencedByDeletedImageRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('deleted' => 1, 'image' => basename($fileName))
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedFilesDeletesImageFileReferencedByNoImageRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedFilesKeepsImageFileReferencedByHiddenImageRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('hidden' =>  1, 'image' => basename($fileName))
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertTrue(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedFilesDeletesTextFile() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/test.txt'
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedFilesKeepsImageFileReferencedByEnabledImageRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES, array('image' => basename($fileName))
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertTrue(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedFilesDeletesTwoImageFilesReferencedByNoImageRecords() {
		$fileName1 = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);
		$fileName2 = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertFalse(
			file_exists($fileName1)
		);
		$this->assertFalse(
			file_exists($fileName2)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedFilesDeletesDocumentFileReferencedByDeletedDocumentRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/document.pdf'
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array('deleted' => 1, 'filename' => basename($fileName))
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedFilesDeletesDocumentFileReferencedByNoDocumentRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/document.pdf'
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedFilesKeepsDocumentFileReferencedByNonDeletedDocumentRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/document.pdf'
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents', array('filename' => basename($fileName))
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertTrue(
			file_exists($fileName)
		);
	}


	////////////////////////////
	// Testing getStatistics()
	////////////////////////////

	/**
	 * @test
	 */
	public function getStatisticsAfterDeletingFileReturnsFileDeletedInformation() {
		$this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);

		$this->fixture->deleteUnusedFiles();

		$this->assertContains(
			'Files deleted: 1',
			$this->fixture->getStatistics()
		);
	}

	/**
	 * @test
	 */
	public function getStatistics_afterHidingAnImageRecordReturnsImageRecordDeletedInformation() {
		$this->testingFramework->createRecord(REALTY_TABLE_IMAGES);

		$this->fixture->hideUnusedImagesInDatabase();

		$this->assertContains(
			', 1 of those were hidden',
			$this->fixture->getStatistics()
		);
	}
}
?>