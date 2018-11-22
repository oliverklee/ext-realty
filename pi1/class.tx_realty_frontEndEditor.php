<?php

use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * This class provides an FE editor the realty plugin.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_frontEndEditor extends tx_realty_frontEndForm
{
    /**
     * @var string[] table names which are allowed as form values
     */
    private static $allowedTables = [
        'tx_realty_cities',
        'tx_realty_districts',
        'tx_realty_apartment_types',
        'tx_realty_house_types',
        'tx_realty_car_places',
        'tx_realty_pets',
        'static_countries',
    ];

    /**
     * @var string[] field keys that are numeric
     */
    private static $numericFields = [
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
    ];

    /**
     * Deletes the currently loaded realty record.
     *
     * Note: This function does not check whether a FE user is authorized.
     *
     * @return void
     */
    public function deleteRecord()
    {
        if ($this->realtyObjectUid === 0) {
            return;
        }

        $this->realtyObject->setToDeleted();
        // Providing the PID ensures the record not to change the location.
        $this->realtyObject->writeToDatabase(
            $this->realtyObject->getProperty('pid')
        );
        tx_realty_cacheManager::clearFrontEndCacheForRealtyPages();
    }

    ////////////////////////////////
    // Functions used by the form.
    ////////////////////////////////
    // * Functions for rendering.
    ///////////////////////////////

    /**
     * Renders the form and remove the "for" attribute of the label if this
     * field is read-only.
     *
     * @param array $unused unused
     *
     * @return string the HTML output for the FE editor, will not be empty
     */
    public function render(array $unused = [])
    {
        $result = parent::render();

        $result = str_replace(
            '###DISTRICT_VISIBILITY###',
            $this->getDistrictVisibility(),
            $result
        );
        if ($this->isObjectNumberReadonly()) {
            $result = str_replace(
                ' for="tx_realty_frontEndEditor_object_number"',
                '',
                $result
            );
        }

        return $result;
    }

    /**
     * Checks whether the object number is readonly.
     *
     * @return bool TRUE if the object number is readonly, FALSE otherwise
     */
    public function isObjectNumberReadonly()
    {
        return $this->realtyObjectUid > 0;
    }

    /**
     * Creates a list of cities.
     *
     * @return array[] items for the city selector, will be empty if there are no cities in the database
     */
    public static function populateCityList()
    {
        $options = [];

        /** @var tx_realty_Mapper_City $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_City::class);
        $cities = $mapper->findAll('title');
        /** @var tx_realty_Model_City $city */
        foreach ($cities as $city) {
            $options[] = [
                'value' => $city->getUid(),
                'caption' => $city->getTitle(),
            ];
        }

        return $options;
    }

    /**
     * Creates a list of districts.
     *
     * @return array[] items for the district selector, will be empty if no city
     *               is selected of there are no districts for the selected city
     */
    public function populateDistrictList()
    {
        $cityUid = $this->getSelectedCityUid();
        if ($cityUid === 0) {
            return [];
        }

        $options = [];

        /** @var tx_realty_Mapper_District $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_District::class);
        $districts = $mapper->findAllByCityUidOrUnassigned($cityUid);
        /** @var tx_realty_Model_District $district */
        foreach ($districts as $district) {
            $options[] = [
                'value' => $district->getUid(),
                'caption' => $district->getTitle(),
            ];
        }

        return $options;
    }

    /**
     * Creates a CSS style rule for showing/hiding the district selector.
     *
     * The district selector is shown if a city is selected. It is hidden if no
     * city is selected.
     *
     * @return string the style rule to hide/show the district selector, will
     *                start with "display:" and end with a semicolon
     */
    protected function getDistrictVisibility()
    {
        return ($this->getSelectedCityUid() > 0)
            ? 'display: table;' : 'display: none;';
    }

    /**
     * Returns the UID of the currently selected city.
     *
     * @return int the UID of the currently selected city, will be >= 0,
     *                 will be 0 if no city is selected
     */
    private function getSelectedCityUid()
    {
        return (int)$this->getFormValue('city');
    }

    /**
     * Provides data items to fill select boxes. Returns caption-value pairs from
     * the database table named $tableName.
     * The field "title" will be returned within the array as caption. The UID
     * will be the value.
     *
     * @param array $formData
     *        Form data, must at least contain one element with the key 'table' and the table name to query as value.
     *        May also have an element 'title_column' where the database column name of the field that will be used as
     *     the title can be defined, If not set, the key 'title' is assumed to be the title. There may also be an
     *     element
     *        'has_dummy_column' which needs to be FALSE if the table has no column 'is_dummy_record'.
     *
     * @return array[] items for the select box, will be empty if there are no
     *               matching records or if the provided table name was invalid
     *
     * @throws Tx_Oelib_Exception_Database
     */
    public function populateList(array $formData)
    {
        $this->checkForValidTableName($formData['table']);

        $titleColumn = (isset($formData['title_column']) && (string)$formData['title_column'] !== '')
            ? $formData['title_column'] : 'title';
        $this->checkForValidFieldName($titleColumn, $formData['table']);

        $hasDummyColumn = Tx_Oelib_Db::tableHasColumn($formData['table'], 'is_dummy_record');
        $databaseConnection = Tx_Oelib_Db::getDatabaseConnection();

        $dbResult = $databaseConnection->exec_SELECTquery(
            $titleColumn . ',uid',
            $formData['table'],
            '1=1' . Tx_Oelib_Db::enableFields($formData['table']) . ($hasDummyColumn ? $this->getWhereClauseForTesting()
                : ''),
            '',
            $titleColumn
        );
        if ($dbResult === false) {
            throw new Tx_Oelib_Exception_Database();
        }

        /** @var array[] $items */
        $items = [];
        while ($row = $databaseConnection->sql_fetch_assoc($dbResult)) {
            $items[] = [
                'value' => $row['uid'],
                'caption' => $row[$titleColumn],
            ];
        }
        $databaseConnection->sql_free_result($dbResult);

        // Resets the array pointer as expected by FORMidable.
        reset($items);

        return $items;
    }

    ////////////////////////////
    // * Validation functions.
    ////////////////////////////

    /**
     * Checks whether a number is a valid non-negative number and does not have
     * decimal digits.
     *
     * @param array $formData
     *        array with one element named "value" that contains the number to check, this number may also be empty
     *
     * @return bool TRUE if the number is a non-negative integer or empty
     */
    public function isValidNonNegativeIntegerNumber(array $formData)
    {
        return $this->isValidNumber($formData['value'], false);
    }

    /**
     * Checks whether a number is valid and does not have decimal digits.
     *
     * @param array $formData
     *        array with one element named "value" that contains the number to check, this number may also be empty
     *
     * @return bool TRUE if the number is an integer or empty
     */
    public function isValidIntegerNumber(array $formData)
    {
        $value = strncmp($formData['value'], '-', 1) === 0 ? substr($formData['value'], 1) : $formData['value'];

        return $this->isValidNumber($value, false);
    }

    /**
     * Checks whether a number which may have decimal digits is valid.
     *
     * @param array $formData
     *        array with one element named "value" that contains the number to check, this number may also be empty
     *
     * @return bool TRUE if the number is valid or empty
     */
    public function isValidNumberWithDecimals(array $formData)
    {
        return $this->isValidNumber($formData['value'], true);
    }

    /**
     * Checks whether a form data value is within a range of allowed integers.
     * The provided form data array must contain the keys 'value' and 'range'.
     * 'range' must be two integers separated by '-'.
     *
     * @param array $formData
     *        array with the elements 'value', 'range' 'value' is the form data value to check and can be empty,
     *        'range' must be two integers separated by '-'
     *
     * @return bool
     */
    public function isIntegerInRange(array $formData)
    {
        $value = $formData['value'];
        if ($value === '') {
            return true;
        }
        if (!$this->isValidNonNegativeIntegerNumber(['value' => $value])) {
            return false;
        }

        $range = GeneralUtility::trimExplode('-', $formData['range'], true);

        return $value >= min($range) && $value <= max($range);
    }

    /**
     * Checks whether the provided year is this year or earlier.
     *
     * @param array $formData
     *        array with one element named "value" that contains the year to check, this must be this year or earlier
     *        or empty
     *
     * @return bool
     */
    public function isValidYear(array $formData)
    {
        return $this->isValidNumber($formData['value'], false);
    }

    /**
     * Checks whether the price is non-empty and valid if the object is for sale.
     *
     * @param array $formData
     *        array with one element named "value" that contains the price to check for non-emptiness if an object is
     *        for sale
     *
     * @return bool TRUE if the price is valid and non-empty, also TRUE if
     *                 the price is valid or empty if the object is for rent
     */
    public function isNonEmptyValidPriceForObjectForSale(array $formData)
    {
        return $this->isValidPriceForObjectType(
            $formData['value'],
            tx_realty_Model_RealtyObject::TYPE_FOR_SALE
        );
    }

    /**
     * Checks whether the price is non-empty and valid if the object is for rent.
     *
     * Note: This function is used in the renderlet for 'rent_excluding_bills'
     * but also checks "year_rent" and "rent_including_heating_costs" as at least one of these fields is
     * required to be filled for an object to rent.
     *
     * @param array $formData array with one element named "value" that contains the price to check
     *
     * @return bool if the object is for rent, TRUE is returned if at
     *                 least one of the prices is non-empty and both are
     *                 valid or empty, if the object is for sale, TRUE is
     *                 returned if both prices are valid or empty,
     *                 otherwise the result is FALSE
     */
    public function isNonEmptyValidPriceForObjectForRent(array $formData)
    {
        $yearRent = $this->getFormValue('year_rent');
        $rentWithHeatingCosts = $this->getFormValue('rent_with_heating_costs');

        $allValuesAreValid =
            $this->isValidNumberWithDecimals($formData)
            && $this->isValidNumberWithDecimals(['value' => $yearRent])
            && $this->isValidNumberWithDecimals(['value' => $rentWithHeatingCosts]);

        $atLeastOneNoneEmptyValue =
            $this->isValidPriceForObjectType($formData['value'], tx_realty_Model_RealtyObject::TYPE_FOR_RENT)
            || $this->isValidPriceForObjectType($yearRent, tx_realty_Model_RealtyObject::TYPE_FOR_RENT)
            || $this->isValidPriceForObjectType($rentWithHeatingCosts, tx_realty_Model_RealtyObject::TYPE_FOR_RENT);

        return $allValuesAreValid && $atLeastOneNoneEmptyValue;
    }

    /**
     * Checks whether the object number is non-empty and whether the combination
     * of object number and language is unique in the database.
     *
     * Always returns TRUE if an existing object is edited.
     *
     * @param array $formData
     *        array with one element named "value" that contains the entered object number, this number may be empty
     *
     * @return bool TRUE if the object number is non empty and unique for the entered language,
     *                 also TRUE if the object already exists in the database
     *
     * @throws Tx_Oelib_Exception_Database
     */
    public function isObjectNumberUniqueForLanguage(array $formData)
    {
        // FE users cannot change the object number of existing objects anyway.
        if ($this->realtyObjectUid > 0) {
            return true;
        }
        // Empty object numbers are not allowed.
        if ((string)$formData['value'] === '') {
            return false;
        }

        $databaseConnection = Tx_Oelib_Db::getDatabaseConnection();
        $dbResult = $databaseConnection->exec_SELECTquery(
            'language',
            'tx_realty_objects',
            'object_number="' .
            $databaseConnection->quoteStr($formData['value'], 'tx_realty_objects') . '"' .
            Tx_Oelib_Db::enableFields('tx_realty_objects', 1) . $this->getWhereClauseForTesting()
        );
        if ($dbResult === false) {
            throw new Tx_Oelib_Exception_Database();
        }

        $languages = [];
        while ($row = $databaseConnection->sql_fetch_assoc($dbResult)) {
            $languages[] = $row['language'];
        }
        $databaseConnection->sql_free_result($dbResult);

        // Initially, new objects will always have an empty language because
        // FE users cannot set the language.
        return !in_array('', $languages, true);
    }

    /**
     * Checks whether the provided number is a UID in the provided table or zero
     * if this should be allowed.
     *
     * @param array $formData
     *        array with the elements 'value' which contains the value to check to be an identifying value of a record
     *     and 'table' which contains the name of the corresponding database table and must not be empty
     * @param bool $mayBeEmptyOrZero
     *        TRUE if the value to check may be empty or zero instead of pointing to an existing record, FALSE
     *     otherwise
     *
     * @return bool TRUE if the form data value is actually the UID of
     *                 a record in a valid table, FALSE otherwise
     *
     * @throws Tx_Oelib_Exception_Database
     */
    public function checkKeyExistsInTable(array $formData, $mayBeEmptyOrZero = true)
    {
        $this->checkForValidTableName($formData['table']);
        if ($mayBeEmptyOrZero && (($formData['value'] === '0') || ($formData['value'] === ''))) {
            return true;
        }

        $databaseConnection = Tx_Oelib_Db::getDatabaseConnection();
        $dbResult = $databaseConnection->exec_SELECTquery(
            'uid',
            $formData['table'],
            'uid="' . $databaseConnection->quoteStr($formData['value'], $formData['table']) . '"' .
            Tx_Oelib_Db::enableFields($formData['table'])
        );
        if ($dbResult === false) {
            throw new Tx_Oelib_Exception_Database();
        }

        $result = $databaseConnection->sql_fetch_assoc($dbResult) !== false;
        $databaseConnection->sql_free_result($dbResult);

        return $result;
    }

    /**
     * Checks whether the submitted UID for 'city' is actually a database record
     * or zero. If the UID is zero, there must be a value provided in 'new_city'.
     *
     * @param array $formData
     *        array with one element named "value" that contains the number which is checked to be the UID of an
     *     existing record, This number must be an integer >= 0
     *
     * @return bool TRUE if the provided UID is valid or if there is a
     *                 value in 'new_city', FALSE otherwise
     */
    public function isAllowedValueForCity(array $formData)
    {
        $mayBeEmpty = $this->getFormValue('new_city') !== '';

        return $this->checkKeyExistsInTable(
            [
                'value' => $formData['value'],
                'table' => 'tx_realty_cities',
            ],
            $mayBeEmpty
        );
    }

    /**
     * Checks whether no existing record is selected if a new record title is
     * provided. Returns always TRUE if no new record title is provided.
     *
     * @param array $formData
     *        form data with one element named 'value' that contains the title for the new record or may be empty and
     *     one element
     *        'fieldName' where the key used in tx_realty_objets for this record is defined and must not be empty
     *
     * @return bool TRUE if the value for 'fieldName' is empty when
     *                 there is a value for 'value' provided, also TRUE if
     *                 'value' is empty, FALSE otherwise
     */
    public function isAtMostOneValueForAuxiliaryRecordProvided(array $formData)
    {
        return (string)$formData['value'] === '' || (int)$this->getFormValue($formData['fieldName']) === 0;
    }

    /**
     * Checks whether there is no existing city record selected at the same time
     * a new one should be created.
     *
     * @param array $valueToCheck
     *        array with one element named "value" that contains the value which contains the string for the new city
     *     record
     *
     * @return bool TRUE if no existing city record is selected or if
     *                 the string for the new city record is empty
     */
    public function isAtMostOneValueForCityRecordProvided(array $valueToCheck)
    {
        return $this->isAtMostOneValueForAuxiliaryRecordProvided($valueToCheck['value']);
    }

    /**
     * Checks whether the provided value is non-empty or the owner's data is
     * chosen as contact data source.
     *
     * @param array $formData array with one element named "value" that contains the value which contains the string to
     *     check
     *
     * @return bool TRUE if the provided value is non-empty or if the contact
     *                 data source is the owner's account, FALSE otherwise
     */
    public function isNonEmptyOrOwnerDataUsed(array $formData)
    {
        if ((int)$this->getFormValue('contact_data_source')
            === \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT
        ) {
            return true;
        }

        return (string)$formData['value'] !== '';
    }

    /**
     * Checks whether a longitute degree is correctly formatted and within
     * range.
     *
     * Empty values are considered valid.
     *
     * @param array $formData array with one element named "value" that contains the value which contains the string to
     *     check
     *
     * @return bool TRUE if $formData['value'] is valid, FALSE otherwise
     */
    public function isValidLongitudeDegree(array $formData)
    {
        return $this->checkGeoCoordinate(
            $formData['value'],
            -180.00,
            180.00
        );
    }

    /**
     * Checks whether a latitude degree is correctly formatted and within range.
     *
     * Empty values are considered valid.
     *
     * @param array $formData array with one element named "value" that contains the value which contains the string to
     *     check
     *
     * @return bool TRUE if $formData['value'] is valid, FALSE otherwise
     */
    public function isValidLatitudeDegree(array $formData)
    {
        return $this->checkGeoCoordinate($formData['value'], -90.00, 90.00);
    }

    /**
     * Checks whether a geo coordinate is correctly formatted and within range.
     *
     * Empty values are considered valid.
     *
     * @param string $valueToCheck the input data that should checked, may be empty
     * @param float $minimum mininum allowed value
     * @param float $maximum maximum allowed value
     *
     * @return bool TRUE if $valueToCheck is valid or empty, FALSE otherwise
     */
    private function checkGeoCoordinate($valueToCheck, $minimum, $maximum)
    {
        if ((string)$valueToCheck === '') {
            return true;
        }

        $unifiedValueToCheck = $this->unifyNumber((string)$valueToCheck);

        $valueContainsOnlyAllowedCharacters = (bool)preg_match(
            '/^-?\\d{1,3}(\\.\\d{1,14})?$/',
            $unifiedValueToCheck
        );
        $valueIsInAllowedRange = ((float)$unifiedValueToCheck >= $minimum) && ((float)$unifiedValueToCheck <= $maximum);

        return $valueContainsOnlyAllowedCharacters && $valueIsInAllowedRange;
    }

    /**
     * Checks whether the a number is correctly formatted. The format must be
     * according to the current locale.
     *
     * @param string $valueToCheck value to check to be a valid number, may be empty
     * @param bool $mayHaveDecimals whether the number may have decimals
     *
     * @return bool
     */
    private function isValidNumber($valueToCheck, $mayHaveDecimals)
    {
        if ($valueToCheck === '') {
            return true;
        }

        $unifiedValueToCheck = $this->unifyNumber($valueToCheck);
        if ($mayHaveDecimals) {
            $result = (bool)preg_match('/^[\\d]*(\\.[\\d]{1,2})?$/', $unifiedValueToCheck);
        } else {
            $result = (bool)preg_match('/^[\\d]*$/', $unifiedValueToCheck);
        }

        return $result;
    }

    /**
     * Checks whether $price depending on the object type and $typeOfField is
     * either a valid price and non-empty or a valid price or empty.
     *
     * @param string $price price to validate, may be empty
     * @param int $typeOfField one if the price was entered as a buying price, zero if it derived from a field for rent
     *
     * @return bool TRUE if the object type and $typeOfField match and
     *                 $price is non-empty and valid, also TRUE if object
     *                 type and $typeOfField do not match and $price is
     *                 valid or empty
     */
    private function isValidPriceForObjectType($price, $typeOfField)
    {
        if ($this->getObjectType() === $typeOfField) {
            $result = $this->isValidNumber($price, true) && $price !== '';
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
     * @param array $formData
     *        form data, must contain the key 'fieldName', the value of 'fieldName' must be a database column name of
     *        'tx_realty_objects' which concerns the message, must not be empty
     *
     * @return string localized message following the pattern
     *                "[field name]: [message]" if $labelOfField was
     *                non-empty, otherwise only the message is returned
     */
    public function getNoValidPriceOrEmptyMessage(array $formData)
    {
        $isObjectToBuy = $this->getObjectType() === \tx_realty_Model_RealtyObject::TYPE_FOR_SALE;
        $isFieldForBuying = $formData['fieldName'] === 'buying_price';

        $fieldSuffix = $isFieldForBuying === $isObjectToBuy ? '_non_empty' : '_or_empty';
        $fieldSuffix .= $isFieldForBuying ? '_buying_price' : '_rent';

        return $this->getMessageForRealtyObjectField([
            'fieldName' => $formData['fieldName'],
            'label' => 'message_enter_valid' . $fieldSuffix,
        ]);
    }

    /**
     * Returns a localized message that the object number is empty or that it
     * already exists in the database.
     *
     * @return string localized message following the pattern
     *                "[field name]: [message]" if $labelOfField was
     *                non-empty, otherwise only the message is returned
     */
    public function getInvalidObjectNumberMessage()
    {
        if ($this->getFormValue('object_number') === '') {
            $message = 'message_required_field';
        } else {
            $message = 'message_object_number_exists';
        }

        return $this->getMessageForRealtyObjectField(
            ['fieldName' => 'object_number', 'label' => $message]
        );
    }

    /**
     * Returns a localized message that either the entered value for city is not
     * valid or that it must not be empty.
     *
     * @return string localized message following the pattern
     *                "[field name]: [invalid message]"
     */
    public function getInvalidOrEmptyCityMessage()
    {
        return $this->getMessageForRealtyObjectField(
            [
                'fieldName' => 'city',
                'label' => (int)$this->getFormValue('city') === 0
                    ? 'message_required_field'
                    : 'message_value_not_allowed',
            ]
        );
    }

    /**
     * Returns a localized validation error message.
     *
     * @param array $formData
     *        Form data, must contain the elements 'fieldName' and 'label'.The value of 'fieldName' must be a database
     *     column name of 'tx_realty_objects' which concerns the message and must not be empty. The element 'label'
     *     defines the label of the message to return and must be a key defined in
     *     Resources/Private/Language/locallang.xlf.
     *
     * @return string localized message following the pattern
     *                "[field name]: [message]", in case no valid field
     *                name was provided, only the message is returned, if
     *                the label for the message was invalid, the message
     *                will always be "value not allowed"
     */
    public function getMessageForRealtyObjectField(array $formData)
    {
        // This  will lead to an exception for an invalid non-empty field name.
        $labelOfField = $this->checkForValidFieldName(
            $formData['fieldName'],
            'tx_realty_objects',
            true
        ) ? 'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:' . 'tx_realty_objects' . '.' .
            $formData['fieldName']
            : '';
        // This will cause an exception if the locallang key was invalid.
        $this->checkForValidLocallangKey($formData['label']);

        return $this->getMessageForField($labelOfField, $formData['label']);
    }

    /**
     * Returns a localized message for a certain field.
     *
     * @param string $labelOfField
     *        label of the field which concerns the the message, must be the absolute path starting with "LLL:EXT:",
     *     may be empty
     * @param string $labelOfMessage
     *        label of the message to return, must be defined in Resources/Private/Language/locallang.xlf, must not be
     *     empty
     *
     * @return string localized message following the pattern
     *                "[field name]: [message]" if $labelOfField was
     *                non-empty, otherwise only the message is returned
     */
    private function getMessageForField($labelOfField, $labelOfMessage)
    {
        $localizedFieldName = $labelOfField !== ''
            ? ($this->getFrontEndController()->sL($labelOfField) . ': ')
            : '';

        return $localizedFieldName . $this->translate($labelOfMessage);
    }

    /**
     * Checks whether a locallang key contains only allowed characters. If not,
     * an exception will be thrown.
     *
     * @param string $label locallang key to check, must not be empty
     *
     * @return bool TRUE if the provided locallang key only consists of
     *                 allowed characters, otherwise an exception is thrown
     */
    private function checkForValidLocallangKey($label)
    {
        if (!preg_match('/^([a-z_])+$/', $label)) {
            throw new InvalidArgumentException('"' . $label . '" is not a valid locallang key.', 1333036148);
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
     * @see addAdministrativeData(), unifyNumbersToInsert(),
     *      storeNewAuxiliaryRecords(), purgeNonRealtyObjectFields()
     *
     * @param array $formData form data, must not be empty
     *
     * @return array form data with additional administrative data and
     *               unified numbers
     */
    public function modifyDataToInsert(array $formData)
    {
        $modifiedFormData = $formData;

        $this->storeNewAuxiliaryRecords($modifiedFormData);
        $this->purgeNonRealtyObjectFields($modifiedFormData);
        $this->unifyNumbersToInsert($modifiedFormData);
        $this->addAdministrativeData($modifiedFormData);

        return $modifiedFormData;
    }

    /**
     * Sends an e-mail if a new object has been created.
     *
     * Clears the FE cache for pages with the realty plugin.
     *
     * @return void
     */
    public function sendEmailForNewObjectAndClearFrontEndCache()
    {
        $this->sendEmailForNewObject();
        tx_realty_cacheManager::clearFrontEndCacheForRealtyPages();
    }

    /**
     * Sends an e-mail if a new object has been created.
     *
     * @return void
     */
    private function sendEmailForNewObject()
    {
        if (($this->realtyObjectUid > 0) || !$this->hasConfValueString('feEditorNotifyEmail', 's_feeditor')) {
            return;
        }

        /** @var MailMessage $email */
        $email = GeneralUtility::makeInstance(MailMessage::class);
        $email->setTo([$this->getConfValueString('feEditorNotifyEmail', 's_feeditor') => '']);
        $email->setSubject($this->translate('label_email_subject_fe_editor'));
        $email->setBody($this->getFilledEmailBody());

        $user = Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser('tx_realty_Mapper_FrontEndUser');
        $email->setReplyTo([$user->getEmailAddress() => $user->getName()]);

        /** @var array $emailConfiguration */
        $emailConfiguration = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];
        $email->setFrom([$emailConfiguration['defaultMailFromAddress'] => $emailConfiguration['defaultMailFromName']]);

        $email->send();
    }

    /**
     * Returns the e-mail body formatted according to the template and filled
     * with the new object's summarized data.
     *
     * Note: The e-mail body will only contain the correct UID if the record
     * this e-mail is about is the last record that was added to the database.
     *
     * @return string body for the e-mail to send, will not be empty
     */
    private function getFilledEmailBody()
    {
        $user = Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser('tx_realty_Mapper_FrontEndUser');

        $insertId = Tx_Oelib_Db::getDatabaseConnection()->sql_insert_id();
        foreach (
            [
                'username' => $user->getUserName(),
                'name' => $user->getName(),
                'object_number' => $this->getFormValue('object_number'),
                'title' => $this->getFormValue('title'),
                'uid' => $insertId,
            ] as $marker => $value) {
            $this->setOrDeleteMarkerIfNotEmpty($marker, $value, '', 'wrapper');
        }

        return $this->getSubpart('FRONT_END_EDITOR_EMAIL');
    }

    /**
     * Removes all form data elements that are not fields in the realty objects
     * table, for example the fields named "new_*" which are used to add new
     * auxiliary records.
     *
     * @param array &$formData form data, will be modified, must not be empty
     *
     * @return void
     */
    private function purgeNonRealtyObjectFields(array &$formData)
    {
        foreach (array_keys($formData) as $key) {
            if (!Tx_Oelib_Db::tableHasColumn('tx_realty_objects', $key)) {
                unset($formData[$key]);
            }
        }
    }

    /**
     * Stores new auxiliary records in the database if there are any in the
     * provided form data and modifies the form data.
     *
     * The UIDs of the new records are written to the form data.
     *
     * @param array &$formData form data, will be modified, must not be empty
     *
     * @return void
     */
    private function storeNewAuxiliaryRecords(array &$formData)
    {
        $table = 'tx_realty_cities';
        $key = 'city';

        $title = trim($formData['new_' . $key]);
        if ($title !== '' && (int)$formData[$key] === 0) {
            $uid = $this->getUidIfAuxiliaryRecordExists($title, $table);

            if ($uid === 0) {
                $uid = $this->createNewAuxiliaryRecord($title, $table);
            }

            $formData[$key] = $uid;
        }
    }

    /**
     * Returns the UID of an auxiliary record's title or zero if it does not
     * exist.
     *
     * @param string $title title of an auxiliary record to search, must not be empty
     * @param string $table table where to search this title, must not be empty
     *
     * @return int UID of the record with the title to search or zero if there is no record with this title
     *
     * @throws Tx_Oelib_Exception_Database
     */
    private function getUidIfAuxiliaryRecordExists($title, $table)
    {
        $databaseConnection = Tx_Oelib_Db::getDatabaseConnection();
        $dbResult = $databaseConnection->exec_SELECTquery(
            'uid',
            $table,
            'title="' . $databaseConnection->quoteStr($title, $table) . '"' . $this->getWhereClauseForTesting()
        );
        if ($dbResult === false) {
            throw new Tx_Oelib_Exception_Database();
        }

        $result = $databaseConnection->sql_fetch_assoc($dbResult);
        $databaseConnection->sql_free_result($dbResult);

        return ($result !== false) ? $result['uid'] : 0;
    }

    /**
     * Inserts a new auxiliary record into the database.
     *
     * @param string $title title of an auxiliary record to create, must not be empty
     * @param string $table table where to add this title, must not be empty
     *
     * @return int UID of the new record, will be > 0
     */
    private function createNewAuxiliaryRecord($title, $table)
    {
        return Tx_Oelib_Db::insert(
            $table,
            [
                'title' => $title,
                'pid' => self::getPageIdForAuxiliaryRecords(),
                'tstamp' => time(),
                'crdate' => time(),
                'is_dummy_record' => $this->isTestMode,
            ]
        );
    }

    /**
     * Gets the page ID for new auxiliary records from the configuration.
     *
     * @return int the page ID for new auxiliary records, will be >= 0
     */
    private static function getPageIdForAuxiliaryRecords()
    {
        return Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
            ->getAsInteger('sysFolderForFeCreatedAuxiliaryRecords');
    }

    /**
     * Unifies all numbers before they get inserted into the database.
     *
     * @param array &$formData form data, will be modified, must not be empty
     *
     * @return void
     */
    private function unifyNumbersToInsert(array &$formData)
    {
        foreach (self::$numericFields as $key) {
            if (isset($formData[$key])) {
                $formData[$key] = $this->unifyNumber($formData[$key]);
            }
        }
        // ensures the object type is always 'rent' or 'sale'
        $formData['object_type'] = $this->getObjectType();
    }

    /**
     * Adds some values to the form data before insertion into the database.
     * Added values for new objects are: 'crdate', 'tstamp', 'pid' and 'owner'.
     * In addition they become marked as 'hidden'.
     * For objects to update, just the 'tstamp' will be refreshed.
     *
     * @param array &$formData form data, will be modified, must not be empty
     *
     * @return void
     */
    private function addAdministrativeData(array &$formData)
    {
        $formData['tstamp'] = time();

        // New records need some additional data.
        if ($this->realtyObjectUid === 0) {
            /** @var tx_realty_Model_FrontEndUser $user */
            $user = Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser('tx_realty_Mapper_FrontEndUser');

            $formData['hidden'] = 1;
            $formData['crdate'] = time();
            $formData['owner'] = $user->getUid();
            $formData['openimmo_anid'] = $user->getOpenImmoOffererId();
            $formData['pid'] = $this->getConfValueString(
                'sysFolderForFeCreatedRecords',
                's_feeditor'
            );
        }

        // The PID might change also for existing records if the city changes
        // and 'save_folder' is defined in the city record.
        $pidFromCity = $this->getPidFromCityRecord((int)$formData['city']);
        if ($pidFromCity !== 0) {
            $formData['pid'] = $pidFromCity;
        }
    }

    /**
     * Returns the PID from the field 'save_folder'. This PID defines where to
     * store records for the city defined by $cityUid.
     *
     * @param int $cityUid UID of the city record from which to get the system folder ID, must be an integer > 0
     *
     * @return int UID of the system folder where to store this city's records, will be zero if no folder was set
     *
     * @throws Tx_Oelib_Exception_Database
     */
    private function getPidFromCityRecord($cityUid)
    {
        $databaseConnection = Tx_Oelib_Db::getDatabaseConnection();
        $dbResult = $databaseConnection->exec_SELECTquery(
            'save_folder',
            'tx_realty_cities',
            'uid=' . $cityUid
        );
        if ($dbResult === false) {
            throw new Tx_Oelib_Exception_Database();
        }

        $row = $databaseConnection->sql_fetch_assoc($dbResult);
        $databaseConnection->sql_free_result($dbResult);

        return (int)$row['save_folder'];
    }

    ////////////////////////////////////
    // Miscellaneous helper functions.
    ////////////////////////////////////

    /**
     * Unifies a number.
     *
     * Replaces a comma by a dot and strips whitespaces.
     *
     * @param string $number number to be unified, may be empty
     *
     * @return string unified number with a dot as decimal separator, will
     *                be empty if $number was empty
     */
    private function unifyNumber($number)
    {
        if ((string)$number === '') {
            return '';
        }

        return str_replace([',', ' '], ['.', ''], $number);
    }

    /**
     * Returns the current object type.
     *
     * @return int one if the object is for sale, zero if it is for rent
     */
    private function getObjectType()
    {
        return MathUtility::forceIntegerInRange(
            $this->getFormValue('object_type'),
            tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
            tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
            tx_realty_Model_RealtyObject::TYPE_FOR_RENT
        );
    }

    /**
     * Checks whether a provided field name is actually the name of a database
     * column of $tableName. The result will be TRUE if the field name is valid,
     * otherwise, an exception will be thrown. Only if $noExceptionIfEmpty is
     * set to TRUE, the result will just be FALSE for an empty field name.
     *
     * @param string $fieldName field name to check, may be empty
     * @param string $tableName table name, must be a valid database table name, will be tx_realty_objects if no other
     *     table is set
     * @param bool $noExceptionIfEmpty TRUE if the the field name to check may be empty, FALSE otherwise
     *
     * @return bool TRUE if $fieldName is a database colum name of the
     *                 realty objects table and non-empty, FALSE otherwise
     */
    private function checkForValidFieldName(
        $fieldName,
        $tableName = 'tx_realty_objects',
        $noExceptionIfEmpty = false
    ) {
        if ($noExceptionIfEmpty && trim($fieldName) === '') {
            return false;
        }

        if (!Tx_Oelib_Db::tableHasColumn($tableName, $fieldName)) {
            throw new InvalidArgumentException(
                '"' . $fieldName . '" is not a valid column name for ' . $tableName . '.',
                1333036182
            );
        }

        return true;
    }

    /**
     * Checks whether a table name is within the list of allowed table names.
     * Throws an exception it is not.
     *
     * @param string $tableName table name to check, must not be empty
     *
     * @return bool TRUE if the table name is allowed, an exception is thrown otherwise
     */
    private function checkForValidTableName($tableName)
    {
        if (!in_array($tableName, self::$allowedTables)) {
            throw new InvalidArgumentException('"' . $tableName . '" is not a valid table name.', 1333036203);
        }

        return true;
    }

    ///////////////////////////////////
    // Utility functions for testing.
    ///////////////////////////////////

    /**
     * Fakes that mkforms has inserted a new record into the database.
     *
     * This function writes the array of faked form values to the database and
     * is for testing purposes.
     *
     * @return void
     */
    public function writeFakedFormDataToDatabase()
    {
        // The faked record is marked as a test record and no fields are
        // required to be set.
        $this->setFakedFormValue('is_dummy_record', 1);
        $this->realtyObject = GeneralUtility::makeInstance(\tx_realty_Model_RealtyObject::class, $this->isTestMode);
        $this->realtyObject->setRequiredFields([]);
        $this->realtyObject->loadRealtyObject($this->fakedFormValues);
        $this->realtyObject->writeToDatabase();
    }

    /**
     * Returns a WHERE clause part for the test mode. So only dummy records will
     * be received for testing.
     *
     * @return string WHERE clause part for testing starting with ' AND' if the
     *                test mode is enabled, an empty string otherwise
     */
    private function getWhereClauseForTesting()
    {
        return $this->isTestMode ? ' AND is_dummy_record=1' : '';
    }
}
