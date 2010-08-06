<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$extPath = t3lib_extMgm::extPath($_EXTKEY);
$extRelPath = t3lib_extMgm::extRelPath($_EXTKEY);
$extIconRelPath = $extRelPath . 'icons/';

$TCA['tx_realty_objects'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'type' => 'object_type',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_objects.gif',
		'requestUpdate' => 'city',
	)
);

$TCA['tx_realty_apartment_types'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_apartment_types',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_apartment_types.gif'
	)
);

$TCA['tx_realty_house_types'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_house_types',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_house_types.gif'
	)
);

$TCA['tx_realty_car_places'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_car_places',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_car_places.gif'
	)
);

$TCA['tx_realty_pets'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_pets',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_pets.gif'
	)
);

$TCA['tx_realty_images'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images',
		'label' => 'caption',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_images.gif'
	)
);

$TCA['tx_realty_documents'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_documents',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(),
		'dynamicConfigFile' => $extPath . 'tca.php',
		'iconfile' => $extIconRelPath . 'icon_tx_realty_documents.gif'
	)
);

$TCA['tx_realty_cities'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_cities',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_cities.gif'
	)
);

$TCA['tx_realty_districts'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_districts',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_districts.gif'
	)
);

t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns(
	'fe_users',
	array(
		'tx_realty_openimmo_anid' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:fe_users.tx_realty_openimmo_anid',
			'config' => array(
				'type' => 'input',
				'size' => '31',
				'eval' => 'trim',
			)
		),
		'tx_realty_maximum_objects' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:fe_users.tx_realty_maximum_objects',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '9999',
					'lower' => '0',
				),
				'default' => 0,
			),
		)
	),
	TRUE
);
t3lib_extMgm::addToAllTCAtypes('fe_users','--div--;LLL:EXT:realty/locallang_db.xml:fe_users.tx_realty_tab,tx_realty_openimmo_anid,tx_realty_maximum_objects;;;;1-1-1,');

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']
	= 'layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']
	= 'pi_flexform';

t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:realty/pi1/flexform_pi1_ds.xml');

t3lib_extMgm::addPlugin(
	array(
		'LLL:EXT:realty/locallang_db.xml:tt_content.list_type_pi1',
		$_EXTKEY.'_pi1',
		t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif',
	),
	'list_type'
);

t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/', 'Realty Manager');

if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_realty_pi1_wizicon']
		= $extPath.'pi1/class.tx_realty_pi1_wizicon.php';

	t3lib_extMgm::addModulePath(
		'web_txrealtyM1', t3lib_extMgm::extPath($_EXTKEY) . 'BackEnd/'
	);
	t3lib_extMgm::addModule(
		'web', 'txrealtyM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'BackEnd/'
	);
}
?>