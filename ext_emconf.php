<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "realty".
 *
 * Auto generated 17-01-2015 14:12
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Realty Manager',
    'description' => 'This extension provides a plugin that displays realty objects (immovables, properties, real estate), including an image gallery for each object. For compatibility with TYPO3 7.6 and 8.7, please see the manual.',
    'category' => 'plugin',
    'author' => 'Oliver Klee',
    'author_email' => 'typo3-coding@oliverklee.de',
    'shy' => 0,
    'dependencies' => 'oelib,ameos_formidable,static_info_tables',
    'conflicts' => 'dbal',
    'priority' => '',
    'module' => 'BackEnd',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => 1,
    'createDirs' => 'uploads/tx_realty/rte/',
    'modify_tables' => 'fe_users',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'author_company' => 'oliverklee.de',
    'version' => '0.6.56',
    '_md5_values_when_last_written' => '',
    'constraints' => array(
        'depends' => array(
            'php' => '5.5.0-7.0.99',
            'typo3' => '6.2.0-7.9.99',
            'oelib' => '0.9.52-1.9.99',
            'ameos_formidable' => '1.1.564-1.9.99',
            'static_info_tables' => '6.2.0-',
        ),
        'conflicts' => array(
            'dbal' => '',
        ),
        'suggests' => array(
            'sr_feuser_register' => '',
        ),
    ),
    'suggests' => array(
    ),
);
