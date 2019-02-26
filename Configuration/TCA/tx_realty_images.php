<?php
defined('TYPO3_MODE') or die('Access denied.');

return [
    'ctrl' => [
        'delete' => 'deleted',
        'hideTable' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
    ],
    'columns' => [
        'object' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
