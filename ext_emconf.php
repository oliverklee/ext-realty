<?php

########################################################################
# Extension Manager/Repository config file for ext: "realty"
#
# Auto generated 01-07-2009 14:42
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
	'dependencies' => 'cms,oelib,ameos_formidable,static_info_tables',
	'conflicts' => 'dbal',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => 'uploads/tx_realty/rte/',
	'modify_tables' => 'fe_users',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.4.0',
	'_md5_values_when_last_written' => 'a:102:{s:9:"ChangeLog";s:4:"8cfc";s:31:"class.tx_realty_configcheck.php";s:4:"31b9";s:21:"ext_conf_template.txt";s:4:"578d";s:12:"ext_icon.gif";s:4:"f073";s:17:"ext_localconf.php";s:4:"6268";s:14:"ext_tables.php";s:4:"df2c";s:14:"ext_tables.sql";s:4:"7ee8";s:13:"locallang.xml";s:4:"69a8";s:16:"locallang_db.xml";s:4:"4d94";s:7:"tca.php";s:4:"ff2f";s:8:"todo.txt";s:4:"c7b3";s:36:"lib/class.tx_realty_cacheManager.php";s:4:"1f56";s:44:"lib/class.tx_realty_domDocumentConverter.php";s:4:"c495";s:38:"lib/class.tx_realty_fileNameMapper.php";s:4:"a5ca";s:40:"lib/class.tx_realty_googleMapsLookup.php";s:4:"7491";s:40:"lib/class.tx_realty_lightboxIncluder.php";s:4:"371a";s:33:"lib/class.tx_realty_mapMarker.php";s:4:"77c8";s:38:"lib/class.tx_realty_openImmoImport.php";s:4:"928a";s:34:"lib/class.tx_realty_translator.php";s:4:"f811";s:17:"lib/locallang.xml";s:4:"75c4";s:27:"lib/tx_realty_constants.php";s:4:"10f1";s:36:"lib/tx_realty_emailNotification.tmpl";s:4:"c378";s:46:"Mapper/class.tx_realty_Mapper_FrontEndUser.php";s:4:"1f05";s:46:"Mapper/class.tx_realty_Mapper_RealtyObject.php";s:4:"8f7c";s:40:"icons/icon_tx_realty_apartment_types.gif";s:4:"d517";s:35:"icons/icon_tx_realty_car_places.gif";s:4:"bb75";s:31:"icons/icon_tx_realty_cities.gif";s:4:"bfc0";s:34:"icons/icon_tx_realty_districts.gif";s:4:"5fc7";s:36:"icons/icon_tx_realty_house_types.gif";s:4:"e878";s:31:"icons/icon_tx_realty_images.gif";s:4:"e1a6";s:34:"icons/icon_tx_realty_images__h.gif";s:4:"a067";s:30:"icons/icon_tx_realty_items.gif";s:4:"475a";s:32:"icons/icon_tx_realty_objects.gif";s:4:"f073";s:35:"icons/icon_tx_realty_objects__h.gif";s:4:"a523";s:29:"icons/icon_tx_realty_pets.gif";s:4:"57cd";s:44:"Model/class.tx_realty_Model_FrontEndUser.php";s:4:"c002";s:44:"Model/class.tx_realty_Model_RealtyObject.php";s:4:"7e03";s:14:"doc/manual.sxw";s:4:"be6d";s:14:"pi1/ce_wiz.gif";s:4:"fe10";s:35:"pi1/class.tx_realty_contactForm.php";s:4:"3805";s:34:"pi1/class.tx_realty_filterForm.php";s:4:"f81b";s:38:"pi1/class.tx_realty_frontEndEditor.php";s:4:"6dd8";s:36:"pi1/class.tx_realty_frontEndForm.php";s:4:"f1bc";s:43:"pi1/class.tx_realty_frontEndImageUpload.php";s:4:"07a1";s:35:"pi1/class.tx_realty_offererList.php";s:4:"0cf8";s:27:"pi1/class.tx_realty_pi1.php";s:4:"3c1f";s:39:"pi1/class.tx_realty_pi1_AccessCheck.php";s:4:"c2f4";s:45:"pi1/class.tx_realty_pi1_ActionButtonsView.php";s:4:"fbdd";s:39:"pi1/class.tx_realty_pi1_AddressView.php";s:4:"c858";s:45:"pi1/class.tx_realty_pi1_ContactButtonView.php";s:4:"0d85";s:43:"pi1/class.tx_realty_pi1_DescriptionView.php";s:4:"cf8f";s:37:"pi1/class.tx_realty_pi1_ErrorView.php";s:4:"2a95";s:37:"pi1/class.tx_realty_pi1_Formatter.php";s:4:"421f";s:40:"pi1/class.tx_realty_pi1_FrontEndView.php";s:4:"cbe0";s:50:"pi1/class.tx_realty_pi1_FurtherDescriptionView.php";s:4:"f214";s:42:"pi1/class.tx_realty_pi1_GoogleMapsView.php";s:4:"74b4";s:39:"pi1/class.tx_realty_pi1_HeadingView.php";s:4:"5673";s:47:"pi1/class.tx_realty_pi1_ImageThumbnailsView.php";s:4:"358c";s:39:"pi1/class.tx_realty_pi1_OffererView.php";s:4:"8170";s:45:"pi1/class.tx_realty_pi1_OverviewTableView.php";s:4:"1bf4";s:37:"pi1/class.tx_realty_pi1_PriceView.php";s:4:"bf14";s:38:"pi1/class.tx_realty_pi1_SingleView.php";s:4:"5f3c";s:35:"pi1/class.tx_realty_pi1_wizicon.php";s:4:"4bcc";s:23:"pi1/flexform_pi1_ds.xml";s:4:"35e5";s:17:"pi1/locallang.xml";s:4:"d864";s:17:"pi1/submit_bg.gif";s:4:"9359";s:33:"pi1/tx_realty_frontEndEditor.html";s:4:"38e1";s:32:"pi1/tx_realty_frontEndEditor.xml";s:4:"715b";s:37:"pi1/tx_realty_frontEndImageUpload.xml";s:4:"9e2e";s:20:"pi1/tx_realty_pi1.js";s:4:"270a";s:25:"pi1/tx_realty_pi1.tpl.css";s:4:"163d";s:25:"pi1/tx_realty_pi1.tpl.htm";s:4:"9ec6";s:27:"pi1/tx_realty_pi1_print.css";s:4:"3a05";s:28:"pi1/tx_realty_pi1_screen.css";s:4:"3e4b";s:22:"pi1/contrib/builder.js";s:4:"e8d4";s:22:"pi1/contrib/effects.js";s:4:"51fa";s:24:"pi1/contrib/lightbox.css";s:4:"a0fa";s:23:"pi1/contrib/lightbox.js";s:4:"5e8a";s:24:"pi1/contrib/prototype.js";s:4:"95e1";s:28:"pi1/contrib/scriptaculous.js";s:4:"5b00";s:33:"pi1/images/button_act_bg_left.png";s:4:"576e";s:34:"pi1/images/button_act_bg_right.png";s:4:"b2d7";s:29:"pi1/images/button_bg_left.png";s:4:"43d8";s:30:"pi1/images/button_bg_right.png";s:4:"63f6";s:30:"pi1/images/cityselector_bg.png";s:4:"11bc";s:32:"pi1/images/cityselector_head.png";s:4:"4106";s:25:"pi1/images/closelabel.gif";s:4:"f34a";s:24:"pi1/images/fav_arrow.png";s:4:"de5e";s:25:"pi1/images/fav_button.png";s:4:"91ae";s:22:"pi1/images/loading.gif";s:4:"7e99";s:24:"pi1/images/nextlabel.gif";s:4:"b25c";s:23:"pi1/images/page_act.png";s:4:"02fe";s:22:"pi1/images/page_no.png";s:4:"a172";s:24:"pi1/images/prevlabel.gif";s:4:"0f43";s:28:"pi1/images/search_button.png";s:4:"0f4c";s:26:"pi1/images/sort_button.png";s:4:"e6b0";s:28:"pi1/images/submit_button.png";s:4:"97e3";s:38:"pi1/images/submit_button_fe_editor.png";s:4:"4859";s:37:"pi1/images/submit_button_inactive.png";s:4:"d2e2";s:24:"pi1/static/constants.txt";s:4:"1928";s:20:"pi1/static/setup.txt";s:4:"0bb8";s:27:"cli/class.tx_realty_cli.php";s:4:"7450";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.1.2-0.0.0',
			'cms' => '',
			'oelib' => '0.6.1-',
			'ameos_formidable' => '1.1.0-1.9.99',
			'static_info_tables' => '2.0.2-',
		),
		'conflicts' => array(
			'dbal' => '',
		),
		'suggests' => array(
			'sr_feuser_register' => '',
		),
	),
	'suggests' => array(
		'sr_feuser_register' => '',
	),
);

?>