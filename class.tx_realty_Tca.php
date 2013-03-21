<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Class tx_realty_Tca for the "realty" extension.
 *
 * This class provides functions for the TCA.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Tca {
	/**
	 * Gets the districts for a certain city.
	 *
	 * @param array $data the TCEforms data, must at least contain [row][city]
	 *
	 * @return array the TCEforms data with the districts added
	 */
	public function getDistrictsForCity(array $data) {
		$items = array(array('', 0));

		$districs = tx_oelib_MapperRegistry::get('tx_realty_Mapper_District')
			->findAllByCityUidOrUnassigned(intval($data['row']['city']));
		foreach ($districs as $district) {
			$items[] = array($district->getTitle(), $district->getUid());
		}

		$data['items'] = $items;

		return $data;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/class.tx_realty_Tca.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/class.tx_realty_Tca.php']);
}
?>