<?php
defined('TYPO3_MODE') or die('Access denied.');

$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $_EXTKEY . '_pi1',
    'FILE:EXT:realty/Configuration/FlexForms/Plugin.xml'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi1',
        $_EXTKEY . '_pi1',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'ext_icon.gif',
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,
    'Configuration/TypoScript/',
    'Realty Manager'
);

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'OliverKlee.Realty',
        'web',
        'openImmo',
        'bottom',
        [
            'OpenImmo' => 'index, import',
        ],
        [
            'access' => 'group',
            'icon' => 'EXT:realty/Resources/Public/Icons/BackEndModule.gif',
            'labels' => 'LLL:EXT:realty/Resources/Private/Language/locallang_mod.xlf',
            // hide the page tree
            'navigationComponentId' => '',
        ]
    );
    $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['tx_realty_pi1_wizicon']
        = $extPath . 'pi1/class.tx_realty_pi1_wizicon.php';
}
