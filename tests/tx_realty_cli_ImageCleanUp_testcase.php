<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_cli_ImageCleanUp class in the 'realty' extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_cli_ImageCleanUp_testcase extends tx_phpunit_testcase {
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
	private $uploadFolder = 'uploads_tx_realty_test';

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->setUploadFolderPath(PATH_site);
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
			'The folder ' .  PATH_site . 'uploads_tx_realty_test/no_folder/' .
				' with the uploaded realty images does not exist.' .
				' Please check your configuration and restart the clean-up.'
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
			array('realty_object_uid' => $this->testingFramework->createRecord(
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
			array('realty_object_uid' => $this->testingFramework->createRecord(
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
			array('realty_object_uid' => $this->testingFramework->createRecord(
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


	/////////////////////////////////////
	// Testing deleteUnusedImageFiles()
	/////////////////////////////////////

	/**
	 * @test
	 */
	public function deleteUnusedImageFiles_deletesImageFileReferencedByDeletedImageRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('deleted' => 1, 'image' => basename($fileName))
		);

		$this->fixture->deleteUnusedImageFiles();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedImageFiles_deletesImageFileReferencedByNoImageRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);

		$this->fixture->deleteUnusedImageFiles();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedImageFiles_notDeletesImageFileReferencedByHiddenImageRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('hidden' =>  1, 'image' => basename($fileName))
		);

		$this->fixture->deleteUnusedImageFiles();

		$this->assertTrue(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedImageFiles_notDeletesNonImageFile() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/test.txt'
		);

		$this->fixture->deleteUnusedImageFiles();

		$this->assertTrue(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedImageFiles_notDeletesImageFileReferencedByEnabledImageRecord() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES, array('image' => basename($fileName))
		);

		$this->fixture->deleteUnusedImageFiles();

		$this->assertTrue(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function deleteUnusedImageFiles_deletesTwoImageFilesReferencedByNoImageRecords() {
		$fileName1 = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);
		$fileName2 = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);

		$this->fixture->deleteUnusedImageFiles();

		$this->assertFalse(
			file_exists($fileName1)
		);
		$this->assertFalse(
			file_exists($fileName2)
		);
	}


	////////////////////////////
	// Testing getStatistics()
	////////////////////////////

	/**
	 * @test
	 */
	public function getStatistics_afterDeletingAnImageFileReturnsImageFileDeletedInformation() {
		$fileName = $this->testingFramework->createDummyFile(
			$this->uploadFolder . '/image.jpg'
		);

		$this->fixture->deleteUnusedImageFiles();

		$this->assertContains(
			'Image files deleted: 1',
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