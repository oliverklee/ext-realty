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
 * Class 'tx_realty_openimmo_import' for the 'realty' extension.
 *
 * This class imports ZIPs containing OpenImmo records.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_openimmo_import {
	/** stores information for the log entry */
	private $logEntry = '';

	/**
	 * Extracts ZIPs from an absolute path of a directory:
	 * If the directory exists and ZIPs are found, it creates folders named like
	 * the ZIPs to unpack them.
	 * Afterwards it removes the folders and passes back a log string about the
	 * proceedings of import. If there is no valid directory, only the log
	 * entry is returned.
	 *
	 * @param	string		absolute path of the dir which contains the ZIPs,
	 * 						may end with a trailing slash
	 *
	 * @return	string		log entry with information about the proceedings of
	 * 						ZIP import, contains a timestamp in any case
	 */
	public function importFromZip($importDirectory) {
		$this->addToLogEntry(date('Y-m-d G:i:s').' - ');

		$checkedImportDirectory = $this->unifyImportPath($importDirectory);
		$zipsToExtract = $this->getPathsOfZipsToExtract($checkedImportDirectory);
		if (!empty($zipsToExtract)) {
			$this->extractZips($zipsToExtract);
		} else {
			$this->addToLogEntry('No ZIPs to extract. Either there is a mistake '
				.'in configuration or the selected folder does not contain ZIPs.'
			);
		}
		$this->cleanUp($checkedImportDirectory);

		return $this->logEntry;
	}

	/**
	 * Stores information for a log entry to be returned at the end of import.
	 *
	 * @param	string		message to log, may be empty
	 */
	private function addToLogEntry($logFraction) {
		$this->logEntry .= $logFraction.chr(10);
	}

	/**
	 * Checks the correct punctuation of the import path. Adds a slash if missing
	 * and strips whitespaces.
	 *
	 * @param	string		path to be checked
	 *
	 * @return	string		checked path, possibly modified
	 */
	protected function unifyImportPath($importDirectory) {
		$checkedPath = trim($importDirectory);
		if (strpos($checkedPath, '/', strlen($checkedPath) - 1) === false) {
			$checkedPath .= '/';
		}
		return $checkedPath;
	}

	/**
	 * Gets an array of the paths of all ZIPs in the import folder.
	 *
	 * @param	string		absolute path of the directory which contains the
	 * 						ZIPs
	 *
	 * @return	array		absolute paths of ZIPs in the import folder,
	 * 						might be empty
	 */
	protected function getPathsOfZipsToExtract($importDirectory) {
		$result = array();
		if (is_dir($importDirectory)) {
			$result = glob($importDirectory.'*.zip');
		}

		return $result;
	}

	/**
	 * Extracts each ZIP into a directory in the import folder which is named
	 * like the ZIP file. Logs success and failures.
	 *
	 * @param	array		paths to zips to extract, must not be empty
	 */
	public function extractZips(array $zipsToExtract) {
		$zip = new ZipArchive();

		foreach ($zipsToExtract as $currentZip) {
			if ($zip->open($currentZip)) {
				$extractionDirectory = $this->createExtractionFolder($currentZip);
				if ($extractionDirectory != '') {
					$zip->extractTo($extractionDirectory);
					$zip->close();
					$this->addToLogEntry($currentZip.' extracted successfully.');
				}
			} else {
				$this->addToLogEntry('Extraction failed: '.$currentZip);
			}
		}
	}

	/**
	 * Gets a name for a folder according to the ZIP to extract to it.
	 *
	 * @param	string		path of a ZIP, should not be empty
	 *
	 * @return	string		path for a folder named like the ZIP, empty if
	 * 						passed string is empty
	 */
	protected function getNameForExtractionFolder($pathOfZip) {
		return str_replace('.zip', '/', $pathOfZip);
	}

	/**
	 * Creates a folder to extract a ZIP to.
	 *
	 * @param	string		path of a ZIP to get the folders name
	 *
	 * @return	string		path for folder named like the ZIP
	 */
	public function createExtractionFolder($pathOfZip) {
		if (!file_exists($pathOfZip)) {
			return '';
		}

		$folderForZipExtraction = $this->getNameForExtractionFolder($pathOfZip);
		if (!is_dir($folderForZipExtraction)) {
			mkdir($folderForZipExtraction);
		}

		return $folderForZipExtraction;
	}

	/**
	 * Removes the the folders which have been created to extract ZIPs.
	 *
	 * @param	string		absolute path of the folder which contains the ZIPs
	 */
	public function cleanUp($importDirectory) {
		if (!is_dir($importDirectory)) {
			return;
		}

		$originalPaths = $this->getPathsOfZipsToExtract($importDirectory);

		foreach ($originalPaths as $currentOriginalPath) {
			$currentFolder = $this->getNameForExtractionFolder($currentOriginalPath);
			foreach (glob($currentFolder.'*') as $fileToDelete) {
				unlink($fileToDelete);
			}
			if (is_dir($currentFolder)) {
				rmdir($currentFolder);
				$this->addToLogEntry('The folder '.$currentFolder
					.'with recently unpacked content has been removed.'
				);
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_openimmo_import.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_openimmo_import.php']);
}

?>
