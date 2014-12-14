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
 * This class provides access via command-line interface.
 *
 * To run this script, use the following command in a console: '/[absolute path
 * of the TYPO3 installation]/typo3/cli_dispatch.phpsh openImmoImport'.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_cli {
	/**
	 * Calls the OpenImmo importer.
	 *
	 * @return void
	 */
	public function main() {
		try {
			$importer = t3lib_div::makeInstance('tx_realty_openImmoImport');
			echo $importer->importFromZip();
		} catch (Exception $exception) {
			echo $exception->getMessage() . LF . LF .
				$exception->getTraceAsString() . LF .LF;
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/cli/class.tx_realty_cli.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/cli/class.tx_realty_cli.php']);
}

t3lib_div::makeInstance('tx_realty_cli')->main();