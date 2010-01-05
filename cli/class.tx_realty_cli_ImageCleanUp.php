<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_cli_ImageCleanUp' for the 'realty' extension.
 *
 * This class removes unused images from the Realty upload folder.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_cli_ImageCleanUp {
	/**
	 * @var string additional WHERE clause, used for testing
	 */
	private $additionalWhereClause = '';

	/**
	 * @var string upload folder, relative to PATH_site
	 */
	private $uploadFolder = REALTY_UPLOAD_FOLDER;

	/**
	 * @var array associative array with statistical information collected
	 *            during clean-up
	 */
	private $statistics = array();

	/**
	 * Checks whether the Realty upload folder exists and is writable.
	 *
	 * @throws Exception if the upload folder does not exist
	 * @throws tx_oelib_Exception_AccessDenied if the upload folder is not
	 *                                         writable
	 */
	public function checkUploadFolder() {
		$absolutePath = PATH_site . $this->uploadFolder;
		if (!@is_dir($absolutePath)){
			throw new Exception(
				'The folder ' .  $absolutePath .
					' with the uploaded realty images does not exist.' .
					' Please check your configuration and restart the clean-up.'
			);
		}
		if (!@is_writable($absolutePath)) {
			$ownerUid = fileowner($absolutePath);
			$owner = posix_getpwuid($ownerUid);

			throw new Exception(
				'The folder ' .  $absolutePath .
					' is not writable. Please fix file permissions and restart' .
					' the import. The folder belongs to the user: ' .
					$owner['name'] . ', ' . $ownerUid . ', and has the ' .
					'following permissions: ' .
					substr(decoct(fileperms($absolutePath)), 2) . '. The user ' .
					'starting this import was: ' . get_current_user() . '.'
			);
		}
	}

	/**
	 * Hides unused images in the database. Images in the database are
	 * considered as unused if there is no non-deleted realty record related to
	 * this image.
	 */
	public function hideUnusedImagesInDatabase() {
		$dbResult = tx_oelib_db::selectColumnForMultiple(
			'uid', REALTY_TABLE_OBJECTS,
			'1=1' . tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS, 1) .
				$this->additionalWhereClause
		);

		$nonDeletedRealtyRecordUids = implode(',', $dbResult);
		$imagesForRealtyRecords = ($nonDeletedRealtyRecordUids == '')
			? array()
			: tx_oelib_db::selectColumnForMultiple(
				'uid', REALTY_TABLE_IMAGES,
				'realty_object_uid IN (' . $nonDeletedRealtyRecordUids . ')' .
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
	 * Deletes all image files from the Realty upload folder which do not have a
	 * corresponding database record.
	 * (Subfolders, such as /rte, remain untouched.)
	 */
	public function deleteUnusedImageFiles() {
		$absolutePath = PATH_site . $this->uploadFolder;
		$imageFilesInUploadFolder = t3lib_div::getFilesInDir(
			$absolutePath, $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
		);
		$imageFileNamesInDatabase = array_unique(
			tx_oelib_db::selectColumnForMultiple(
				'image', REALTY_TABLE_IMAGES,
				'1=1' . tx_oelib_db::enableFields(REALTY_TABLE_IMAGES, 1) .
					$this->additionalWhereClause
			)
		);
		$this->addToStatistics(
			'Image files in upload folder', count($imageFilesInUploadFolder)
		);
		$this->addToStatistics(
			'Image files with corresponding image record',
			count($imageFileNamesInDatabase)
		);
		$imagesToDelete = array_diff(
			$imageFilesInUploadFolder, $imageFileNamesInDatabase
		);
		$this->addToStatistics('Image files deleted', count($imagesToDelete));
		foreach ($imagesToDelete as $image) {
			tx_oelib_FileFunctions::rmdir($absolutePath . $image);
		}
		$imagesOnlyInDatabase = array_diff(
			$imageFileNamesInDatabase, $imageFilesInUploadFolder
		);
		$numberOfImagesOnlyInDatabase = count($imagesOnlyInDatabase);
		$this->addToStatistics(
			'Image records without image file', $numberOfImagesOnlyInDatabase .
				(($numberOfImagesOnlyInDatabase > 0)
					? ', file names: ' . LF . TAB .
						implode(LF . TAB, $imagesOnlyInDatabase)
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
	 * @param array $value value to be added to the statistics, must not be empty
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
	 */
	public function setTestMode($uploadFolder) {
		$this->uploadFolder = $uploadFolder . '/';
		$this->additionalWhereClause = ' AND is_dummy_record = 1';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/cli/class.tx_realty_cli_ImageCleanUp.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/cli/class.tx_realty_cli_ImageCleanUp.php']);
}
?>