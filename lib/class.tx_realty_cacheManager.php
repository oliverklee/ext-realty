<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de>
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
		if (t3lib_div::int_from_ver(TYPO3_version) > 4002999) {
			self::clearCacheWithCachingFramework();
		} else {
			$pageUids = self::getPageUids();
			if(empty($pageUids)) {
				return;
			}

			tx_oelib_db::delete(
				'cache_pages', 'page_id IN (' . implode(',', $pageUids) . ')'
			);
		}
	}

	/**
	 * Returns the page UIDs of the pages with the realty plugin.
	 *
	 * @param string prefix for each UID, leave empty to set no prefix
	 *
	 * @return array page UIDs of the pages with the realty plugin, each will be
	 *               prefixed with $prefix, will be empty if there are none
	 */
	private static function getPageUids($prefix = '') {
		$pageUids = tx_oelib_db::selectMultiple(
			'pid', 'tt_content', 'list_type = "realty_pi1"'
		);

		$result = array();
		foreach ($pageUids as $pageUid) {
			$result[] = $prefix . $pageUid['pid'];
		}

		return $result;
	}

	/**
	 * Uses the TYPO3 caching framework to clear the cache for the pages with
	 * the realty plugin.
	 *
	 * Note: The caching framework is not avialable in TYPO3 versions lower than 4.3.
	 */
	private static function clearCacheWithCachingFramework() {
		if (!($GLOBALS['typo3CacheManager'] instanceof t3lib_cache_Manager)) {
			t3lib_cache::initializeCachingFramework();
		}

		try {
			$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
		} catch(t3lib_cache_exception_NoSuchCache $exception) {
			t3lib_cache::initPageCache();
			$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
		}

		$pageCache->flushByTags(self::getPageUids('pageId_'));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_cacheManager.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_cacheManager.php']);
}
?>