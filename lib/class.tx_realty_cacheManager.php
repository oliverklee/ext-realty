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
	 * @var t3lib_cache_Manager
	 */
	private static $cacheManager = NULL;

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
		if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) < 4006000) {
			try {
				/** @var $pageCache t3lib_cache_frontend_AbstractFrontend */
				$pageCache = self::getCacheManager()->getCache('cache_pages');
			} catch (t3lib_cache_exception_NoSuchCache $exception) {
				t3lib_cache::initPageCache();
				/** @var $pageCache t3lib_cache_frontend_AbstractFrontend */
				$pageCache = self::getCacheManager()->getCache('cache_pages');
			}
			$pageCache->flushByTags(self::getPageUids('pageId_'));
		} else {
			/** @var $pageCache t3lib_cache_frontend_AbstractFrontend */
			$pageCache = self::getCacheManager()->getCache('cache_pages');
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

	/**
	 * Fetches the core cache manager.
	 *
	 * @return t3lib_cache_Manager
	 *
	 * @throws BadMethodCallException
	 */
	public static function getCacheManager() {
		if (!self::isCachingFrameworkEnabled()) {
			throw new BadMethodCallException('This method must only be called with an enabled caching framework.', 1416868334);
		}

		if (self::$cacheManager === NULL) {
			if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 6002000) {
				self::$cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Cache\\CacheManager'
				);
			} else {
				if (!($GLOBALS['typo3CacheManager'] instanceof t3lib_cache_Manager)) {
					t3lib_cache::initializeCachingFramework();
				}
				self::$cacheManager = $GLOBALS['typo3CacheManager'];
			}
		}

		return self::$cacheManager;
	}

	/**
	 * Injects the core cache manager.
	 *
	 * This function is intended to be used mainly in unit tests.
	 *
	 * @param t3lib_cache_Manager $cacheManager
	 *
	 * @return void
	 */
	public static function injectCacheManager(t3lib_cache_Manager $cacheManager) {
		self::$cacheManager = $cacheManager;
	}

	/**
	 * Purges the reference to the core cache manager.
	 *
	 * This function is intended to be used mainly in unit tests.
	 *
	 * @return void
	 */
	public static function purgeCacheManager() {
		self::$cacheManager = NULL;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_cacheManager.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_cacheManager.php']);
}