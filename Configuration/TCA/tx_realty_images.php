<?php
defined('TYPO3_MODE') or die('Access denied.');

return [
    'ctrl' => [
        'title' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images',
        'label' => 'caption',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY object',
        'delete' => 'deleted',
        'hideTable' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:realty/icons/icon_tx_realty_images.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,caption,image,thumbnail,position',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0],
                ],
            ],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_realty_images',
                'foreign_table_where' => 'AND tx_realty_images.pid=###CURRENT_PID### AND tx_realty_images.sys_language_uid IN (-1, 0)',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'object' => [
            'exclude' => 0,
            'label' => '',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_realty_objects',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'caption' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images.caption',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'image' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images.image',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => 2000,
                'uploadfolder' => 'uploads/tx_realty',
                'show_thumbs' => 1,
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'thumbnail' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images.thumbnail',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => 2000,
                'uploadfolder' => 'uploads/tx_realty',
                'show_thumbs' => 1,
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'position' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images.position',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images.position.0', '0'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images.position.1', '1'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images.position.2', '2'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images.position.3', '3'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_images.position.4', '4'],
                ],
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;2, caption, image;;1, position'],
    ],
    'palettes' => [
        '1' => ['showitem' => 'thumbnail'],
    ],
];
