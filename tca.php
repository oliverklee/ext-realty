<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TCA']['tx_realty_objects'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_realty_objects']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,' .
			'hidden,starttime,endtime,object_number,object_type,title,sorting,' .
			'emphasized,show_address,street,zip,city,district,country,distance_to_the_sea,sea_view' .
			'number_of_rooms,living_area,total_area,shop_area,sales_area,' .
			'total_usable_area,storage_area,office_space,other_area,window_bank' .
			'estate_size,site_occupancy_index,floor_space_index,' .
			'rent_excluding_bills,rent_per_square_meter,extra_charges,' .
			'heating_included,deposit,provision,usable_from,buying_price,hoa_fee,' .
			'year_rent,rental_income_target,status,apartment_type,house_type,floor,floors,bedrooms,' .
			'bathrooms,heating_type,has_air_conditioning,garage_type,parking_spaces,garage_rent,' .
			'garage_price,pets,flooring,construction_year,old_or_new_building,' .
			'state,furnishing_category,balcony,garden,elevator,barrier_free,' .
			'assisted_living,fitted_kitchen, has_pool,has_community_pool,teaser,' .
			'description,equipment,layout,location,misc,details_page,images,' .
			'employer,openimmo_anid,openimmo_obid,utilization,contact_data_source,' .
			'contact_person,contact_email,phone_switchboard,' .
			'phone_direct_extension,owner,language,currency,' .
			'has_coordinates,coordinates_problem,longitude,latitude,' .
			'advertised_date, energy_certificate_type, energy_certificate_valid_until, energy_consumption_characteristic, ' .
			'with_hot_water, ultimate_energy_demand, primary_energy_carrier, electric_power_consumption_characteristic, ' .
			'heat_energy_consumption_characteristic, value_category, year_of_construction, energy_certificate_issue_date, ' .
			'energy_certificate_year, building_type, energy_certificate_text, heat_energy_requirement_value, ' .
			'heat_energy_requirement_class, total_energy_efficiency_value, total_energy_efficiency_class'
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
		'sorting' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.sorting',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'default' => '0',
				'checkbox' => '0',
				'range' => array(
					'upper' => '9999',
					'lower' => '0',
				),
				'eval' => 'num',
			),
		),
		'show_address' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.show_address',
			'config' => array(
				'type' => 'check',
			)
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
			'displayCond' => 'FIELD:city:>:0',
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.district',
			'config' => array(
				'type' => 'select',
				'itemsProcFunc' => 'tx_realty_Tca->getDistrictsForCity',
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
		'distance_to_the_sea' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.distance_to_the_sea',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'num',
				'checkbox' => '0',
			),
		),
		'sea_view' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.sea_view',
			'config' => array(
				'type' => 'check',
			),
		),
		'has_coordinates' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.has_coordinates',
			'config' => array(
				'type' => 'check',
			),
		),
		'coordinates_problem' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.coordinates_problem',
			'config' => array(
				'type' => 'check',
			),
		),
		'longitude' => array(
			'displayCond' => 'FIELD:has_coordinates:!=:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.longitude',
			'config' => array(
				'type' => 'input',
				'size' => '19',
				'eval' => 'trim',
				'default' => '0.000000',
			),
		),
		'latitude' => array(
			'displayCond' => 'FIELD:has_coordinates:!=:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.latitude',
			'config' => array(
				'type' => 'input',
				'size' => '19',
				'eval' => 'trim',
				'default' => '0.000000',
			),
		),
		'number_of_rooms' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.number_of_rooms',
			'config' => array(
				'type' => 'input',
				'size' => '2',
				'max' => '5',
				'eval' => 'double2',
				'range' => array(
					'upper' => '99',
					'lower' => '0',
				),
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
		'shop_area' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.shop_area',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
				'checkbox' => '0.00',
			),
		),
		'sales_area' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.sales_area',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
				'checkbox' => '0.00',
			),
		),
		'total_usable_area' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.total_usable_area',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
				'checkbox' => '0.00',
			),
		),
		'storage_area' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.storage_area',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
				'checkbox' => '0.00',
			),
		),
		'office_space' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.office_space',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
				'checkbox' => '0.00',
			),
		),
		'other_area' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.other_area',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
				'checkbox' => '0.00',
			),
		),
		'window_bank' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.window_bank',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
				'checkbox' => '0.00',
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
		'site_occupancy_index' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.site_occupancy_index',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
				'checkbox' => '0.00',
			),
		),
		'floor_space_index' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floor_space_index',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'eval' => 'double2',
				'checkbox' => '0.00',
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
		'rent_per_square_meter' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rent_per_square_meter',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'eval' => 'double2',
				'checkbox' => '0.00',
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
		'rental_income_target' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rental_income_target',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'eval' => 'double2',
			),
		),
		'status' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.status',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.status.0', 0),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.status.1', 1),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.status.2', 2),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.status.3', 3),
				),
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
					'lower' => '-99',
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
				'max' => '5',
				'eval' => 'double2',
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
				'max' => '5',
				'eval' => 'double2',
				'checkbox' => '0',
				'range' => array(
					'upper' => '99',
					'lower' => '0',
				),
				'default' => 0,
			),
		),
		'heating_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.1', 1),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.2', 2),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.3', 3),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.4', 4),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.5', 5),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.6', 6),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.7', 7),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.8', 8),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.9', 9),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.10', 10),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.11', 11),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heating_type.12', 12),
				),
				'renderMode' => 'checkbox',
				'minitems' => 0,
				'maxitems' => 12,
			),
		),
		'has_air_conditioning' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.has_air_conditioning',
			'config' => array(
				'type' => 'check',
			)
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
		'parking_spaces' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.parking_spaces',
			'config' => array(
				'type' => 'input',
				'size' => '3',
				'eval' => 'num',
				'checkbox' => '0',
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
		'flooring' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.1', 1),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.2', 2),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.3', 3),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.4', 4),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.5', 5),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.6', 6),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.7', 7),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.8', 8),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.9', 9),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.10', 10),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.flooring.11', 11),
				),
				'renderMode' => 'checkbox',
				'minitems' => 0,
				'maxitems' => 11,
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
					array('', '0'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.1', 1),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.2', 2),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.3', 3),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.4', 4),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.5', 5),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.6', 6),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.7', 7),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.8', 8),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.9', 9),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.10', 10),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.11', 11),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.12', 12),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.13', 13),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.14', 14),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.15', 15),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.16', 16),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.17', 17),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.18', 18),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.19', 19),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.20', 20),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.state.21', 21),
				),
			),
		),
		'furnishing_category' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.furnishing_category',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', '0'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.furnishing_category.1', 1),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.furnishing_category.2', 2),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.furnishing_category.3', 3),
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
		'has_pool' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.has_pool',
			'config' => array(
				'type' => 'check',
			)
		),
		'has_community_pool' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.has_community_pool',
			'config' => array(
				'type' => 'check',
			)
		),
		'teaser' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.teaser',
			'config' => array(
				'type' => 'text',
				'cols' => '20',
				'rows' => '3',
			)
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
				'foreign_field' => 'object',
				'foreign_sortby' => 'sorting',
				'minitems' => 0,
				'maxitems' => 99,
				'appearance' => array(
					'collapseAll' => 1,
					'expandSingle' => 1,
					'newRecordLinkAddTitle' => 1,
					'levelLinksPosition' => 'bottom',
				),
			),
		),
		'documents' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.documents',
			'config' => array(
				'type'=> 'inline',
				'foreign_table' => 'tx_realty_documents',
				'foreign_field' => 'object',
				'foreign_sortby' => 'sorting',
				'minitems' => 0,
				'maxitems' => 99,
				'appearance' => array(
					'collapseAll' => 1,
					'expandSingle' => 1,
					'newRecordLinkAddTitle' => 1,
					'levelLinksPosition' => 'bottom',
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
		'phone_switchboard' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.phone_switchboard',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'phone_direct_extension' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.phone_direct_extension',
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
		'advertised_date' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.advertised_date',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'max' => '10',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
			),
		),
		'energy_certificate_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_UNDEFINED),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_type.requirement', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_REQUIREMENT),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_type.consumption', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_CONSUMPTION),
				),
			),
		),
		'energy_certificate_valid_until' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_valid_until',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'energy_consumption_characteristic' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_consumption_characteristic',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'with_hot_water' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.with_hot_water',
			'config' => array(
				'type' => 'check',
				'default' => '0',
			),
		),
		'ultimate_energy_demand' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.ultimate_energy_demand',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'primary_energy_carrier' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.primary_energy_carrier',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'electric_power_consumption_characteristic' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.electric_power_consumption_characteristic',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'heat_energy_consumption_characteristic' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heat_energy_consumption_characteristic',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'value_category' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.value_category',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'year_of_construction' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.year_of_construction',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'energy_certificate_issue_date' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_issue_date',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0',
			),
		),
		'energy_certificate_year' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_year',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_UNDEFINED),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_year.2008', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_2008),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_year.2014', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_2014),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_year.not_available', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_NOT_AVAILABLE),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_year.not_required', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_NOT_REQUIRED),
				),
			),
		),
		'building_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.building_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', tx_realty_Model_RealtyObject::BUILDING_TYPE_UNDEFINED),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.building_type.residential', tx_realty_Model_RealtyObject::BUILDING_TYPE_RESIDENTIAL),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.building_type.business', tx_realty_Model_RealtyObject::BUILDING_TYPE_BUSINESS),
				),
			),
		),
		'energy_certificate_text' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate_text',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			),
		),
		'heat_energy_requirement_value' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heat_energy_requirement_value',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'heat_energy_requirement_class' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.heat_energy_requirement_class',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'total_energy_efficiency_value' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.total_energy_efficiency_value',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
		'total_energy_efficiency_class' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.total_energy_efficiency_class',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim',
			),
		),
	),
	'types' => array(
		'0' => array(
			// for rent
			'showitem' => 'sys_language_uid;;;;1-1-1, ' .
				'l18n_parent, l18n_diffsource, hidden;;1, ' .
				'object_number, openimmo_anid, openimmo_obid, object_type, ' .
				'utilization, title;;;;2-2-2, emphasized, sorting, ' .
				'show_address;;;;2-2-2, street, zip, city, district, country, distance_to_the_sea, sea_view, number_of_rooms, ' .
				'living_area, total_area, shop_area, sales_area, total_usable_area, ' .
				'storage_area, office_space, other_area, window_bank, ' .
				'estate_size, site_occupancy_index, floor_space_index, ' .
				'rent_excluding_bills, rent_per_square_meter,' .
				'extra_charges, heating_included, deposit, provision, usable_from, status, ' .
				'apartment_type, house_type, floor, floors, bedrooms, ' .
				'bathrooms, heating_type, has_air_conditioning, garage_type, parking_spaces, ' .
				'garage_rent, pets, flooring, construction_year, old_or_new_building, ' .
				'state, furnishing_category, balcony, garden, elevator, barrier_free, ' .
				'assisted_living, fitted_kitchen, has_pool, has_community_pool,' .
				'teaser;;;richtext[cut|copy|paste|formatblock|textcolor|' .
					'bold|italic|underline|left|center|right|orderedlist|' .
					'unorderedlist|outdent|indent|link|table|image|line|chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
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
				'details_page, images, documents, contact_data_source, employer, ' .
				'contact_person, contact_email, phone_switchboard, ' .
				'phone_direct_extension, owner, language, currency, ' .
				'advertised_date;;;;2-2-2, ' .
				'--div--;LLL:EXT:realty/locallang_db.xml:tx_realty_objects.geo, ' .
				'has_coordinates, coordinates_problem, longitude, latitude' .
				'--div--;LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate, ' .
				'energy_certificate_type, energy_certificate_valid_until, energy_consumption_characteristic, ' .
				'with_hot_water, ultimate_energy_demand, primary_energy_carrier, electric_power_consumption_characteristic, ' .
				'heat_energy_consumption_characteristic, value_category, year_of_construction, energy_certificate_issue_date, ' .
				'energy_certificate_year, building_type, energy_certificate_text, heat_energy_requirement_value, ' .
				'heat_energy_requirement_class, total_energy_efficiency_value, total_energy_efficiency_class'
		),
		'1' => array(
			// for sale
			'showitem' => 'sys_language_uid;;;;1-1-1, ' .
				'l18n_parent, l18n_diffsource, hidden;;1, ' .
				'object_number, openimmo_anid, openimmo_obid, object_type, ' .
				'title;;;;2-2-2, emphasized, sorting, ' .
				'show_address;;;;2-2-2, street, zip, city, district, country, distance_to_the_sea, sea_view, number_of_rooms, ' .
				'living_area, total_area, shop_area, sales_area, total_usable_area, ' .
				'storage_area, office_space, other_area, window_bank, ' .
				'estate_size, site_occupancy_index, ' .
				'floor_space_index, provision, usable_from, ' .
				'buying_price, hoa_fee, extra_charges, year_rent, rental_income_target, status, ' .
				'apartment_type, house_type, floor, floors, bedrooms, bathrooms, ' .
				'heating_type, has_air_conditioning, garage_type, parking_spaces, garage_price, ' .
				'flooring, construction_year, old_or_new_building, state, ' .
				'furnishing_category, balcony, garden, elevator, barrier_free, ' .
				'fitted_kitchen, has_pool, has_community_pool, ' .
				'teaser;;;richtext[cut|copy|paste|formatblock|textcolor|' .
					'bold|italic|underline|left|center|right|orderedlist|' .
					'unorderedlist|outdent|indent|link|table|image|line|chMode]' .
					':rte_transform[mode=ts_css|imgpath=uploads/tx_realty/rte/], ' .
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
				'details_page, images, documents, contact_data_source, employer, '.
				'contact_person, contact_email, phone_switchboard, ' .
				'phone_direct_extension, owner, language, currency, ' .
				'advertised_date;;;;2-2-2, ' .
				'--div--;LLL:EXT:realty/locallang_db.xml:tx_realty_objects.geo, ' .
				'has_coordinates, coordinates_problem, longitude, latitude, ' .
				'--div--;LLL:EXT:realty/locallang_db.xml:tx_realty_objects.energy_certificate, ' .
				'energy_certificate_type, energy_certificate_valid_until, energy_consumption_characteristic, ' .
				'with_hot_water, ultimate_energy_demand, primary_energy_carrier, electric_power_consumption_characteristic, ' .
				'heat_energy_consumption_characteristic, value_category, year_of_construction, energy_certificate_issue_date, ' .
				'energy_certificate_year, building_type, energy_certificate_text, heat_energy_requirement_value, ' .
				'heat_energy_requirement_class, total_energy_efficiency_value, total_energy_efficiency_class'
		)
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime, endtime'),
	),
);



$GLOBALS['TCA']['tx_realty_apartment_types'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_realty_apartment_types']['ctrl'],
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



$GLOBALS['TCA']['tx_realty_house_types'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_realty_house_types']['ctrl'],
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



$GLOBALS['TCA']['tx_realty_car_places'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_realty_car_places']['ctrl'],
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



$GLOBALS['TCA']['tx_realty_pets'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_realty_pets']['ctrl'],
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



$GLOBALS['TCA']['tx_realty_images'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_realty_images']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,caption,image,thumbnail,position'
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
		'object' => array(
			'exclude' => 0,
			'label' => '',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_realty_objects',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'caption' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images.caption',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			),
		),
		'image' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images.image',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => 2000,
				'uploadfolder' => 'uploads/tx_realty',
				'show_thumbs' => 1,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'thumbnail' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images.thumbnail',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => 2000,
				'uploadfolder' => 'uploads/tx_realty',
				'show_thumbs' => 1,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'position' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images.position',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_images.position.0', '0'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_images.position.1', '1'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_images.position.2', '2'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_images.position.3', '3'),
					array('LLL:EXT:realty/locallang_db.xml:tx_realty_images.position.4', '4'),
				),
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;2, caption, image;;1, position'),
	),
	'palettes' => array(
		'1' => array('showitem' => 'thumbnail'),
	),
);



$GLOBALS['TCA']['tx_realty_documents'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_realty_documents']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title,filename'
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
				'foreign_table' => 'tx_realty_documents',
				'foreign_table_where' => 'AND tx_realty_documents.pid=###CURRENT_PID### AND tx_realty_documents.sys_language_uid IN (-1, 0)',
			),
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'object' => array(
			'exclude' => 0,
			'label' => '',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_realty_objects',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_documents.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
		'filename' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_documents.filename',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'pdf',
				'max_size' => 2000,
				'uploadfolder' => 'uploads/tx_realty',
				'show_thumbs' => 0,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title, filename'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
);


$GLOBALS['TCA']['tx_realty_cities'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_realty_cities']['ctrl'],
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
				'allowed' => 'pages',
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



$GLOBALS['TCA']['tx_realty_districts'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_realty_districts']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title,city'
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
		'city' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_districts.city',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_realty_cities',
				'foreign_table_where' => ' ORDER BY title ASC',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2, city'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	)
);