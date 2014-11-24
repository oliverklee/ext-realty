<?php
/**
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

/**
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
	 *
	 * @return void
	 */
	public static function clearFrontEndCacheForRealtyPages() {
		if (self::isCachingFrameworkEnabled()) {
			self::clearCacheWithCachingFramework();
		} else {
			self::deleteCacheInTable();
		}
	}

	/**
	 * Returns the page UIDs of the pages with the realty plugin.
	 *
	 * @param string $prefix prefix for each UID, leave empty to set no prefix
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
	 * @return void
	 */
	private static function clearCacheWithCachingFramework() {
		if (!($GLOBALS['typo3CacheManager'] instanceof t3lib_cache_Manager)) {
			t3lib_cache::initializeCachingFramework();
		}

		if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) < 4006000) {
			try {
				/** @var $pageCache t3lib_cache_frontend_AbstractFrontend */
				$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
			} catch (t3lib_cache_exception_NoSuchCache $exception) {
				t3lib_cache::initPageCache();
			}
			$pageCache->flushByTags(self::getPageUids('pageId_'));
		} else {
			/** @var $pageCache t3lib_cache_frontend_AbstractFrontend */
			$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
			foreach (self::getPageUids() as $pageUid) {
				$pageCache->getBackend()->flushByTag('pageId_' . $pageUid);
			}
		}
	}

	/**
	 * Deletes the cache entries in the cache table to clear the cache.
	 *
	 * @return void
	 */
	private static function deleteCacheInTable() {
		$pageUids = self::getPageUids();
		if (empty($pageUids)) {
			return;
		}

		tx_oelib_db::delete(
			'cache_pages', 'page_id IN (' . implode(',', $pageUids) . ')'
		);
	}

	/**
	 * Checks whether the caching framework is enabled.
	 *
	 * @return bool
	 */
	private static function isCachingFrameworkEnabled() {
		return (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 4006000) || TYPO3_UseCachingFramework;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_cacheManager.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_cacheManager.php']);
}