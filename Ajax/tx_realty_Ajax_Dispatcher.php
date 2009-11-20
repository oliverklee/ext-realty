<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Oliver Klee <typo3-coding@oliverklee.de>
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

/**
 * File tx_realty_Ajax_Dispatcher for the "realty" extension.
 *
 * This script acts as a dispatcher for AJAX requests.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */

tslib_eidtools::connectDB();
if (t3lib_div::int_from_ver(TYPO3_version) > 4002999) {
	tslib_eidtools::initTCA();
} else {
	if (!is_array($GLOBALS['TCA']) || !isset($GLOBALS['TCA']['pages'])) {
		require_once(PATH_tslib.'class.tslib_fe.php');

		tx_oelib_ObjectFactory::make(
			'tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], 0, 0
		)->includeTCA(FALSE);;
	}
}

$cityUid = intval(t3lib_div::_GET('city'));
if ($cityUid > 0) {
	$output = tx_realty_Ajax_DistrictSelector::render($cityUid);
} else {
	$output = '';
}

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');
header('Content-Length: '.strlen($output));

echo $output;
?>