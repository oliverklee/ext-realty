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

/**
 * Class 'tx_realty_frontEndEditor' for the 'realty' extension. This class
 * provides a FE editor the realty plugin.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty').'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty').'lib/class.tx_realty_object.php');
require_once(t3lib_extMgm::extPath('realty').'lib/class.tx_realty_cacheManager.php');
require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_frontEndForm.php');

define('OBJECT_TYPE_SALE', 1);
define('OBJECT_TYPE_RENT', 0);

class tx_realty_frontEndEditor extends tx_realty_frontEndForm {
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
	 * Fills the select box for city records.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	public function populateListOfCities() {
		return $this->populateListByTitleAndUid(REALTY_TABLE_CITIES);
	}

	/**
	 * Fills the select box for district records.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	public function populateListOfDistricts() {
		return $this->populateListByTitleAndUid(REALTY_TABLE_DISTRICTS);
	}

	/**
	 * Fills the select box for house type records.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	public function populateListOfHouseTypes() {
		return $this->populateListByTitleAndUid(REALTY_TABLE_HOUSE_TYPES);
	}

	/**
	 * Fills the select box for apartment type records.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	public function populateListOfApartmentTypes() {
		return $this->populateListByTitleAndUid(REALTY_TABLE_APARTMENT_TYPES);
	}

	/**
	 * Fills the select box for heating type records.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	public function populateListOfHeatingTypes() {
		return $this->populateListByTitleAndUid(REALTY_TABLE_HEATING_TYPES);
	}

	/**
	 * Fills the select box for car place records.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	public function populateListOfCarPlaces() {
		return $this->populateListByTitleAndUid(REALTY_TABLE_CAR_PLACES);
	}

	/**
	 * Fills the select box for state records.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	public function populateListOfConditions() {
		return $this->populateListByTitleAndUid(REALTY_TABLE_CONDITIONS);
	}

	/**
	 * Fills the select box for pet records.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	public function populateListOfPets() {
		return $this->populateListByTitleAndUid(REALTY_TABLE_PETS);
	}

	/**
	 * Fills the select box for languages.
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	public function populateListOfLanguages() {
		return $this->populateList(
			'static_languages', 'lg_name_en', 'lg_iso_2', false
		);
	}

	/**
	 * Provides data items to fill select boxes. Returns caption-value pairs from
	 * the database table named $tableName.
	 * The field "title" will be returned within the array as caption. The UID
	 * will be the value.
	 *
	 * @param	string		the table name to query, must not be empty
	 * @param	boolean		whether the table has the column 'is_dummy_record'
	 * 						for the test mode flag
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	private function populateListByTitleAndUid(
		$tableName, $hasTestModeColumn = true
	) {
		return $this->populateList($tableName, 'title', 'uid', $hasTestModeColumn);
	}

	/**
	 * Provides data items to fill select boxes. Returns caption-value pairs from
	 * the database table named $tableName.
	 *
	 * @param	string		the table name to query, must not be empty
	 * @param	string		name of the database column for the caption, must
	 * 						not be empty
	 * @param	string		name of the database column for the value, must not
	 * 						be empty
	 * @param	boolean		whether the table has the column 'is_dummy_record'
	 * 						for the test mode flag
	 *
	 * @return	array		items for the select box, will be empty if there are
	 * 						no matching records
	 */
	private function populateList(
		$tableName, $keyForCaption, $keyForValue, $hasTestModeColumn = true
	) {
		$items = array();
		$whereClause = '1=1';

		if ($hasTestModeColumn) {
			$whereClause .= $this->getWhereClauseForTesting();
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$keyForCaption.','.$keyForValue,
			$tableName,
			$whereClause.$this->enableFields($tableName),
			'',
			$keyForCaption
		);
		if (!$dbResult) {
			throw new Exception('There was an error with the database query.');
		}

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$items[] = array(
				'caption' => $row[$keyForCaption], 'value' => $row[$keyForValue]
			);
		}

		// Resets the array pointer as the populateList* functions expect
		// arrays with a reset array pointer.
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
	public function isValidIntegerNumber(array $valueToCheck) {
		return $this->isValidNumber($valueToCheck['value'], false);
	}

	/**
	 * Checks whether a number which may have decimal digits is valid.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number to check, this number may also be empty
	 *
	 * @return	boolean		true if the number is valid or empty
	 */
	public function isValidNumberWithDecimals(array $valueToCheck) {
		return $this->isValidNumber($valueToCheck['value'], true);
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
	public function isValidYear(array $valueToCheck) {
		return ($this->isValidNumber($valueToCheck['value'], false)
			&& ($valueToCheck['value'] <= date('Y', mktime())));
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
	public function isNonEmptyValidPriceForObjectForSale(array $valueToCheck) {
		return $this->isValidPriceForObjectType(
			$valueToCheck['value'], OBJECT_TYPE_SALE
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
	public function isNonEmptyValidPriceForObjectForRent(array $valueToCheck) {
		$yearRent = $this->getFormValue('year_rent');

		$twoValidValues =
			$this->isValidNumberWithDecimals($valueToCheck)
			&& $this->isValidNumberWithDecimals(array('value' =>$yearRent));

		$oneValueMatchesObjectTypeConditions =
			$this->isValidPriceForObjectType($valueToCheck['value'], OBJECT_TYPE_RENT)
			|| $this->isValidPriceForObjectType($yearRent, OBJECT_TYPE_RENT);

		return $twoValidValues && $oneValueMatchesObjectTypeConditions;
	}

	/**
	 * Checks whether the object number is non-empty and whether the combination
	 * of object number and language is unique in the database.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the entered object number, this number may be empty
	 *
	 * @return	boolean		true if the object number is non empty and unique
	 * 						for the entered language
	 */
	public function isObjectNumberUniqueForLanguage(array $valueToCheck) {
		if ($valueToCheck['value'] == '') {
			return false;
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'object_number, language',
			REALTY_TABLE_OBJECTS,
			'object_number="'.$GLOBALS['TYPO3_DB']->quoteStr(
				$valueToCheck['value'], REALTY_TABLE_OBJECTS
			).'" AND language="'.$GLOBALS['TYPO3_DB']->quoteStr(
				$this->getFormValue('language'), REALTY_TABLE_OBJECTS
			).'"'.$this->enableFields(REALTY_TABLE_OBJECTS)
		);
		if (!$dbResult) {
			throw new Exception('There was an error with the database query.');
		}

		return ($GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult) === false);
	}

	/**
	 * Checks whether the submitted UID for 'city' is actually a database record.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number which is checked to be the UID of an
	 * 						existing record, this number must be integer > 0
	 *
	 * @return	boolean		true if the provided UID is valid, false otherwise
	 */
	public function isAllowedValueForCity(array $valueToCheck) {
		return $this->isIdentifierOfRecord(
			$valueToCheck['value'], REALTY_TABLE_CITIES, false
		);
	}

	/**
	 * Checks whether the submitted UID for 'district' is actually a database
	 * record.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number which is checked to be the UID of an
	 * 						existing record, this number must be integer > 0
	 *
	 * @return	boolean		true if the provided UID is valid, false otherwise
	 */
	public function isAllowedValueForDistrict(array $valueToCheck) {
		return $this->isIdentifierOfRecord(
			$valueToCheck['value'], REALTY_TABLE_DISTRICTS, true
		);
	}

	/**
	 * Checks whether the submitted UID for 'house_type' is actually a database
	 * record.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number which is checked to be the UID of an
	 * 						existing record, this number must be integer > 0
	 *
	 * @return	boolean		true if the provided UID is valid, false otherwise
	 */
	public function isAllowedValueForHouseType(array $valueToCheck) {
		return $this->isIdentifierOfRecord(
			$valueToCheck['value'], REALTY_TABLE_HOUSE_TYPES, true
		);
	}

	/**
	 * Checks whether the submitted UID for 'apartment_type' is actually a
	 * database record.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number which is checked to be the UID of an
	 * 						existing record, this number must be integer > 0
	 *
	 * @return	boolean		true if the provided UID is valid, false otherwise
	 */
	public function isAllowedValueForApartmentType(array $valueToCheck) {
		return $this->isIdentifierOfRecord(
			$valueToCheck['value'], REALTY_TABLE_APARTMENT_TYPES, true
		);
	}

	/**
	 * Checks whether the submitted UID for 'heating_type' is actually a
	 * database record.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number which is checked to be the UID of an
	 * 						existing record, this number must be integer > 0
	 *
	 * @return	boolean		true if the provided UID is valid, false otherwise
	 */
	public function isAllowedValueForHeatingType(array $valueToCheck) {
		return $this->isIdentifierOfRecord(
			$valueToCheck['value'], REALTY_TABLE_HEATING_TYPES, true
		);
	}

	/**
	 * Checks whether the submitted UID for 'garage_type' is actually a database
	 * record.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number which is checked to be the UID of an
	 * 						existing record, this number must be integer > 0
	 *
	 * @return	boolean		true if the provided UID is valid, false otherwise
	 */
	public function isAllowedValueForGarageType(array $valueToCheck) {
		return $this->isIdentifierOfRecord(
			$valueToCheck['value'], REALTY_TABLE_CAR_PLACES, true
		);
	}

	/**
	 * Checks whether the submitted UID for 'state' is actually a database
	 * record.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number which is checked to be the UID of an
	 * 						existing record, this number must be integer > 0
	 *
	 * @return	boolean		true if the provided UID is valid, false otherwise
	 */
	public function isAllowedValueForState(array $valueToCheck) {
		return $this->isIdentifierOfRecord(
			$valueToCheck['value'], REALTY_TABLE_CONDITIONS, true
		);
	}

	/**
	 * Checks whether the submitted UID for 'pets' is actually a database
	 * record.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the number which is checked to be the UID of an
	 * 						existing record, this number must be integer > 0
	 *
	 * @return	boolean		true if the provided UID is valid, false otherwise
	 */
	public function isAllowedValueForPets(array $valueToCheck) {
		return $this->isIdentifierOfRecord(
			$valueToCheck['value'], REALTY_TABLE_PETS, true
		);
	}

	/**
	 * Checks whether the submitted value for 'language' is within the set of
	 * allowed values.
	 *
	 * @param	array		array with one element named "value" that contains
	 * 						the value which is checked to be allowed or empty
	 *
	 * @return	boolean		true if the provided value is valid or empty,
	 * 						false otherwise
	 */
	public function isAllowedValueForLanguage(array $valueToCheck) {
		return $this->isIdentifierOfRecord(
			$valueToCheck['value'], 'static_languages', true, 'lg_iso_2'
		);
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

	/**
	 * Checks whether the provided number is actually an identifying value of a
	 * record in $table.
	 *
	 * @param	string		value to check to be an identifying value of a
	 * 						record in $table
	 * @param	string		table name, must not be empty
	 * @param	boolean		whether $valueToCheck may be empty or zero instead
	 * 						of pointing to an existing record
	 * @param	string		name of the database column in which the provided
	 * 						value is expected to occur, must not be empty
	 */
	private function isIdentifierOfRecord(
		$valueToCheck, $table, $mayBeEmptyOrZero, $databaseColumn = 'uid'
	) {
		if ($mayBeEmptyOrZero
			&& (($valueToCheck === '0') || ($valueToCheck === ''))
		) {
			return true;
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$databaseColumn,
			$table,
			$databaseColumn.'="'
				.$GLOBALS['TYPO3_DB']->quoteStr($valueToCheck, $table).'"'
				.$this->enableFields($table)
		);
		if (!$dbResult) {
			throw new Exception('There was an error with the database query.');
		}

		return (boolean) $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
	}


	//////////////////////////////////
	// * Message creation functions.
	//////////////////////////////////

	/**
	 * Returns a localized message that the provided value is not allowed.
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
	public function getValueNotAllowedMessage(array $formData) {
		return $this->getMessageForRealtyObjectField(
			$formData['fieldName'], 'message_value_not_allowed'
		);
	}

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

		return $this->getMessageForRealtyObjectField(
			$formData['fieldName'], 'message_enter_valid'.$fieldSuffix
		);
	}

	/**
	 * Returns a localized message that the provided field is required to be
	 * non-empty.
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
	public function getRequiredFieldMessage(array $formData) {
		return $this->getMessageForRealtyObjectField(
			$formData['fieldName'], 'message_required_field'
		);
	}

	/**
	 * Returns a localized message that the number entered in the provided field
	 * is not valid.
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
	public function getNoValidNumberMessage(array $formData) {
		return $this->getMessageForRealtyObjectField(
			$formData['fieldName'], 'message_no_valid_number'
		);
	}

	/**
	 * Returns a localized message that the price entered in the provided field
	 * is not valid.
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
	public function getNoValidPriceMessage(array $formData) {
		return $this->getMessageForRealtyObjectField(
			$formData['fieldName'], 'message_no_valid_price'
		);
	}

	/**
	 * Returns a localized message that the year entered in the provided field
	 * is not valid.
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
	public function getNoValidYearMessage(array $formData) {
		return $this->getMessageForRealtyObjectField(
			$formData['fieldName'], 'message_no_valid_year'
		);
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
			'object_number', $message
		);
	}

	/**
	 * Returns a localized message that the entered e-mail is not valid.
	 *
	 * @param	array	 	form data, must contain the key 'fieldName', the
	 * 						value of 'fieldName' must be a database column name
	 * 						of 'tx_realty_objects' which concerns the message,
	 * 						must not be empty
	 *
	 * @return	string		localized message following the pattern
	 * 						"[field name]: [invalid price message]"
	 */
	public function getNoValidEmailMessage() {
		return $this->getMessageForRealtyObjectField(
			'contact_email', 'label_set_valid_email_address'
		);
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
		$GLOBALS['LANG']->lang = $GLOBALS['TSFE']->lang;

		$localizedFieldName = ($labelOfField != '')
			? ($GLOBALS['LANG']->sL($labelOfField).': ')
			: '';

		return $localizedFieldName.$this->plugin->translate($labelOfMessage);
	}

	/**
	 * Returns a localized validation error message.
	 *
	 * @param	string		name of a database column of 'tx_realty_objects'
	 * 						which concerns the the message, must not be empty
	 * @param	string		label of the message to return, must be the absolute
	 * 						path starting with "LLL:EXT:", must not be empty
	 *
	 * @return	string		localized message following the pattern
	 * 						"[field name]: [message]" if $labelOfField was
	 * 						non-empty, otherwise only the message is returned
	 */
	private function getMessageForRealtyObjectField($fieldName, $messageLabel) {
		$labelOfField = $this->isValidFieldName($fieldName)
			? 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects.'.$fieldName
			: '';

		return $this->getMessageForField($labelOfField, $messageLabel);
	}

	/**
	 * Checks whether a provided field name is actually the name of a database
	 * column of tx_realty_objects.
	 *
	 * @param	string		field name to check, must not be empty
	 *
	 * @return	boolean		true if $fieldName is a databse colum name of
	 * 						tx_realty_objects, false otherwise
	 */
	private function isValidFieldName($fieldName) {
		$allowedFieldNames =
			$GLOBALS['TYPO3_DB']->admin_get_fields(REALTY_TABLE_OBJECTS);

		return isset($allowedFieldNames[$fieldName]);
	}


	///////////////////////////////////
	// * Functions used after submit.
	///////////////////////////////////

	/**
	 * Adds administrative data and unifies numbers.
	 *
	 * @see	addAdministrativeData(), unifyNumbersToInsert()
	 *
	 * @param	array 		form data, may be empty
	 *
	 * @return	array		form data with additional administrative data and
	 * 						unified numbers
	 */
	public function modifyDataToInsert(array $formData) {
		return $this->addAdministrativeData(
			$this->unifyNumbersToInsert($formData)
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
			|| !$this->plugin->hasConfValueString('feEditorNotifyEmail')
		) {
			return;
		}

		tx_oelib_mailerFactory::getInstance()->getMailer()->sendEmail(
			$this->plugin->getConfValueString('feEditorNotifyEmail'),
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
	 * @return	string		body for the e-mail to send, will not be
	 * 						empty
	 */
	private function getFilledEmailBody() {
		$frontEndUserData = $this->getFrontEndUserData();
		foreach (array(
			'username' => $frontEndUserData['username'],
			'name' => $frontEndUserData['name'],
			'object_number' => $this->getFormValue('object_number'),
			'title' => $this->getFormValue('title'),
			'uid' => $this->getUidOfNewObject(),
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
		$frontEndUserData = $this->getFrontEndUserData();
		return 'From: "'.$frontEndUserData['name'].'" '
			.'<'.$frontEndUserData['email'].'>'.LF;
	}

	/**
	 * Returns the 'name', 'username' and 'email' of a FE user record.
	 *
	 * Note: This function requires a FE user to be logged in.
	 *
	 * @return	array		associative array with the keys 'name', 'username'
	 * 						and 'email', will be empty if the database result
	 * 						could not be fetched
	 */
	private function getFrontEndUserData() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'username, name, email',
			'fe_users',
			'uid='.$this->getFeUserUid()
		);
		if (!$dbResult) {
			throw new Exception('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if ($result === false) {
			throw new Exception(
				'The FE user data could not be fetched. '
				.'Please ensure a FE user to be logged in.'
			);
		}

		return $result;
	}

	/**
	 * Returns the UID of the realty object that has recently been added to the
	 * database.
	 *
	 * Note: This function is to be used only if there is really a new database
	 * record with the current form data.
	 *
	 * @return	integer		UID of the newly added database record, will be > 0
	 */
	private function getUidOfNewObject() {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			REALTY_TABLE_OBJECTS,
			'object_number="'.$GLOBALS['TYPO3_DB']->quoteStr(
				$this->getFormValue('object_number'), REALTY_TABLE_OBJECTS
			).'" AND language="'.$GLOBALS['TYPO3_DB']->quoteStr(
				$this->getFormValue('language'), REALTY_TABLE_OBJECTS
			).'"'.$this->getWhereClauseForTesting()
		);
		if (!$dbResult) {
			throw new Exception('There was an error with the database query.');
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if ($result === false) {
			throw new Exception(
				'There is no database record with the current form data.'
			);
		}

		return $result['uid'];
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
			'construction_year'
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
			: $this->plugin->getConfValueString('sysFolderForFeCreatedRecords');
		// New records need some additional data.
		if ($this->realtyObjectUid == 0) {
			$modifiedFormData['crdate'] = mktime();
			$modifiedFormData['owner'] = $this->getFeUserUid();
			$modifiedFormData['hidden'] = 1;
		}

		return $modifiedFormData;
	}

	/**
	 * Returns the PID from the field 'save_folder'. This PID defines where to
	 * store records for the city defined by $cityUid.
	 *
	 * @param	integer		UID of the city record from which to get the system
	 * 						folder ID, must be integer > 0
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
			throw new Exception('There was an error with the database query.');
		}

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);

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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndEditor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndEditor.php']);
}

?>
