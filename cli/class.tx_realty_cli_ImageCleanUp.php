<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Saskia Metzler <saskia@merlin.owl.de>
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
 * This class removes unused files from the realty upload folder.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_cli_ImageCleanUp {
	/**
	 * @var string additional WHERE clause, used for testing
	 */
	private $additionalWhereClause = '';

	/**
	 * @var string upload folder, relative to PATH_site
	 */
	private $uploadFolder = tx_realty_Model_Image::UPLOAD_FOLDER;

	/**
	 * @var array associative array with statistical information collected
	 *            during clean-up
	 */
	private $statistics = array();

	/**
	 * Checks whether the Realty upload folder exists and is writable.
	 *
	 * @throws RuntimeException if the upload folder does not exist
	 * @throws tx_oelib_Exception_AccessDenied if the upload folder is not
	 *                                         writable
	 *
	 * @return void
	 */
	public function checkUploadFolder() {
		$absolutePath = PATH_site . $this->uploadFolder;
		if (!@is_dir($absolutePath)){
			throw new RuntimeException(
				'The folder ' .  $absolutePath . ' with the uploaded realty files does not exist. ' .
					'Please check your configuration and restart the clean-up.',
				1333035462
			);
		}
		if (!@is_writable($absolutePath)) {
			$ownerUid = fileowner($absolutePath);
			$owner = posix_getpwuid($ownerUid);

			throw new tx_oelib_Exception_AccessDenied(
				'The folder ' .  $absolutePath . ' is not writable. Please fix file permissions and restart' .
					' the import. The folder belongs to the user: ' . $owner['name'] . ', ' . $ownerUid .
					', and has the following permissions: ' . substr(decoct(fileperms($absolutePath)), 2) .
					'. The user starting this import was: ' . get_current_user() . '.',
				1333035471
			);
		}
	}

	/**
	 * Hides unused images in the database. Images in the database are
	 * considered as unused if there is no non-deleted realty record related to
	 * this image.
	 *
	 * @return void
	 */
	public function hideUnusedImagesInDatabase() {
		$nonDeletedRealtyRecordUids = $this->retrieveRealtyObjectUids();
		$imagesForRealtyRecords = ($nonDeletedRealtyRecordUids == '')
			? array()
			: tx_oelib_db::selectColumnForMultiple(
				'uid', REALTY_TABLE_IMAGES,
				'object IN (' . $nonDeletedRealtyRecordUids . ')' .
					tx_oelib_db::enableFields(REALTY_TABLE_IMAGES, 1) .
					$this->additionalWhereClause
			);
		$this->addToStatistics(
			'Enabled image records with references to realty records',
			count($imagesForRealtyRecords)
		);

		$numberOfImagesHidden = tx_oelib_db::update(
			REALTY_TABLE_IMAGES, (empty($imagesForRealtyRecords)
					? '1=1'
					: 'uid NOT IN (' . implode(',', $imagesForRealtyRecords) . ')'
				) . $this->additionalWhereClause,
			array('hidden' => 1)
		);
		$hiddenImageRecords = tx_oelib_db::selectSingle(
			'COUNT(*) AS number', REALTY_TABLE_IMAGES,
			'hidden = 1 OR deleted = 1' . $this->additionalWhereClause
		);
		$this->addToStatistics(
			'Total hidden or deleted image records',
			$hiddenImageRecords['number'] . ', ' . $numberOfImagesHidden .
				' of those were hidden during this run (due to missing' .
				' corresponding realty records).'
		);
	}

	/**
	 * Deletes unused document records. Documents in the database are
	 * considered as unused if there is no non-deleted realty record related to
	 * this document.
	 *
	 * @return void
	 */
	public function deleteUnusedDocumentRecords() {
		$nonDeletedRealtyRecordUids = $this->retrieveRealtyObjectUids();
		$documentsWithRealtyRecords = ($nonDeletedRealtyRecordUids == '')
			? array()
			: tx_oelib_db::selectColumnForMultiple(
				'uid', 'tx_realty_documents',
				'object IN (' . $nonDeletedRealtyRecordUids . ')' .
					tx_oelib_db::enableFields('tx_realty_documents', 1) .
					$this->additionalWhereClause
			);
		$this->addToStatistics(
			'Enabled document records with references to realty records',
			count($documentsWithRealtyRecords)
		);

		$numberOfDeletedDocumentRecords = tx_oelib_db::update(
			'tx_realty_documents', (empty($documentsWithRealtyRecords)
					? '1=1'
					: 'uid NOT IN (' . implode(',', $documentsWithRealtyRecords) . ')'
				) . $this->additionalWhereClause,
			array('deleted' => 1)
		);
		$this->addToStatistics(
			'Total deleted document records', $numberOfDeletedDocumentRecords
		);
	}

	/**
	 * Gets a comma-separated list of UIDs of non-deleted (but potentially
	 * hidden) realty records in the database.
	 *
	 * @return string
	 *         comma-separated list of UIDs, will be empty if there are no
	 *         matching records
	 */
	private function retrieveRealtyObjectUids() {
		$uids = tx_oelib_db::selectColumnForMultiple(
			'uid', REALTY_TABLE_OBJECTS,
			'1=1' . tx_oelib_db::enableFields('tx_realty_objects', 1) .
				$this->additionalWhereClause
		);

		return implode(',', $uids);
	}

	/**
	 * Deletes all files from the realty upload folder which do not have a
	 * corresponding image or document record.
	 * (Subfolders, such as /rte, remain untouched.)
	 *
	 * @return void
	 */
	public function deleteUnusedFiles() {
		$absolutePath = PATH_site . $this->uploadFolder;
		$filesInUploadFolder = t3lib_div::getFilesInDir($absolutePath);
		$this->addToStatistics(
			'Files in upload folder', count($filesInUploadFolder)
		);

		$imageFileNamesInDatabase = tx_oelib_db::selectColumnForMultiple(
			'image', REALTY_TABLE_IMAGES,
			'1=1' . tx_oelib_db::enableFields(REALTY_TABLE_IMAGES, 1) .
				$this->additionalWhereClause
		);
		$this->addToStatistics(
			'Files with corresponding image record',
			count($imageFileNamesInDatabase)
		);

		$documentFileNamesInDatabase = tx_oelib_db::selectColumnForMultiple(
			'filename', 'tx_realty_documents',
			'1=1' . tx_oelib_db::enableFields('tx_realty_documents', 1) .
				$this->additionalWhereClause
		);
		$this->addToStatistics(
			'Files with corresponding document record',
			count($documentFileNamesInDatabase)
		);

		$filesToDelete = array_diff(
			$filesInUploadFolder,
			$imageFileNamesInDatabase, $documentFileNamesInDatabase
		);
		$this->addToStatistics('Files deleted', count($filesToDelete));
		foreach ($filesToDelete as $image) {
			unlink($absolutePath . $image);
		}

		$filesOnlyInDatabase = array_diff(
			array_merge($imageFileNamesInDatabase, $documentFileNamesInDatabase),
			$filesInUploadFolder
		);
		$numberOfFilesOnlyInDatabase = count($filesOnlyInDatabase);
		$this->addToStatistics(
			'Image and documents records without image file',
			$numberOfFilesOnlyInDatabase . (($numberOfFilesOnlyInDatabase > 0)
				? ', file names: ' . LF . TAB .
					implode(LF . TAB, $filesOnlyInDatabase)
				: '')
		);
	}

	/**
	 * Returns collected statistics.
	 *
	 * @return string statistical information collected during clean-up
	 */
	public function getStatistics() {
		return 'Clean-up results:' . LF . implode(LF , $this->statistics);
	}

	/**
	 * Stores an information about statistics.
	 *
	 * @param string $title title of the entry to add, must not be empty
	 * @param string $value value to be added to the statistics, must not be empty
	 *
	 * @return void
	 */
	private function addToStatistics($title, $value) {
		$this->statistics[] = $title . ': ' . $value;
	}

	/**
	 * Sets the test mode.
	 *
	 * @param string $uploadFolder
	 *               folder to use as upload folder for testing, must be
	 *               relative to PATH_site, a trailing slash will be appended,
	 *               must not be empty
	 *
	 * @return void
	 */
	public function setTestMode($uploadFolder) {
		$this->uploadFolder = $uploadFolder . '/';
		$this->additionalWhereClause = ' AND is_dummy_record = 1';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/cli/class.tx_realty_cli_ImageCleanUp.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/cli/class.tx_realty_cli_ImageCleanUp.php']);
}