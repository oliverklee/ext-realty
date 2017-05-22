<?php
defined('TYPO3_MODE') or die('Access denied.');

return [
    'ctrl' => [
        'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_apartment_types',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'iconfile' => 'EXT:realty/icons/icon_tx_realty_apartment_types.gif',
        'searchFields' => 'uid,title',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title',
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
                'foreign_table' => 'tx_realty_apartment_types',
                'foreign_table_where' => 'AND tx_realty_apartment_types.pid=###CURRENT_PID### AND tx_realty_apartment_types.sys_language_uid IN (-1, 0)',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_apartment_types.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
