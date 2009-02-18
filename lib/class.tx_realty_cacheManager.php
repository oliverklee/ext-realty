<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de> All rights reserved
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

require_once(t3lib_extMgm::extPath('oelib') . 'tx_oelib_commonConstants.php');

/**
 * Class 'tx_realty_cacheManager' for the 'realty' extension.
 * This class provides a function to clear the FE cache for pages with the
 * realty plugin.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_cacheManager {
	/**
	 * Clears the FE cache for pages with a realty plugin.
	 *
	 * @see tslib_fe::clearPageCacheContent_pidList()
	 */
	public static function clearFrontEndCacheForRealtyPages() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid', 'tt_content', 'list_type="realty_pi1"'
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$pageUids = array();
		while ($recordData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$pageUids[] = $recordData['pid'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		if (empty($pageUids)) {
			return;
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'cache_pages', 'page_id IN (' . implode(',', $pageUids) . ')'
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_cacheManager.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_cacheManager.php']);
}
?>