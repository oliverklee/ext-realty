<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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