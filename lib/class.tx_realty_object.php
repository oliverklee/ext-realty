<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2008 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_object' for the 'realty' extension.
 *
 * This class represents a realty object.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
require_once(PATH_t3lib.'class.t3lib_refindex.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_templatehelper.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_configurationProxy.php');

class tx_realty_object {
	/** contains the realty object's data */
	private $realtyObjectData = array();

	/**
	 * records of images are stored here until they are inserted to
	 * 'tx_realty_images'
	 */
	private $images = array();

	/** required fields for OpenImmo records */
	private $requiredFields = array(
		'zip',
		'object_number',
		// 'object_type' refers to 'vermarktungsart' in the OpenImmo schema.
		'object_type',
		'house_type',
		'employer',
		'openimmo_anid',
		'openimmo_obid',
		'utilization',
		'contact_person',
		'contact_email'
	);

	/** associates property names and their corresponding tables */
	private $propertyTables = array(
		'tx_realty_cities' => 'city',
		'tx_realty_apartment_types' => 'apartment_type',
		'tx_realty_house_types' => 'house_type',
		'tx_realty_districts' => 'district',
		'tx_realty_pets' => 'pets',
		'tx_realty_car_places' => 'garage_type',
		'tx_realty_heating_types' => 'heating_type'
	);

	/** instance of tx_oelb_templatehelper */
	private $templateHelper;

	/** whether a newly created record is for testing purposes only */
	private $isDummyRecord = false;

	/**
	 * Constructor.
	 *
	 * @param	boolean		whether the database records to create are for
	 * 						testing purposes only
	 */
	public function __construct($createDummyRecords = false) {
		$this->isDummyRecord = $createDummyRecords;
		$this->templateHelper = t3lib_div::makeInstance(
			'tx_oelib_templatehelper'
		);
 		$this->templateHelper->init();
	}

	/**
	 * Receives the data for a new realty object, converts it to an array and
	 * writes it to $this->realtyObjectData.
	 * The received data can either be a database result row or an array which
	 * has database column names as keys (may be empty). The data can also be a
	 * UID of an existent realty object to load from the database. If the data
	 * is of an invalid type, $this->realtyObjectData stays empty.
	 *
	 * @param	mixed		data for the realty object: an array, a database
	 * 						result row, or a UID (of integer > 0) of an existing
	 * 						record, an array must not contain the key 'uid'
	 * @param	boolean		whether realty objects to be loaded by database ID
	 * 						must be non-deleted and not hidden
	 */
	public function loadRealtyObject($realtyData, $enabledObjectsOnly = false) {
		switch ($this->getDataType($realtyData)) {
			case 'array' :
				if (isset($realtyData['uid'])) {
					throw new Exception(
						'The column "uid" must not be set in $realtyData.'
					);
				}
				$convertedData = $this->isolateImageRecords($realtyData);
				break;
			case 'uid' :
				$convertedData = $this->loadDatabaseEntry(
					intval($realtyData),
					$enabledObjectsOnly
				);
				break;
			case 'dbResult' :
				$convertedData = $this->fetchDatabaseResult($realtyData);
				break;
			default :
				$convertedData = array();
				break;
 		}
		$this->realtyObjectData = $convertedData;
	}

	/**
	 * Checks the type of data input. Returns the type if it is valid, else
	 * returns an empty string.
	 *
	 * @param	mixed		data for the realty object, an array, a UID (of
	 * 						integer > 0) or a database result row
	 *
	 * @return	string		type of data: 'array', 'uid', 'dbResult' or empty in
	 * 						case of any other type
	 */
	protected function getDataType($realtyData) {
		if ($realtyData == null) {
			return '';
		}

		$result = '';

		if (is_array($realtyData)) {
			$result = 'array';
		} elseif (is_numeric($realtyData)) {
			$result = 'uid';
		} elseif (is_resource($realtyData)
			&& (get_resource_type($realtyData) == 'mysql result'))
		{
				$result = 'dbResult';
		}

		return $result;
	}

	/**
	 * Loads an existing realty object entry from the database. If
	 * $enabledObjectsOnly is set, deleted or hidden records will not be loaded.
	 *
	 * @param	integer		UID of the database entry to load, must be > 0
	 * @param	boolean		whether only non-deleted and non-hidden objects
	 * 						should be loaded
	 *
	 * @return	array		contents of the database entry, empty if database
	 * 						result could not be fetched
	 */
	protected function loadDatabaseEntry($uid, $enabledObjectsOnly) {
		$result = array();

		$additionalWhereClause = '';
		if ($enabledObjectsOnly) {
			$additionalWhereClause = 
				$this->templateHelper->enableFields('tx_realty_objects');
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_realty_objects',
			'uid='.$uid.$additionalWhereClause
		);

		if ($dbResult) {
			$result = $this->fetchDatabaseResult($dbResult);
		}

		return $result;
	}

	/**
	 * Stores the image records to $this->images and wirtes the number of images
	 * to the imported data array instead as this number is expected by the
	 * database configuration.
	 *
	 * @param	array		realty record to be loaded as a realty object, may
	 * 						be empty
	 *
	 * @return	array		realty record ready to load, image records got
	 * 						separated, empty if the given array was empty
	 */
	private function isolateImageRecords(array $realtyDataArray) {
		$result = $realtyDataArray;

		if (is_array($realtyDataArray['images'])) {
			$this->images = $realtyDataArray['images'];
			$result['images'] = count($realtyDataArray['images']);
		}

		return $result;
	}

	/**
	 * Checks '$this->realtyObjectData' for emptiness. Returns true if
	 * '$this->realtyObjectData' is empty.
	 *
	 * @return	boolean		true if '$this->realtyObjectData' is empty, false
	 * 						otherwise
	 */
	public function isRealtyObjectDataEmpty() {
		$data = $this->getAllProperties();
		return empty($data);
	}

	/**
	 * Writes a realty object to the database. Deletes keys which do not exist
	 * in the database and inserts certain values to separate tables, as
	 * associated in $this->propertyTables.
	 * A new record will only be inserted if all required fields occur as keys
	 * in the realty object data to insert.
	 *
	 * @param	integer		PID for new records (omit this parameter to use
	 * 						the PID set in the global configuration)
	 *
	 * @return 	string		locallang key of an error message if the record was
	 * 						not written to database, an empty string if it was
	 * 						written successfully
	 */
	public function writeToDatabase($overridePid = 0) {
		if ($this->isRealtyObjectDataEmpty()) {
			return 'message_object_not_loaded';
		}

		$errorMessage = '';

		if (($this->hasProperty('uid') || $this->hasProperty('object_number'))
			&& $this->recordExistsInDatabase($this->realtyObjectData, 'object_number')
		) {
			$this->prepareInsertionAndInsertRelations();
			$this->ensureUid($this->realtyObjectData, 'object_number');
			if (!$this->updateDatabaseEntry($this->realtyObjectData)) {
				$errorMessage = 'message_updating_failed';
			}
		} elseif (!$this->getProperty('deleted')) {
			$requiredFields = $this->checkForRequiredFields();
			if (empty($requiredFields)) {
				$this->prepareInsertionAndInsertRelations();
				if (!$this->createNewDatabaseEntry(
					$this->realtyObjectData, 'tx_realty_objects', $overridePid
				)) {
					$errorMessage = 'message_insertion_failed';
				}
			} else {
				$errorMessage = 'message_fields_required';
			}
		}

		if ($this->getProperty('deleted')) {
			$this->deleteRelatedImageRecords();
			$errorMessage = 'message_deleted_flag_set';
		} elseif (($errorMessage == '') && !empty($this->images)) {
			$this->insertImageEntries($this->getAllImageData());
		}

		return $errorMessage;
	}

	/**
	 * Returns a value for a given key from a loaded realty object. If the key
	 * does not exist or no object is loaded, an empty string is returned.
	 *
	 * @param	string		key of value to fetch from current realty object,
	 * 						must not be empty
	 *
	 * @return	mixed		corresponding value or an empty string if the key
	 * 						does not exist
	 */
	public function getProperty($key) {
		if ($this->isRealtyObjectDataEmpty()
			|| !$this->hasProperty($key)
		) {
			return '';
		}

		return $this->realtyObjectData[$key];
	}

	/**
	 * Returns all data from a realty object as an array.
	 *
	 * @return	array		current realty object data, may be empty
	 */
	protected function getAllProperties() {
		return $this->realtyObjectData;
	}

	/**
	 * Sets an existing key from a loaded realty object to a value. Does nothing
	 * if the key does not exist in the current realty object or no object is
	 * loaded.
	 *
	 * @param	string		key of the value to set in current realty object,
	 * 						must not be empty and must not be 'uid'
	 * @param	mixed		value to set, must be either numeric or a string
	 * 						(also empty) or of boolean, may not be null
	 */
	public function setProperty($key, $value) {
		if ($this->isRealtyObjectDataEmpty()
			|| !$this->isAllowedValue($value)
			|| !$this->hasProperty($key)
		) {
			return;
		}

		if ($key == 'uid') {
			throw new Exception('The key must not be "uid".');
		}

		$this->realtyObjectData[$key] = $value;
	}

	/**
	 * Checks whether a value is either numeric or a string or of boolean.
	 *
	 * @param	mixed		value to check
	 *
	 * @return	boolean		true if the value is either numeric or a string
	 * 						or of boolean, false otherwise
	 */
	private function isAllowedValue($value) {
		return (is_numeric($value) || is_string($value) || is_bool($value));
	}

	/**
	 * Checks whether a $key is an element of the currently loaded realty
	 * object.
 	 *
	 * @param	string		key of value to fetch from current realty object,
	 * 						must not be empty
 	 *
	 * @return	boolean		true if $key exists in the currently loaded realty
	 * 						object, false otherwise
	 */
	private function hasProperty($key) {
		return array_key_exists($key, $this->realtyObjectData);
	}

	/**
	 * Checks whether a realty object contains all possible database column
	 * names. An array with column names which could not be found in the realty
	 * object but in the database is returned. An empty array will be returned
	 * if all column names occur in the realty object.
	 *
	 * @return	array		names of columns which are not set in the realty
	 * 						object but exist in database
	 */
	protected function checkMissingColumnNames() {
		$fieldsInDb = array_keys(
			$GLOBALS['TYPO3_DB']->admin_get_fields('tx_realty_objects')
		);
		return array_diff($fieldsInDb, array_keys($this->getAllProperties()));
	}

	/**
	 * Checks wether all required fields are set in the realty object.
	 * $this->requiredFields must have already been loaded.
	 *
	 * @return	array		array of missing required fields, empty if all
	 * 						required fields are set
	 */
	public function checkForRequiredFields() {
		$allMissingFields = $this->checkMissingColumnNames();
		return array_intersect($allMissingFields, $this->requiredFields);
	}

	/**
	 * Deletes elements of the realty object which do not exist in the database.
	 * Auxiliary properties are not added to the database as this would cause an
	 * exception.
	 */
	protected function deleteSurplusFields() {
		$fieldsInDb = array_keys(
			$GLOBALS['TYPO3_DB']->admin_get_fields('tx_realty_objects')
		);
		$surplusFieldsInRealtyObjectData = array_diff(
			array_keys($this->getAllProperties()),
			$fieldsInDb
		);

		if (!empty($surplusFieldsInRealtyObjectData)) {
			foreach ($surplusFieldsInRealtyObjectData as $currentField) {
				unset($this->realtyObjectData[$currentField]);
			}
		}
	}

	/**
	 * Sets the required fields for $this->realtyObjectData.
	 *
	 * @param	array		required fields, may be empty
	 */
	public function setRequiredFields(array $fields) {
		$this->requiredFields = $fields;
	}

	/**
	 * Gets the required fields for '$this->realtyObjectData'.
	 *
	 * @return	array		required fields, may be empty
	 */
	public function getRequiredFields() {
		return $this->requiredFields;
	}

	/**
	 * Prepares the realty object for insertion and inserts records to
	 * the related tables.
	 * It writes the values of 'city', 'apartment_type', 'house_type',
	 * 'district', 'pets', 'garage_type' and 'heating_type', in case they are
	 * defined, as records to their own tables and stores the UID of the record
	 * to '$this->realtyObjectData' instead of the value.
	 */
	protected function prepareInsertionAndInsertRelations() {
		$this->deleteSurplusFields();

		$referenceIndex = t3lib_div::makeInstance('t3lib_refindex');
		foreach ($this->propertyTables as $currentTable => $currentProperty) {
			$uidOfProperty = $this->insertPropertyToOwnTable(
				$currentProperty,
				$currentTable
			);

			if ($uidOfProperty != 0) {
				$this->setProperty($currentProperty, $uidOfProperty);
				$referenceIndex->updateRefIndexTable(
					$currentTable,
					$uidOfProperty
				);
			}
		}
	}

	/**
	 * Inserts a property of a realty object into the table $table. Returns the
	 * UID of the newly created record or an empty string if the value to insert
	 * is not set.
	 *
	 * @param	string		key of property to insert from current realty
	 * 						object, must not be empty
	 * @param	string		name of a table where to insert the property, must
	 * 						not be empty
	 *
	 * @return	integer		UID of the newly created record, 0 if no record was
	 * 						created
	 */
	private function insertPropertyToOwnTable($key, $table) {
		if (!$this->hasProperty($key)) {
			return 0;
		}

		$result = 0;
		$propertyArray = array('title' => $this->getProperty($key));

		if ($this->recordExistsInDatabase($propertyArray, 'title', $table)) {
			$this->ensureUid($propertyArray, 'title', $table);
			$this->updateDatabaseEntry(
				$propertyArray,
				$table
			);
		} else {
			$this->createNewDatabaseEntry($propertyArray, $table);
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			$table,
			'title="'.$propertyArray['title'].'"'
		);
		if ($dbResult &&
			($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
			if (array_key_exists('uid', $row)) {
				$result = $row['uid'];
			}
		}
		return $result;
	}

	/**
	 * Inserts entries for images of the current realty object to the database
	 * table 'tx_realty_images'. Does nothing if no image records are given.
	 * Images can only be linked with the current realty object if it has at
	 * least an object number or a UID.
	 *
	 * @param	array		array with data for each image to insert, may be
	 * 						empty
	 */
	protected function insertImageEntries(array $imagesArray) {
		if (!$this->hasProperty('uid') && !$this->hasProperty('object_number')) {
			return;
		}

		$this->ensureUid($this->realtyObjectData, 'object_number');
		$objectUid = $this->getProperty('uid');
		$counter = 1;

		foreach ($imagesArray as $imageData) {
			if ($this->recordExistsInDatabase(
				$imageData,
				'image',
				'tx_realty_images'
			)) {
				$this->ensureUid($imageData, 'image', 'tx_realty_images');
				$this->updateDatabaseEntry(
					$imageData,
					'tx_realty_images'
				);
			} else {
				$this->createNewDatabaseEntry($imageData, 'tx_realty_images');
			}

			$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'tx_realty_images',
				'image='.$GLOBALS['TYPO3_DB']->fullQuoteStr(
					$imageData['image'],
					'tx_realty_images'
				)
			);
			if ($dbResult
				&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
			) {
				if (array_key_exists('uid', $row)) {
					$this->linkImageWithObject(
						$objectUid,
						$row['uid'],
						$counter
					);
					$counter++;
				}
			}
		}
	}

	/**
	 * Sets the deleted flag for all image records related to the current realty
	 * object to delete.
	 */
	private function deleteRelatedImageRecords() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_foreign',
			'tx_realty_objects_images_mm',
			'uid_local='.intval($this->getProperty('uid'))
		);

		if ($dbResult) {
			$imagesToDelete = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))	{
				$imagesToDelete[] = $row['uid_foreign'];
			}

			if(!empty($imagesToDelete)) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tx_realty_images',
					'uid IN('.implode(',', $imagesToDelete).')',
					array('deleted' => 1)
				);
			}
		}
	}

	/**
	 * Creates a relation between an image and a realty record in the table
	 * 'tx_realty_objects_images_mm' if the relation does not exist yet.
	 *
	 * @param	integer		UID of the current realty object, must be > 0
	 * @param	integer		UID of the image to link, must be > 0
	 * @param	integer		number of images (including the current) which are
	 * 						momentary related to the current realty record
	 */
	private function linkImageWithObject($objectUid, $imageUid, $counter = 0) {
		$resultRow = false;

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_local, uid_foreign',
			'tx_realty_objects_images_mm',
			'uid_local='.intval($objectUid).' AND uid_foreign='.intval($imageUid)
		);
		if ($dbResult) {
			$resultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		}

		if (!$resultRow) {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tx_realty_objects_images_mm',
				array(
					'uid_local' => intval($objectUid),
					'uid_foreign' => intval($imageUid),
					'sorting' => intval($counter),
					// allows an easy removal of records created during the unit tests
					'is_dummy_record' => $this->isDummyRecord
				)
			);
		}
	}

	/**
	 * Returns an array of data for each image.
	 *
	 * @return	array		images data, may be empty
	 */
	protected function getAllImageData() {
		return $this->images;
	}

	/**
	 * Creates a new record with the contents of the array $realtyData, unless
	 * it is empty, in the database. All fields to insert must already exist in
	 * the database.
	 * The values for PID, 'tstamp' and 'crdate' are provided by this function.
	 *
	 * @param	array		database column names as keys, must not be empty and
	 * 						must not contain the key 'uid'
	 * @param	string		name of the database table, must not be empty
	 * @param	integer		PID for new realty and image records (omit this
	 * 						parameter to use the PID set in the global
	 * 						configuration)
	 *
	 * @return 	boolean		true if the insert query was successful, false
	 * 						otherwise
	 */
	protected function createNewDatabaseEntry(
		array $realtyData, $table = 'tx_realty_objects', $overridePid = 0
	) {
		if (empty($realtyData)) {
			return false;
		}

		if (isset($realtyData['uid'])) {
			throw new Exception(
				'The column "uid" must not be set in $realtyData.'
			);
		}

		$dataToInsert = $realtyData;
		$pid = tx_oelib_configurationProxy::getInstance('realty')->
			getConfigurationValueInteger('pidForAuxiliaryRecords');
		if (($pid == 0)
			|| ($table == 'tx_realty_images')
			|| ($table == 'tx_realty_objects')
		) {
			if ($overridePid > 0) {
				$pid = $overridePid;
			} else {
				$pid = tx_oelib_configurationProxy::getInstance('realty')->
					getConfigurationValueInteger('pidForRealtyObjectsAndImages');
			}
		}

		$dataToInsert['pid'] = $pid;
		$dataToInsert['tstamp'] = mktime();
		$dataToInsert['crdate'] = mktime();
		// allows an easy removal of records created during the unit tests
		$dataToInsert['is_dummy_record'] = $this->isDummyRecord;

		return (boolean) $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$table,
			$dataToInsert
		);
	}

	/**
	 * Updates an existing database entry. The provided data must contain the
	 * element 'uid'.
	 * The value for 'tstamp' is set automatically
	 *
	 * @param	array		database column names as keys to update an already
	 * 						existing entry, must at least contain an element
	 * 						with the key 'uid'
	 * @param	string		name of the database table, must not be empty
	 *
	 * @return	boolean		true if the update query was succesful, false
	 * 						otherwise
	 */
	protected function updateDatabaseEntry(
		array $realtyData,
		$table = 'tx_realty_objects'
	) {
		if (!$realtyData['uid']) {
			return false;
		}

		$dataForUpdate = $realtyData;
		$dataForUpdate['tstamp'] = mktime();

		return (boolean) $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$table,
			'uid='.intval($dataForUpdate['uid']),
			$dataForUpdate
		);
	}

	/**
	 * Fetches one database record and fits it to an array which is returned.
	 *
	 * @param	resource	database result of one record
	 *
	 * @return	array		contains the fetched data, may be empty
	 */
	protected function fetchDatabaseResult($dbResult) {
		$result = array();

		if ($dbResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
        	$result = $row;
		}

		return $result;
	}

	/**
	 * Checks whether there is a database entry with the given UID or if there
	 * is no element UID in $dataArray, a key named $alternativeKey already
	 * exists in the database.
	 * The result will be false if neither 'uid' nor $alternativeKey are
	 * elements of $dataArray.
	 *
	 * @param	array		array of realty data, must not be empty
	 * @param	string		Database column name which also occurs in the data
	 * 						array as a key. This key's value is searched in the
	 * 						database column in case there is no array key 'uid'.
	 * @param	string		Name of table where to find out whether an entry yet
	 * 						exists. Must not be empty.
	 *
	 * @return	boolean		True if the UID in the data array equals an existing
	 * 						entry or if the value of the alternative key was found
	 * 						in the database. False in any other case, also if
	 * 						the database result could not be fetched or if
	 * 						neither 'uid' nor $alternativeKey were elements of
	 * 						$dataArray.
	 */
	protected function recordExistsInDatabase(
		array $dataArray,
		$alternativeKey,
		$table = 'tx_realty_objects'
	) {
		$recordExists = false;
		$keyToSearch = '';

		if (array_key_exists('uid', $dataArray) && ($dataArray['uid'] != 0)) {
			$keyToSearch = 'uid';
		} elseif (array_key_exists($alternativeKey, $dataArray)) {
			$keyToSearch = $alternativeKey;
		}

		if ($keyToSearch != '') {
			$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'COUNT(*) AS number',
				$table,
				$keyToSearch.'="'.$dataArray[$keyToSearch].'"'
					.$this->templateHelper->enableFields($table)
			);

			if ($dbResult
				&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
			) {
				$recordExists = ($row['number'] >= 1);
			}
		}

		return $recordExists;
	}

	/**
	 * Checks whether a record with a certain object number exists in the
	 * database table 'tx_realty_objects'.
	 *
	 * @param	string		object_number to find in database
	 *
	 * @return	boolean		true if the object number could be found in the
	 * 						database, false otherwise
	 */
	private function objectNumberExistsInDatabase($objectNumber) {
		return $this->recordExistsInDatabase(
			array('object_number' => $objectNumber),
			'object_number'
		);
	}

	/**
	 * Adds the UID from database to $dataArray if an entry, specified by $key,
	 * already exists in database. $key also needs to be a key of $dataArray,
	 * otherwise the UID cannot be added.
	 *
	 * @param	array		data of an entry which already exists in database,
	 * 						must not be empty
	 * @param	string		key by which the existance of a database entry will
	 * 						be proven, must not be empty and must also be a key
	 * 						of $dataArray
	 * @param	string		name of the table where to find out whether an entry
	 * 						yet exists
	 */
	private function ensureUid(
		array &$dataArray,
		$key,
		$table = 'tx_realty_objects'
	) {
		if (!array_key_exists($key, $dataArray)
			|| array_key_exists('uid', $dataArray)
		) {
			return;
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			$table,
			$key.'="'.$dataArray[$key].'"'
				.$this->templateHelper->enableFields($table)
		);
		if ($dbResult
			&& ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))
		) {
			$dataArray = array_merge($dataArray, array('uid' => $row['uid']));
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_object.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_object.php']);
}

?>
