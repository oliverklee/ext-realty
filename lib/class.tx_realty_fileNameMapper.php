<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(PATH_t3lib . 'class.t3lib_basicfilefunc.php');

/**
 * Class 'tx_realty_fileNameMapper' for the 'realty' extension.
 *
 * This class maps file names to their unique correspondants in the file system.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_fileNameMapper {
	/**
	 * @var array associative array with the unique file name as key and the
	 *            original file name as value
	 */
	private $fileNames = array();

	/** @var string path of the folder in which to check whether a file exists */
	private $destinationPath = '';

	/** @var t3lib_basicFileFunctions */
	private static $fileFunctions = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->destinationPath = PATH_site . 'uploads/tx_realty/';
	}

	/**
	 * Destructor.
	 */
	public function __destruct() {
		unset($this->fileNames, $this->destinationPath);
	}

	/**
	 * Returns the unique file name for the provided file name within the
	 * destination directory and maps both names internally.
	 *
	 * @param string file name to receive the unique name for, must not be empty
	 *
	 * @return string unique file name, will not be empty
	 */
	public function getUniqueFileNameAndMapIt($fileName) {
		$uniqueFileName = $this->getUniqueFileName($fileName);
		$this->fileNames[$uniqueFileName] = $fileName;

		return $uniqueFileName;
	}

	/**
	 * Returns the unique file name for a provided file name by checking that
	 * the name neither occurs in the destination folder nor in the internal
	 * array yet. Also replaces any character not matching [.a-zA-Z0-9_-] by '_'
	 * within the file name.
	 *
	 * t3lib's basic file functions class is not used to create the unique name
	 * as it only can produce unique names for files which already exist in the
	 * file system. Here, also the internal mapping has to be taken into account.
	 *
	 * @param string original file name, must not be empty
	 *
	 * @return string cleaned original file name extended with a unique suffix,
	 *                will not be empty
	 */
	private function getUniqueFileName($originalFileName) {
		$splittedFileName = t3lib_div::split_fileref($originalFileName);
		$newFileName = $this->getCleanedFileNameBody(
				$splittedFileName['filebody']
			) . '.' . $splittedFileName['realFileext'];


		while (isset($this->fileNames[$newFileName])
			|| file_exists($this->destinationPath . $newFileName)
		) {
			$this->createNewFileName($newFileName);
		}

		return $newFileName;
	}

	/**
	 * Returns the given file name body with any character not matching
	 * [.a-zA-Z0-9_-] replaced by '_'.
	 *
	 * @param string file name body, must not be empty
	 *
	 * @return string cleaned file name body, will not be empty
	 */
	private function getCleanedFileNameBody($fileNameBody) {
		if (!self::$fileFunctions) {
			self::$fileFunctions = t3lib_div::makeInstance(
				't3lib_basicFileFunctions'
			);
		}

		return self::$fileFunctions->cleanFileName($fileNameBody);
	}

	/**
	 * Increases the appended number of the provided file name.
	 *
	 * @param string file name, will be modified, must not empty
	 */
	private function createNewFileName(&$fileName) {
		$splittedFileName = t3lib_div::split_fileref($fileName);

		$matches = array();
		preg_match('/^(.*)_([0-9]+)$/', $splittedFileName['filebody'], $matches);

		if (!empty($matches)) {
			$fileBodyWithoutSuffix = $matches[1];
			$suffixNumber = $matches[2];
		} else {
			$fileBodyWithoutSuffix = $splittedFileName['filebody'];
			$suffixNumber = -1;
		}

		$suffixNumber++;
		$fileName = $fileBodyWithoutSuffix . '_' .
			sprintf('%02d', $suffixNumber) . '.' .
			$splittedFileName['realFileext'];
	}

	/**
	 * Returns the unique file names mapped for one original file name and
	 * deletes the mappings for this name as they must not be used again to
	 * ensure the uniqueness.
	 *
	 * @param string original file name, must not be empty
	 *
	 * @return array mapped unique file names for one original file name, will
	 *               be empty if there were no mappings
	 */
	public function releaseMappedFileNames($originalFileName) {
		$result = array_keys($this->fileNames, $originalFileName);

		foreach ($result as $usedFileName) {
			unset($this->fileNames[$usedFileName]);
		}

		return $result;
	}

	/**
	 * Sets the destination folder where to check whether a file already exists.
	 *
	 * @param string absolute path of the destination folder, must end with a
	 *               trailing slash and must not be empty
	 */
	public function setDestinationFolder($folder) {
		$this->destinationPath = $folder;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_fileNameMapper.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_fileNameMapper.php']);
}
?>