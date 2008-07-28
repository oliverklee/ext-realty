<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_realty_objects'] = array(
	'ctrl' => $TCA['tx_realty_objects']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,' .
			'hidden,starttime,endtime,object_number,object_type,title,' .
			'emphasized,street,zip,city,country,district,number_of_rooms,living_area,' .
			'total_area,estate_size,rent_excluding_bills,extra_charges,' .
			'heating_included,deposit,provision,usable_from,buying_price,hoa_fee,' .
			'year_rent,rented,apartment_type,house_type,floor,floors,bedrooms,' .
			'bathrooms,heating_type,garage_type,garage_rent,garage_price,pets,' .
			'construction_year,old_or_new_building,state,balcony,garden,elevator,' .
			'barrier_free,assisted_living,fitted_kitchen,description,equipment,' .
			'layout,location,misc,details_page,images,employer,openimmo_anid,' .
			'openimmo_obid,utilization,contact_data_source,contact_person,' .
			'contact_email,contact_phone,owner,language,currency,' .
			'exact_coordinates_are_cached,exact_longitude,exact_latitude,' .
			'rough_coordinates_are_cached,rough_longitude,rough_latitude'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_objects',
				'foreign_table_where' => 'AND tx_realty_objects.pid=###CURRENT_PID### AND tx_realty_objects.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0',
			),
		),
		'starttime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0',
			),
		),
		'endtime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y')),
				),
			),
		),
		'object_number' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_number',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'object_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_type',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_type.I.0', '0'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_type.I.1', '1'),
				),
			),
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required,trim',
			),
		),
		'emphasized' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.emphasized',
			'config' => array(
				'type' => 'check',
			),
		),
		'street' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.street',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'zip' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.zip',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'max' => '5',
				'eval' => 'num',
			),
		),
		'city' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.city',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_realty_cities',
				'foreign_table_where' => 'AND tx_realty_cities.pid=###STORAGE_PID### ORDER BY tx_realty_cities.title',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'wizards' => array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => array(
							'table'=>'tx_realty_cities',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend',
						),
						'script' => 'wizard_add.php',
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'district' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.district',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_districts',
				'foreign_table_where' => 'AND tx_realty_districts.pid=###STORAGE_PID### ORDER BY tx_realty_districts.title',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => array(
							'table'=>'tx_realty_districts',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend',
						),
						'script' => 'wizard_add.php',
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'country' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.country',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'static_countries',
				'foreign_table_where' => 'ORDER BY static_countries.cn_short_en',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'exact_coordinates_are_cached' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.exact_coordinates_are_cached',
			'config' => array(
				'type' => 'check',
			)
		),
		'exact_longitude' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.exact_longitude',
			'config' => array(
				'type' => 'input',
				'size' => '19',
				'eval' => 'trim',
			),
		),
		'exact_latitude' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.exact_latitude',
			'config' => array(
				'type' => 'input',
				'size' => '19',
				'eval' => 'trim',
			),
		),
		'rough_coordinates_are_cached' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rough_coordinates_are_cached',
			'config' => array(
				'type' => 'check',
			),
		),
		'rough_longitude' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rough_longitude',
			'config' => array(
				'type' => 'input',
				'size' => '19',
				'eval' => 'trim',
			),
		),
		'rough_latitude' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rough_latitude',
			'config' => array(
				'type' => 'input',
				'size' => '19',
				'eval' => 'trim',
			),
		),
		'number_of_rooms' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.number_of_rooms',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'trim',
			),
		),
		'living_area' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.living_area',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			),
		),
		'total_area' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.total_area',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			),
		),
		'estate_size' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.estate_size',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			),
		),
		'rent_excluding_bills' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rent_excluding_bills',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			),
		),
		'extra_charges' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.extra_charges',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			),
		),
		'heating_included' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_included',
			'config' => array(
				'type' => 'check',
			),
		),
		'deposit' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.deposit',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'provision' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.provision',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'usable_from' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.usable_from',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'buying_price' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.buying_price',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
			),
		),
		'hoa_fee' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.hoa_fee',
			'config' => array(
				'type' => 'input',
				'size' => '7',
				'eval' => 'double2',
			),
		),
		'year_rent' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.year_rent',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
			),
		),
		'rented' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rented',
			'config' => array(
				'type' => 'check',
			),
		),
		'apartment_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.apartment_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'tx_realty_apartment_types',
				'foreign_table_where' => 'AND tx_realty_apartment_types.pid=###STORAGE_PID### ORDER BY tx_realty_apartment_types.title',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => array(
							'table'=>'tx_realty_apartment_types',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend',
						),
						'script' => 'wizard_add.php',
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'house_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.house_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_house_types',
				'foreign_table_where' => 'AND tx_realty_house_types.pid=###STORAGE_PID### ORDER BY tx_realty_house_types.title',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => array(
							'table'=>'tx_realty_house_types',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend',
						),
						'script' => 'wizard_add.php',
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'floor' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floor',
			'config' => array(
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '99',
					'lower' => '0',
				),
				'default' => 0,
			),
		),
		'floors' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floors',
			'config' => array(
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '99',
					'lower' => '0',
				),
				'default' => 0,
			),
		),
		'bedrooms' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.bedrooms',
			'config' => array(
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '99',
					'lower' => '0',
				),
				'default' => 0,
			),
		),
		'bathrooms' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.bathrooms',
			'config' => array(
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '99',
					'lower' => '0',
				),
				'default' => 0,
			),
		),
		'heating_type' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_heating_types',
				'foreign_table_where' => 'AND tx_realty_heating_types.pid=###STORAGE_PID### ORDER BY tx_realty_heating_types.title',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => array(
							'table'=>'tx_realty_heating_types',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend',
						),
						'script' => 'wizard_add.php',
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'garage_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.garage_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_car_places',
				'foreign_table_where' => 'AND tx_realty_car_places.pid=###STORAGE_PID### ORDER BY tx_realty_car_places.title',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => array(
							'table'=>'tx_realty_car_places',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend',
						),
						'script' => 'wizard_add.php',
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'garage_rent' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.garage_rent',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
			),
		),
		'garage_price' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.garage_price',
			'config' => array(
				'type' => 'input',
				'size' => '6',
				'eval' => 'double2',
			),
		),
		'pets' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.pets',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_pets',
				'foreign_table_where' => 'AND tx_realty_pets.pid=###STORAGE_PID### ORDER BY tx_realty_pets.title',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => array(
							'table'=>'tx_realty_pets',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend',
						),
						'script' => 'wizard_add.php',
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'construction_year' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.construction_year',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '2100',
					'lower' => '1400',
				),
				'default' => 0,
			),
		),
		'old_or_new_building' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.old_or_new_building',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', '0'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.old_or_new_building.I.1', '1'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.old_or_new_building.I.2', '2'),
				),
			),
		),
		'state' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_conditions',
				'foreign_table_where' => 'AND tx_realty_conditions.pid=###STORAGE_PID### ORDER BY tx_realty_conditions.title',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type' => 'script',
						'title' => 'Create new record',
						'icon' => 'add.gif',
						'params' => array(
							'table'=>'tx_realty_conditions',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'prepend',
						),
						'script' => 'wizard_add.php',
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'balcony' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.balcony',
			'config' => array(
				'type' => 'check',
			),
		),
		'garden' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.garden',
			'config' => array(
				'type' => 'check',
			),
		),
		'elevator' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.elevator',
			'config' => array(
				'type' => 'check',
			),
		),
		'barrier_free' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.barrier_free',
			'config' => array(
				'type' => 'check',
			),
		),
		'assisted_living' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.assisted_living',
			'config' => array(
				'type' => 'check',
			),
		),
		'fitted_kitchen' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.fitted_kitchen',
			'config' => array(
				'type' => 'check',
			),
		),
		'description' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.description',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			),
		),
		'equipment' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.equipment',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			),
		),
		'layout' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.layout',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
			),
		),
		'location' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.location',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			),
		),
		'misc' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.misc',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			),
		),
		'details_page' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.details_page',
			'config' => array(
				'type' => 'input',
				'size' => '15',
				'max' => '255',
				'checkbox' => '',
				'eval' => 'trim',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'images' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.images',
			'config' => array(
				'type'=> 'inline',
				'foreign_table' => 'tx_realty_images',
				'foreign_field' => 'realty_object_uid',
				'minitems' => 0,
				'maxitems' => 99,
				'appearance' => array(
					'collapseAll' => 1,
					'expandSingle' => 1,
					'newRecordLinkAddTitle' => 1,
					'newRecordLinkPosition' => 'bottom',
				),
			),
		),
		'employer' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.employer',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'openimmo_anid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.openimmo_anid',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'openimmo_obid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.openimmo_obid',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'utilization' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.utilization',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'contact_data_source' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.contact_data_source',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.contact_data_source.I.0', '0'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.contact_data_source.I.1', '1'),
				),
			),
		),
		'contact_person' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.contact_person',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'contact_email' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.contact_email',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'contact_phone' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.contact_phone',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'owner' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.owner',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'language' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.language',
			'config' => array(
				'type' => 'input',
				'size' => 5,
				'eval' => 'trim',
			),
		),
		'currency' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.currency',
			'config' => array(
				'type' => 'input',
				'size' => 10,
				'eval' => 'trim',
			),
		),
	),
	'types' => array(
		'0' => array(
			'showitem' => 'sys_language_uid;;;;1-1-1,l18n_parent, l18n_diffsource, hidden;;1, ' .
				'object_number, openimmo_anid, openimmo_obid, object_type, ' .
				'utilization, title;;;;2-2-2, emphasized;;;;3-3-3, ' .
				'street, zip, city, district, country, number_of_rooms, living_area, ' .
				'total_area, estate_size, rent_excluding_bills, extra_charges, ' .
				'heating_included, deposit, provision, usable_from, ' .
				'apartment_type, house_type, floor, floors, bedrooms, ' .
				'bathrooms, heating_type, garage_type, garage_rent, pets, ' .
				'construction_year, old_or_new_building, state, balcony, garden, ' .
				'elevator, barrier_free, assisted_living, fitted_kitchen, ' .
				'description;;;richtext[cut|copy|paste|formatblock|textcolor|' .
					'bold|italic|underline|left|center|right|orderedlist|' .
					'unorderedlist|outdent|indent|link|table|image|line|chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
				'equipment;;;richtext[cut|copy|paste|formatblock|textcolor|' .
					'bold|italic|underline|left|center|right|orderedlist|' .
					'unorderedlist|outdent|indent|link|table|image|line|chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
				'layout, location;;;richtext[cut|copy|paste|formatblock|' .
					'textcolor|bold|italic|underline|left|center|right|' .
					'orderedlist|unorderedlist|outdent|indent|link|table|image|' .
					'line|chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
				'misc;;;richtext[cut|copy|paste|formatblock|textcolor|bold|' .
					'italic|underline|left|center|right|orderedlist|' .
					'unorderedlist|outdent|indent|link|table|image|line|' .
					'chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
				'details_page, images, contact_data_source, employer, ' .
				'contact_person, contact_email, contact_phone, owner, language, ' .
				'currency' .
				'exact_coordinates_are_cached;;2, ' .
				'rough_coordinates_are_cached;;3'
		),
		'1' => array(
			'showitem' => 'sys_language_uid;;;;1-1-1,l18n_parent, l18n_diffsource, hidden;;1, ' .
				'object_number, openimmo_anid, openimmo_obid, object_type, ' .
				'utilization, title;;;;2-2-2, emphasized;;;;3-3-3, ' .
				'street, zip, city, district, country, number_of_rooms, living_area, ' .
				'total_area, estate_size, provision, usable_from, buying_price, hoa_fee' .
				'year_rent, rented, apartment_type, house_type, floor, floors, ' .
				'bedrooms, bathrooms, heating_type, garage_type, garage_price, ' .
				'construction_year, old_or_new_building, state, balcony, garden, ' .
				'elevator, barrier_free, fitted_kitchen, ' .
				'description;;;richtext[cut|copy|paste|formatblock|textcolor|' .
					'bold|italic|underline|left|center|right|orderedlist|' .
					'unorderedlist|outdent|indent|link|table|image|line|chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
				'equipment;;;richtext[cut|copy|paste|formatblock|textcolor|' .
					'bold|italic|underline|left|center|right|orderedlist|' .
					'unorderedlist|outdent|indent|link|table|image|line|chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
				'layout, location;;;richtext[cut|copy|paste|formatblock|' .
					'textcolor|bold|italic|underline|left|center|right|' .
					'orderedlist|unorderedlist|outdent|indent|link|table|image|' .
					'line|chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
				'misc;;;richtext[cut|copy|paste|formatblock|textcolor|bold|' .
					'italic|underline|left|center|right|orderedlist|' .
					'unorderedlist|outdent|indent|link|table|image|line|' .
					'chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
				'details_page, images, contact_data_source, employer, '.
				'contact_person, contact_email, contact_phone, owner, language, ' .
				'currency' .
				'exact_coordinates_are_cached;;2, ' .
				'rough_coordinates_are_cached;;3'
		)
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime, endtime'),
		'2' => array('showitem' => 'exact_longitude, exact_latitude'),
		'3' => array('showitem' => 'rough_longitude, rough_latitude'),
	),
);



$TCA['tx_realty_apartment_types'] = array(
	'ctrl' => $TCA['tx_realty_apartment_types']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_apartment_types',
				'foreign_table_where' => 'AND tx_realty_apartment_types.pid=###CURRENT_PID### AND tx_realty_apartment_types.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_apartment_types.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	)
);



$TCA['tx_realty_house_types'] = array(
	'ctrl' => $TCA['tx_realty_house_types']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_house_types',
				'foreign_table_where' => 'AND tx_realty_house_types.pid=###CURRENT_PID### AND tx_realty_house_types.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_house_types.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	)
);



$TCA['tx_realty_heating_types'] = array(
	'ctrl' => $TCA['tx_realty_heating_types']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_heating_types',
				'foreign_table_where' => 'AND tx_realty_heating_types.pid=###CURRENT_PID### AND tx_realty_heating_types.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_heating_types.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	)
);


$TCA['tx_realty_car_places'] = array(
	'ctrl' => $TCA['tx_realty_car_places']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'tx_realty_car_places',
				'foreign_table_where' => 'AND tx_realty_car_places.pid=###CURRENT_PID### AND tx_realty_car_places.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_car_places.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	)
);



$TCA['tx_realty_pets'] = array(
	'ctrl' => $TCA['tx_realty_pets']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_pets',
				'foreign_table_where' => 'AND tx_realty_pets.pid=###CURRENT_PID### AND tx_realty_pets.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_pets.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	)
);



$TCA['tx_realty_conditions'] = array(
	'ctrl' => $TCA['tx_realty_conditions']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_conditions',
				'foreign_table_where' => 'AND tx_realty_conditions.pid=###CURRENT_PID### AND tx_realty_conditions.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_conditions.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	)
);

$TCA['tx_realty_images'] = array(
	'ctrl' => $TCA['tx_realty_images']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,caption,image'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_images',
				'foreign_table_where' => 'AND tx_realty_images.pid=###CURRENT_PID### AND tx_realty_images.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0',
			),
		),
		'caption' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images.caption',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
		'image' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images.image',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'gif,png,jpeg,jpg',
				'max_size' => 500,
				'uploadfolder' => 'uploads/tx_realty',
				'show_thumbs' => 1,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, caption, image'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
);



$TCA['tx_realty_cities'] = array(
	'ctrl' => $TCA['tx_realty_cities']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_cities',
				'foreign_table_where' => 'AND tx_realty_cities.pid=###CURRENT_PID### AND tx_realty_cities.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_cities.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
		'save_folder' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_cities.save_folder',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2, save_folder'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
);



$TCA['tx_realty_districts'] = array(
	'ctrl' => $TCA['tx_realty_districts']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_districts',
				'foreign_table_where' => 'AND tx_realty_districts.pid=###CURRENT_PID### AND tx_realty_districts.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_districts.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	)
);
?>