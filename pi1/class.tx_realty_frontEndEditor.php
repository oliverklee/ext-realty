<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Saskia Metzler <saskia@merlin.owl.de> All rights reserved
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_db.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_object.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_cacheManager.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_frontEndForm.php');

define('OBJECT_TYPE_SALE', 1);
define('OBJECT_TYPE_RENT', 0);

/**
 * Class 'tx_realty_frontEndEditor' for the 'realty' extension. This class
 * provides a FE editor the realty plugin.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_frontEndEditor extends tx_realty_frontEndForm {
	/** @var	array		cached column names of tables */
	private $tablesAndFieldNames = array();

	/** @var	array		table names which are allowed as form values */
	private static $allowedTables = array(
		REALTY_TABLE_CITIES,
		REALTY_TABLE_DISTRICTS,
		REALTY_TABLE_APARTMENT_TYPES,
		REALTY_TABLE_HOUSE_TYPES,
		REALTY_TABLE_CAR_PLACES,
		REALTY_TABLE_PETS,
		STATIC_COUNTRIES,
	);

	/**
	 * Deletes a record if the current object UID is a valid UID that identifies
	 * an object of an authorized FE user. Otherwise an error message will be
	 * returned.
	 *
	 * @return	string		empty if there was either nothing to delete or the
	 * 						deletion was allowed, otherwise HTML of an error
	 * 						message
	 */
	public function deleteRecord() {
		$errorMessage = $this->checkAccess();
		if ($errorMessage != '') {
			return $errorMessage;
		}

		if ($this->realtyObjectUid != 0) {
			$this->realtyObject->setProperty('deleted', true);
			// Providing the PID ensures the record not to change the location.
			$this->realtyObject->writeToDatabase(
				$this->realtyObject->getProperty('pid')
			);
			tx_realty_cacheManager::clearFrontEndCacheForRealtyPages();
		}

		return '';
	}


	////////////////////////////////
	// Functions used by the form.
	////////////////////////////////
	// * Functions for rendering.
	///////////////////////////////

	/**
	 * Checks whether the object number is readonly.
	 *
	 * @return	boolean		true if the object number is readonly, false
	 * 						otherwise
	 */
	public function isObjectNumberReadonly() {
		return $this->realtyObjectUid > 0;
	}

	/**
	 * Provides data items to fill select boxes. Returns caption-value pairs from
	 * the database table named $tableName.
	 * The field "title" will be returned within the array as caption. The UID
	 * will be the value.
	 *
	 * @param	array		not used (items currently defined in the form)
	 * @param	array		Form data array, must at least contain one element
	 * 						with the key 'table' and the table name to query as
	 * 						value. May also have an element 'title_column' where
	 * 						the database column name of the field that will be
	 * 						used as	the title can be defined, if not set, the
	 * 						key 'title' is assumed to be the title. There may
	 * 						also be an element 'has_dummy_column' which needs to
	 * 						be false if the table has no column 'is_dummy_record'.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records or if the provided table name
	 * 						was invalid
	 */
	public function populateList(array $notUsed, array $formData) {
		$this->checkForValidTableName($formData['table']);

		$titleColumn = (isset($formData['title_column'])
				&& ($formData['title_column'] != '')
			) ? $formData['title_column']
			: 'title';
		$this->checkForValidFieldName($titleColumn, $formData['table']);

		$this->loadFieldNames($formData['table']);
		$hasDummyColumn = isset(
			$this->tablesAndFieldNames[$formData['table']]['is_dummy_record']
		);

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$titleColumn . ',uid',
			$formData['table'],
			'1=1' . tx_oelib_db::enableFields($formData['table']) .
				($hasDummyColumn ? $this->getWhereClauseForTesting() : ''),
			'',
			$titleColumn
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$items = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$items[] = array('caption' => $row[$titleColumn], 'value' => $row['uid']);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		// Resets the array pointer as expected by FORMidable.
		reset($items);

		return $items;
	}


	////////////////////////////
	// * Validation functions.
	////////////////////////////

	/**
	 * Checks whether a number is valid and does not have decimal digits.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number to check, this number may also be empty
	 *
	 * @return	boolean		true if the number is an integer or empty
	 */
	public function isValidIntegerNumber(array $formData) {
		return $this->isValidNumber($formData['value'], false);
	}

	/**
	 * Checks whether a number which may have decimal digits is valid.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number to check, this number may also be empty
	 *
	 * @return	boolean		true if the number is valid or empty
	 */
	public function isValidNumberWithDecimals(array $formData) {
		return $this->isValidNumber($formData['value'], true);
	}

	/**
	 * Checks whether a form data value is within a range of allowed integers.
	 * The provided form data array must contain the keys 'value', 'range' and
	 * 'multiple'. 'range' must be two integers separated by '-'. If 'multiple',
	 * which is supposed to be boolean, is set to true, multiple values are
	 * allowed in 'value'. In this case, 'value' is expected to contain an inner
	 * array.
	 *
	 * @param	array		array with the elements 'value', 'range' and
	 * 						'multiple', 'value' is the form data value to check
	 * 						and can be empty, 'range' must be two integers
	 * 						separated by '-' and 'multiple' must be boolean
	 *
	 * @return	boolean		true if the values to check are empty or in range,
	 * 						false otherwise
	 */
	public function isIntegerInRange(array $formData) {
		if ($formData['value'] === '') {
			return true;
		}

		$result = true;

		$range = explode('-', $formData['range']);
		$valuesToCheck = $formData['multiple']
			? $formData['value']
			: array($formData['value']);

		foreach ($valuesToCheck as $value) {
			if (!$this->isValidIntegerNumber(array('value' => $value))) {
				$result = false;
			}
		}

		if ((min($valuesToCheck) < min($range))
			|| (max($valuesToCheck) > max($range))
		) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Checks whether the provided year is this year or earlier.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the year to check, this must be this year or earlier
	 * 						or empty
	 *
	 * @return	boolean		true if the year is valid or empty
	 */
	public function isValidYear(array $formData) {
		return ($this->isValidNumber($formData['value'], false)
			&& ($formData['value'] <= date('Y', mktime())));
	}

	/**
	 * Checks whether the price is non-empty and valid if the object is for sale.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the price to check for non-emptiness if an object is
	 * 						for sale
	 *
	 * @return	boolean		true if the price is valid and non-empty, also
	 * 						true if the price is valid or empty if the object
	 * 						is for rent
	 */
	public function isNonEmptyValidPriceForObjectForSale(array $formData) {
		return $this->isValidPriceForObjectType(
			$formData['value'], OBJECT_TYPE_SALE
		);
	}

	/**
	 * Checks whether the price is non-empty and valid if the object is for rent.
	 *
	 * Note: This function is used in the renderlet for 'rent_excluding_bills'
	 * but also checks 'year_rent' as at least one of these fields is
	 * required to be filled for an object to rent.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the price to check
	 *
	 * @return	boolean		if the object is for rent, true is returned if at
	 * 						least one of the prices is non-empty and both are
	 * 						valid or empty, if the object is for sale, true is
	 * 						returned if both prices are valid or empty,
	 * 						otherwise the result is false
	 */
	public function isNonEmptyValidPriceForObjectForRent(array $formData) {
		$yearRent = $this->getFormValue('year_rent');

		$twoValidValues =
			$this->isValidNumberWithDecimals($formData)
			&& $this->isValidNumberWithDecimals(array('value' =>$yearRent));

		$oneValueMatchesObjectTypeConditions =
			$this->isValidPriceForObjectType($formData['value'], OBJECT_TYPE_RENT)
			|| $this->isValidPriceForObjectType($yearRent, OBJECT_TYPE_RENT);

		return $twoValidValues && $oneValueMatchesObjectTypeConditions;
	}

	/**
	 * Checks whether the object number is non-empty and whether the combination
	 * of object number and language is unique in the database.
	 *
	 * Always returns true if an existing object is edited.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the entered object number, this number may be empty
	 *
	 * @return	boolean		true if the object number is non empty and unique
	 * 						for the entered language, also true if the object
	 * 						already exists in the database
	 */
	public function isObjectNumberUniqueForLanguage(array $formData) {
		// FE users cannot change the object number of existing objects anyway.
		if ($this->realtyObjectUid > 0) {
			return true;
		}

		// Empty object numbers are not allowed.
		if ($formData['value'] == '') {
			return false;
		}

		$languages = array();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'language',
			REALTY_TABLE_OBJECTS,
			'object_number="' .
				$GLOBALS['TYPO3_DB']->quoteStr(
					$formData['value'], REALTY_TABLE_OBJECTS
				) . '"' . tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS, 1) .
				$this->getWhereClauseForTesting()
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$languages[] = $row['language'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		// Initially, new objects will always have an empty language because
		// FE users cannot set the language.
		return !in_array('', $languages);
	}

	/**
	 * Checks whether the provided number is a UID in the provided table or zero
	 * if this should be allowed.
	 *
	 * @param	array		array with the elements 'value' which contains the
	 * 						value to check to be an identifying value of a
	 * 						record and 'table' which contains the name of the
	 * 						corresponding database table and must not be empty
	 * @param	boolean		true if the value to check may be empty or zero
	 * 						instead of pointing to an existing record, false
	 * 						otherwise
	 *
	 * @return	boolean		true if the form data value is actually the UID of
	 * 						a record in a valid table, false otherwise
	 */
	public function checkKeyExistsInTable(
		array $formData, $mayBeEmptyOrZero = true
	) {
		$this->checkForValidTableName($formData['table']);

		if ($mayBeEmptyOrZero
			&& (($formData['value'] === '0') || ($formData['value'] === ''))
		) {
			return true;
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			$formData['table'],
			'uid="' .
				$GLOBALS['TYPO3_DB']->quoteStr(
					$formData['value'], $formData['table']
				) . '"' . tx_oelib_db::enableFields($formData['table'])
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$result = (boolean) $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		return $result;
	}

	/**
	 * Checks whether the submitted UID for 'city' is actually a database record
	 * or zero. If the UID is zero, there must be a value provided in 'new_city'.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number which is checked to be the UID of an
	 * 						existing record, this number must be an integer >= 0
	 *
	 * @return	boolean		true if the provided UID is valid or if there is a
	 * 						value in 'new_city', false otherwise
	 */
	public function isAllowedValueForCity(array $formData) {
		$mayBeEmpty = ($this->getFormValue('new_city') == '') ? false : true;

		return $this->checkKeyExistsInTable(array(
				'value' => $formData['value'], 'table' => REALTY_TABLE_CITIES
			),
			$mayBeEmpty
		);
	}

	/**
	 * Checks whether no existing record is selected if a new record title is
	 * provided. Returns always true if no new record title is provided.
	 *
	 * @param	array		form data with one element named 'value' that
	 * 						contains the title for the new record or may be
	 * 						empty and one element 'fieldName' where the key used
	 * 						in tx_realty_objets for this record is defined and
	 * 						must not be empty
	 *
	 * @return	boolean		true if the value for 'fieldName' is empty when
	 * 						there is a value for 'value' provided, also true if
	 * 						'value' is empty, false otherwise
	 */
	public function isAtMostOneValueForAuxiliaryRecordProvided(array $formData) {
		return (($formData['value'] == '')
			|| ($this->getFormValue($formData['fieldName']) == 0)
		);
	}

	/**
	 * Checks whether there is no existing city record selected at the same time
	 * a new one should be created.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the value which contains the string for the new city
	 * 						record
	 *
	 * @return	boolean		true if no existing city record is selected or if
	 * 						the string for the new city record is empty
	 */
	public function isAtMostOneValueForCityRecordProvided(array $valueToCheck) {
		return $this->isAtMostOneValueForAuxiliaryRecordProvided(
			$valueToCheck['value'], 'city'
		);
	}

	/**
	 * Checks whether there is no existing district record selected at the same
	 * time a new one should be created.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the value which contains the string for the new
	 * 						district record
	 *
	 * @return	boolean		true if no existing district record is selected or
	 * 						if the string for the new district record is empty
	 */
	public function isAtMostOneValueForDistrictRecordProvided(array $valueToCheck) {
		return $this->isAtMostOneValueForAuxiliaryRecordProvided(
			$valueToCheck['value'], 'district'
		);
	}

	/**
	 * Checks whether the provided value is non-empty or the owner's data is
	 * chosen as contact data source.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the value which contains the string to check
	 *
	 * @return	boolean		true if the provided value is non-empty or if
	 * 						the contact data source is the owner's account,
	 * 						false otherwise
	 */
	public function isNonEmptyOrOwnerDataUsed(array $formData) {
		if ($this->getFormValue('contact_data_source')
			== REALTY_CONTACT_FROM_OWNER_ACCOUNT
		) {
			return true;
		}

		return ($formData['value'] != '');
	}

	/**
	 * Checks whether a longitute degree is correctly formatted and within
	 * range.
	 *
	 * Empty values are considered valid.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the value which contains the string to check
	 *
	 * @return	boolean		true if $formData['value'] is valid, false otherwise
	 */
	public function isValidLongitudeDegree(array $formData) {
		return $this->checkGeoCoordinate(
			$formData['value'], -180.00, 180.00
		);
	}

	/**
	 * Checks whether a latitude degree is correctly formatted and within range.
	 *
	 * Empty values are considered valid.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the value which contains the string to check
	 *
	 * @return	boolean		true if $formData['value'] is valid, false otherwise
	 */
	public function isValidLatitudeDegree(array $formData) {
		return $this->checkGeoCoordinate($formData['value'], -90.00, 90.00);
	}

	/**
	 * Checks whether a geo coordinate is correctly formatted and within range.
	 *
	 * Empty values are considered valid.
	 *
	 * @param	string		the input data that should checked, may be empty
	 * @param	float		mininum allowed value
	 * @param	float		maximum allowed value
	 *
	 * @return	boolean		true if $valueToCheck is valid or empty, false
	 * 						otherwise
	 */
	private function checkGeoCoordinate($valueToCheck, $minimum, $maximum) {
		if ($valueToCheck == '') {
			return true;
		}

		$unifiedValueToCheck = $this->unifyNumber($valueToCheck);

		$valueContainsOnlyAllowedCharacters = (boolean) preg_match(
			'/^-?\d{1,3}(\.\d{1,14})?$/', $unifiedValueToCheck
		);
		$valueIsInAllowedRange = (floatval($unifiedValueToCheck) >= $minimum)
			&& (floatval($unifiedValueToCheck) <= $maximum);

		return ($valueContainsOnlyAllowedCharacters && $valueIsInAllowedRange);
	}

	/**
	 * Checks whether the a number is correctly formatted. The format must be
	 * according to the current locale.
	 *
	 * @param	string		value to check to be a valid number, may be empty
	 * @param	boolean		whether the number may have decimals
	 *
	 * @return	boolean		true if $valueToCheck is valid or empty, false
	 * 						otherwise
	 */
	private function isValidNumber($valueToCheck, $mayHaveDecimals) {
		if ($valueToCheck == '') {
			return true;
		}

		$unifiedValueToCheck = $this->unifyNumber($valueToCheck);

		if ($mayHaveDecimals) {
			$result = preg_match('/^[\d]*(\.[\d]{1,2})?$/', $unifiedValueToCheck);
		} else {
			$result = preg_match('/^[\d]*$/', $unifiedValueToCheck);
		}

		return (boolean) $result;
	}

	/**
	 * Checks whether $price depending on the object type and $typeOfField is
	 * either a valid price and non-empty or a valid price or empty.
	 *
	 * @param	string		price to validate, may be empty
	 * @param	integer		one if the price was entered as a buying price,
	 * 						zero if it derived from a field for rent
	 *
	 * @return	boolean		true if the object type and $typeOfField match and
	 * 						$price is non-empty and valid, also true if object
	 * 						type and $typeOfField do not match and $price is
	 * 						valid or empty
	 */
	private function isValidPriceForObjectType($price, $typeOfField) {
		if ($this->getObjectType() == $typeOfField) {
			$result = ($this->isValidNumber($price, true) && ($price != ''));
		} else {
			$result = $this->isValidNumber($price, true);
		}

		return $result;
	}


	//////////////////////////////////
	// * Message creation functions.
	//////////////////////////////////

	/**
	 * Returns a localized message that the provided field is required to be
	 * valid and if object type corresponds to the field name also non-empty.
	 *
	 * @param	array	 	form data, must contain the key 'fieldName', the
	 * 						value of 'fieldName' must be a database column name
	 * 						of 'tx_realty_objects' which concerns the message,
	 * 						must not be empty
	 *
	 * @return	string		localized message following the pattern
	 * 						"[field name]: [message]" if $labelOfField was
	 * 						non-empty, otherwise only the message is returned
	 */
	public function getNoValidPriceOrEmptyMessage(array $formData) {
		$isObjectToBuy = ($this->getObjectType() == 1);
		$isFieldForBuying = ($formData['fieldName'] == 'buying_price');

		$fieldSuffix = ($isFieldForBuying == $isObjectToBuy)
			? '_non_empty' : '_or_empty';
		$fieldSuffix .= $isFieldForBuying ? '_buying_price' : '_rent';

		return $this->getMessageForRealtyObjectField(array(
			'fieldName' => $formData['fieldName'],
			'label' => 'message_enter_valid' . $fieldSuffix,
		));
	}

	/**
	 * Returns a localized message that the object number is empty or that it
	 * already exists in the database.
	 *
	 * @return	string		localized message following the pattern
	 * 						"[field name]: [message]" if $labelOfField was
	 * 						non-empty, otherwise only the message is returned
	 */
	public function getInvalidObjectNumberMessage() {
		if ($this->getFormValue('object_number') == '') {
			$message = 'message_required_field';
		} else {
			$message = 'message_object_number_exists';
		}

		return $this->getMessageForRealtyObjectField(
			array('fieldName' => 'object_number', 'label' => $message)
		);
	}

	/**
	 * Returns a localized message that either the entered value for city is not
	 * valid or that it must not be empty.
	 *
	 * @return	string		localized message following the pattern
	 * 						"[field name]: [invalid message]"
	 */
	public function getInvalidOrEmptyCityMessage() {
		return $this->getMessageForRealtyObjectField(array(
			'fieldName' => 'city',
			'label' => (($this->getFormValue('city') == 0)
				? 'message_required_field'
				: 'message_value_not_allowed'
			),
		));
	}

	/**
	 * Returns a localized validation error message.
	 *
	 * @param	array		Form data, must contain the elements 'fieldName' and
	 * 						'label'. The value of 'fieldName' must be a database
	 * 						column name of 'tx_realty_objects' which concerns
	 * 						the message and must not be empty. The element
	 * 						'label' defines the label of the message to return
	 * 						and must be a key defined in /pi1/locallang.xml.
	 *
	 * @return	string		localized message following the pattern
	 * 						"[field name]: [message]", in case no valid field
	 * 						name was provided, only the message is returned, if
	 * 						the label for the message was invalid, the message
	 * 						will always be "value not allowed"
	 */
	public function getMessageForRealtyObjectField(array $formData) {
		// This  will lead to an exception for an invalid non-empty field name.
		$labelOfField = $this->checkForValidFieldName(
				$formData['fieldName'], REALTY_TABLE_OBJECTS, true
			) ? 'LLL:EXT:realty/locallang_db.xml:' . REALTY_TABLE_OBJECTS . '.' .
				$formData['fieldName']
			: '';
		// This will cause an exception if the locallang key was invalid.
		$this->checkForValidLocallangKey($formData['label']);

		return $this->getMessageForField($labelOfField, $formData['label']);
	}

	/**
	 * Returns a localized message for a certain field.
	 *
	 * @param	string		label of the field which concerns the the message,
	 * 						must be the absolute path starting with "LLL:EXT:",
	 * 						may be empty
	 * @param	string		label of the message to return, must be defined in
	 * 						pi1/locallang.xml, must not be empty
	 *
	 * @return	string		localized message following the pattern
	 * 						"[field name]: [message]" if $labelOfField was
	 * 						non-empty, otherwise only the message is returned
	 */
	private function getMessageForField($labelOfField, $labelOfMessage) {
		$localizedFieldName = ($labelOfField != '')
			? ($GLOBALS['TSFE']->sL($labelOfField) . ': ')
			: '';

		return $localizedFieldName . $this->plugin->translate($labelOfMessage);
	}

	/**
	 * Checks whether a locallang key contains only allowed characters. If not,
	 * an exception will be thrown.
	 *
	 * @param	string		locallang key to check, must not be empty
	 *
	 * @return	boolean		true if the provided locallang key only consists of
	 * 						allowed characters, otherwise an exception is thrown
	 */
	private function checkForValidLocallangKey($label) {
		if (!preg_match('/^([a-z_])+$/', $label)) {
			throw new Exception('"' . $label . '" is not a valid locallang key.');
		}

		return true;
	}


	///////////////////////////////////
	// * Functions used after submit.
	///////////////////////////////////

	/**
	 * Adds administrative data, unifies numbers and stores new auxiliary
	 * records if there are any.
	 *
	 * @see	addAdministrativeData(), unifyNumbersToInsert(),
	 * 		storeNewAuxiliaryRecords(), purgeNonRealtyObjectFields()
	 *
	 * @param	array 		form data, must not be empty
	 *
	 * @return	array		form data with additional administrative data and
	 * 						unified numbers
	 */
	public function modifyDataToInsert(array $formData) {
		$modifiedFormData = $this->storeNewAuxiliaryRecords($formData);
		$modifiedFormData = $this->purgeNonRealtyObjectFields($modifiedFormData);

		return $this->addAdministrativeData(
			$this->unifyNumbersToInsert($modifiedFormData)
		);
	}

	/**
	 * Sends an e-mail if a new object hase been createed.
	 *
	 * Clears the FE cache for pages with the realty plugin.
	 */
	public function sendEmailForNewObjectAndClearFrontEndCache() {
		$this->sendEmailForNewObject();
		tx_realty_cacheManager::clearFrontEndCacheForRealtyPages();
	}

	/**
	 * Sends an e-mail if a new object has been created.
	 */
	private function sendEmailForNewObject() {
		if (($this->realtyObjectUid > 0)
			|| !$this->plugin->hasConfValueString('feEditorNotifyEmail', 's_feeditor')
		) {
			return;
		}

		tx_oelib_mailerFactory::getInstance()->getMailer()->sendEmail(
			$this->plugin->getConfValueString('feEditorNotifyEmail', 's_feeditor'),
			$this->plugin->translate('label_email_subject_fe_editor'),
			$this->getFilledEmailBody(),
			$this->getFromLineForEmail(),
			'',
			'UTF-8'
		);
	}

	/**
	 * Returns the e-mail body formatted according to the template and filled
	 * with the new object's summarized data.
	 *
	 * Note: The e-mail body will only contain the correct UID if the record
	 * this e-mail is about is the last record that was added to the database.
	 *
	 * @return	string		body for the e-mail to send, will not be
	 * 						empty
	 */
	private function getFilledEmailBody() {
		$frontEndUserData = $this->getFrontEndUserData('name, username');
		foreach (array(
			'username' => $frontEndUserData['username'],
			'name' => $frontEndUserData['name'],
			'object_number' => $this->getFormValue('object_number'),
			'title' => $this->getFormValue('title'),
			'uid' => $GLOBALS['TYPO3_DB']->sql_insert_id(),
		) as $marker => $value) {
			$this->plugin->setOrDeleteMarkerIfNotEmpty(
				$marker, $value, '', 'wrapper'
			);
		}

		return $this->plugin->getSubpart('FRONT_END_EDITOR_EMAIL');
	}

	/**
	 * Returns the formatted "From:" header line for the e-mail to send.
	 *
	 * @return	string		formatted e-mail header line containing the sender,
	 * 						will not be empty
	 */
	private function getFromLineForEmail() {
		$frontEndUserData = $this->getFrontEndUserData('name, email');
		return 'From: "'.$frontEndUserData['name'].'" '
			.'<'.$frontEndUserData['email'].'>'.LF;
	}

	/**
	 * Removes all form data elements that are not fields in the realty objects
	 * table. E.g. spacers and the "new_*" used to add new auxiliary records.
	 *
	 * @param	array 		form data, must not be empty
	 *
	 * @return	array		modified form data
	 */
	private function purgeNonRealtyObjectFields(array $formData) {
		$modifiedFormData = array();
		$this->loadFieldNames(REALTY_TABLE_OBJECTS);

		foreach ($formData as $key => $value) {
			if ($this->tablesAndFieldNames[REALTY_TABLE_OBJECTS][$key]) {
				$modifiedFormData[$key] = $value;
			}
		}

		return $modifiedFormData;
	}

	/**
	 * Stores new auxiliary records in the database if there are any in the
	 * provided form data and modifies the form data.
	 * The UIDs of the new records are written to the form data.
	 *
	 * @param	array 		form data, must not be empty
	 *
	 * @return	array		modified form data
	 */
	private function storeNewAuxiliaryRecords(array $formData) {
		$modifiedFormData = $formData;

		foreach (array(
			REALTY_TABLE_CITIES => 'city', REALTY_TABLE_DISTRICTS => 'district'
		) as $table => $key) {
			$title = trim($modifiedFormData['new_'.$key]);

			if (($title != '') && ($modifiedFormData[$key] == 0)) {
				$uid = $this->getUidIfAuxiliaryRecordExists($title, $table);

				if ($uid == 0) {
					$uid = $this->createNewAuxiliaryRecord($title, $table);
				}

				$modifiedFormData[$key] = $uid;
			}
		}

		return $modifiedFormData;
	}

	/**
	 * Returns the UID of an auxiliary record's title or zero if it does not
	 * exist.
	 *
	 * @param	string		title of an auxiliary record to search, must not
	 * 						be empty
	 * @param	string		table where to search this title, must not be empty
	 *
	 * @return	integer		UID of the record with the title to search or zero
	 * 						if there is no record with this title
	 */
	private function getUidIfAuxiliaryRecordExists($title, $table) {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			$table,
			'title="'.$GLOBALS['TYPO3_DB']->quoteStr($title, $table).'"'
				.$this->getWhereClauseForTesting()
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		return ($result !== false) ? $result['uid'] : 0;
	}

	/**
	 * Inserts a new auxiliary record into the database.
	 *
	 * @param	string		title of an auxiliary record to create, must not
	 * 						be empty
	 * @param	string		table where to add this title, must not be empty
	 *
	 * @return	integer		UID of the new record, will be > 0
	 */
	private function createNewAuxiliaryRecord($title, $table) {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$table,
			array(
				'title' => $title,
				'pid' => $this->plugin->getConfValueInteger(
					'sysFolderForFeCreatedAuxiliaryRecords', 's_feeditor'
				),
				'tstamp' => mktime(),
				'crdate' => mktime(),
				'is_dummy_record' => $this->isTestMode
			)
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		return $GLOBALS['TYPO3_DB']->sql_insert_id();
	}

	/**
	 * Unifies all numbers before they get inserted into the database.
	 *
	 * @param	array		data that will be inserted, may be empty
	 *
	 * @return	array		data that will be inserted with unified numbers,
	 * 						will be empty if an empty array was provided
	 */
	private function unifyNumbersToInsert(array $formData) {
		$modifiedFormData = $formData;
		$numericFields = array(
			'number_of_rooms',
			'living_area',
			'total_area',
			'estate_size',
			'rent_excluding_bills',
			'extra_charges',
			'year_rent',
			'floor',
			'floors',
			'bedrooms',
			'bathrooms',
			'garage_rent',
			'garage_price',
			'construction_year',
			'exact_longitude',
			'exact_latitude',
			'rough_longitude',
			'rough_latitude',
		);

		foreach ($numericFields as $key) {
			if (isset($modifiedFormData[$key])) {
				$modifiedFormData[$key] = $this->unifyNumber($modifiedFormData[$key]);
			}
		}
		// ensures the object type is always 'rent' or 'sale'
		$modifiedFormData['object_type'] = $this->getObjectType();

		return $modifiedFormData;
	}

	/**
	 * Adds some values to the form data before insertion into the database.
	 * Added values for new objects are: 'crdate', 'tstamp', 'pid' and 'owner'.
	 * In addition they become marked as 'hidden'.
	 * For objects to update, just the 'tstamp' will be refreshed.
	 *
	 * @param	array		form data, may be empty
	 *
	 * @return	array		form data with additional elements: always 'tstamp',
	 * 						for new objects also 'hidden', 'crdate', 'pid' and
	 * 						'owner'
	 */
	private function addAdministrativeData(array $formData) {
		$pidFromCity = $this->getPidFromCityRecord(intval($formData['city']));
		$modifiedFormData = $formData;

		$modifiedFormData['tstamp'] = mktime();
		// The PID might have changed if the city did.
		$modifiedFormData['pid'] = ($pidFromCity != 0)
			? $pidFromCity
			: $this->plugin->getConfValueString(
				'sysFolderForFeCreatedRecords', 's_feeditor'
			);
		// New records need some additional data.
		if ($this->realtyObjectUid == 0) {
			$frontEndUserAnid = $this->getFrontEndUserData('tx_realty_openimmo_anid');

			$modifiedFormData['hidden'] = 1;
			$modifiedFormData['crdate'] = mktime();
			$modifiedFormData['owner'] = $this->getFeUserUid();
			$modifiedFormData['openimmo_anid']
				= $frontEndUserAnid['tx_realty_openimmo_anid'];
		}

		return $modifiedFormData;
	}

	/**
	 * Returns the PID from the field 'save_folder'. This PID defines where to
	 * store records for the city defined by $cityUid.
	 *
	 * @param	integer		UID of the city record from which to get the system
	 * 						folder ID, must be an integer > 0
	 *
	 * @return	integer		UID of the system folder where to store this city's
	 * 						records, will be zero if no folder was set
	 */
	private function getPidFromCityRecord($cityUid) {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'save_folder',
			REALTY_TABLE_CITIES,
			'uid='.$cityUid
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		return intval($row['save_folder']);
	}


	////////////////////////////////////
	// Miscellaneous helper functions.
	////////////////////////////////////

	/**
	 * Unifies a number.
	 *
	 * Replaces a comma by a dot and strips whitespaces.
	 *
	 * @param	string		number to be unified, may be empty
	 *
	 * @return	string		unified number with a dot as decimal separator, will
	 * 						be empty if $number was empty
	 */
	private function unifyNumber($number) {
		if ($number == '') {
			return '';
		}

		$unifiedNumber = str_replace(',', '.', $number);

		return str_replace(' ', '', $unifiedNumber);
	}

	/**
	 * Returns the current object type.
	 *
	 * @return	integer		one if the object is for sale, zero if it is for
	 * 						rent
	 */
	private function getObjectType() {
		return t3lib_div::intInRange(
			$this->getFormValue('object_type'),
			OBJECT_TYPE_RENT,
			OBJECT_TYPE_SALE,
			OBJECT_TYPE_RENT
		);
	}

	/**
	 * Checks whether a provided field name is actually the name of a database
	 * column of $tableName. The result will be true if the field name is valid,
	 * otherwise, an exception will be thrown. Only if $noExceptionIfEmpty is
	 * set to true, the result will just be false for an empty field name.
	 *
	 * @param	string		field name to check, may be empty
	 * @param	string		table name, must be a valid database table name,
	 * 						will be tx_realty_objects if no other table is set
	 * @param	boolean		true if the the field name to check may be empty,
	 * 						false otherwise
	 *
	 * @return	boolean		true if $fieldName is a database colum name of the
	 * 						realty objects table and non-empty, false otherwise
	 */
	private function checkForValidFieldName(
		$fieldName, $tableName = REALTY_TABLE_OBJECTS, $noExceptionIfEmpty = false
	) {
		if ((trim($fieldName) == '') && $noExceptionIfEmpty) {
			return false;
		}

		$this->loadFieldNames($tableName);
		if (!isset($this->tablesAndFieldNames[$tableName][$fieldName])) {
			throw new Exception(
				'"' . $fieldName . '" is not a valid column name for ' .
				$tableName . '.'
			);
		}

		return true;
	}

	/**
	 * Writes the column names of $table to $this->tablesAndFieldNames if they
	 * are not cached yet.
	 *
	 * @param	string		table name, must not be empty
	 */
	private function loadFieldNames($table) {
		// To reduce database queries in order to improve performance, the
		// column names stored in an member variable.
		if (isset($this->tablesAndFieldNames[$table])
			&& !empty($this->tablesAndFieldNames[$table])
		) {
			return;
		}

		$this->tablesAndFieldNames[$table]
			= $GLOBALS['TYPO3_DB']->admin_get_fields($table);
	}

	/**
	 * Checks whether a table name is within the list of allowed table names.
	 * Throws an exception it is not.
	 *
	 * @param	string		table name to check, must not be empty
	 *
	 * @return	boolean		true if the table name is allowed, an exception is
	 * 						thrown otherwise
	 */
	private function checkForValidTableName($tableName) {
		if (!in_array($tableName, self::$allowedTables)) {
			throw new Exception(
				'"' . $tableName . '" is not a valid table name.'
			);
		}

		return true;
	}

	/**
	 * Returns an associative array of selected keys of a FE user record.
	 *
	 * Note: This function requires a FE user to be logged in.
	 *
	 * @param	string		Comma-separated list of keys of the fe_users table
	 * 						for which to return the values for the current FE
	 * 						user or '*' for all keys. Will be used as SELECT for
	 * 						the database query, must not be empty.
	 *
	 * @return	array		associative array with the provided keys and their
	 * 						corresponding values, will not be empty
	 */
	private function getFrontEndUserData($keys) {
		if ($keys == '') {
			throw new Exception('$keys must not be empty.');
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$keys,
			'fe_users',
			'uid=' . $this->getFeUserUid()
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if ($result === false) {
			throw new Exception(
				'The FE user data could not be fetched. Please ensure ' .
				'a FE user to be logged in.'
			);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		return $result;
	}


	///////////////////////////////////
	// Utility functions for testing.
	///////////////////////////////////

	/**
	 * Fakes that FORMidable has inserted a new record into the database.
	 *
	 * This function writes the array of faked form values to the database and
	 * is for testing purposes.
	 */
	public function writeFakedFormDataToDatabase() {
		// The faked record is marked as a test record and no fields are
		// required to be set.
		$this->setFakedFormValue('is_dummy_record', 1);
		$this->realtyObject->setRequiredFields(array());
		$this->realtyObject->loadRealtyObject($this->fakedFormValues);
		$this->realtyObject->writeToDatabase();
	}

	/**
	 * Returns a WHERE clause part for the test mode. So only dummy records will
	 * be received for testing.
	 *
	 * @return	string		WHERE clause part for testing starting with ' AND'
	 * 						if the test mode is enabled, an empty string
	 *						otherwise
	 */
	private function getWhereClauseForTesting() {
		return $this->isTestMode ? ' AND is_dummy_record=1' : '';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndEditor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndEditor.php']);
}
?>