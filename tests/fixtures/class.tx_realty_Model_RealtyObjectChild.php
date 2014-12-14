<?php
/*
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
 * This is merely a class used for unit tests. Don't use it for any other purpose.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
final class tx_realty_Model_RealtyObjectChild extends tx_realty_Model_RealtyObject {
	/**
	 * Checks whether a record exists in the database.
	 * If $dataArray has got an element named 'uid', the database match is
	 * searched by this UID. Otherwise, the database match is searched by the
	 * list of alternative keys.
	 * The result will be TRUE if either the UIDs matched or if all the elements
	 * of $dataArray which correspond to the list of alternative keys match the
	 * a database record.
	 *
	 * @param array $dataArray
	 *        Data array with database column names and the corresponding values.
	 *        The database match is searched by all these keys' values in case there is no UID within the array.
	 * @param string $table
	 *        name of table where to find out whether an entry yet exists, must not be empty
	 *
	 * @return bool True if the UID in the data array equals an existing
	 *                 entry or if the value of the alternative key was found in
	 *                 the database. False in any other case, also if the
	 *                 database result could not be fetched or if neither 'uid'
	 *                 nor $alternativeKey were elements of $dataArray.
	 */
	public function recordExistsInDatabase(
		$dataArray, $table = 'tx_realty_objects'
	) {
		return parent::recordExistsInDatabase($dataArray, $table);
	}

	/**
	 * Creates a new record with the contents of the array $realtyData, unless
	 * it is empty, in the database. All fields to insert must already exist in
	 * the database.
	 * The values for PID, 'tstamp' and 'crdate' are provided by this function.
	 *
	 * @param array $realtyData
	 *        database column names as keys, must not be empty and must not contain the key 'uid'
	 * @param string $table
	 *        name of the database table, must not be empty
	 * @param int $overridePid PID
	 *        for new realty and image records (omit this parameter to use the PID set in the global configuration)
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return int UID of the new database entry, will be zero if no new
	 *                 record could be created, will be -1 if the deleted flag
	 *                 was set
	 */
	public function createNewDatabaseEntry(
		array $realtyData, $table = 'tx_realty_objects', $overridePid = 0
	) {
		return parent::createNewDatabaseEntry($realtyData, $table, $overridePid);
	}

	/**
	 * Checks the type of data input. Returns the type if it is valid, else
	 * returns an empty string.
	 *
	 * @param mixed $realtyData
	 *        data for the realty object: an array or a UID (of integer > 0)
	 *
	 * @return string
	 *         type of data: 'array', 'uid' or empty in case case of any other
	 *         type
	 */
	public function getDataType($realtyData) {
		return parent::getDataType($realtyData);
	}

	/**
	 * Loads an existing realty object entry from the database. If
	 * $enabledObjectsOnly is set, deleted or hidden records will not be loaded.
	 *
	 * @param int $uid UID of the database entry to load, must be > 0
	 *
	 * @return array contents of the database entry, empty if database
	 *               result could not be fetched
	 */
	public function loadDatabaseEntry($uid) {
		return parent::loadDatabaseEntry($uid);
	}

	/**
	 * Checks whether all required fields are set in the realty object.
	 * $this->requiredFields must have already been loaded.
	 *
	 * @return array array of missing required fields, empty if all
	 *               required fields are set
	 */
	public function checkForRequiredFields() {
		return parent::checkForRequiredFields();
	}

	/**
	 * Prepares the realty object for insertion and inserts records to
	 * the related tables.
	 * It writes the values of 'city', 'apartment_type', 'house_type',
	 * 'district', 'pets' and 'garage_type', in case they are defined, as
	 * records to their own tables and stores the UID of the record instead of
	 * the value.
	 *
	 * @return void
	 */
	public function prepareInsertionAndInsertRelations() {
		parent::prepareInsertionAndInsertRelations();
	}

	/**
	 * Returns all data from a realty object as an array.
	 *
	 * @return array current realty object data, may be empty
	 */
	public function getAllProperties() {
		return parent::getAllProperties();
	}
}