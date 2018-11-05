<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Realty Manager',
    'description' => 'Provides a plugin that displays realty objects (properties, real estate), including an image gallery for each object.',
    'category' => 'plugin',
    'author' => 'Oliver Klee',
    'author_email' => 'typo3-coding@oliverklee.de',
    'shy' => 0,
    'dependencies' => 'oelib,mkforms,static_info_tables',
    'conflicts' => 'dbal',
    'state' => 'stable',
    'createDirs' => 'uploads/tx_realty/,uploads/tx_realty/rte/',
    'modify_tables' => 'fe_users',
    'clearCacheOnLoad' => 1,
    'author_company' => 'oliverklee.de',
    'version' => '1.1.0',
    '_md5_values_when_last_written' => '',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.2.99',
            'typo3' => '7.6.23-8.7.99',
            'extbase' => '7.6.23-8.7.99',
            'fluid' => '7.6.23-8.7.99',
            'oelib' => '1.5.0-2.9.99',
            'mkforms' => '3.0.21-3.99.99',
            'static_info_tables' => '6.4.0-',
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
            'cli',
            'lib',
            'Mapper',
            'Model',
            'pi1',
            'Tests',
        ],
    ],
];
