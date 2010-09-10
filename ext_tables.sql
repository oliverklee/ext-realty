#
# Table structure for table 'tx_realty_objects'
#
CREATE TABLE tx_realty_objects (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	object_number tinytext NOT NULL,
	object_type int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	emphasized tinyint(3) DEFAULT '0' NOT NULL,
	street tinytext NOT NULL,
	zip tinytext NOT NULL,
	city int(11) DEFAULT '0' NOT NULL,
	district int(11) DEFAULT '0' NOT NULL,
	country int(11) DEFAULT '0' NOT NULL,
	show_address tinyint(1) DEFAULT '0' NOT NULL,
	exact_coordinates_are_cached tinyint(1) unsigned DEFAULT '0' NOT NULL,
	exact_longitude varchar(19) DEFAULT '' NOT NULL,
	exact_latitude varchar(19) DEFAULT '' NOT NULL,
	rough_coordinates_are_cached tinyint(1) unsigned DEFAULT '0' NOT NULL,
	rough_longitude varchar(19) DEFAULT '' NOT NULL,
	rough_latitude varchar(19) DEFAULT '' NOT NULL,
	number_of_rooms decimal(5,2) DEFAULT '0.00' NOT NULL,
	living_area tinytext NOT NULL,
	total_area tinytext NOT NULL,
	estate_size tinytext NOT NULL,
	rent_excluding_bills tinytext NOT NULL,
	extra_charges tinytext NOT NULL,
	heating_included tinyint(3) DEFAULT '0' NOT NULL,
	deposit tinytext NOT NULL,
	provision tinytext NOT NULL,
	usable_from tinytext NOT NULL,
	buying_price tinytext NOT NULL,
	hoa_fee tinytext NOT NULL,
	year_rent tinytext NOT NULL,
	status tinyint(3) DEFAULT '0' NOT NULL,
	apartment_type int(11) DEFAULT '0' NOT NULL,
	house_type int(11) DEFAULT '0' NOT NULL,
	floor int(11) DEFAULT '0' NOT NULL,
	floors int(11) DEFAULT '0' NOT NULL,
	bedrooms decimal(5,2) DEFAULT '0.00' NOT NULL,
	bathrooms decimal(5,2) DEFAULT '0.00' NOT NULL,
	heating_type tinytext NOT NULL,
	has_air_conditioning tinyint(3) DEFAULT '0' NOT NULL,
	garage_type int(11) DEFAULT '0' NOT NULL,
	garage_rent tinytext NOT NULL,
	garage_price tinytext NOT NULL,
	pets int(11) DEFAULT '0' NOT NULL,
	construction_year int(11) DEFAULT '0' NOT NULL,
	old_or_new_building tinyint(3) DEFAULT '0' NOT NULL,
	state int(11) DEFAULT '0' NOT NULL,
	balcony tinyint(3) DEFAULT '0' NOT NULL,
	garden tinyint(3) DEFAULT '0' NOT NULL,
	elevator tinyint(3) DEFAULT '0' NOT NULL,
	barrier_free tinyint(3) DEFAULT '0' NOT NULL,
	assisted_living tinyint(3) DEFAULT '0' NOT NULL,
	fitted_kitchen tinyint(3) DEFAULT '0' NOT NULL,
	has_pool tinyint(3) DEFAULT '0' NOT NULL,
	has_community_pool tinyint(3) DEFAULT '0' NOT NULL,
	teaser text NOT NULL,
	description text NOT NULL,
	equipment text NOT NULL,
	layout tinytext NOT NULL,
	location text NOT NULL,
	misc text NOT NULL,
	details_page tinytext,
	images int(11) DEFAULT '0' NOT NULL,
	documents int(11) DEFAULT '0' NOT NULL,
	employer text NOT NULL,
	openimmo_anid text NOT NULL,
	openimmo_obid text NOT NULL,
	utilization text NOT NULL,
	contact_data_source tinyint(1) DEFAULT '0' NOT NULL,
	contact_person tinytext NOT NULL,
	contact_email tinytext NOT NULL,
	phone_switchboard tinytext NOT NULL,
	phone_direct_extension tinytext NOT NULL,
	owner int(11) unsigned DEFAULT '0' NOT NULL,
	language tinytext NOT NULL,
	currency tinytext NOT NULL,
	advertised_date int(11) DEFAULT '0' NOT NULL,
	rent_per_square_meter decimal(8,2) DEFAULT '0.00' NOT NULL,
	shop_area decimal(8,2) DEFAULT '0.00' NOT NULL,
	total_usable_area decimal(8,2) DEFAULT '0.00' NOT NULL,
	storage_area decimal(8,2) DEFAULT '0.00' NOT NULL,
	office_space decimal(8,2) DEFAULT '0.00' NOT NULL,
	site_occupancy_index decimal(5,2) DEFAULT '0.00' NOT NULL,
	floor_space_index decimal(5,2) DEFAULT '0.00' NOT NULL,
	parking_spaces int(11) unsigned DEFAULT '0' NOT NULL,
	furnishing_category tinyint(3) DEFAULT '0' NOT NULL,
	flooring tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY dummy (is_dummy_record),
	KEY owner (owner),
	KEY city (city),
	KEY district (district)
);

#
# Table structure for table 'tx_realty_apartment_types'
#
CREATE TABLE tx_realty_apartment_types (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY dummy (is_dummy_record)
);

#
# Table structure for table 'tx_realty_house_types'
#
CREATE TABLE tx_realty_house_types (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY dummy (is_dummy_record)
);

#
# Table structure for table 'tx_realty_car_places'
#
CREATE TABLE tx_realty_car_places (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY dummy (is_dummy_record)
);

#
# Table structure for table 'tx_realty_pets'
#
CREATE TABLE tx_realty_pets (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY dummy (is_dummy_record)
);

#
# Table structure for table 'tx_realty_images'
#
CREATE TABLE tx_realty_images (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	object int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	caption tinytext NOT NULL,
	image tinytext NOT NULL,
	thumbnail tinytext NOT NULL,
	position tinyint(1) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY dummy (is_dummy_record),
	KEY container (object)
);

#
# Table structure for table 'tx_realty_cities'
#
CREATE TABLE tx_realty_cities (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	save_folder int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY dummy (is_dummy_record)
);

#
# Table structure for table 'tx_realty_districts'
#
CREATE TABLE tx_realty_districts (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	city int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY dummy (is_dummy_record),
	KEY city (city)
);

#
# Table structure for table 'tx_realty_documents'
#
CREATE TABLE tx_realty_documents (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	object int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	filename tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY dummy (is_dummy_record),
	KEY container (object)
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_realty_openimmo_anid tinytext NOT NULL,
	tx_realty_maximum_objects tinyint(4) DEFAULT '0' NOT NULL,
);
