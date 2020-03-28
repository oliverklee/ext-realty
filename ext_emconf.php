<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Realty Manager',
    'description' => 'Provides a plugin that displays realty objects (properties, real estate), including an image gallery for each object.',
    'version' => '3.0.2',
    'category' => 'plugin',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.2.99',
            'typo3' => '7.6.23-9.5.99',
            'extbase' => '7.6.23-9.5.99',
            'fluid' => '7.6.23-9.5.99',
            'oelib' => '2.3.5-3.9.99',
            'mkforms' => '9.5.1-9.5.99',
            'static_info_tables' => '6.4.0-',
        ],
        'conflicts' => [
            'dbal' => '',
        ],
        'suggests' => [
            'sr_feuser_register' => '',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => true,
    'createDirs' => 'uploads/tx_realty/',
    'clearCacheOnLoad' => true,
    'author' => 'Oliver Klee',
    'author_email' => 'typo3-coding@oliverklee.de',
    'author_company' => 'oliverklee.de',
    'autoload' => [
        'classmap' => [
            'Classes',
            'Ajax',
            'lib',
            'Mapper',
            'Model',
            'pi1',
            'Tests',
        ],
    ],
];
