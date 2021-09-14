<?php
defined('TYPO3_MODE') or die('Access denied.');

$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('realty');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'realty',
    'Configuration/TypoScript/',
    'Realty Manager'
);

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

// Include base TSconfig setup
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:realty/Configuration/TSconfig/ContentElementWizard.tsconfig'"
);

