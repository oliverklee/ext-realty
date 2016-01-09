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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
	 * @var CacheManager
	 */
	private static $cacheManager = NULL;

	/**
	 * Clears the FE cache for pages with a realty plugin.
	 *
	 * @return void
	 */
	public static function clearFrontEndCacheForRealtyPages() {
		self::clearCacheWithCachingFramework();
	}

	/**
	 * Returns the page UIDs of the pages with the realty plugin.
	 *
	 * @param string $prefix prefix for each UID, leave empty to set no prefix
	 *
	 * @return string[] page UIDs of the pages with the realty plugin, each will be
	 *               prefixed with $prefix, will be empty if there are none
	 */
	private static function getPageUids($prefix = '') {
		$pageUids = Tx_Oelib_Db::selectMultiple(
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
		/** @var $pageCache t3lib_cache_frontend_AbstractFrontend */
		$pageCache = self::getCacheManager()->getCache('cache_pages');
		foreach (self::getPageUids() as $pageUid) {
			$pageCache->getBackend()->flushByTag('pageId_' . $pageUid);
		}
	}

	/**
	 * Fetches the core cache manager.
	 *
	 * @return CacheManager
	 */
	public static function getCacheManager() {
		if (self::$cacheManager === NULL) {
			self::$cacheManager = GeneralUtility::makeInstance(CacheManager::class);
		}

		return self::$cacheManager;
	}

	/**
	 * Injects the core cache manager.
	 *
	 * This function is intended to be used mainly in unit tests.
	 *
	 * @param CacheManager $cacheManager
	 *
	 * @return void
	 */
	public static function injectCacheManager(CacheManager $cacheManager) {
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