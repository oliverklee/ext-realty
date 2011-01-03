<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Class 'tx_realty_Mapper_City' for the 'realty' extension.
 *
 * This class represents a mapper for cities.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_City extends tx_oelib_DataMapper {
	/**
	 * @var string the name of the database table for this mapper
	 */
	protected $tableName = 'tx_realty_cities';

	/**
	 * @var string the model class name for this mapper, must not be empty
	 */
	protected $modelClassName = 'tx_realty_Model_City';

	/**
	 * the column names of additional string keys
	 *
	 * @var array<string>
	 */
	protected $additionalKeys = array('title');

	/**
	 * Finds a city by its name.
	 *
	 * @throws tx_oelib_Exception_NotFound if there is no city with the
	 *                                     given name
	 *
	 * @param string $name the name of the city to find, must not be empty
	 *
	 * @return tx_oelib_Model_City the city with the given name
	 */
	public function findByName($name) {
		return $this->findOneByKey('title', $name);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_City.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_City.php']);
}
?>