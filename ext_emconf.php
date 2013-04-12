<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "realty".
 *
 * Auto generated 12-04-2013 23:33
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
	'author_company' => '',
	'version' => '0.5.68',
	'_md5_values_when_last_written' => 'a:139:{s:9:"ChangeLog";s:4:"11df";s:20:"class.ext_update.php";s:4:"f6f6";s:31:"class.tx_realty_configcheck.php";s:4:"d773";s:23:"class.tx_realty_Tca.php";s:4:"cb2d";s:16:"ext_autoload.php";s:4:"8464";s:21:"ext_conf_template.txt";s:4:"578d";s:12:"ext_icon.gif";s:4:"f073";s:17:"ext_localconf.php";s:4:"1e6a";s:14:"ext_tables.php";s:4:"f39e";s:14:"ext_tables.sql";s:4:"2c5f";s:13:"locallang.xml";s:4:"9d1b";s:16:"locallang_db.xml";s:4:"0108";s:7:"tca.php";s:4:"986e";s:8:"todo.txt";s:4:"7f11";s:46:"Ajax/class.tx_realty_Ajax_DistrictSelector.php";s:4:"70e6";s:34:"Ajax/tx_realty_Ajax_Dispatcher.php";s:4:"ca9b";s:19:"BackEnd/BackEnd.css";s:4:"6175";s:42:"BackEnd/class.tx_realty_BackEnd_Module.php";s:4:"2a8c";s:17:"BackEnd/clear.gif";s:4:"cc11";s:16:"BackEnd/conf.php";s:4:"7541";s:17:"BackEnd/index.php";s:4:"551b";s:21:"BackEnd/locallang.xml";s:4:"5ab5";s:25:"BackEnd/locallang_mod.xml";s:4:"83d9";s:25:"BackEnd/mod_template.html";s:4:"3162";s:22:"BackEnd/moduleicon.gif";s:4:"f073";s:38:"Mapper/class.tx_realty_Mapper_City.php";s:4:"56c4";s:42:"Mapper/class.tx_realty_Mapper_District.php";s:4:"2b1e";s:46:"Mapper/class.tx_realty_Mapper_FrontEndUser.php";s:4:"1f95";s:46:"Mapper/class.tx_realty_Mapper_RealtyObject.php";s:4:"733f";s:19:"Mapper/Document.php";s:4:"4f5f";s:16:"Mapper/Image.php";s:4:"06fd";s:36:"Model/class.tx_realty_Model_City.php";s:4:"8394";s:40:"Model/class.tx_realty_Model_District.php";s:4:"8a2d";s:44:"Model/class.tx_realty_Model_FrontEndUser.php";s:4:"9395";s:44:"Model/class.tx_realty_Model_RealtyObject.php";s:4:"229a";s:18:"Model/Document.php";s:4:"ca99";s:15:"Model/Image.php";s:4:"e8e5";s:30:"Resources/Public/Icons/Pdf.png";s:4:"2021";s:27:"cli/class.tx_realty_cli.php";s:4:"d682";s:40:"cli/class.tx_realty_cli_ImageCleanUp.php";s:4:"9b51";s:47:"cli/class.tx_realty_cli_ImageCleanUpStarter.php";s:4:"89e9";s:14:"doc/manual.sxw";s:4:"35d6";s:40:"icons/icon_tx_realty_apartment_types.gif";s:4:"d517";s:35:"icons/icon_tx_realty_car_places.gif";s:4:"bb75";s:31:"icons/icon_tx_realty_cities.gif";s:4:"bfc0";s:34:"icons/icon_tx_realty_districts.gif";s:4:"5fc7";s:34:"icons/icon_tx_realty_documents.gif";s:4:"a771";s:36:"icons/icon_tx_realty_house_types.gif";s:4:"e878";s:31:"icons/icon_tx_realty_images.gif";s:4:"e1a6";s:34:"icons/icon_tx_realty_images__h.gif";s:4:"a067";s:30:"icons/icon_tx_realty_items.gif";s:4:"475a";s:32:"icons/icon_tx_realty_objects.gif";s:4:"f073";s:35:"icons/icon_tx_realty_objects__h.gif";s:4:"a523";s:29:"icons/icon_tx_realty_pets.gif";s:4:"57cd";s:36:"lib/class.tx_realty_cacheManager.php";s:4:"b107";s:44:"lib/class.tx_realty_domDocumentConverter.php";s:4:"8c6e";s:38:"lib/class.tx_realty_fileNameMapper.php";s:4:"8f53";s:40:"lib/class.tx_realty_googleMapsLookup.php";s:4:"820e";s:40:"lib/class.tx_realty_lightboxIncluder.php";s:4:"5dc2";s:33:"lib/class.tx_realty_mapMarker.php";s:4:"1ef1";s:38:"lib/class.tx_realty_openImmoImport.php";s:4:"bd2e";s:34:"lib/class.tx_realty_translator.php";s:4:"cc9a";s:17:"lib/locallang.xml";s:4:"836b";s:27:"lib/tx_realty_constants.php";s:4:"cb02";s:36:"lib/tx_realty_emailNotification.tmpl";s:4:"c378";s:14:"pi1/ce_wiz.gif";s:4:"fe10";s:35:"pi1/class.tx_realty_contactForm.php";s:4:"b1a2";s:34:"pi1/class.tx_realty_filterForm.php";s:4:"c973";s:38:"pi1/class.tx_realty_frontEndEditor.php";s:4:"92f9";s:36:"pi1/class.tx_realty_frontEndForm.php";s:4:"87d7";s:43:"pi1/class.tx_realty_frontEndImageUpload.php";s:4:"d0c5";s:35:"pi1/class.tx_realty_offererList.php";s:4:"7ef1";s:27:"pi1/class.tx_realty_pi1.php";s:4:"199b";s:44:"pi1/class.tx_realty_pi1_AbstractListView.php";s:4:"0d9e";s:39:"pi1/class.tx_realty_pi1_AccessCheck.php";s:4:"e1a0";s:39:"pi1/class.tx_realty_pi1_AddressView.php";s:4:"814c";s:52:"pi1/class.tx_realty_pi1_AddToFavoritesButtonView.php";s:4:"1ec5";s:42:"pi1/class.tx_realty_pi1_BackButtonView.php";s:4:"3463";s:45:"pi1/class.tx_realty_pi1_ContactButtonView.php";s:4:"4ac4";s:43:"pi1/class.tx_realty_pi1_DefaultListView.php";s:4:"c3d5";s:43:"pi1/class.tx_realty_pi1_DescriptionView.php";s:4:"42fd";s:37:"pi1/class.tx_realty_pi1_ErrorView.php";s:4:"8b29";s:45:"pi1/class.tx_realty_pi1_FavoritesListView.php";s:4:"070a";s:37:"pi1/class.tx_realty_pi1_Formatter.php";s:4:"118c";s:40:"pi1/class.tx_realty_pi1_FrontEndView.php";s:4:"d52b";s:50:"pi1/class.tx_realty_pi1_FurtherDescriptionView.php";s:4:"3c5a";s:42:"pi1/class.tx_realty_pi1_GoogleMapsView.php";s:4:"966f";s:39:"pi1/class.tx_realty_pi1_HeadingView.php";s:4:"80f0";s:47:"pi1/class.tx_realty_pi1_ImageThumbnailsView.php";s:4:"9d03";s:43:"pi1/class.tx_realty_pi1_ListViewFactory.php";s:4:"7d0a";s:45:"pi1/class.tx_realty_pi1_MyObjectsListView.php";s:4:"247c";s:51:"pi1/class.tx_realty_pi1_NextPreviousButtonsView.php";s:4:"af44";s:50:"pi1/class.tx_realty_pi1_ObjectsByOwnerListView.php";s:4:"03d5";s:39:"pi1/class.tx_realty_pi1_OffererView.php";s:4:"3cff";s:45:"pi1/class.tx_realty_pi1_OverviewTableView.php";s:4:"3281";s:37:"pi1/class.tx_realty_pi1_PriceView.php";s:4:"b874";s:47:"pi1/class.tx_realty_pi1_PrintPageButtonView.php";s:4:"7e5d";s:38:"pi1/class.tx_realty_pi1_SingleView.php";s:4:"5748";s:35:"pi1/class.tx_realty_pi1_wizicon.php";s:4:"fc1b";s:21:"pi1/DocumentsView.php";s:4:"c1df";s:23:"pi1/flexform_pi1_ds.xml";s:4:"dac9";s:17:"pi1/locallang.xml";s:4:"13d4";s:18:"pi1/StatusView.php";s:4:"1a29";s:17:"pi1/submit_bg.gif";s:4:"9359";s:33:"pi1/tx_realty_frontEndEditor.html";s:4:"996d";s:32:"pi1/tx_realty_frontEndEditor.xml";s:4:"681d";s:37:"pi1/tx_realty_frontEndImageUpload.xml";s:4:"96ae";s:20:"pi1/tx_realty_pi1.js";s:4:"b408";s:25:"pi1/tx_realty_pi1.tpl.css";s:4:"f244";s:25:"pi1/tx_realty_pi1.tpl.htm";s:4:"b94e";s:27:"pi1/tx_realty_pi1_print.css";s:4:"3a05";s:28:"pi1/tx_realty_pi1_screen.css";s:4:"c4f6";s:22:"pi1/contrib/builder.js";s:4:"759d";s:22:"pi1/contrib/effects.js";s:4:"2a35";s:24:"pi1/contrib/lightbox.css";s:4:"a0fa";s:23:"pi1/contrib/lightbox.js";s:4:"5e8a";s:24:"pi1/contrib/prototype.js";s:4:"9ce4";s:28:"pi1/contrib/scriptaculous.js";s:4:"6a3c";s:33:"pi1/images/button_act_bg_left.png";s:4:"576e";s:34:"pi1/images/button_act_bg_right.png";s:4:"b2d7";s:29:"pi1/images/button_bg_left.png";s:4:"43d8";s:30:"pi1/images/button_bg_right.png";s:4:"63f6";s:30:"pi1/images/cityselector_bg.png";s:4:"11bc";s:32:"pi1/images/cityselector_head.png";s:4:"4106";s:25:"pi1/images/closelabel.gif";s:4:"f34a";s:24:"pi1/images/fav_arrow.png";s:4:"de5e";s:25:"pi1/images/fav_button.png";s:4:"91ae";s:22:"pi1/images/loading.gif";s:4:"7e99";s:24:"pi1/images/nextlabel.gif";s:4:"b25c";s:23:"pi1/images/page_act.png";s:4:"02fe";s:22:"pi1/images/page_no.png";s:4:"a172";s:24:"pi1/images/prevlabel.gif";s:4:"0f43";s:28:"pi1/images/search_button.png";s:4:"0f4c";s:26:"pi1/images/sort_button.png";s:4:"e6b0";s:28:"pi1/images/submit_button.png";s:4:"97e3";s:38:"pi1/images/submit_button_fe_editor.png";s:4:"4859";s:37:"pi1/images/submit_button_inactive.png";s:4:"d2e2";s:24:"pi1/static/constants.txt";s:4:"1928";s:20:"pi1/static/setup.txt";s:4:"18eb";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.5.0-4.7.99',
			'css_styled_content' => '',
			'oelib' => '0.7.67-',
			'ameos_formidable' => '1.1.0-1.9.99',
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

?>