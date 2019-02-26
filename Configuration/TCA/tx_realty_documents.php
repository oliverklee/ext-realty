<?php
defined('TYPO3_MODE') or die('Access denied.');

return [
    'ctrl' => [
        'delete' => 'deleted',
        'hideTable' => true,
    ],
    'columns' => [
        'object' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
