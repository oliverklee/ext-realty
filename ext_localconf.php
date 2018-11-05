<?php
defined('TYPO3_MODE') or die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    $_EXTKEY,
    'pi1/class.tx_realty_pi1.php',
    '_pi1',
    'list_type',
    1
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    $_EXTKEY,
    'setup',
    "\ntt_content.shortcut.20.conf.tx_realty_objects = < plugin."
    . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . "_pi1\n" .
    "tt_content.shortcut.20.conf.tx_realty_objects.CMD = singleView\n",
    43
);

// registers the eID functions for AJAX
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Ajax/tx_realty_Ajax_Dispatcher.php';

// RealURL autoconfiguration
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration']['realty']
    = 'Tx_Realty_Configuration_RealUrl_Configuration->addConfiguration';

$openImmoTaskConfiguration = [
    'extension' => 'realty',
    'title' => 'LLL:EXT:realty/Resources/Private/Language/locallang.xlf:schedulerTask.openImmoImport.title',
    'description' => 'LLL:EXT:realty/Resources/Private/Language/locallang.xlf:schedulerTask.openImmoImport.description',
    'additionalFields' => \OliverKlee\Realty\SchedulerTask\OpenImmoImport::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\OliverKlee\Realty\SchedulerTask\OpenImmoImport::class]
    = $openImmoTaskConfiguration;
unset($openImmoTaskConfiguration);

$imageCleanupTaskConfiguration = [
    'extension' => 'realty',
    'title' => 'LLL:EXT:realty/Resources/Private/Language/locallang.xlf:schedulerTask.imageCleanup.title',
    'description' => 'LLL:EXT:realty/Resources/Private/Language/locallang.xlf:schedulerTask.imageCleanup.description',
    'additionalFields' => \OliverKlee\Realty\SchedulerTask\ImageCleanup::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\OliverKlee\Realty\SchedulerTask\ImageCleanup::class]
    = $imageCleanupTaskConfiguration;
unset($imageCleanupTaskConfiguration);
