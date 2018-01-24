<?php
defined('TYPO3_MODE') or die('Access denied.');

return [
    'ctrl' => [
        'title' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'type' => 'object_type',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:realty/icons/icon_tx_realty_objects.gif',
        'requestUpdate' => 'city,has_coordinates',
        'searchFields' => 'uid,title,object_number,zip,openimmo_anid,openimmo_obid',
    ],
    'interface' => [
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
            'contact_person,contact_person_first_name,contact_person_salutation,contact_email,phone_switchboard,' .
            'phone_direct_extension,owner,language,currency,' .
            'has_coordinates,coordinates_problem,longitude,latitude,' .
            'advertised_date, energy_certificate_type, energy_certificate_valid_until, energy_consumption_characteristic, ' .
            'with_hot_water, ultimate_energy_demand, primary_energy_carrier, electric_power_consumption_characteristic, ' .
            'heat_energy_consumption_characteristic, value_category, year_of_construction, energy_certificate_issue_date, ' .
            'energy_certificate_year, building_type, energy_certificate_text, heat_energy_requirement_value, ' .
            'heat_energy_requirement_class, total_energy_efficiency_value, total_energy_efficiency_class',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0],
                ],
            ],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_realty_objects',
                'foreign_table_where' => 'AND tx_realty_objects.pid=###CURRENT_PID### AND tx_realty_objects.sys_language_uid IN (-1, 0)',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'checkbox' => '0',
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'checkbox' => '0',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y')),
                ],
            ],
        ],
        'object_number' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.object_number',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'object_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.object_type',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.object_type.I.0', '0'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.object_type.I.1', '1'],
                ],
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required,trim',
            ],
        ],
        'emphasized' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.emphasized',
            'config' => [
                'type' => 'check',
            ],
        ],
        'sorting' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.sorting',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'default' => '0',
                'checkbox' => '0',
                'range' => [
                    'upper' => '9999',
                    'lower' => '0',
                ],
                'eval' => 'num',
            ],
        ],
        'show_address' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.show_address',
            'config' => [
                'type' => 'check',
            ]
        ],
        'street' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.street',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'zip' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.zip',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'max' => '5',
                'eval' => 'num',
            ],
        ],
        'city' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.city',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_realty_cities',
                'foreign_table_where' => 'AND tx_realty_cities.pid=###STORAGE_PID### ORDER BY tx_realty_cities.title',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'wizards' => [
                    '_PADDING' => 2,
                    '_VERTICAL' => 1,
                    'add' => [
                        'type' => 'script',
                        'title' => 'Create new record',
                        'icon' => 'add.gif',
                        'params' => [
                            'table' => 'tx_realty_cities',
                            'pid' => '###STORAGE_PID###',
                            'setValue' => 'prepend',
                        ],
                        'script' => 'wizard_add.php',
                    ],
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'Edit',
                        'script' => 'wizard_edit.php',
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'edit2.gif',
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'district' => [
            'displayCond' => 'FIELD:city:>:0',
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.district',
            'config' => [
                'type' => 'select',
                'itemsProcFunc' => 'tx_realty_Tca->getDistrictsForCity',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'wizards' => [
                    '_PADDING' => 2,
                    '_VERTICAL' => 1,
                    'add' => [
                        'type' => 'script',
                        'title' => 'Create new record',
                        'icon' => 'add.gif',
                        'params' => [
                            'table' => 'tx_realty_districts',
                            'pid' => '###STORAGE_PID###',
                            'setValue' => 'prepend',
                        ],
                        'script' => 'wizard_add.php',
                    ],
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'Edit',
                        'script' => 'wizard_edit.php',
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'edit2.gif',
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'country' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.country',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'static_countries',
                'foreign_table_where' => 'ORDER BY static_countries.cn_short_en',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'distance_to_the_sea' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.distance_to_the_sea',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'num',
                'checkbox' => '0',
            ],
        ],
        'sea_view' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.sea_view',
            'config' => [
                'type' => 'check',
            ],
        ],
        'has_coordinates' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.has_coordinates',
            'config' => [
                'type' => 'check',
            ],
        ],
        'coordinates_problem' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.coordinates_problem',
            'config' => [
                'type' => 'check',
            ],
        ],
        'longitude' => [
            'displayCond' => 'FIELD:has_coordinates:!=:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.longitude',
            'config' => [
                'type' => 'input',
                'size' => '19',
                'eval' => 'trim',
                'default' => '0.000000',
            ],
        ],
        'latitude' => [
            'displayCond' => 'FIELD:has_coordinates:!=:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.latitude',
            'config' => [
                'type' => 'input',
                'size' => '19',
                'eval' => 'trim',
                'default' => '0.000000',
            ],
        ],
        'number_of_rooms' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.number_of_rooms',
            'config' => [
                'type' => 'input',
                'size' => '2',
                'max' => '5',
                'eval' => 'double2',
                'range' => [
                    'upper' => '99',
                    'lower' => '0',
                ],
            ],
        ],
        'living_area' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.living_area',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'double2',
            ],
        ],
        'total_area' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.total_area',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'double2',
            ],
        ],
        'shop_area' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.shop_area',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'sales_area' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.sales_area',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'total_usable_area' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.total_usable_area',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'storage_area' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.storage_area',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'office_space' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.office_space',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'other_area' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.other_area',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'window_bank' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.window_bank',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'estate_size' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.estate_size',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'double2',
            ],
        ],
        'site_occupancy_index' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.site_occupancy_index',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'floor_space_index' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.floor_space_index',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'rent_excluding_bills' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.rent_excluding_bills',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'double2',
            ],
        ],
        'rent_per_square_meter' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.rent_per_square_meter',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
                'checkbox' => '0.00',
            ],
        ],
        'extra_charges' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.extra_charges',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'double2',
            ],
        ],
        'heating_included' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_included',
            'config' => [
                'type' => 'check',
            ],
        ],
        'deposit' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.deposit',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'provision' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.provision',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'usable_from' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.usable_from',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'buying_price' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.buying_price',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
            ],
        ],
        'hoa_fee' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.hoa_fee',
            'config' => [
                'type' => 'input',
                'size' => '7',
                'eval' => 'double2',
            ],
        ],
        'year_rent' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.year_rent',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'double2',
            ],
        ],
        'rental_income_target' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.rental_income_target',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'double2',
            ],
        ],
        'status' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.status',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.status.0', 0],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.status.1', 1],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.status.2', 2],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.status.3', 3],
                ],
            ],
        ],
        'apartment_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.apartment_type',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'tx_realty_apartment_types',
                'foreign_table_where' => 'AND tx_realty_apartment_types.pid=###STORAGE_PID### ORDER BY tx_realty_apartment_types.title',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'wizards' => [
                    '_PADDING' => 2,
                    '_VERTICAL' => 1,
                    'add' => [
                        'type' => 'script',
                        'title' => 'Create new record',
                        'icon' => 'add.gif',
                        'params' => [
                            'table' => 'tx_realty_apartment_types',
                            'pid' => '###STORAGE_PID###',
                            'setValue' => 'prepend',
                        ],
                        'script' => 'wizard_add.php',
                    ],
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'Edit',
                        'script' => 'wizard_edit.php',
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'edit2.gif',
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'house_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.house_type',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_realty_house_types',
                'foreign_table_where' => 'AND tx_realty_house_types.pid=###STORAGE_PID### ORDER BY tx_realty_house_types.title',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'wizards' => [
                    '_PADDING' => 2,
                    '_VERTICAL' => 1,
                    'add' => [
                        'type' => 'script',
                        'title' => 'Create new record',
                        'icon' => 'add.gif',
                        'params' => [
                            'table' => 'tx_realty_house_types',
                            'pid' => '###STORAGE_PID###',
                            'setValue' => 'prepend',
                        ],
                        'script' => 'wizard_add.php',
                    ],
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'Edit',
                        'script' => 'wizard_edit.php',
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'edit2.gif',
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'floor' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.floor',
            'config' => [
                'type' => 'input',
                'size' => '2',
                'max' => '2',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '99',
                    'lower' => '-99',
                ],
                'default' => 0,
            ],
        ],
        'floors' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.floors',
            'config' => [
                'type' => 'input',
                'size' => '2',
                'max' => '2',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '99',
                    'lower' => '0',
                ],
                'default' => 0,
            ],
        ],
        'bedrooms' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.bedrooms',
            'config' => [
                'type' => 'input',
                'size' => '2',
                'max' => '5',
                'eval' => 'double2',
                'checkbox' => '0',
                'range' => [
                    'upper' => '99',
                    'lower' => '0',
                ],
                'default' => 0,
            ],
        ],
        'bathrooms' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.bathrooms',
            'config' => [
                'type' => 'input',
                'size' => '2',
                'max' => '5',
                'eval' => 'double2',
                'checkbox' => '0',
                'range' => [
                    'upper' => '99',
                    'lower' => '0',
                ],
                'default' => 0,
            ],
        ],
        'heating_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.1', 1],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.2', 2],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.3', 3],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.4', 4],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.5', 5],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.6', 6],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.7', 7],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.8', 8],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.9', 9],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.10', 10],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.11', 11],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.12', 12],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.13', 13],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.14', 14],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.15', 15],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.16', 16],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.17', 17],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heating_type.18', 18],
                ],
                'renderMode' => 'checkbox',
                'minitems' => 0,
                'maxitems' => 18,
            ],
        ],
        'has_air_conditioning' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.has_air_conditioning',
            'config' => [
                'type' => 'check',
            ]
        ],
        'garage_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.garage_type',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_realty_car_places',
                'foreign_table_where' => 'AND tx_realty_car_places.pid=###STORAGE_PID### ORDER BY tx_realty_car_places.title',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'wizards' => [
                    '_PADDING' => 2,
                    '_VERTICAL' => 1,
                    'add' => [
                        'type' => 'script',
                        'title' => 'Create new record',
                        'icon' => 'add.gif',
                        'params' => [
                            'table' => 'tx_realty_car_places',
                            'pid' => '###STORAGE_PID###',
                            'setValue' => 'prepend',
                        ],
                        'script' => 'wizard_add.php',
                    ],
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'Edit',
                        'script' => 'wizard_edit.php',
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'edit2.gif',
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'parking_spaces' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.parking_spaces',
            'config' => [
                'type' => 'input',
                'size' => '3',
                'eval' => 'num',
                'checkbox' => '0',
            ],
        ],
        'garage_rent' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.garage_rent',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'double2',
            ],
        ],
        'garage_price' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.garage_price',
            'config' => [
                'type' => 'input',
                'size' => '6',
                'eval' => 'double2',
            ],
        ],
        'pets' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.pets',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_realty_pets',
                'foreign_table_where' => 'AND tx_realty_pets.pid=###STORAGE_PID### ORDER BY tx_realty_pets.title',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'wizards' => [
                    '_PADDING' => 2,
                    '_VERTICAL' => 1,
                    'add' => [
                        'type' => 'script',
                        'title' => 'Create new record',
                        'icon' => 'add.gif',
                        'params' => [
                            'table' => 'tx_realty_pets',
                            'pid' => '###STORAGE_PID###',
                            'setValue' => 'prepend',
                        ],
                        'script' => 'wizard_add.php',
                    ],
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'Edit',
                        'script' => 'wizard_edit.php',
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'edit2.gif',
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'flooring' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.1', 1],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.2', 2],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.3', 3],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.4', 4],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.5', 5],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.6', 6],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.7', 7],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.8', 8],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.9', 9],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.10', 10],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.flooring.11', 11],
                ],
                'renderMode' => 'checkbox',
                'minitems' => 0,
                'maxitems' => 11,
            ],
        ],
        'construction_year' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.construction_year',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '2100',
                    'lower' => '1400',
                ],
                'default' => 0,
            ],
        ],
        'old_or_new_building' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.old_or_new_building',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', '0'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.old_or_new_building.I.1', '1'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.old_or_new_building.I.2', '2'],
                ],
            ],
        ],
        'state' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', '0'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.1', 1],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.2', 2],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.3', 3],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.4', 4],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.5', 5],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.6', 6],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.7', 7],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.8', 8],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.9', 9],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.10', 10],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.11', 11],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.12', 12],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.13', 13],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.14', 14],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.15', 15],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.16', 16],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.17', 17],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.18', 18],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.19', 19],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.20', 20],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.state.21', 21],
                ],
            ],
        ],
        'furnishing_category' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.furnishing_category',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', '0'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.furnishing_category.1', 1],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.furnishing_category.2', 2],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.furnishing_category.3', 3],
                ],
            ],
        ],
        'balcony' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.balcony',
            'config' => [
                'type' => 'check',
            ],
        ],
        'garden' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.garden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'elevator' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.elevator',
            'config' => [
                'type' => 'check',
            ],
        ],
        'barrier_free' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.barrier_free',
            'config' => [
                'type' => 'check',
            ],
        ],
        'assisted_living' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.assisted_living',
            'config' => [
                'type' => 'check',
            ],
        ],
        'fitted_kitchen' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.fitted_kitchen',
            'config' => [
                'type' => 'check',
            ],
        ],
        'has_pool' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.has_pool',
            'config' => [
                'type' => 'check',
            ]
        ],
        'has_community_pool' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.has_community_pool',
            'config' => [
                'type' => 'check',
            ]
        ],
        'teaser' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.teaser',
            'config' => [
                'type' => 'text',
                'cols' => '20',
                'rows' => '3',
            ]
        ],
        'description' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'equipment' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.equipment',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'layout' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.layout',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '3',
            ],
        ],
        'location' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.location',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'misc' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.misc',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'details_page' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.details_page',
            'config' => [
                'type' => 'input',
                'size' => '15',
                'max' => '255',
                'checkbox' => '',
                'eval' => 'trim',
                'wizards' => [
                    '_PADDING' => 2,
                    'link' => [
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'link_popup.gif',
                        'script' => 'browse_links.php?mode=wizard',
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'images' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.images',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_realty_images',
                'foreign_field' => 'object',
                'foreign_sortby' => 'sorting',
                'minitems' => 0,
                'maxitems' => 99,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                    'newRecordLinkAddTitle' => 1,
                    'levelLinksPosition' => 'bottom',
                ],
            ],
        ],
        'documents' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.documents',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_realty_documents',
                'foreign_field' => 'object',
                'foreign_sortby' => 'sorting',
                'minitems' => 0,
                'maxitems' => 99,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                    'newRecordLinkAddTitle' => 1,
                    'levelLinksPosition' => 'bottom',
                ],
            ],
        ],
        'employer' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.employer',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'openimmo_anid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.openimmo_anid',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'openimmo_obid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.openimmo_obid',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'utilization' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.utilization',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'contact_data_source' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.contact_data_source',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.contact_data_source.I.0', '0'],
                    ['LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.contact_data_source.I.1', '1'],
                ],
            ],
        ],
        'contact_person' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.contact_person',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'contact_person_first_name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.contact_person_first_name',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'contact_person_salutation' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.contact_person_salutation',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'eval' => 'trim',
            ],
        ],
        'contact_email' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.contact_email',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'phone_switchboard' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.phone_switchboard',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'phone_direct_extension' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.phone_direct_extension',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'owner' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.owner',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'language' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.language',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'eval' => 'trim',
            ],
        ],
        'currency' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.currency',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim',
            ],
        ],
        'advertised_date' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.advertised_date',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'date',
                'checkbox' => '0',
                'default' => '0',
            ],
        ],
        'energy_certificate_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_type',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_UNDEFINED],
                    [
                        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_type.requirement',
                        tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_REQUIREMENT,
                    ],
                    [
                        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_type.consumption',
                        tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_CONSUMPTION,
                    ],
                ],
            ],
        ],
        'energy_certificate_valid_until' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_valid_until',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'energy_consumption_characteristic' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_consumption_characteristic',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'with_hot_water' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.with_hot_water',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'ultimate_energy_demand' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.ultimate_energy_demand',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'primary_energy_carrier' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.primary_energy_carrier',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'electric_power_consumption_characteristic' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.electric_power_consumption_characteristic',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'heat_energy_consumption_characteristic' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heat_energy_consumption_characteristic',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'value_category' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.value_category',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'year_of_construction' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.year_of_construction',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'energy_certificate_issue_date' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_issue_date',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'checkbox' => '0',
            ],
        ],
        'energy_certificate_year' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_year',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_UNDEFINED],
                    [
                        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_year.2008',
                        tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_2008,
                    ],
                    [
                        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_year.2014',
                        tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_2014,
                    ],
                    [
                        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_year.not_available',
                        tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_NOT_AVAILABLE,
                    ],
                    [
                        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_year.not_required',
                        tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_NOT_REQUIRED,
                    ],
                ],
            ],
        ],
        'building_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.building_type',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', tx_realty_Model_RealtyObject::BUILDING_TYPE_UNDEFINED],
                    [
                        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.building_type.residential',
                        tx_realty_Model_RealtyObject::BUILDING_TYPE_RESIDENTIAL,
                    ],
                    [
                        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.building_type.business',
                        tx_realty_Model_RealtyObject::BUILDING_TYPE_BUSINESS,
                    ],
                ],
            ],
        ],
        'energy_certificate_text' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate_text',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'heat_energy_requirement_value' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heat_energy_requirement_value',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'heat_energy_requirement_class' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.heat_energy_requirement_class',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'total_energy_efficiency_value' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.total_energy_efficiency_value',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'total_energy_efficiency_class' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.total_energy_efficiency_class',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
    ],
    'types' => [
        '0' => [
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
                'contact_person;;2, contact_email, phone_switchboard, ' .
                'phone_direct_extension, owner, language, currency, ' .
                'advertised_date;;;;2-2-2, ' .
                '--div--;LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.geo, ' .
                'has_coordinates, coordinates_problem, longitude, latitude, ' .
                '--div--;LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate, ' .
                'energy_certificate_type, energy_certificate_valid_until, energy_consumption_characteristic, ' .
                'with_hot_water, ultimate_energy_demand, primary_energy_carrier, electric_power_consumption_characteristic, ' .
                'heat_energy_consumption_characteristic, value_category, year_of_construction, energy_certificate_issue_date, ' .
                'energy_certificate_year, building_type, energy_certificate_text, heat_energy_requirement_value, ' .
                'heat_energy_requirement_class, total_energy_efficiency_value, total_energy_efficiency_class',
        ],
        '1' => [
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
                'details_page, images, documents, contact_data_source, employer, ' .
                'contact_person;;2, contact_email, phone_switchboard, ' .
                'phone_direct_extension, owner, language, currency, ' .
                'advertised_date;;;;2-2-2, ' .
                '--div--;LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.geo, ' .
                'has_coordinates, coordinates_problem, longitude, latitude, ' .
                '--div--;LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.energy_certificate, ' .
                'energy_certificate_type, energy_certificate_valid_until, energy_consumption_characteristic, ' .
                'with_hot_water, ultimate_energy_demand, primary_energy_carrier, electric_power_consumption_characteristic, ' .
                'heat_energy_consumption_characteristic, value_category, year_of_construction, energy_certificate_issue_date, ' .
                'energy_certificate_year, building_type, energy_certificate_text, heat_energy_requirement_value, ' .
                'heat_energy_requirement_class, total_energy_efficiency_value, total_energy_efficiency_class',
        ]
    ],
    'palettes' => [
        '1' => ['showitem' => 'starttime, endtime'],
        '2' => ['showitem' => 'contact_person_salutation, contact_person_first_name'],
    ],
];
