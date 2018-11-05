<?php
defined('TYPO3_MODE') or die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'fe_users',
    [
        'tx_realty_openimmo_anid' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:fe_users.tx_realty_openimmo_anid',
            'config' => [
                'type' => 'input',
                'size' => 31,
                'eval' => 'trim',
            ],
        ],
        'tx_realty_maximum_objects' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:fe_users.tx_realty_maximum_objects',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'max' => 4,
                'eval' => 'int',
                'range' => [
                    'lower' => 0,
                    'upper' => 9999,
                ],
                'default' => 0,
            ],
        ],
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    '--div--;LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:fe_users.tx_realty_tab,tx_realty_openimmo_anid,tx_realty_maximum_objects'
);
