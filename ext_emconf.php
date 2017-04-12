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
	'description' => 'This extension provides a plugin that displays realty objects (immovables, properties, real estate), including an image gallery for each object.',
	'category' => 'plugin',
	'author' => 'Oliver Klee',
	'author_email' => 'typo3-coding@oliverklee.de',
	'shy' => 0,
	'dependencies' => 'css_styled_content,oelib,ameos_formidable,static_info_tables',
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
	'version' => '0.6.3',
	'_md5_values_when_last_written' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-5.6.99',
			'typo3' => '4.5.0-6.2.99',
			'css_styled_content' => '',
			'oelib' => '0.8.0-1.9.99',
			'ameos_formidable' => '1.1.563-1.9.99',
			'static_info_tables' => '2.1.0-',
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