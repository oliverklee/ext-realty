<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2011 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Class tx_realty_Ajax_DistrictSelector for the "realty" extension.
 *
 * This class creates a district drop-down for a selected city.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Ajax_DistrictSelector {
	/**
	 * Creates a drop-down for all districts within a city. Districts without a
	 * city will also be listed.
	 *
	 * At the top, an empty option with the value 0 will always be included.
	 *
	 * @param integer $cityUid
	 *        the UID of a city for which to get the districts, must be > 0,
	 *        may also point to an inexistent record
	 * @param boolean $showWithNumbers
	 *        if TRUE, the number of matching objects will be displayed behind
	 *        the district name, and districts without matches will be omitted;
	 *        if FALSE, the number of matches will not be displayed, and
	 *        districts without matches will also be displayed
	 *
	 * @return string the HTML of the drop-down items with the districts, will
	 *                not be empty
	 */
	static public function render($cityUid, $showWithNumbers = FALSE) {
		$options = '<option value="0">&nbsp;</option>';

		$objectMapper =
			tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');

		$districts = tx_oelib_MapperRegistry::get('tx_realty_Mapper_District')
			->findAllByCityUidOrUnassigned($cityUid);
		foreach ($districts as $district) {
			if ($showWithNumbers) {
				$numberOfMatches = $objectMapper->countByDistrict($district);
				if ($numberOfMatches == 0) {
					continue;
				}
				$displayedNumber = ' (' . $numberOfMatches . ')';
			} else {
				$displayedNumber = '';
			}

			$options .= '<option value="' . $district->getUid() . '">' .
				htmlspecialchars($district->getTitle()) .
				$displayedNumber . '</option>' . LF;
		}

		return $options;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Ajax/class.tx_realty_Ajax_DistrictSelector.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Ajax/class.tx_realty_Ajax_DistrictSelector.php']);
}
?>