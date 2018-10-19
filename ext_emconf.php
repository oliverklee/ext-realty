<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Realty Manager',
    'description' => 'Provides a plugin that displays realty objects (properties, real estate), including an image gallery for each object. For compatibility with TYPO3 7.6 and 8.7, please see the manual.',
    'category' => 'plugin',
    'author' => 'Oliver Klee',
    'author_email' => 'typo3-coding@oliverklee.de',
    'shy' => 0,
    'dependencies' => 'oelib,mkforms,static_info_tables',
    'conflicts' => 'dbal',
    'priority' => '',
    'module' => 'BackEnd',
    'state' => 'stable',
    'internal' => '',
    'createDirs' => 'uploads/tx_realty/,uploads/tx_realty/rte/',
    'modify_tables' => 'fe_users',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'author_company' => 'oliverklee.de',
    'version' => '1.0.1',
    '_md5_values_when_last_written' => '',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.2.99',
            'typo3' => '6.2.0-7.9.99',
            'oelib' => '1.3.0-2.9.99',
            'mkforms' => '3.0.21-3.99.99',
            'static_info_tables' => '6.3.7-',
        ],
        'conflicts' => [
            'dbal' => '',
        ],
        'suggests' => [
            'sr_feuser_register' => '',
        ],
    ],
    'autoload' => [
        'classmap' => [
            'Classes',
            'Ajax',
            'BackEnd',
            'cli',
            'lib',
            'Mapper',
            'Model',
            'pi1',
            'tests',
        ],
    ],
];
