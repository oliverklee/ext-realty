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


require_once(PATH_t3lib . 'class.t3lib_refindex.php');
require_once(PATH_t3lib . 'class.t3lib_befunc.php');

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_templatehelper.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_configurationProxy.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_googleMapsLookup.php');

/**
 * Class 'tx_realty_object' for the 'realty' extension.
 *
 * This class represents a realty object.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 * @author		Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_object {
	/** @var	integer		the length of cropped titles */
	const CROP_SIZE = 32;

	/** contains the realty object's data */
	private $realtyObjectData = array();

	/**
	 * records of images are stored here until they are inserted to
	 * 'tx_realty_images'
	 */
	private $images = array();

	/** the owner record is cached in order to improve performance */
	private $ownerData = array();

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

	/** allowed field names in the table for realty objects */
	private $allowedFieldNames = array();

	/** associates property names and their corresponding tables */
	private $propertyTables = array(
		REALTY_TABLE_CITIES => 'city',
		REALTY_TABLE_APARTMENT_TYPES => 'apartment_type',
		REALTY_TABLE_HOUSE_TYPES => 'house_type',
		REALTY_TABLE_DISTRICTS => 'district',
		REALTY_TABLE_PETS => 'pets',
		REALTY_TABLE_CAR_PLACES => 'garage_type',
	);

	/** @var	tx_oelib_templatehelper */
	private $templateHelper;

	/** whether hidden objects are loadable */
	private $canLoadHiddenObjects = false;

	/** whether a newly created record is for testing purposes only */
	private $isDummyRecord = false;

	/** @var	tx_realty_googleMapsLookup		a geo coordinate finder */
	private static $geoFinder;

	/**
	 * @var	array		cached city names using the UID as numeric key and the
	 * 					title as value
	 */
	private static $cityCache = array();

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
	 * @param	boolean		whether hidden objects are loadable
	 */
	public function loadRealtyObject(
		$realtyData, $canLoadHiddenObjects = false
	) {
		$this->canLoadHiddenObjects = $canLoadHiddenObjects;
		switch ($this->getDataType($realtyData)) {
			case 'array' :
				$this->realtyObjectData
					= $this->isolateImageRecords($realtyData);
				break;
			case 'uid' :
				$this->realtyObjectData = $this->loadDatabaseEntry(
					intval($realtyData)
				);
				$this->loadImages();
				break;
			case 'dbResult' :
				$this->realtyObjectData = $this->fetchDatabaseResult($realtyData);
				$this->loadImages();
				break;
			default :
				$this->realtyObjectData = array();
				break;
 		}

		$this->loadOwnerRecord();
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
	 *
	 * @return	array		contents of the database entry, empty if database
	 * 						result could not be fetched
	 */
	protected function loadDatabaseEntry($uid) {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			REALTY_TABLE_OBJECTS,
			'uid='.$uid.$this->templateHelper->enableFields(
				REALTY_TABLE_OBJECTS, $this->canLoadHiddenObjects ? 1 : -1
			)
		);

		return $this->fetchDatabaseResult($dbResult);;
	}

	/**
	 * Stores the image records to $this->images and writes the number of images
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
	 * @param	boolean		true if the owner may be set, false otherwise
	 *
	 * @return 	string		locallang key of an error message if the record was
	 * 						not written to database, an empty string if it was
	 * 						written successfully
	 */
	public function writeToDatabase($overridePid = 0, $setOwner = false) {
		if ($this->isRealtyObjectDataEmpty()) {
			return 'message_object_not_loaded';
		}
		if (count($this->checkForRequiredFields()) > 0) {
			return 'message_fields_required';
		}

		$errorMessage = '';
		$this->prepareInsertionAndInsertRelations();
		if ($setOwner) {
			$this->processOwnerData();
		}

		if ($this->recordExistsInDatabase(
			$this->realtyObjectData, 'object_number, language, openimmo_obid'
			)
		) {
			$this->ensureUid(
				$this->realtyObjectData, 'object_number, language, openimmo_obid'
			);
			if (!$this->updateDatabaseEntry($this->realtyObjectData)) {
				$errorMessage = 'message_updating_failed';
			}
		} else {
			$newUid = $this->createNewDatabaseEntry(
				$this->realtyObjectData, REALTY_TABLE_OBJECTS, $overridePid
			);
			if ($newUid != 0) {
				$this->realtyObjectData['uid'] = $newUid;
			} else {
				$errorMessage = 'message_insertion_failed';
			}
		}

		if ($this->getProperty('deleted')) {
			$this->deleteRelatedImageRecords();
			$errorMessage = 'message_deleted_flag_set';
		}

		if (!empty($this->images)
			&& (($errorMessage == '') || ($errorMessage == 'message_deleted_flag_set'))
		) {
			$this->insertImageEntries($overridePid);
		}

		return $errorMessage;
	}

	/**
	 * Loads the owner's database record into $this->ownerData.
	 */
	private function loadOwnerRecord() {
		if ((intval($this->getProperty('owner')) == 0)
			&& ($this->getProperty('openimmo_anid') == '')
		) {
			return;
		}

		if (intval($this->getProperty('owner')) != 0) {
			$whereClause = 'uid=' . $this->getProperty('owner');
		} else {
			$whereClause = 'tx_realty_openimmo_anid="' .
				$GLOBALS['TYPO3_DB']->quoteStr(
					$this->getProperty('openimmo_anid'), REALTY_TABLE_OBJECTS
				) . '" ';
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'fe_users',
			$whereClause . $this->templateHelper->enableFields('fe_users')
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);

		if (is_array($row)) {
			$this->ownerData = $row;
		}
	}

	/**
	 * Links the current realty object to the owner's FE user record if there is
	 * one. The owner is identified by their OpenImmo ANID.
	 * Sets whether to use the FE user's contact data or the data provided
	 * within the realty record, according to the configuration.
	 */
	private function processOwnerData() {
		$this->addRealtyRecordsOwner();
		$this->addIsOwnerDataUsable();
	}

	/**
	 * Adds the current realty record's owner. The owner is the FE user who has
	 * the same OpenImmo ANID as provided in the current realty record.
	 *
	 * If the current record already has an owner or if no matching ANID was
	 * found, no owner will be set.
	 */
	private function addRealtyRecordsOwner() {
		// Saves an existing owner from being overwritten.
		if (intval($this->getProperty('owner')) != 0) {
			return;
		}

		$this->setProperty('owner', intval($this->getOwnerProperty('uid')));
	}

	/**
	 * Adds information concerning whether the owner's data may be used in the
	 * FE according to the current configuration.
	 */
	private function addIsOwnerDataUsable() {
		if ((intval($this->getProperty('owner')) != 0)
			&& tx_oelib_configurationProxy::getInstance('realty')->
				getConfigurationValueBoolean(
					'useFrontEndUserDataAsContactDataForImportedRecords'
				)
		) {
			$this->setProperty('contact_data_source', 1);
		}
	}

	/**
	 * Returns a value for a given key from an owner of a loaded realty object.
	 * If the key does not exist no owner is loaded, an empty string is returned.
	 *
	 * @param	string		key of value to fetch from current realty object's
	 * 						owner, must not be empty
	 *
	 * @return	mixed		corresponding value or an empty string if the key
	 * 						does not exist
	 */
	public function getOwnerProperty($key) {
		if (empty($this->ownerData) || !isset($this->ownerData[$key])) {
			return '';
		}

		return $this->ownerData[$key];
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
		if ($this->isRealtyObjectDataEmpty() || !$this->hasProperty($key)) {
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
	 * Reloads the owner's data.
	 *
	 * @param	string		key of the value to set in current realty object,
	 * 						must not be empty and must not be 'uid'
	 * @param	mixed		value to set, must be either numeric or a string
	 * 						(also empty) or of boolean, may not be null
	 */
	public function setProperty($key, $value) {
		if ($this->isRealtyObjectDataEmpty()
			|| !$this->isAllowedValue($value)
			|| !in_array($key, $this->getAllowedFieldNames())
		) {
			return;
		}

		if ($key == 'uid') {
			throw new Exception('The key must not be "uid".');
		}

		$this->realtyObjectData[$key] = $value;

		// Ensures the owner's data becomes loaded if one was added.
		if (($key == 'owner') || ($key == 'openimmo_anid')) {
			$this->loadOwnerRecord();
		}
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
	 * Checks whether $key is an element of the currently loaded realty object.
 	 *
	 * @param	string		key of value to fetch from current realty object,
	 * 						must not be empty
 	 *
	 * @return	boolean		true if $key exists in the currently loaded realty
	 * 						object, false otherwise
	 */
	private function hasProperty($key) {
		return isset($this->realtyObjectData[$key]);
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
		return array_diff(
			$this->getAllowedFieldNames(), array_keys($this->getAllProperties())
		);
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
		$surplusFieldsInRealtyObjectData = array_diff(
			array_keys($this->getAllProperties()),
			$this->getAllowedFieldNames()
		);

		if (!empty($surplusFieldsInRealtyObjectData)) {
			foreach ($surplusFieldsInRealtyObjectData as $currentField) {
				unset($this->realtyObjectData[$currentField]);
			}
		}
	}

	/**
	 * Returns all allowed field names for the realty objects table in an array.
	 *
	 * @return	array		column name from the realty objects table
	 */
	private function getAllowedFieldNames() {
		// In order to improve performance, the result of admin_get_fields()
		// is cached.
		if (empty($this->allowedFieldNames)) {
			$this->allowedFieldNames = array_keys(
				$GLOBALS['TYPO3_DB']->admin_get_fields(REALTY_TABLE_OBJECTS)
			);
		}

		return $this->allowedFieldNames;
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
	 * 'district', 'pets' and 'garage_type', in case they are defined, as
	 * records to their own tables and stores the UID of the record
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
		// If the property is not defined or the value is an empty string or
		// zero, no record will be created.
		if (!$this->hasProperty($key) || ($this->getProperty($key) == '')
			|| ($this->getProperty($key) == '0')
		) {
			return 0;
		}
		// If the value is a non-zero integer, the relation has already been
		// inserted.
		if (intval($this->getProperty($key)) != 0) {
			return $this->getProperty($key);
		}

		$propertyArray = array('title' => $this->getProperty($key));
		$uidOfProperty = 0;

		if ($this->recordExistsInDatabase($propertyArray, 'title', $table)) {
			$this->ensureUid($propertyArray, 'title', $table);
			$uidOfProperty = $propertyArray['uid'];
		} else {
			$uidOfProperty = $this->createNewDatabaseEntry($propertyArray, $table);
		}

		return $uidOfProperty;
	}

	/**
	 * Inserts entries for images of the current realty object to the database
	 * table 'tx_realty_images'. Does nothing if no image records are given.
	 * Images can only be linked with the current realty object if it has at
	 * least an object number or a UID.
	 *
	 * @param	integer		PID for new object and image records (omit this
	 * 						parameter to use the PID set in the global
	 * 						configuration)
	 */
	private function insertImageEntries($overridePid = 0) {
		if (!$this->hasProperty('uid') && !$this->hasProperty('object_number')) {
			return;
		}

		$this->ensureUid(
			$this->realtyObjectData, 'object_number, language, openimmo_obid'
		);

		foreach ($this->getAllImageData() as $imageData) {
			// Creates a relation to the parent realty object for each image.
			$imageData['realty_object_uid'] = $this->getUid();

			if ($this->recordExistsInDatabase(
				$imageData, 'image, realty_object_uid', REALTY_TABLE_IMAGES
			)) {
				// For image records, only titles can be updated. Titles should
				// not get emptied.
				if ($imageData['caption'] != '') {
					$this->ensureUid($imageData, 'image', REALTY_TABLE_IMAGES);
					// Updating will delete the image if the deleted flag is set.
					$this->updateDatabaseEntry($imageData, REALTY_TABLE_IMAGES);
				}
			} else {
				// If the title is empty, the file name also becomes the title
				// to ensure the title is non-empty.
				if ($imageData['caption'] == '') {
					$imageData['caption'] = $imageData['image'];
				}
				$this->createNewDatabaseEntry(
					$imageData, REALTY_TABLE_IMAGES, $overridePid
				);
			}
		}
	}

	/**
	 * Sets the deleted flag for all image records related to the current realty
	 * object to delete.
	 */
	private function deleteRelatedImageRecords() {
		foreach ($this->getAllImageData() as $imageKey => $imageData) {
			$this->markImageRecordAsDeleted($imageKey);
		}
	}

	/**
	 * Returns an array of data for each image.
	 *
	 * @return	array		images data, may be empty
	 */
	public function getAllImageData() {
		return $this->images;
	}

	/**
	 * Loads the images of the current realty object into the local images array.
	 */
	private function loadImages() {
		if (!$this->hasProperty('uid') && !$this->hasProperty('object_number')) {
			return;
		}

		$this->ensureUid(
			$this->realtyObjectData, 'object_number, language, openimmo_obid'
		);
		$this->images =array();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'caption, image',
			REALTY_TABLE_IMAGES,
			'realty_object_uid=' . $this->getProperty('uid') .
				$this->templateHelper->enableFields(REALTY_TABLE_IMAGES),
			'',
			'uid'
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))	{
			$this->images[] = $row;
		}
	}

	/**
	 * Adds a new image record to the currently loaded object.
	 *
	 * Note: This function does not check whether $fileName points to a file.
	 *
	 * @param	string		caption for the new image record, must not be empty
	 * @param	string		name of the image in the upload directory, must not
	 * 						be empty
	 *
	 * @return	integer		key of the newly created record, will be >= 0
	 */
	public function addImageRecord($caption, $fileName) {
		if ($this->isRealtyObjectDataEmpty()) {
			throw new Exception(
				'A realty record must be loaded before images can be appended.'
			);
		}

		$this->images[] = array('caption' => $caption, 'image' => $fileName);

		return count($this->images) - 1;
	}

	/**
	 * Marks an image record of the currently loaded object as deleted. This
	 * record will be marked as deleted in the database when the object is
	 * written to the database.
	 *
	 * @param	integer		key of the image record to mark as deleted, must be
	 * 						a key of the image data array and must be >= 0
	 */
	public function markImageRecordAsDeleted($imageKey) {
		if ($this->isRealtyObjectDataEmpty()) {
			throw new Exception(
				'A realty record must be loaded before images can be marked '
					.'as deleted.'
			);
		}
		if (!isset($this->images[$imageKey])) {
			throw new Exception('The image record does not exist.');
		}

		$this->images[$imageKey]['deleted'] = 1;
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
	 * @return 	integer		UID of the new database entry, will be zero if no
	 * 						new record was created, e.g. if the deleted flag
	 * 						was set
	 */
	protected function createNewDatabaseEntry(
		array $realtyData, $table = REALTY_TABLE_OBJECTS, $overridePid = 0
	) {
		if (empty($realtyData) || $realtyData['deleted']) {
			return 0;
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
			|| ($table == REALTY_TABLE_IMAGES)
			|| ($table == REALTY_TABLE_OBJECTS)
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

		$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $dataToInsert);

		return $GLOBALS['TYPO3_DB']->sql_insert_id();
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
		$table = REALTY_TABLE_OBJECTS
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
	 * Fetches one database result row.
	 *
	 * @param	resource	database result of one record
	 *
	 * @return	array		database result row, will be empty if $dbResult
	 * 						was false or empty
	 */
	protected function fetchDatabaseResult($dbResult) {
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);

		return is_array($result) ? $result : array();
	}

	/**
	 * Checks whether a record exists in the database.
	 * If $dataArray has got an element named 'uid', the database match is
	 * searched by this UID. Otherwise, the database match is searched by the
	 * list of alternative keys.
	 * The result will be true if either the UIDs matched or if all the elements
	 * of $dataArray which correspond to the list of alternative keys match the
	 * a database record.
	 *
	 * @param	array		array of realty data, must not be empty
	 * @param	string		Comma-separated list of database column names which
	 * 						also occur in the data array as keys. The database
	 * 						match is searched by all these keys' values in case
	 * 						there is no key 'uid' in $dataArray. The list may
	 * 						contain spaces.
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
		$alternativeKeys,
		$table = REALTY_TABLE_OBJECTS
	) {
		if (isset($dataArray['uid']) && ($dataArray['uid'] != 0)) {
			$keys = 'uid';
		} else {
			$keys = $alternativeKeys;
		}

		$databaseResult = $this->compareWithDatabase(
			'COUNT(*) AS number', $dataArray, $keys, $table
		);

		return empty($databaseResult) ? false : ($databaseResult['number'] >= 1);
	}

	/**
	 * Adds the UID from a database record to $dataArray if all keys mentioned
	 * in $keys match the values of $dataArray and the database entry.
	 *
	 * @param	array		data of an entry which already exists in database,
	 * 						must not be empty
	 * @param	string		comma-separated list of all the keys by which the
	 * 						existance of a database entry will be proven, must
	 * 						be keys of $dataArray and of $table, may contain
	 * 						spaces, must not be empty
	 * @param	string		name of the table where to find out whether an entry
	 * 						yet exists
	 */
	private function ensureUid(
		array &$dataArray, $keys, $table = REALTY_TABLE_OBJECTS
	) {
		if (isset($dataArray['uid'])) {
			return;
		}

		$databaseResultRow = $this->compareWithDatabase(
			'uid', $dataArray, $keys, $table
		);
		if (!empty($databaseResultRow)) {
			$dataArray['uid'] = $databaseResultRow['uid'];
		}
	}

	/**
	 * Retrieves an associative array of data and returns the database result
	 * according to $whatToSelect of the attempt to find matches for the list of
	 * $keys. $keys is a comma-separated list of the database collumns which
	 * should be compared with the corresponding values in $dataArray.
	 *
	 * @param	string		list of fields to select from the database table
	 * 						(part of the sql-query right after SELECT), must not
	 * 						be empty
	 * @param	array		data from which to take elements for the database
	 * 						comparison, must not be empty
	 * @param	string		comma-separated list of keys whose values in
	 * 						$dataArray and in the database should be compared,
	 * 						may contain spaces, must not be empty
	 * @param	string		table name, must not be empty
	 *
	 * @return	array		database result row in an array, will be empty if
	 * 						no matching record was found
	 */
	private function compareWithDatabase($whatToSelect, $dataArray, $keys, $table) {
		$result = false;

		$keysToMatch = array();
		foreach (explode(',', $keys) as $key) {
			$trimmedKey = trim($key);
			if (isset($dataArray[$trimmedKey])) {
				$keysToMatch[] = $trimmedKey;
			}
		}

		if (!empty($keysToMatch)) {
			$whereClauseParts = array();
			foreach ($keysToMatch as $key) {
				$whereClauseParts[] = $key.'="'.$dataArray[$key].'"';
			}

			$showHidden = -1;
			if (($table == REALTY_TABLE_OBJECTS) && $this->canLoadHiddenObjects) {
				$showHidden = 1;
			}

			$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$whatToSelect,
				$table,
				implode(' AND ', $whereClauseParts)
					.$this->templateHelper->enableFields($table, $showHidden)
			);
			if (!$dbResult) {
				throw new Exception(DATABASE_QUERY_ERROR);
			}

			$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		}

		return is_array($result) ? $result : array();
	}

	/**
	 * Tries to retrieve the geo coordinates for this object's address.
	 *
	 * If retrieving the coordinates was successfull, the object will be written
	 * to the database. (Usually, the object should already exist in the DB, but
	 * creating a new object will work fine as well.)
	 *
	 * If this object already has cached geo coordinates, this function will do
	 * nothing.
	 *
	 * @param	tx_oelib_templatehelper	object that contains the plugin
	 * 									configuration
	 *
	 * @return	array		array with the keys "latitude" and "longitude" or
	 * 						an empty array if no coordinates could be retrieved
	 */
	public function retrieveCoordinates(
		tx_oelib_templatehelper $configuration
	) {
		if ($this->getProperty('show_address')) {
			$prefix = 'exact';
			$street = $this->getProperty('street');
		} else {
			$prefix = 'rough';
			$street = '';
		}

		if (!$this->hasCachedCoordinates($prefix)) {
			$coordinates = $this->createGeoFinder($configuration)->lookUp(
				$street,
				$this->getProperty('zip'),
				$this->getCityName(),
				intval($this->getProperty('country'))
			);

			if (!empty($coordinates)) {
				$this->setProperty(
					$prefix . '_coordinates_are_cached', 1
				);
				$this->setProperty(
					$prefix . '_latitude', $coordinates['latitude']
				);
				$this->setProperty(
					$prefix . '_longitude', $coordinates['longitude']
				);
				$this->writeToDatabase();
			}
		}

		return $this->getCachedCoordinates($prefix);
	}

	/**
	 * Gets the shared geo coordinate finder.
	 *
	 * If it does not exist yet, it will be created first.
	 *
	 * @param	tx_oelib_templatehelper	object that contains the plugin
	 * 									configuration
	 *
	 * @return	tx_realty_googleMapsLookup	our geo coordinate finder
	 */
	private function createGeoFinder(tx_oelib_templatehelper $configuration) {
		if (!self::$geoFinder) {
			$className = t3lib_div::makeInstanceClassName(
				'tx_realty_googleMapsLookup'
			);
			self::$geoFinder = new $className($configuration);
		}

		return self::$geoFinder;
	}

	/**
	 * Get this object's city name.
	 *
	 * @return	string		this object's city name or an empty string if this
	 * 						object does not have a city set
	 */
	private function getCityName() {
		$cityProperty = $this->getProperty('city');
		if ($cityProperty === 0) {
			return '';
		}
		if (!is_numeric($cityProperty)) {
			return $cityProperty;
		}

		$uid = intval($cityProperty);
		if (!isset(self::$cityCache[$uid])) {
			$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'title',
				REALTY_TABLE_CITIES,
				'uid = ' . $uid .
					$this->templateHelper->enableFields(REALTY_TABLE_CITIES)
			);
			if (!$dbResult) {
				throw new Exception(DATABASE_QUERY_ERROR);
			}
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
			if (!$row) {
				throw new Exception(DATABASE_RESULT_ERROR);
			}

			self::$cityCache[$uid] = $row['title'];
		}

		return self::$cityCache[$uid];
	}

	/**
	 * Clears the city cache.
	 *
	 * This function is intended to be used for testing purposes only.
	 */
	public function clearCityCache() {
		self::$cityCache = array();
	}

	/**
	 * Checks whether we already have cached geo coordinates.
	 *
	 * This function only checks whether the "has cached coordinates" flag is
	 * set, but not for non-emptiness or validity of the coordinates.
	 *
	 * @param	string		either "exact" or "rough" to indicate which
	 * 						coordinates to check
	 *
	 * @return	boolean		true if we have exact coordinates with the exactness
	 * 						indicated by $prefix, false otherwise
	 */
	private function hasCachedCoordinates($prefix) {
		return (boolean)
			$this->getProperty($prefix . '_coordinates_are_cached');
	}

	/**
	 * Gets this object's cached geo coordinates.
	 *
	 * @param	string		either "exact" or "rough" to indicate which
	 * 						coordinates to get
	 *
	 * @param	array	the coordinates using the keys "latitude" and
	 * 					"longitude" or an empty array if no non-empty cached
	 * 					coordinates are available
	 */
	private function getCachedCoordinates($prefix) {
		if (!$this->hasCachedCoordinates($prefix)) {
			return array();
		}

		$latitude = $this->getProperty($prefix . '_latitude');
		$longitude = $this->getProperty($prefix . '_longitude');

		if ($longitude != '' && $latitude != '') {
			$result = array(
				'latitude' => $latitude,
				'longitude' => $longitude,
			);
		} else {
			$result = array();
		}

		return $result;
	}

	/**
	 * Gets this object's UID.
	 *
	 * @return	integer		this object's UID, will be 0 if this object does not
	 * 						have a UID yet
	 */
	public function getUid() {
		return intval($this->getProperty('uid'));
	}

	/**
	 * Gets this object's title.
	 *
	 * @return	string		this object's title, will be empty if this object
	 * 						does not have a title
	 */
	public function getTitle() {
		return $this->getProperty('title');
	}

	/**
	 * Gets this object's title, cropped after CROP_SIZE characters, with an
	 * ellipsis at the end if the full title was long enough to be cropped.
	 *
	 * @return	string		this object's cropped title, will be empty if this
	 * 						object does not have a title
	 */
	public function getCroppedTitle() {
		$fullTitle = $this->getTitle();

		return ((mb_strlen($fullTitle) <= self::CROP_SIZE)
			? $fullTitle : (mb_substr($fullTitle, 0, self::CROP_SIZE) . '…'));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_object.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_object.php']);
}
?>