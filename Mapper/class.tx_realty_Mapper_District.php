<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Oliver Klee <typo3-coding@oliverklee.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class 'tx_realty_Mapper_District' for the 'realty' extension.
 *
 * This class represents a mapper for districts.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_District extends tx_oelib_DataMapper {
	/**
	 * @var string the name of the database table for this mapper
	 */
	protected $tableName = 'tx_realty_districts';

	/**
	 * @var string the model class name for this mapper, must not be empty
	 */
	protected $modelClassName = 'tx_realty_Model_District';

	/**
	 * @var array the (possible) relations of the created models in the format
	 *            DB column name => mapper name
	 */
	protected $relations = array(
		'city' => 'tx_realty_Mapper_City',
	);

	/**
	 * Finds all districts that belong to a certain city.
	 *
	 * If $uid is zero, this function returns all districts without a city.
	 *
	 * @param integer $uid
	 *        the UID of the city for which to find the disctricts, must be >= 0
	 *
	 * @return tx_oelib_List the districts within the given city, may be empty
	 */
	public function findAllByCityUid($uid) {
		return $this->findByWhereClause('city = ' . $uid, 'title ASC');
	}

	/**
	 * Finds all districts that belong to a certain or no city.
	 *
	 * If $uid is zero, this function returns all districts without a city.
	 *
	 * @param integer $uid
	 *        the UID of the city for which to find the disctricts, must be >= 0
	 *
	 * @return tx_oelib_List the districts within the given city or without a city,
	 *                       may be empty
	 */
	public function findAllByCityUidOrUnassigned($uid) {
		return $this->findByWhereClause('city = 0 OR city = ' . $uid, 'title ASC');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_District.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_District.php']);
}
?>