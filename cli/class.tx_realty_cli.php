#! /usr/bin/php -q
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
 * Class 'tx_realty_cli' for the 'realty' extension.
 *
 * This class provides access via command line interface.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

define('TYPO3_cliMode', true);
define('TYPO3_MOD_PATH', '../typo3conf/ext/realty/cli/');
$BACK_PATH = '../../../../../';
$MCONF['name'] = '_CLI_realty';

require_once($_SERVER['SCRIPT_FILENAME'].'/'.$BACK_PATH.'typo3/init.php');

class tx_realty_cli {
	/**
	 * The constructor.
	 */
	public function tx_realty_cli() {
		echo 'runs!';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/class.tx_realty_cli.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/class.tx_realty_cli.php']);
}

$SOBE = t3lib_div::makeInstance('tx_realty_cli');

?>
