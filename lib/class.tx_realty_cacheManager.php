<?php

use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides a function to clear the FE cache for pages with the
 * realty plugin.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_cacheManager
{
    /**
     * @var CacheManager
     */
    private static $cacheManager = null;

    /**
     * Clears the FE cache for pages with a realty plugin.
     *
     * @return void
     */
    public static function clearFrontEndCacheForRealtyPages()
    {
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
    private static function getPageUids($prefix = '')
    {
        $pageUids = Tx_Oelib_Db::selectMultiple(
            'pid',
            'tt_content',
            'list_type = "realty_pi1"'
        );

        $result = [];
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
    private static function clearCacheWithCachingFramework()
    {
        /** @var FrontendInterface $pageCache */
        $pageCache = self::getCacheManager()->getCache('cache_pages');
        /** @var TaggableBackendInterface $cacheBackend */
        $cacheBackend = $pageCache->getBackend();
        foreach (self::getPageUids() as $pageUid) {
            $cacheBackend->flushByTag('pageId_' . $pageUid);
        }
    }

    /**
     * Fetches the core cache manager.
     *
     * @return CacheManager
     */
    public static function getCacheManager()
    {
        if (self::$cacheManager === null) {
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
    public static function injectCacheManager(CacheManager $cacheManager)
    {
        self::$cacheManager = $cacheManager;
    }

    /**
     * Purges the reference to the core cache manager.
     *
     * This function is intended to be used mainly in unit tests.
     *
     * @return void
     */
    public static function purgeCacheManager()
    {
        self::$cacheManager = null;
    }
}
