<?php
defined('TYPO3_MODE') or die('Access denied.');

return [
    'ctrl' => [
        'title' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_documents',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY object',
        'delete' => 'deleted',
        'hideTable' => true,
        'enablecolumns' => [],
        'iconfile' => 'EXT:realty/icons/icon_tx_realty_documents.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title,filename',
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
                'foreign_table' => 'tx_realty_documents',
                'foreign_table_where' => 'AND tx_realty_documents.pid=###CURRENT_PID### AND tx_realty_documents.sys_language_uid IN (-1, 0)',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ]
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
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_documents.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            ],
        ],
        'filename' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_documents.filename',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'pdf',
                'max_size' => 2000,
                'uploadfolder' => 'uploads/tx_realty',
                'show_thumbs' => 0,
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title, filename'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

