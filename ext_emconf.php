<?php

########################################################################
# Extension Manager/Repository config file for ext: "realty"
#
# Auto generated 04-11-2008 12:32
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Realty Manager',
	'description' => 'This extension provides a plugin that displays realty objects (immovables, properties, real estate), including an image gallery for each object.',
	'category' => 'plugin',
	'author' => 'Oliver Klee',
	'author_email' => 'typo3-coding@oliverklee.de',
	'shy' => 0,
	'dependencies' => 'cms,oelib,ameos_formidable',
	'conflicts' => 'dbal',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => 'uploads/tx_realty/rte/',
	'modify_tables' => 'fe_users',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.3.0',
	'_md5_values_when_last_written' => 'a:91:{s:9:"ChangeLog";s:4:"cbe7";s:31:"class.tx_realty_configcheck.php";s:4:"830c";s:21:"ext_conf_template.txt";s:4:"578d";s:12:"ext_icon.gif";s:4:"f073";s:17:"ext_localconf.php";s:4:"3af8";s:14:"ext_tables.php";s:4:"3202";s:14:"ext_tables.sql";s:4:"d28d";s:13:"locallang.xml";s:4:"475f";s:16:"locallang_db.xml";s:4:"06b7";s:7:"tca.php";s:4:"a47b";s:8:"todo.txt";s:4:"c7b3";s:36:"lib/class.tx_realty_cacheManager.php";s:4:"79d3";s:44:"lib/class.tx_realty_domDocumentConverter.php";s:4:"957c";s:38:"lib/class.tx_realty_fileNameMapper.php";s:4:"d6b1";s:30:"lib/class.tx_realty_object.php";s:4:"623e";s:38:"lib/class.tx_realty_openImmoImport.php";s:4:"97e2";s:34:"lib/class.tx_realty_translator.php";s:4:"b49b";s:17:"lib/locallang.xml";s:4:"e641";s:27:"lib/tx_realty_constants.php";s:4:"b1ce";s:36:"lib/tx_realty_emailNotification.tmpl";s:4:"c378";s:40:"icons/icon_tx_realty_apartment_types.gif";s:4:"d517";s:35:"icons/icon_tx_realty_car_places.gif";s:4:"bb75";s:31:"icons/icon_tx_realty_cities.gif";s:4:"bfc0";s:35:"icons/icon_tx_realty_conditions.gif";s:4:"c6d7";s:34:"icons/icon_tx_realty_districts.gif";s:4:"5fc7";s:36:"icons/icon_tx_realty_house_types.gif";s:4:"e878";s:31:"icons/icon_tx_realty_images.gif";s:4:"e1a6";s:34:"icons/icon_tx_realty_images__h.gif";s:4:"a067";s:30:"icons/icon_tx_realty_items.gif";s:4:"475a";s:32:"icons/icon_tx_realty_objects.gif";s:4:"f073";s:35:"icons/icon_tx_realty_objects__h.gif";s:4:"a523";s:29:"icons/icon_tx_realty_pets.gif";s:4:"57cd";s:41:"tests/tx_realty_cacheManager_testcase.php";s:4:"3784";s:40:"tests/tx_realty_contactForm_testcase.php";s:4:"cd11";s:49:"tests/tx_realty_domDocumentConverter_testcase.php";s:4:"2cf9";s:43:"tests/tx_realty_fileNameMapper_testcase.php";s:4:"ed94";s:39:"tests/tx_realty_filterForm_testcase.php";s:4:"7f98";s:43:"tests/tx_realty_frontEndEditor_testcase.php";s:4:"f0f3";s:41:"tests/tx_realty_frontEndForm_testcase.php";s:4:"a8b6";s:48:"tests/tx_realty_frontEndImageUpload_testcase.php";s:4:"fa03";s:35:"tests/tx_realty_object_testcase.php";s:4:"2982";s:43:"tests/tx_realty_openImmoImport_testcase.php";s:4:"7065";s:32:"tests/tx_realty_pi1_testcase.php";s:4:"5d79";s:39:"tests/tx_realty_translator_testcase.php";s:4:"32b7";s:60:"tests/fixtures/class.tx_realty_domDocumentConverterChild.php";s:4:"606b";s:46:"tests/fixtures/class.tx_realty_objectChild.php";s:4:"3ac9";s:54:"tests/fixtures/class.tx_realty_openImmoImportChild.php";s:4:"7474";s:45:"tests/fixtures/tx_realty_fixtures/bar-bar.zip";s:4:"09cf";s:41:"tests/fixtures/tx_realty_fixtures/bar.zip";s:4:"b4ad";s:53:"tests/fixtures/tx_realty_fixtures/contains-folder.zip";s:4:"7a6a";s:43:"tests/fixtures/tx_realty_fixtures/email.zip";s:4:"ac21";s:43:"tests/fixtures/tx_realty_fixtures/empty.zip";s:4:"6110";s:41:"tests/fixtures/tx_realty_fixtures/foo.zip";s:4:"d84c";s:47:"tests/fixtures/tx_realty_fixtures/same-name.zip";s:4:"7390";s:44:"tests/fixtures/tx_realty_fixtures/schema.xsd";s:4:"bf47";s:49:"tests/fixtures/tx_realty_fixtures/valid-email.zip";s:4:"7cf0";s:66:"tests/fixtures/tx_realty_fixtures/with-email-and-openimmo-anid.zip";s:4:"d728";s:56:"tests/fixtures/tx_realty_fixtures/with-openimmo-anid.zip";s:4:"424c";s:73:"tests/fixtures/tx_realty_fixtures/changed-copy-of-same-name/same-name.zip";s:4:"6785";s:14:"doc/manual.sxw";s:4:"c3d0";s:14:"pi1/ce_wiz.gif";s:4:"fe10";s:35:"pi1/class.tx_realty_contactForm.php";s:4:"d3c0";s:34:"pi1/class.tx_realty_filterForm.php";s:4:"359c";s:38:"pi1/class.tx_realty_frontEndEditor.php";s:4:"b302";s:36:"pi1/class.tx_realty_frontEndForm.php";s:4:"57c4";s:43:"pi1/class.tx_realty_frontEndImageUpload.php";s:4:"6f08";s:27:"pi1/class.tx_realty_pi1.php";s:4:"cc8b";s:35:"pi1/class.tx_realty_pi1_wizicon.php";s:4:"9ac1";s:23:"pi1/flexform_pi1_ds.xml";s:4:"7182";s:17:"pi1/locallang.xml";s:4:"fc5e";s:17:"pi1/submit_bg.gif";s:4:"9359";s:32:"pi1/tx_realty_frontEndEditor.xml";s:4:"e71a";s:37:"pi1/tx_realty_frontEndImageUpload.xml";s:4:"072d";s:25:"pi1/tx_realty_pi1.tpl.css";s:4:"8614";s:25:"pi1/tx_realty_pi1.tpl.htm";s:4:"d521";s:33:"pi1/images/button_act_bg_left.png";s:4:"576e";s:34:"pi1/images/button_act_bg_right.png";s:4:"b2d7";s:29:"pi1/images/button_bg_left.png";s:4:"43d8";s:30:"pi1/images/button_bg_right.png";s:4:"63f6";s:30:"pi1/images/cityselector_bg.png";s:4:"11bc";s:32:"pi1/images/cityselector_head.png";s:4:"4106";s:24:"pi1/images/fav_arrow.png";s:4:"de5e";s:25:"pi1/images/fav_button.png";s:4:"a879";s:23:"pi1/images/page_act.png";s:4:"02fe";s:22:"pi1/images/page_no.png";s:4:"a172";s:28:"pi1/images/search_button.png";s:4:"0f4c";s:26:"pi1/images/sort_button.png";s:4:"f8da";s:24:"pi1/static/constants.txt";s:4:"acd8";s:24:"pi1/static/editorcfg.txt";s:4:"7c17";s:20:"pi1/static/setup.txt";s:4:"176d";s:27:"cli/class.tx_realty_cli.php";s:4:"6cc5";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.1.2-0.0.0',
			'cms' => '',
			'oelib' => '0.4.3-',
			'ameos_formidable' => '1.1.0-1.9.99',
		),
		'conflicts' => array(
			'dbal' => '',
		),
		'suggests' => array(
			'mailform_userfunc' => '0.0.3.-',
		),
	),
	'suggests' => array(
		'mailform_userfunc' => '0.0.3.-',
	),
);

?>