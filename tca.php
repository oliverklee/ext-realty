<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_realty_objects'] = Array (
	'ctrl' => $TCA['tx_realty_objects']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,object_number,object_type,title,emphasized,street,zip,city,district,number_of_rooms,living_area,total_area,rent_excluding_bills,extra_charges,heating_included,deposit,provision,usable_from,buying_price,year_rent,rented,apartment_type,house_type,floor,floors,bedrooms,bathrooms,heating_type,garage_type,garage_rent,garage_price,pets,construction_year,state,balcony,garden,elevator,accessible,assisted_living,fitted_kitchen,description,equipment,layout,location,misc,images'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_objects',
				'foreign_table_where' => 'AND tx_realty_objects.pid=###CURRENT_PID### AND tx_realty_objects.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
				)
			)
		),
		'object_number' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_number',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'object_type' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_type',
			'config' => Array (
				'type' => 'radio',
				'items' => Array (
					Array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_type.I.0', '0'),
					Array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_type.I.1', '1'),
				),
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
				'eval' => 'trim',
			)
		),
		'emphasized' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.emphasized',
			'config' => Array (
				'type' => 'check',
			)
		),
		'street' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.street',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'zip' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.zip',
			'config' => Array (
				'type' => 'input',
				'size' => '5',
				'max' => '5',
				'eval' => 'num',
			)
		),
		'city' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.city',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tx_realty_cities',
				'foreign_table_where' => 'AND tx_realty_cities.pid=###STORAGE_PID### ORDER BY tx_realty_cities.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_realty_cities',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'district' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.district',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_districts',
				'foreign_table_where' => 'AND tx_realty_districts.pid=###STORAGE_PID### ORDER BY tx_realty_districts.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_realty_districts',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'number_of_rooms' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.number_of_rooms',
			'config' => Array (
				'type' => 'input',
				'size' => '5',
				'eval' => 'trim',
			)
		),
		'living_area' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.living_area',
			'config' => Array (
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			)
		),
		'total_area' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.total_area',
			'config' => Array (
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			)
		),
		'rent_excluding_bills' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rent_excluding_bills',
			'config' => Array (
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			)
		),
		'extra_charges' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.extra_charges',
			'config' => Array (
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			)
		),
		'heating_included' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_included',
			'config' => Array (
				'type' => 'check',
			)
		),
		'deposit' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.deposit',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'provision' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.provision',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'usable_from' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.usable_from',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'buying_price' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.buying_price',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
			)
		),
		'year_rent' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.year_rent',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
			)
		),
		'rented' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rented',
			'config' => Array (
				'type' => 'check',
			)
		),
		'apartment_type' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.apartment_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_apartment_types',
				'foreign_table_where' => 'AND tx_realty_apartment_types.pid=###STORAGE_PID### ORDER BY tx_realty_apartment_types.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_realty_apartment_types',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'house_type' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.house_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_house_types',
				'foreign_table_where' => 'AND tx_realty_house_types.pid=###STORAGE_PID### ORDER BY tx_realty_house_types.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_realty_house_types',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'floor' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floor',
			'config' => Array (
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '99',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'floors' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floors',
			'config' => Array (
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '99',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'bedrooms' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.bedrooms',
			'config' => Array (
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '99',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'bathrooms' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.bathrooms',
			'config' => Array (
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '99',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'heating_type' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_heating_types',
				'foreign_table_where' => 'AND tx_realty_heating_types.pid=###STORAGE_PID### ORDER BY tx_realty_heating_types.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_realty_heating_types',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'garage_type' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.garage_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_car_places',
				'foreign_table_where' => 'AND tx_realty_car_places.pid=###STORAGE_PID### ORDER BY tx_realty_car_places.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_realty_car_places',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'garage_rent' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.garage_rent',
			'config' => Array (
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			)
		),
		'garage_price' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.garage_price',
			'config' => Array (
				'type' => 'input',
				'size' => '6',
				'eval' => 'double2',
			)
		),
		'pets' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.pets',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_pets',
				'foreign_table_where' => 'AND tx_realty_pets.pid=###STORAGE_PID### ORDER BY tx_realty_pets.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_realty_pets',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'construction_year' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.construction_year',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '2100',
					'lower' => '1400'
				),
				'default' => 0
			)
		),
		'state' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_conditions',
				'foreign_table_where' => 'AND tx_realty_conditions.pid=###STORAGE_PID### ORDER BY tx_realty_conditions.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_realty_conditions',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'balcony' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.balcony',
			'config' => Array (
				'type' => 'check',
			)
		),
		'garden' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.garden',
			'config' => Array (
				'type' => 'check',
			)
		),
		'elevator' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.elevator',
			'config' => Array (
				'type' => 'check',
			)
		),
		'accessible' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.accessible',
			'config' => Array (
				'type' => 'check',
			)
		),
		'assisted_living' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.assisted_living',
			'config' => Array (
				'type' => 'check',
			)
		),
		'fitted_kitchen' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.fitted_kitchen',
			'config' => Array (
				'type' => 'check',
			)
		),
		'description' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.description',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'equipment' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.equipment',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'layout' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.layout',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
			)
		),
		'location' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.location',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'misc' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.misc',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'images' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.images',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tx_realty_images',
				'foreign_table_where' => 'AND tx_realty_images.pid=###CURRENT_PID### ORDER BY tx_realty_images.uid',
				'size' => 5,
				'minitems' => 0,
				'maxitems' => 99,
				'MM' => 'tx_realty_objects_images_mm',
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_realty_images',
							'pid' => '###CURRENT_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, object_number, object_type, title;;;;2-2-2, emphasized;;;;3-3-3, street, zip, city, district, number_of_rooms, living_area, total_area, rent_excluding_bills, extra_charges, heating_included, deposit, provision, usable_from, apartment_type, house_type, floor, floors, bedrooms, bathrooms, heating_type, garage_type, garage_rent, pets, construction_year, state, balcony, garden, elevator, accessible, assisted_living, fitted_kitchen, description;;;richtext[cut|copy|paste|formatblock|textcolor|bold|italic|underline|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image|line|chMode]:rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], equipment;;;richtext[cut|copy|paste|formatblock|textcolor|bold|italic|underline|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image|line|chMode]:rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], layout, location;;;richtext[cut|copy|paste|formatblock|textcolor|bold|italic|underline|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image|line|chMode]:rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], misc;;;richtext[cut|copy|paste|formatblock|textcolor|bold|italic|underline|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image|line|chMode]:rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], images'),
		'1' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, object_number, object_type, title;;;;2-2-2, emphasized;;;;3-3-3, street, zip, city, district, number_of_rooms, living_area, total_area, provision, usable_from, buying_price, year_rent, rented, apartment_type, house_type, floor, floors, bedrooms, bathrooms, heating_type, garage_type, garage_price, construction_year, state, balcony, garden, elevator, accessible, fitted_kitchen, description;;;richtext[cut|copy|paste|formatblock|textcolor|bold|italic|underline|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image|line|chMode]:rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], equipment;;;richtext[cut|copy|paste|formatblock|textcolor|bold|italic|underline|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image|line|chMode]:rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], layout, location;;;richtext[cut|copy|paste|formatblock|textcolor|bold|italic|underline|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image|line|chMode]:rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], misc;;;richtext[cut|copy|paste|formatblock|textcolor|bold|italic|underline|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image|line|chMode]:rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], images')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime')
	)
);



$TCA['tx_realty_apartment_types'] = Array (
	'ctrl' => $TCA['tx_realty_apartment_types']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_apartment_types',
				'foreign_table_where' => 'AND tx_realty_apartment_types.pid=###CURRENT_PID### AND tx_realty_apartment_types.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_apartment_types.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_realty_house_types'] = Array (
	'ctrl' => $TCA['tx_realty_house_types']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_house_types',
				'foreign_table_where' => 'AND tx_realty_house_types.pid=###CURRENT_PID### AND tx_realty_house_types.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_house_types.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_realty_heating_types'] = Array (
	'ctrl' => $TCA['tx_realty_heating_types']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_heating_types',
				'foreign_table_where' => 'AND tx_realty_heating_types.pid=###CURRENT_PID### AND tx_realty_heating_types.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_heating_types.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_realty_car_places'] = Array (
	'ctrl' => $TCA['tx_realty_car_places']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_car_places',
				'foreign_table_where' => 'AND tx_realty_car_places.pid=###CURRENT_PID### AND tx_realty_car_places.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_car_places.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_realty_pets'] = Array (
	'ctrl' => $TCA['tx_realty_pets']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_pets',
				'foreign_table_where' => 'AND tx_realty_pets.pid=###CURRENT_PID### AND tx_realty_pets.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_pets.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_realty_conditions'] = Array (
	'ctrl' => $TCA['tx_realty_conditions']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_conditions',
				'foreign_table_where' => 'AND tx_realty_conditions.pid=###CURRENT_PID### AND tx_realty_conditions.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_conditions.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);

$TCA['tx_realty_images'] = Array (
	'ctrl' => $TCA['tx_realty_images']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,caption,image'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_images',
				'foreign_table_where' => 'AND tx_realty_images.pid=###CURRENT_PID### AND tx_realty_images.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'caption' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images.caption',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'image' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images.image',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'gif,png,jpeg,jpg',
				'max_size' => 500,
				'uploadfolder' => 'uploads/tx_realty',
				'show_thumbs' => 1,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, caption, image')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_realty_cities'] = Array (
	'ctrl' => $TCA['tx_realty_cities']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_cities',
				'foreign_table_where' => 'AND tx_realty_cities.pid=###CURRENT_PID### AND tx_realty_cities.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_cities.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_realty_districts'] = Array (
	'ctrl' => $TCA['tx_realty_districts']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => Array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_realty_districts',
				'foreign_table_where' => 'AND tx_realty_districts.pid=###CURRENT_PID### AND tx_realty_districts.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'title' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_districts.title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);

?>
