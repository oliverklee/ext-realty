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
 * Unit tests for the tx_realty_frontEndEditor class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_testingFramework.php');

require_once(t3lib_extMgm::extPath('realty').'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty').'lib/class.tx_realty_object.php');
require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_pi1.php');
require_once(t3lib_extMgm::extPath('realty').'tests/fixtures/class.tx_realty_frontEndEditorChild.php');

class tx_realty_frontEndEditor_testcase extends tx_phpunit_testcase {
	/** FE editor object to be tested */
	private $fixture;
	/** instance of tx_realty_pi1 */
	private $pi1;
	/** instance of tx_oelib_testingFramework */
	private $testingFramework;

	/** dummy FE user ID */
	private $feUserId;
	/** UID of the dummy object */
	private $dummyObjectUid = 0;
	/** dummy string value */
	private static $dummyStringValue = 'test value';

	public function setUp() {
		// Bolster up the fake front end.
		$GLOBALS['TSFE']->tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$GLOBALS['TSFE']->tmpl->flattenSetup(array(), '', false);
		$GLOBALS['TSFE']->tmpl->init();
		$GLOBALS['TSFE']->tmpl->getCurrentPageData();
		$GLOBALS['LANG']->lang = $GLOBALS['TSFE']->config['config']['language'];

		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->createDummyRecords();

		$this->pi1 = new tx_realty_pi1();
		$this->pi1->init(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm')
		);

		$this->fixture = new tx_realty_frontEndEditorChild($this->pi1, 0, true);
	}

	public function tearDown() {
		$this->testingFramework->logoutFrontEndUser();
		$this->testingFramework->cleanUp();
		unset($this->fixture, $this->pi1, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy records in the DB.
	 */
	private function createDummyRecords() {
		$this->feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->dummyObjectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => self::$dummyStringValue,
				'language' => self::$dummyStringValue
			)
		);
		$this->createAuxiliaryRecords();
	}

	/**
	 * Creates one dummy record in each table for auxiliary records.
	 */
	private function createAuxiliaryRecords() {
		$realtyObject = new tx_realty_object(true);
		$realtyObject->loadRealtyObject($this->dummyObjectUid);

		foreach (array(
			'city' => REALTY_TABLE_CITIES,
			'district' => REALTY_TABLE_DISTRICTS,
			'apartment_type' => REALTY_TABLE_APARTMENT_TYPES,
			'house_type' => REALTY_TABLE_HOUSE_TYPES,
			'heating_type' => REALTY_TABLE_HEATING_TYPES,
			'garage_type' => REALTY_TABLE_CAR_PLACES,
			'pets' => REALTY_TABLE_PETS,
			'state' => REALTY_TABLE_CONDITIONS
		) as $key => $table) {
			$realtyObject->setProperty($key, self::$dummyStringValue);
			$this->testingFramework->markTableAsDirty($table);
		}

		$realtyObject->writeToDatabase();
	}


	///////////////////////////////////////////////
	// Tests concerning access and authorization.
	///////////////////////////////////////////////

	public function testCheckAccessReturnsObjectDoesNotExistMessageForAnInvalidUidAndNoUserLoggedIn() {
		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid + 1);

		$this->assertContains(
			$this->pi1->translate('message_noResultsFound_fe_editor'),
			$this->fixture->checkAccess()
		);
	}

	public function testCheckAccessReturnsObjectDoesNotExistMessageForAnInvalidUidAndAUserLoggedIn() {
		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->testingFramework->loginFrontEndUser($this->feUserId);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid + 1);

		$this->assertContains(
			$this->pi1->translate('message_noResultsFound_fe_editor'),
			$this->fixture->checkAccess()
		);
	}

	public function testCheckAccessReturnsPleaseLoginMessageForANewObjectIfNoUserIsLoggedIn() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertContains(
			$this->pi1->translate('message_please_login'),
			$this->fixture->checkAccess()
		);
	}

	public function testCheckAccessReturnsPleaseLoginMessageForAnExistingObjectIfNoUserIsLoggedIn() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertContains(
			$this->pi1->translate('message_please_login'),
			$this->fixture->checkAccess()
		);
	}

	public function testCheckAccessReturnsAccessDeniedMessageWhenLoggedInUserAttemptsToEditAnObjectHeDoesNotOwn() {
		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->testingFramework->loginFrontEndUser(
			$this->testingFramework->createFrontEndUser(
				$this->testingFramework->createFrontEndUserGroup()
			)
		);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertContains(
			$this->pi1->translate('message_access_denied'),
			$this->fixture->checkAccess()
		);
	}

	public function testCheckAccessReturnsAnEmptyStringIfTheObjectExistsAndTheUserIsAuthorized() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->dummyObjectUid,
			array('owner' => $this->feUserId)
		);
		$this->testingFramework->loginFrontEndUser($this->feUserId);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertEquals(
			'',
			$this->fixture->checkAccess()
		);
	}

	public function testCheckAccessReturnsAnEmptyStringIfTheObjectIsNewAndTheUserIsAuthorized() {
		$this->testingFramework->loginFrontEndUser($this->feUserId);
		$this->fixture->setRealtyObjectUid(0);

		$this->assertEquals(
			'',
			$this->fixture->checkAccess()
		);
	}


	/////////////////////////////////////
	// Tests concerning deleteRecord().
	/////////////////////////////////////

	public function testDeleteRecordReturnsObjectDoesNotExistMessageForAnInvalidUidAndNoUserLoggedIn() {
		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid + 1);

		$this->assertContains(
			$this->pi1->translate('message_noResultsFound_fe_editor'),
			$this->fixture->deleteRecord()
		);
	}

	public function testDeleteRecordReturnsObjectDoesNotExistMessageForAnInvalidUidAndAUserLoggedIn() {
		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->testingFramework->loginFrontEndUser($this->feUserId);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid + 1);

		$this->assertContains(
			$this->pi1->translate('message_noResultsFound_fe_editor'),
			$this->fixture->deleteRecord()
		);
	}

	public function testDeleteRecordReturnsPleaseLoginMessageForANewObjectIfNoUserIsLoggedIn() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertContains(
			$this->pi1->translate('message_please_login'),
			$this->fixture->deleteRecord()
		);
	}

	public function testDeleteRecordReturnsPleaseLoginMessageForAnExistingObjectIfNoUserIsLoggedIn() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertContains(
			$this->pi1->translate('message_please_login'),
			$this->fixture->deleteRecord()
		);
	}

	public function testDeleteRecordReturnsAccessDeniedMessageWhenLoggedInUserAttemptsToDeleteAnObjectHeDoesNotOwn() {
		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->testingFramework->loginFrontEndUser(
			$this->testingFramework->createFrontEndUser(
				$this->testingFramework->createFrontEndUserGroup()
			)
		);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertContains(
			$this->pi1->translate('message_access_denied'),
			$this->fixture->deleteRecord()
		);
	}

	public function testDeleteRecordReturnsAnEmptyStringWhenUserAuthorizedAndUidZero() {
		$this->testingFramework->loginFrontEndUser($this->feUserId);
		$this->fixture->setRealtyObjectUid(0);

		$this->assertEquals(
			'',
			$this->fixture->deleteRecord()
		);
	}

	public function testDeleteRecordFromTheDatabase() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->dummyObjectUid,
			array('owner' => $this->feUserId)
		);
		$this->testingFramework->loginFrontEndUser($this->feUserId);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->deleteRecord();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid='.$this->dummyObjectUid
					.$this->fixture->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testDeleteRecordReturnsAnEmptyStringWhenUserAuthorizedAndRecordWasDeleted() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->dummyObjectUid,
			array('owner' => $this->feUserId)
		);
		$this->testingFramework->loginFrontEndUser($this->feUserId);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertEquals(
			'',
			$this->fixture->deleteRecord()
		);
	}

	////////////////////////////////////////////////////
	// Tests for the functions called in the XML form.
	////////////////////////////////////////////////////
	// * Functions concerning the rendering.
	//////////////////////////////////////////

	public function testIsObjectNumberReadonlyReturnsFalseForANewObject() {
		$this->assertFalse(
			$this->fixture->isObjectNumberReadonly()
		);
	}

	public function testIsObjectNumberReadonlyReturnsTrueForAnExistingObject() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertTrue(
			$this->fixture->isObjectNumberReadonly()
		);
	}

	public function testPopulateListOfCities() {
		$result = $this->fixture->populateListOfCities();
		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	public function testPopulateListOfDistricts() {
		$result = $this->fixture->populateListOfDistricts();
		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	public function testPopulateListOfApartmentTypes() {
		$result = $this->fixture->populateListOfApartmentTypes();
		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	public function testPopulateListOfHouseTypes() {
		$result = $this->fixture->populateListOfHouseTypes();
		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	public function testPopulateListOfHeatingTypes() {
		$result = $this->fixture->populateListOfHeatingTypes();
		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	public function testPopulateListOfConditions() {
		$result = $this->fixture->populateListOfConditions();
		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	public function testPopulateListOfCarPlaces() {
		$result = $this->fixture->populateListOfCarPlaces();
		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	public function testPopulateListOfPets() {
		$result = $this->fixture->populateListOfPets();
		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	public function testPopulateListOfLanguages() {
		$this->assertGreaterThan(
			1,
			count($this->fixture->populateListOfLanguages())
		);
	}

	public function testGetRedirectUrlReturnsCompleteUrlIfConfiguredCorrectly() {
		$fePageUid = $this->testingFramework->createFrontEndPage();
		$this->pi1->setConfigurationValue('feEditorRedirectPid', $fePageUid);

		$this->assertContains(
			'http://',
			$this->fixture->getRedirectUrl()
		);
		$this->assertContains(
			(string) $fePageUid,
			$this->fixture->getRedirectUrl()
		);
	}

	public function testGetRedirectUrlReturnsBaseUrlIfANonExistentPidIsSet() {
		$this->pi1->setConfigurationValue('feEditorRedirectPid', '1234567');

		$this->assertContains(
			'http://',
			$this->fixture->getRedirectUrl()
		);
		$this->assertNotContains(
			'1234567',
			$this->fixture->getRedirectUrl()
		);
	}

	public function testGetRedirectUrlReturnsBaseUrlIfTheConfigurationIsMissing() {
		$this->pi1->setConfigurationValue('feEditorRedirectPid', '0');

		$this->assertContains(
			'http://',
			$this->fixture->getRedirectUrl()
		);
		$this->assertNotContains(
			'0',
			$this->fixture->getRedirectUrl()
		);
	}

	public function testClearFrontEndCacheDeletesCachedPage() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$contentUid = $this->testingFramework->createContentElement(
			$pageUid,
			array('list_type' => 'tx_realty_pi1')
		);
		$this->testingFramework->createPageCacheEntry($contentUid);

		$this->fixture->clearFrontEndCache();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				'cache_pages',
				'page_id='.$pageUid
			)
		);
	}


	//////////////////////////////////
	// * Message creation functions.
	//////////////////////////////////

	public function testGetNoValidNumberMessage() {
		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floor').': '
				.$this->pi1->translate('message_no_valid_number'),
			$this->fixture->getNoValidNumberMessage(array('fieldName' => 'floor'))
		);
	}

	public function testGetNoValidPriceMessage() {
		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floor').': '
				.$this->pi1->translate('message_no_valid_price'),
			$this->fixture->getNoValidPriceMessage(array('fieldName' => 'floor'))
		);
	}

	public function testGetValueNotAllowedMessage() {
		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_type').': '
				.$this->pi1->translate('message_value_not_allowed'),
			$this->fixture->getValueNotAllowedMessage(array('fieldName' => 'object_type'))
		);
	}

	public function testGetRequiredFieldMessage() {
		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.title').': '
				.$this->pi1->translate('message_required_field'),
			$this->fixture->getRequiredFieldMessage(array('fieldName' => 'title'))
		);
	}

	public function testGetNoValidYearMessage() {
		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floor').': '
				.$this->pi1->translate('message_no_valid_year'),
			$this->fixture->getNoValidYearMessage(array('fieldName' => 'construction_year'))
		);
	}

	public function testGetNoValidEmailMessage() {
		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.contact_email').': '
				.$this->pi1->translate('label_set_valid_email_address'),
			$this->fixture->getNoValidEmailMessage()
		);
	}

	public function testGetNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToBuy() {
		$this->fixture->setFakedFormValue('object_type', '1');

		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.buying_price').': '
				.$this->pi1->translate('message_enter_valid_non_empty_buying_price'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'buying_price'))
		);
	}

	public function testGetNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToRent() {
		$this->fixture->setFakedFormValue('object_type', '0');

		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.buying_price').': '
				.$this->pi1->translate('message_enter_valid_or_empty_buying_price'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'buying_price'))
		);
	}

	public function testGetNoValidPriceOrEmptyMessageForRentFieldsIfObjectToRent() {
		$this->fixture->setFakedFormValue('object_type', '0');

		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rent_excluding_bills').': '
				.$this->pi1->translate('message_enter_valid_non_empty_rent'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'rent_excluding_bills'))
		);
	}

	public function testGetNoValidPriceOrEmptyMessageForRentFieldsIfObjectToBuy() {
		$this->fixture->setFakedFormValue('object_type', '1');

		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rent_excluding_bills').': '
				.$this->pi1->translate('message_enter_valid_or_empty_rent'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'rent_excluding_bills'))
		);
	}

	public function testGetInvalidObjectNumberMessageForEmptyObjectNumber() {
		$this->fixture->setFakedFormValue('object_number', '');

		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_number').': '
				.$this->pi1->translate('message_required_field'),
			$this->fixture->getInvalidObjectNumberMessage()
		);
	}

	public function testGetInvalidObjectNumberMessageForNonEmptyObjectNumber() {
		$this->fixture->setFakedFormValue('object_number', 'foo');

		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_number').': '
				.$this->pi1->translate('message_object_number_exists'),
			$this->fixture->getInvalidObjectNumberMessage()
		);
	}

	public function testGetMessageNotReturnsLocalizedFieldNameForInvalidFieldName() {
		$this->assertEquals(
			$this->pi1->translate('message_required_field'),
			$this->fixture->getRequiredFieldMessage(array('fieldName' => 'foo'))
		);
	}


	////////////////////////////
	// * Validation functions.
	////////////////////////////

	public function testIsValidIntegerNumberReturnsTrueForAnIntegerInAString() {
		$this->assertTrue(
			$this->fixture->isValidIntegerNumber(array('value' => '12345'))
		);
	}

	public function testIsValidIntegerNumberReturnsTrueForAnIntegerWithSpaceAsThousandsSeparator() {
		$this->assertTrue(
			$this->fixture->isValidIntegerNumber(array('value' => '12 345'))
		);
	}

	public function testIsValidIntegerNumberReturnsTrueForAnEmptyString() {
		$this->assertTrue(
			$this->fixture->isValidIntegerNumber(array('value' => ''))
		);
	}

	public function testIsValidIntegerNumberReturnsFalseForANumberWithADotAsDecimalSeparator() {
		$this->assertFalse(
			$this->fixture->isValidIntegerNumber(array('value' => '123.45'))
		);
	}

	public function testIsValidIntegerNumberReturnsFalseForANumberWithACommaAsDecimalSeparator() {
		$this->assertFalse(
			$this->fixture->isValidIntegerNumber(array('value' => '123,45')	)
		);
	}

	public function testIsValidIntegerNumberReturnsFalseForANonNumericString() {
		$this->assertFalse(
			$this->fixture->isValidIntegerNumber(array('value' => 'string'))
		);
	}

	public function testIsValidNumberWithDecimalsReturnsTrueForANumberWithOneDecimal() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '1234.5'))
		);
	}

	public function testIsValidNumberWithDecimalsReturnsTrueForANumberWithOneDecimalAndASpace() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '1 234.5'))
		);
	}

	public function testIsValidNumberWithDecimalsReturnsTrueForANumberWithTwoDecimalsSeparatedByDot() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '123.45'))
		);
	}

	public function testIsValidNumberWithDecimalsReturnsTrueForANumberWithTwoDecimalsSeparatedByComma() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '123,45'))
		);
	}

	public function testIsValidNumberWithDecimalsReturnsTrueForANumberWithoutDecimals() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '12345'))
		);
	}

	public function testIsValidNumberWithDecimalsReturnsTrueForAnEmptyString() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => ''))
		);
	}

	public function testIsValidNumberWithDecimalsReturnsFalseForANumberWithMoreThanTwoDecimals() {
		$this->assertFalse(
			$this->fixture->isValidNumberWithDecimals(array('value' => '12.345'))
		);
	}

	public function testIsValidNumberWithDecimalsReturnsFalseForANonNumericString() {
		$this->assertFalse(
			$this->fixture->isValidNumberWithDecimals(array('value' => 'string'))
		);
	}

	public function testIsValidYearReturnsTrueForTheCurrentYear() {
		$this->assertTrue(
			$this->fixture->isValidYear(array('value' => date('Y', mktime())))
		);
	}

	public function testIsValidYearReturnsTrueForAFormerYear() {
		$this->assertTrue(
			$this->fixture->isValidYear(array('value' => '2000'))
		);
	}

	public function testIsValidYearReturnsFalseForAFutureYear() {
		$this->assertFalse(
			$this->fixture->isValidYear(array('value' => '2100'))
		);
	}

	public function testIsNonEmptyValidPriceForObjectForSaleIfThePriceIsValid() {
		$this->fixture->setFakedFormValue('object_type', '1');
		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForSale(
				array('value' => '1234')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForSaleIfThePriceIsInvalid() {
		$this->fixture->setFakedFormValue('object_type', '1');
		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForSale(
				array('value' => 'foo')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForSaleIfThePriceIsEmpty() {
		$this->fixture->setFakedFormValue('object_type', '1');
		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForSale(
				array('value' => '')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfOnePriceIsValidAndOneEmpty() {
		$this->fixture->setFakedFormValue('object_type', '0');
		$this->fixture->setFakedFormValue('year_rent', '');

		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '1234')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfTheOtherPriceIsValidAndOneEmpty() {
		$this->fixture->setFakedFormValue('object_type', '0');
		$this->fixture->setFakedFormValue('year_rent', '1234');

		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfBothPricesAreValid() {
		$this->fixture->setFakedFormValue('object_type', '0');
		$this->fixture->setFakedFormValue('year_rent', '1234');

		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '1234')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfBothPricesAreInvalid() {
		$this->fixture->setFakedFormValue('object_type', '0');
		$this->fixture->setFakedFormValue('year_rent', 'foo');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => 'foo')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfBothPricesAreEmpty() {
		$this->fixture->setFakedFormValue('object_type', '0');
		$this->fixture->setFakedFormValue('year_rent', '');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfOnePriceIsInvalidAndOneValid() {
		$this->fixture->setFakedFormValue('object_type', '0');
		$this->fixture->setFakedFormValue('year_rent', '1234');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => 'foo')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfTheOtherPriceIsInvalidAndOneValid() {
		$this->fixture->setFakedFormValue('object_type', '0');
		$this->fixture->setFakedFormValue('year_rent', 'foo');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '1234')
			)
		);
	}

	public function testIsObjectNumberUniqueForLanguageForUniqueCombination() {
		$this->fixture->setFakedFormValue('language', self::$dummyStringValue);

		$this->assertTrue(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => '1234')
			)
		);
	}

	public function testIsObjectNumberUniqueForLanguageForExistentCombination() {
		$this->fixture->setFakedFormValue('language', self::$dummyStringValue);

		$this->assertFalse(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => self::$dummyStringValue)
			)
		);
	}

	public function testIsObjectNumberUniqueForLanguageForEmptyObjectNumber() {
		$this->assertFalse(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => '')
			)
		);
	}

	public function testIsAllowedValueForCityReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForCity(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES))
			)
		);
	}

	public function testIsAllowedValueForCityReturnsFalseForZero() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForCity(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForCityReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForCity(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES) + 1)
			)
		);
	}

	public function testIsAllowedValueForDistrictReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForDistrict(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_DISTRICTS))
			)
		);
	}

	public function testIsAllowedValueForDistrictReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForDistrict(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForDistrictReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForDistrict(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_DISTRICTS) + 1)
			)
		);
	}

	public function testIsAllowedValueForHouseTypeReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForHouseType(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_HOUSE_TYPES))
			)
		);
	}

	public function testIsAllowedValueForHouseTypeReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForHouseType(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForHouseTypeReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForHouseType(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_HOUSE_TYPES) + 1)
			)
		);
	}

	public function testIsAllowedValueForApartmentTypeReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForApartmentType(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_APARTMENT_TYPES))
			)
		);
	}

	public function testIsAllowedValueForApartmentTypeReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForApartmentType(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForApartmentTypeReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForApartmentType(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_APARTMENT_TYPES) + 1)
			)
		);
	}

	public function testIsAllowedValueForHeatingTypeReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForHeatingType(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_HEATING_TYPES))
			)
		);
	}

	public function testIsAllowedValueForHeatingTypeReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForHeatingType(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForHeatingTypeReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForHeatingType(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_HEATING_TYPES) + 1)
			)
		);
	}

	public function testIsAllowedValueForGarageTypeReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForGarageType(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_CAR_PLACES))
			)
		);
	}

	public function testIsAllowedValueForGarageTypeReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForGarageType(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForGarageTypeReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForGarageType(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_CAR_PLACES) + 1)
			)
		);
	}

	public function testIsAllowedValueForStateReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForState(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_CONDITIONS))
			)
		);
	}

	public function testIsAllowedValueForStateReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForState(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForStateReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForState(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_CONDITIONS) + 1)
			)
		);
	}

	public function testIsAllowedValueForPetsReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForPets(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_PETS))
			)
		);
	}

	public function testIsAllowedValueForPetsReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForPets(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForPetsReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForPets(
				array('value' => $this->testingFramework->createRecord(REALTY_TABLE_PETS) + 1)
			)
		);
	}

	public function testIsAllowedValueForLanguageReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForLanguage(
				array('value' => 'EN')
			)
		);
	}

	public function testIsAllowedValueForLanguageReturnsTrueForEmptyValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForLanguage(
				array('value' => '')
			)
		);
	}


	///////////////////////////////////////////////
	// * Functions called right before insertion.
	///////////////////////////////////////////////

	public function testAddAdministrativeDataAddsTheTimeStampForAnExistingObject() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertEquals(
			'tstamp',
			key($this->fixture->modifyDataToInsert(array()))
		);
	}

	public function testAddAdministrativeDataAddsTimeStampDatePidAndOwnerForANewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertEquals(
			array('tstamp', 'pid', 'crdate', 'owner'),
			array_keys($this->fixture->modifyDataToInsert(array()))
		);
	}

	public function testAddAdministrativeDataAddsDefaultPidForANewObject() {
		$systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$this->plugin->setConfigurationValue(
			'sysFolderForFeCreatedRecords', $systemFolderPid
		);
		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			$systemFolderPid,
			$result['pid']
		);
	}

	public function testAddAdministrativeDataAddsPidDerivedFromCityRecordForANewObject() {
		$systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('save_folder' => $systemFolderPid)
		);

		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array('city' => $cityUid));

		$this->assertEquals(
			$systemFolderPid,
			$result['pid']
		);
	}

	public function testAddAdministrativeDataAddsPidDerivedFromCityRecordForAnExistentObject() {
		$systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('save_folder' => $systemFolderPid)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('city' => $cityUid)
		);

		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array('city' => $cityUid));

		$this->assertEquals(
			$systemFolderPid,
			$result['pid']
		);
	}

	public function testAddAdministrativeDataAddsFrontEndUserIdForANewObject() {
		$this->testingFramework->loginFrontEndUser($this->feUserId);
		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			$this->feUserId,
			$result['owner']
		);
	}

	public function testAddAdministrativeNotDataAddsFrontEndUserIdForAnObjectToUpdate() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertFalse(
			isset($result['owner'])
		);
	}

	public function testUnifyNumbersToInsertForNoElementsWithNumericValues() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$formData = array('foo' => '12,3.45', 'bar' => 'abc,de.fgh');
		$result = $this->fixture->modifyDataToInsert($formData);
		// PID and time stamp will always be added, they are not needed here.
		unset($result['tstamp'], $result['pid']);

		$this->assertEquals(
			$formData,
			$result
		);
	}

	public function testUnifyNumbersToInsertIfSomeElementsNeedFormatting() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(array(
			'garage_rent' => '123,45',
			'garage_price' => '12 345'
		));
		// PID and time stamp will always be added, they are not needed here.
		unset($result['tstamp'], $result['pid']);

		$this->assertEquals(
			array('garage_rent' => '123.45', 'garage_price' => '12345'),
			$result
		);
	}
}

?>
