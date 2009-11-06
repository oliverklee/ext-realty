<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class tx_realty_Mapper_RealtyObject for the "realty" extension.
 *
 * This class represents a mapper for realty objects.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_RealtyObject extends tx_oelib_DataMapper {
	/**
	 * @var string the name of the database table for this mapper
	 */
	protected $tableName = 'tx_realty_objects';

	/**
	 * @var string the model class name for this mapper, must not be empty
	 */
	protected $modelClassName = 'tx_realty_Model_RealtyObject';

	/**
	 * Returns the number of realty objects in the city $city.
	 *
	 * @param tx_realty_Model_City $city the city for which to count the objects
	 *
	 * @return integer the number of objects in the given city, will be >= 0
	 */
	public function countByCity(tx_realty_Model_City $city) {
		return tx_oelib_db::count(
			$this->tableName,
			'(city = ' . $city->getUid() . ') AND ' .
				$this->getUniversalWhereClause()
		);
	}

	/**
	 * Returns the number of realty objects in the district $district.
	 *
	 * @param tx_realty_Model_District $district
	 *        the district for which to count the objects
	 *
	 * @return integer the number of objects in the given district, will be >= 0
	 */
	public function countByDistrict(tx_realty_Model_District $district) {
		return tx_oelib_db::count(
			$this->tableName,
			'(district = ' . $district->getUid() . ') AND ' .
				$this->getUniversalWhereClause()
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_RealtyObject.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_RealtyObject.php']);
}
?>