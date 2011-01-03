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
	 * the column names of additional string keys
	 *
	 * @var array<string>
	 */
	protected $additionalKeys = array('title');

	/**
	 * cache by district name and city UID, using values from
	 * createCacheKeyFromNameAndCityUid as keys
	 *
	 * @var array<tx_realty_Model_District>
	 */
	private $cacheByNameAndCityUid = array();

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		$this->cacheByNameAndCityUid = array();

		parent::__destruct();
	}

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

	/**
	 * Finds a district by its name.
	 *
	 * @throws tx_oelib_Exception_NotFound if there is no district with the
	 *                                     given name
	 *
	 * @param string $name the name of the district to find, must not be empty
	 *
	 * @return tx_oelib_Model_District the district with the given name
	 */
	public function findByName($name) {
		return $this->findOneByKey('title', $name);
	}

	/**
	 * Finds a district by its name and its associated city.
	 *
	 * @throws tx_oelib_Exception_NotFound if there is no district with the
	 *                                     given name and city
	 *
	 * @param string $districtName
	 *        the name of the district to find, must not be empty
	 * @param integer $cityUid
	 *        the UID of the city of the district to find, must be >= 0
	 *
	 * @return tx_oelib_Model_District the district with the given name and city
	 */
	public function findByNameAndCityUid($districtName, $cityUid) {
		if ($districtName == '') {
			throw new Exception('$districtName must not be empty.');
		}
		if ($cityUid < 0) {
			throw new Exception('$cityUid must be >= 0.');
		}

		try {
			$model = $this->findByNameAndCityUidFromCache(
				$districtName, $cityUid
			);
		} catch (tx_oelib_Exception_NotFound $exception) {
			$model = $this->findByNameAndCityUidFromDatabase(
				$districtName, $cityUid
			);
		}

		return $model;
	}

	/**
	 * Finds a district by its name and its associated city from the cache.
	 *
	 * @throws tx_oelib_Exception_NotFound if there is no district with the
	 *                                     given name and city in the cache
	 *
	 * @param string $districtName
	 *        the name of the district to find, must not be empty
	 * @param integer $cityUid
	 *        the UID of the city of the district to find, must be >= 0
	 *
	 * @return tx_oelib_Model_District the district with the given name and city
	 */
	private function findByNameAndCityUidFromCache($districtName, $cityUid) {
		$cacheKey = $this->createCacheKeyFromNameAndCityUid(
			$districtName, $cityUid
		);
		if (!isset($this->cacheByNameAndCityUid[$cacheKey])) {
			throw new tx_oelib_Exception_NotFound();
		}

		return $this->cacheByNameAndCityUid[$cacheKey];
	}

	/**
	 * Caches a model by additional combined keys.
	 *
	 * @param tx_oelib_Model $model the model to cache
	 * @param array $data the data of the model as it is in the DB, must not be empty
	 *
	 * @return void
	 */
	protected function cacheModelByCombinedKeys(
		tx_oelib_Model $model, array $data
	) {
		$districtName = isset($data['title']) ? $data['title'] : '';
		if ($districtName == '') {
			return;
		}

		$cityUid = isset($data['city']) ? $data['city'] : 0;

		$cacheKey = $this->createCacheKeyFromNameAndCityUid(
			$districtName, $cityUid
		);
		$this->cacheByNameAndCityUid[$cacheKey] = $model;
	}

	/**
	 * Creates a unique cache key for a district name and a city UID.
	 *
	 * @param string $districtName
	 *        the name of a district, must not be empty
	 * @param integer $cityUid the UID of a city of a district, must be >= 0
	 *
	 * @return string a cache key, will be unique for that name/city pair,
	 *                will not be empty
	 */
	private function createCacheKeyFromNameAndCityUid($districtName, $cityUid) {
		return $cityUid . ':' . $districtName;
	}

	/**
	 * Finds a district by its name and its associated city from the database.
	 *
	 * @throws tx_oelib_Exception_NotFound if there is no district with the
	 *                                     given name and city in the database
	 *
	 * @param string $districtName
	 *        the name of the district to find, must not be empty
	 * @param integer $cityUid
	 *        the UID of the city of the district to find, must be >= 0
	 *
	 * @return tx_oelib_Model_District the district with the given name and city
	 */
	private function findByNameAndCityUidFromDatabase($districtName, $cityUid) {
		return $this->findSingleByWhereClause(array(
			'title' => $districtName,
			'city' => $cityUid,
		));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_District.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_District.php']);
}
?>