<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

defined('TYPO3_cliMode') or die('You cannot run this script directly!');

setlocale(LC_NUMERIC, 'C');

/**
 * This class provides access via command-line interface and starts the
 * removal of unused images from the Realty upload folder.
 *
 * To run this script, use the following command in a console: '/[absolute path
 * of the TYPO3 installation]/typo3/cli_dispatch.phpsh cleanUpRealtyImages'.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_cli_ImageCleanUpStarter {
	/**
	 * Starts the clean-up.
	 *
	 * @return void
	 */
	public function main() {
		try {
			$cleanUp = t3lib_div::makeInstance('tx_realty_cli_ImageCleanUp');
			$cleanUp->checkUploadFolder();
			$cleanUp->hideUnusedImagesInDatabase();
			$cleanUp->deleteUnusedDocumentRecords();
			$cleanUp->deleteUnusedFiles();
			echo $cleanUp->getStatistics() . LF . LF;
		} catch (Exception $exception) {
			echo 'An error has occurred during the clean-up: ' . LF .
				$exception->getMessage() . LF . LF .
				$exception->getTraceAsString() . LF . LF;
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/cli/class.tx_realty_cli_ImageCleanUpStarter.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/cli/class.tx_realty_cli_ImageCleanUpStarter.php']);
}

t3lib_div::makeInstance('tx_realty_cli_ImageCleanUpStarter')->main();