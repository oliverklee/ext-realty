<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2011 Saskia Metzler (saskia@merlin.owl.de)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software); you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation); either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY); without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This file defines extension-wide used constants (like table names) and
 * includes the oelib's definitions of common constans.
 */

// table names
define('REALTY_TABLE_OBJECTS', 'tx_realty_objects');
define('REALTY_TABLE_CITIES', 'tx_realty_cities');
define('REALTY_TABLE_DISTRICTS', 'tx_realty_districts');
define('STATIC_COUNTRIES', 'static_countries');
define('REALTY_TABLE_APARTMENT_TYPES', 'tx_realty_apartment_types');
define('REALTY_TABLE_HOUSE_TYPES', 'tx_realty_house_types');
define('REALTY_TABLE_CAR_PLACES', 'tx_realty_car_places');
define('REALTY_TABLE_PETS', 'tx_realty_pets');
define('REALTY_TABLE_IMAGES', 'tx_realty_images');

// sources of contact data
define('REALTY_CONTACT_FROM_REALTY_OBJECT', 0);
define('REALTY_CONTACT_FROM_OWNER_ACCOUNT', 1);

// object types
define('REALTY_FOR_RENTING', 0);
define('REALTY_FOR_SALE', 1);
?>