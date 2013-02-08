<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2013 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_EditorTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_frontEndEditor object to be tested
	 */
	private $fixture;
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var integer UID of the dummy object
	 */
	private $dummyObjectUid = 0;

	/**
	 * @var t3lib_cache_Manager
	 */
	private $cacheManagerBackup = NULL;

	/**
	 * @var string dummy string value
	 */
	private static $dummyStringValue = 'test value';

	public function setUp() {
		tx_oelib_mailerFactory::getInstance()->enableTestMode();
		tx_oelib_headerProxyFactory::getInstance()->enableTestMode();
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		tx_oelib_ConfigurationRegistry::getInstance()
			->set('plugin.tx_realty_pi1', new tx_oelib_Configuration());

		$this->cacheManagerBackup = $GLOBALS['typo3CacheManager'];

		$this->createDummyRecords();

		$this->fixture = new tx_realty_frontEndEditor(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'feEditorTemplateFile'
					=> 'EXT:realty/pi1/tx_realty_frontEndEditor.html',
			),
			$GLOBALS['TSFE']->cObj,
			0,
			'',
			TRUE
		);
	}

	public function tearDown() {
		$GLOBALS['typo3CacheManager'] = $this->cacheManagerBackup;

		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy records in the DB and logs in a front-end user.
	 *
	 * @return void
	 */
	private function createDummyRecords() {
		$user = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getLoadedTestingModel(
				array(
					'username' => 'test_user',
					'name' => 'Mr. Test',
					'email' => 'mr-test@valid-email.org',
					'tx_realty_openimmo_anid' => 'test-user-anid',
				)
			);
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

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
	 *
	 * @return void
	 */
	private function createAuxiliaryRecords() {
		$realtyObject = new tx_realty_Model_RealtyObject(TRUE);
		$realtyObject->loadRealtyObject($this->dummyObjectUid);

		foreach (array(
			'city' => REALTY_TABLE_CITIES,
			'district' => REALTY_TABLE_DISTRICTS,
		) as $key => $table) {
			$realtyObject->setProperty($key, self::$dummyStringValue);
			$this->testingFramework->markTableAsDirty($table);
		}

		$realtyObject->writeToDatabase();
		$realtyObject->__destruct();
	}


	/////////////////////////////////////////
	// Tests concerning the basic functions
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function viewIncludesMainJavaScript() {
		$this->assertTrue(
			isset($GLOBALS['TSFE']->additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID
			])
		);
	}


	/////////////////////////////////////
	// Tests concerning deleteRecord().
	/////////////////////////////////////

	public function testDeleteRecordFromTheDatabase() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->dummyObjectUid,
			array('owner' => $this->testingFramework->createFrontEndUser())
		);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->deleteRecord();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->dummyObjectUid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
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


	//////////////////////////////////////////
	// Tests concerning populateDistrictList
	//////////////////////////////////////////

	/**
	 * @test
	 */
	public function populateDistrictListForSelectedCityReturnsDistrictOfCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts',
			array('city' => $cityUid, 'title' => 'Kreuzberg')
		);
		$this->fixture->setFakedFormValue('city', $cityUid);

		$this->assertTrue(
			in_array(
				array('value' => $districtUid, 'caption' => 'Kreuzberg'),
				$this->fixture->populateDistrictList()
			)
		);
	}

	/**
	 * @test
	 */
	public function populateDistrictListForSelectedCityReturnsDistrictWithoutCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('title' => 'Kreuzberg')
		);
		$this->fixture->setFakedFormValue('city', $cityUid);

		$this->assertTrue(
			in_array(
				array('value' => $districtUid, 'caption' => 'Kreuzberg'),
				$this->fixture->populateDistrictList()
			)
		);
	}

	/**
	 * @test
	 */
	public function populateDistrictListForSelectedCityNotReturnsDistrictOfOtherCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts',
			array('city' => $otherCityUid, 'title' => 'Kreuzberg')
		);
		$this->fixture->setFakedFormValue('city', $cityUid);

		$this->assertFalse(
			in_array(
				array('value' => $districtUid, 'caption' => 'Kreuzberg'),
				$this->fixture->populateDistrictList()
			)
		);
	}

	/**
	 * @test
	 */
	public function populateDistrictListForNoSelectedCityIsEmpty() {
		$this->fixture->setFakedFormValue('city', 0);

		$this->assertEquals(
			array(),
			$this->fixture->populateDistrictList()
		);
	}


	//////////////////////////////////
	// Tests concerning populateList
	//////////////////////////////////

	public function testPopulateListForValidTableReturnsARecordsTitleAsCaption() {
		$result = $this->fixture->populateList(
			array(), array('table' => REALTY_TABLE_CITIES)
		);

		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	public function testPopulateListForInvalidTableThrowsAnExeption() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"invalid_table" is not a valid table name.'
		);
		$this->fixture->populateList(
			array(), array('table' => 'invalid_table')
		);
	}

	public function testPopulateListForInvalidTitleColumnThrowsAnExeption() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"foo" is not a valid column name for ' . REALTY_TABLE_CITIES . '.'
		);
		$this->fixture->populateList(
			array(), array('title_column' => 'foo', 'table' => REALTY_TABLE_CITIES)
		);
	}

	public function testPopulateListOfCountriesContainsDeutschland() {
		$this->assertContains(
			array(
				'value' => '54',
				'caption' => 'Deutschland',
			),
			$this->fixture->populateList(array(), array(
				'table' => STATIC_COUNTRIES,
				'title_column' => 'cn_short_local',
			))
		);
	}


	//////////////////////////////////
	// * Message creation functions.
	//////////////////////////////////

	public function testGetMessageForRealtyObjectFieldCanReturnMessageForField() {
		$this->assertEquals(
			$GLOBALS['TSFE']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floor') . ': ' .
				$this->fixture->translate('message_no_valid_number'),
			$this->fixture->getMessageForRealtyObjectField(
				array('fieldName' => 'floor', 'label' => 'message_no_valid_number')
			)
		);
	}

	public function testGetMessageForRealtyObjectFieldCanReturnMessageWithoutFieldName() {
		$this->assertEquals(
			$this->fixture->translate('message_no_valid_number'),
			$this->fixture->getMessageForRealtyObjectField(
				array('fieldName' => '', 'label' => 'message_no_valid_number')
			)
		);
	}

	public function testGetMessageForRealtyObjectThrowsAnExceptionForAnInvalidFieldName() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"foo" is not a valid column name for ' . REALTY_TABLE_OBJECTS . '.'
		);
		$this->fixture->getMessageForRealtyObjectField(
			array('fieldName' => 'foo', 'label' => 'message_no_valid_number')
		);
	}

	public function testGetMessageForRealtyObjectFieldThrowsAnExceptionForInvalidLocallangKey() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"123" is not a valid locallang key.'
		);
		$this->fixture->getMessageForRealtyObjectField(array('label' => '123'));
	}

	public function testGetMessageForRealtyObjectFieldThrowsAnExceptionForEmptyLocallangKey() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"" is not a valid locallang key.'
		);
		$this->fixture->getMessageForRealtyObjectField(array('label' => ''));
	}

	public function testGetNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToBuy() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_SALE);

		$this->assertEquals(
			$GLOBALS['TSFE']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.buying_price') . ': ' .
				$this->fixture->translate('message_enter_valid_non_empty_buying_price'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'buying_price'))
		);
	}

	public function testGetNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToRent() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_RENTING);

		$this->assertEquals(
			$GLOBALS['TSFE']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.buying_price') . ': ' .
				$this->fixture->translate('message_enter_valid_or_empty_buying_price'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'buying_price'))
		);
	}

	public function testGetNoValidPriceOrEmptyMessageForRentFieldsIfObjectToRent() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_RENTING);

		$this->assertEquals(
			$GLOBALS['TSFE']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rent_excluding_bills') . ': ' .
				$this->fixture->translate('message_enter_valid_non_empty_rent'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'rent_excluding_bills'))
		);
	}

	public function testGetNoValidPriceOrEmptyMessageForRentFieldsIfObjectToBuy() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_SALE);

		$this->assertEquals(
			$GLOBALS['TSFE']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rent_excluding_bills') . ': ' .
				$this->fixture->translate('message_enter_valid_or_empty_rent'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'rent_excluding_bills'))
		);
	}

	public function testGetInvalidObjectNumberMessageForEmptyObjectNumber() {
		$this->fixture->setFakedFormValue('object_number', '');

		$this->assertEquals(
			$GLOBALS['TSFE']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_number') . ': ' .
				$this->fixture->translate('message_required_field'),
			$this->fixture->getInvalidObjectNumberMessage()
		);
	}

	public function testGetInvalidObjectNumberMessageForNonEmptyObjectNumber() {
		$this->fixture->setFakedFormValue('object_number', 'foo');

		$this->assertEquals(
			$GLOBALS['TSFE']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_number') . ': ' .
				$this->fixture->translate('message_object_number_exists'),
			$this->fixture->getInvalidObjectNumberMessage()
		);
	}

	public function testGetInvalidOrEmptyCityMessageForEmptyCity() {
		$this->fixture->setFakedFormValue('city', 0);

		$this->assertEquals(
			$GLOBALS['TSFE']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.city') . ': ' .
				$this->fixture->translate('message_required_field'),
			$this->fixture->getInvalidOrEmptyCityMessage()
		);
	}

	public function testGetInvalidOrEmptyCityMessageForNonEmptyCity() {
		$this->fixture->setFakedFormValue(
			'city', $this->testingFramework->createRecord(
				REALTY_TABLE_CITIES, array('deleted' => 1)
			)
		);

		$this->assertEquals(
			$GLOBALS['TSFE']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.city') . ': ' .
				$this->fixture->translate('message_value_not_allowed'),
			$this->fixture->getInvalidOrEmptyCityMessage()
		);
	}


	////////////////////////////
	// * Validation functions.
	////////////////////////////

	/**
	 * @test
	 */
	public function isValidNonNegativeIntegerNumberForIntegerReturnsTrue() {
		$this->assertTrue(
			$this->fixture->isValidNonNegativeIntegerNumber(array('value' => '12345'))
		);
	}

	/**
	 * @test
	 */
	public function isValidNonNegativeIntegerNumberForIntegerWithSpaceAsThousandsSeparatorReturnsTrue() {
		$this->assertTrue(
			$this->fixture->isValidNonNegativeIntegerNumber(array('value' => '12 345'))
		);
	}

	/**
	 * @test
	 */
	public function isValidNonNegativeIntegerNumberForEmptyStringReturnsTrue() {
		$this->assertTrue(
			$this->fixture->isValidNonNegativeIntegerNumber(array('value' => ''))
		);
	}

	/**
	 * @test
	 */
	public function isValidNonNegativeIntegerNumberForNumberWithDotAsDecimalSeparatorReturnsFalse() {
		$this->assertFalse(
			$this->fixture->isValidNonNegativeIntegerNumber(array('value' => '123.45'))
		);
	}

	/**
	 * @test
	 */
	public function isValidNonNegativeIntegerNumberForNumberWithCommaAsDecimalSeparatorReturnsFalse() {
		$this->assertFalse(
			$this->fixture->isValidNonNegativeIntegerNumber(array('value' => '123,45') )
		);
	}

	/**
	 * @test
	 */
	public function isValidNonNegativeIntegerNumberForNegativeIntegerReturnsFalse() {
		$this->assertFalse(
			$this->fixture->isValidNonNegativeIntegerNumber(array('value' => '-123') )
		);
	}

	/**
	 * @test
	 */
	public function isValidNonNegativeIntegerNumberForNonNumericStringReturnsFalse() {
		$this->assertFalse(
			$this->fixture->isValidNonNegativeIntegerNumber(array('value' => 'string'))
		);
	}

	/**
	 * @test
	 */
	public function isValidIntegerNumberForIntegerReturnsTrue() {
		$this->assertTrue(
			$this->fixture->isValidIntegerNumber(array('value' => '12345'))
		);
	}

	/**
	 * @test
	 */
	public function isValidIntegerNumberForIntegerWithSpaceAsThousandsSeparatorReturnsTrue() {
		$this->assertTrue(
			$this->fixture->isValidIntegerNumber(array('value' => '12 345'))
		);
	}

	/**
	 * @test
	 */
	public function isValidIntegerNumberForEmptyStringReturnsTrue() {
		$this->assertTrue(
			$this->fixture->isValidIntegerNumber(array('value' => ''))
		);
	}

	/**
	 * @test
	 */
	public function isValidIntegerNumberForNumberWithDotAsDecimalSeparatorReturnsFalse() {
		$this->assertFalse(
			$this->fixture->isValidIntegerNumber(array('value' => '123.45'))
		);
	}

	/**
	 * @test
	 */
	public function isValidIntegerNumberForNumberWithCommaAsDecimalSeparatorReturnsFalse() {
		$this->assertFalse(
			$this->fixture->isValidIntegerNumber(array('value' => '123,45') )
		);
	}

	/**
	 * @test
	 */
	public function isValidIntegerNumberForNegativeIntegerReturnsTrue() {
		$this->assertTrue(
			$this->fixture->isValidIntegerNumber(array('value' => '-123') )
		);
	}

	/**
	 * @test
	 */
	public function isValidIntegerNumberForNonNumericStringReturnsFalse() {
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

	public function testIsIntegerInRangeReturnsTrueForSingleAllowedInteger() {
		$this->assertTrue(
			$this->fixture->isIntegerInRange(
				array('value' => '1', 'range' => '1-2', 'multiple' => '0')
			)
		);
	}

	public function testIsIntegerInRangeReturnsFalseForSingleIntegerBelowTheRange() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => '0', 'range' => '1-2', 'multiple' => '0')
			)
		);
	}

	public function testIsIntegerInRangeReturnsFalseForSingleIntegerHigherThanTheRange() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => '2', 'range' => '0-1', 'multiple' => '0')
			)
		);
	}

	public function testIsIntegerInRangeReturnsFalseForNonIntegerValue() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => 'string', 'range' => '0-1', 'multiple' => '0')
			)
		);
	}

	public function testIsIntegerInRangeReturnsTrueForMultipleAllowedIntegers() {
		$this->assertTrue(
			$this->fixture->isIntegerInRange(
				array('value' => array(0, 1, 2), 'range' => '0-2', 'multiple' => '1')
			)
		);
	}

	public function testIsIntegerInRangeReturnsFalseForMultipleIntegersIfOneIsBelowTheRange() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => array(0, 1, 2), 'range' => '1-2', 'multiple' => '1')
			)
		);
	}

	public function testIsIntegerInRangeReturnsFalseForMultipleIntegersIfOneIsHigherThanTheRange() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => array(0, 1, 2), 'range' => '0-1', 'multiple' => '1')
			)
		);
	}

	public function testIsIntegerInRangeReturnsTrueForEmptyValue() {
		$this->assertTrue(
			$this->fixture->isIntegerInRange(
				array('value' => '', 'range' => '1-2', 'multiple' => '0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidYearReturnsTrueForTheCurrentYear() {
		$this->assertTrue(
			$this->fixture->isValidYear(array('value' => date('Y', mktime())))
		);
	}

	/**
	 * @test
	 */
	public function isValidYearReturnsTrueForAFormerYear() {
		$this->assertTrue(
			$this->fixture->isValidYear(array('value' => '2000'))
		);
	}

	/**
	 * @test
	 */
	public function isValidYearReturnsTrueForAFutureYear() {
		$this->assertTrue(
			$this->fixture->isValidYear(array('value' => '2100'))
		);
	}

	/**
	 * @test
	 */
	public function isValidYearReturnsFalseForNumberWithDecimals() {
		$this->assertFalse(
			$this->fixture->isValidYear(array('value' => '42,55'))
		);
	}

	public function testIsNonEmptyValidPriceForObjectForSaleIfThePriceIsValid() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_SALE);
		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForSale(
				array('value' => '1234')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForSaleIfThePriceIsInvalid() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_SALE);
		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForSale(
				array('value' => 'foo')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForSaleIfThePriceIsEmpty() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_SALE);
		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForSale(
				array('value' => '')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfOnePriceIsValidAndOneEmpty() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_RENTING);
		$this->fixture->setFakedFormValue('year_rent', '');

		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '1234')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfTheOtherPriceIsValidAndOneEmpty() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_RENTING);
		$this->fixture->setFakedFormValue('year_rent', '1234');

		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfBothPricesAreValid() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_RENTING);
		$this->fixture->setFakedFormValue('year_rent', '1234');

		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '1234')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfBothPricesAreInvalid() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_RENTING);
		$this->fixture->setFakedFormValue('year_rent', 'foo');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => 'foo')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfBothPricesAreEmpty() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_RENTING);
		$this->fixture->setFakedFormValue('year_rent', '');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfOnePriceIsInvalidAndOneValid() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_RENTING);
		$this->fixture->setFakedFormValue('year_rent', '1234');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => 'foo')
			)
		);
	}

	public function testIsNonEmptyValidPriceForObjectForRentIfTheOtherPriceIsInvalidAndOneValid() {
		$this->fixture->setFakedFormValue('object_type', REALTY_FOR_RENTING);
		$this->fixture->setFakedFormValue('year_rent', 'foo');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '1234')
			)
		);
	}

	public function testIsObjectNumberUniqueForLanguageForUniqueCombination() {
		// The dummy record's language is not ''. A new record's language
		// is always ''.
		$this->assertTrue(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => '1234')
			)
		);
	}

	public function testIsObjectNumberUniqueForLanguageForHiddenRecordWithDifferensObjectNumber() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->dummyObjectUid, array('hidden' => '1')
		);

		$this->assertTrue(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => '1234')
			)
		);
	}

	public function testIsObjectNumberUniqueForLanguageForExistentCombination() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->dummyObjectUid, array('language' => '')
		);

		$this->assertFalse(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => self::$dummyStringValue)
			)
		);
	}

	public function testIsObjectNumberUniqueForLanguageForHiddenRecordWithSameObjectNumber() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->dummyObjectUid,
			array('language' => '', 'hidden' => '1')
		);

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

	public function testIsAllowedValueForCityReturnsTrueForZeroIfANewRecordTitleIsProvided() {
		$this->fixture->setFakedFormValue('new_city', 'new city');

		$this->assertTrue(
			$this->fixture->isAllowedValueForCity(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForCityReturnsFalseForZeroIfNoNewRecordTitleIsProvided() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForCity(
				array('value' => '0')
			)
		);
	}

	public function testIsAllowedValueForCityReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForCity(
				array('value' => $this->testingFramework->createRecord(
					REALTY_TABLE_CITIES, array('deleted' => 1)
				))
			)
		);
	}

	public function testCheckKeyExistsInTableReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->checkKeyExistsInTable(
				array(
					'value' => $this->testingFramework->createRecord(REALTY_TABLE_DISTRICTS),
					'table' => REALTY_TABLE_DISTRICTS,
				)
			)
		);
	}

	public function testCheckKeyExistsInTableReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->checkKeyExistsInTable(
				array('value' => '0', 'table' => REALTY_TABLE_DISTRICTS)
			)
		);
	}

	public function testCheckKeyExistsInTableReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->checkKeyExistsInTable(
				array(
					'value' => $this->testingFramework->createRecord(
						REALTY_TABLE_DISTRICTS, array('deleted' => 1)
					),
					'table' => REALTY_TABLE_DISTRICTS
				)
			)
		);
	}

	public function testCheckKeyExistsInTableThrowsExceptionForInvalidTable() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"invalid_table" is not a valid table name.'
		);
		$this->fixture->checkKeyExistsInTable(array(
			'value' => 1, 'table' => 'invalid_table'
		));
	}

	public function testIsValidLongitudeDegreeReturnsTrueFor180WithoutDecimal() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '180')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsTrueFor180WithOneDecimal() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '180.0')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsTrueFor180WithTwoDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '180.00')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsTrueForMinus180() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '-180.0')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsFalseForGreater180() {
		$this->assertFalse(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '180.1')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsFalseForLowerMinus180() {
		$this->assertFalse(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '-180.1')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsTrueForValueInAllowedPositiveRangeWithManyDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '123.12345678901234')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsTrueForValueInAllowedNegativeRangeWithDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '-123.12345678901234')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '0')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsTrueForEmptyString() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '')
			)
		);
	}

	public function testIsValidLongitudeDegreeReturnsFalseForAlphaChars() {
		$this->assertFalse(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => 'abc')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsTrueFor90WithNoDecimal() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '90')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsTrueFor90WithOneDecimal() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '90.0')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsTrueForMinus90() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '-90.0')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsFalseForGreater90() {
		$this->assertFalse(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '90.1')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsFalseForLowerMinus90() {
		$this->assertFalse(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '-90.1')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsTrueForValueInAllowedPositiveRangeWithDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '83.12345678901234')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsTrueForValueInAllowedNegativeRangeWithDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '-83.12345678901234')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsTrueForEmptyString() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '0')
			)
		);
	}

	public function testIsValidLatitudeDegreeReturnsFalseForAlphaChars() {
		$this->assertFalse(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => 'abc')
			)
		);
	}

	public function testIsAtMostOneValueForAuxiliaryRecordProvidedReturnsTrueForNonEmptyNewTitleAndNoExistingRecord() {
		$this->fixture->setFakedFormValue('city', 0);

		$this->assertTrue(
			$this->fixture->isAtMostOneValueForAuxiliaryRecordProvided(array(
				'value' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES),
				'fieldName' => 'city',
			))
		);
	}

	public function testIsAtMostOneValueForAuxiliaryRecordProvidedReturnsFalseForNonEmptyNewTitleAndExistingRecord() {
		$this->fixture->setFakedFormValue('city', $this->testingFramework->createRecord(REALTY_TABLE_CITIES));

		$this->assertFalse(
			$this->fixture->isAtMostOneValueForAuxiliaryRecordProvided(array(
				'value' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES),
				'fieldName' => 'city'
			))
		);
	}

	public function testIsNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsOwner() {
		$this->fixture->setFakedFormValue(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->assertTrue(
			$this->fixture->isNonEmptyOrOwnerDataUsed(array())
		);
	}

	public function testIsNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsNotOwnerAndTheValueIsNonEmpty() {
		$this->fixture->setFakedFormValue(
			'contact_data_source', REALTY_CONTACT_FROM_REALTY_OBJECT
		);

		$this->assertTrue(
			$this->fixture->isNonEmptyOrOwnerDataUsed(array('value' => 'foo'))
		);
	}

	public function testIsNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsNotOwnerAndTheValueIsEmpty() {
		$this->fixture->setFakedFormValue(
			'contact_data_source', REALTY_CONTACT_FROM_REALTY_OBJECT
		);

		$this->assertFalse(
			$this->fixture->isNonEmptyOrOwnerDataUsed(array())
		);
	}


	///////////////////////////////////////////////
	// * Functions called right before insertion.
	///////////////////////////////////////////////
	// ** addAdministrativeData().
	////////////////////////////////

	public function testAddAdministrativeDataAddsTheTimeStampForAnExistingObject() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$result = $this->fixture->modifyDataToInsert(array());
		// object type will always be added and is not needed here.
		unset($result['object_type']);

		$this->assertEquals(
			'tstamp',
			key($result)
		);
	}

	public function testAddAdministrativeDataAddsTimeStampForANewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('tstamp', $this->fixture->modifyDataToInsert(array()))
		);
	}

	public function testAddAdministrativeDataAddsDateForANewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('crdate', $this->fixture->modifyDataToInsert(array()))
		);
	}

	public function testAddAdministrativeDataAddsPidForANewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('pid', $this->fixture->modifyDataToInsert(array()))
		);
	}

	public function testAddAdministrativeDataAddsHiddenFlagForANewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('hidden', $this->fixture->modifyDataToInsert(array()))
		);
	}

	public function testAddAdministrativeDataAddsObjectTypeForANewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('object_type', $this->fixture->modifyDataToInsert(array()))
		);
	}

	public function testAddAdministrativeDataAddsOwnerForANewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('owner', $this->fixture->modifyDataToInsert(array()))
		);
	}

	public function testAddAdministrativeDataAddsOpenImmoAnidForANewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('openimmo_anid', $this->fixture->modifyDataToInsert(array()))
		);
	}

	public function testAddAdministrativeDataAddsDefaultPidForANewObject() {
		$systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$this->fixture->setConfigurationValue(
			'sysFolderForFeCreatedRecords', $systemFolderPid
		);
		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			$systemFolderPid,
			$result['pid']
		);
	}

	public function testAddAdministrativeDataNotAddsDefaultPidForAnExistingObject() {
		$systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$this->fixture->setConfigurationValue(
			'sysFolderForFeCreatedRecords', $systemFolderPid
		);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertNotEquals(
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

	public function testAddAdministrativeDataAddsFrontEndUserUidForANewObject() {
		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			tx_oelib_FrontEndLoginManager::getInstance()->getLoggedInUser()
				->getUid(),
			$result['owner']
		);
	}

	public function testAddAdministrativeDataNotAddsFrontEndUserUidForAnObjectToUpdate() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertFalse(
			isset($result['owner'])
		);
	}

	public function testAddAdministrativeDataAddsFrontEndUsersOpenImmoAnidForANewObject() {
		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			'test-user-anid',
			$result['openimmo_anid']
		);
	}

	public function testAddAdministrativeDataAddsEmptyOpenImmoAnidForANewObjectIfUserHasNoAnid() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(array());
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			'',
			$result['openimmo_anid']
		);
	}

	public function testAddAdministrativeDataNotAddsFrontEndUsersOpenImmoAnidForAnObjectToUpdate() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertFalse(
			isset($result['openimmo_anid'])
		);
	}

	public function testNewRecordIsMarkedAsHidden() {
		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			1,
			$result['hidden']
		);
	}

	public function testExistingRecordIsNotMarkedAsHidden() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertFalse(
			isset($result['hidden'])
		);
	}


	///////////////////////
	// ** unifyNumbers().
	///////////////////////

	public function testUnifyNumbersToInsertForNonNumericValues() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$formData = array(
			'title' => '12,3.45', 'employer' => 'abc,de.fgh'
		);
		$result = $this->fixture->modifyDataToInsert($formData);
		// PID, object type and time stamp will always be added,
		// they are not needed here.
		unset($result['tstamp'], $result['pid'], $result['object_type']);

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
		// PID, object type and time stamp will always be added,
		// they are not needed here.
		unset($result['tstamp'], $result['pid'], $result['object_type']);

		$this->assertEquals(
			array('garage_rent' => '123.45', 'garage_price' => '12345'),
			$result
		);
	}


	///////////////////////////////////
	// ** storeNewAuxiliaryRecords().
	///////////////////////////////////

	public function testStoreNewAuxiliaryRecordsDeletesNonEmptyNewCityElement() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('new_city' => 'foo',)
		);

		$this->assertFalse(
			isset($result['new_city'])
		);
	}

	public function testStoreNewAuxiliaryRecordsDeletesEmptyNewCityElement() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('new_city' => '')
		);

		$this->assertFalse(
			isset($result['new_city'])
		);
	}

	public function testStoreNewAuxiliaryRecordsDeletesNonEmptyNewDistrictElement() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('new_district' => 'foo',)
		);

		$this->assertFalse(
			isset($result['new_district'])
		);
	}

	public function testStoreNewAuxiliaryRecordsDeletesEmptyNewDistrictElement() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('new_district' => '')
		);

		$this->assertFalse(
			isset($result['new_district'])
		);
	}

	public function testStoreNewAuxiliaryRecordsNotCreatesANewRecordForAnExistingTitle() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->modifyDataToInsert(
			array('new_city' => self::$dummyStringValue)
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_CITIES,
				'title = "' . self::$dummyStringValue . '"'
			)
		);
	}

	public function testStoreNewAuxiliaryRecordsCreatesANewRecordForANewTitle() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->modifyDataToInsert(array('new_city' => 'new city'));

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_CITIES, 'title = "new city"'
			)
		);
	}

	public function testStoreNewAuxiliaryRecordsCreatesANewRecordWithCorrectPid() {
		$pid = $this->testingFramework->createSystemFolder(1);
		$configuration = new tx_oelib_Configuration();
		$configuration->setData(array(
			'sysFolderForFeCreatedAuxiliaryRecords' => $pid
		));
		tx_oelib_ConfigurationRegistry::getInstance()->set(
			'plugin.tx_realty_pi1', $configuration
		);

		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->modifyDataToInsert(array('new_city' => 'new city'));

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_CITIES,
				'title = "new city" AND pid = ' . $pid
			)
		);
	}

	public function testStoreNewAuxiliaryRecordsStoresNewUidToTheFormData() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('new_city' => 'new city')
		);

		$this->assertTrue(
			isset($result['city'])
		);
		$this->assertNotEquals(
			0,
			$result['city']
		);
	}

	public function testStoreNewAuxiliaryRecordsCreatesnoNewRecordForAnEmptyTitle() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->modifyDataToInsert(array('new_city' => ''));

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	public function testStoreNewAuxiliaryRecordsNotCreatesARecordIfAUidIsAlreadySet() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('city' => 1, 'new_city' => 'new city')
		);

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_CITIES, 'title = "new city"'
			)
		);
		$this->assertEquals(
			1,
			$result['city']
		);
	}


	/////////////////////////////////////
	// ** purgeNonRealtyObjectFields().
	/////////////////////////////////////

	/**
	 * @test
	 */
	public function fieldThatDoesNotExistInTheRealtyObjectsTableIsPurged() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertFalse(
			array_key_exists(
				'spacer_01',
				$this->fixture->modifyDataToInsert(array('spacer_01' => 'blubb'))
			)
		);
		// TODO: remove the workaround when PHPUnit Bug 992 is fixed.
		// @see http://www.phpunit.de/ticket/992
	}

	/**
	 * @test
	 */
	public function fieldThatExitsInTheRealtyObjectsTableIsNotPurged() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertTrue(
			array_key_exists(
				'title',
				$this->fixture->modifyDataToInsert(array('title' => 'foo'))
			)
		);
		// TODO: remove the workaround when PHPUnit Bug 992 is fixed.
		// @see http://www.phpunit.de/ticket/992
	}


	////////////////////////////////////////
	// * Functions called after insertion.
	/////////////////////////////////////////////////////
	// ** sendEmailForNewObjectAndClearFrontEndCache().
	/////////////////////////////////////////////////////

	public function testSendEmailForNewObjectSendsToTheConfiguredRecipient() {
		// This will create an empty dummy record.
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@valid-email.org'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();
		$this->assertEquals(
			'recipient@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSentEmailHasTheCurrentFeUserAsFrom() {
		// This will create an empty dummy record.
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@valid-email.org'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertEquals(
			'From: "Mr. Test" <mr-test@valid-email.org>'.LF,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testSentEmailContainsTheFeUsersName() {
		// This will create an empty dummy record.
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@valid-email.org'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertContains(
			'Mr. Test',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsTheFeUsersUsername() {
		// This will create an empty dummy record.
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@valid-email.org'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertContains(
			'test_user',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsTheNewObjectsTitle() {
		$this->fixture->setFakedFormValue('title', 'any title');
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@valid-email.org'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertContains(
			'any title',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsTheNewObjectsObjectNumber() {
		$this->fixture->setFakedFormValue('object_number', '1234');
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@valid-email.org'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertContains(
			'1234',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testSentEmailContainsTheNewObjectsUid() {
		// The UID is found with the help of the combination of object number
		// and language.
		$this->fixture->setFakedFormValue('object_number', '1234');
		$this->fixture->setFakedFormValue('language', 'XY');
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@valid-email.org'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$expectedResult = tx_oelib_db::selectSingle(
			'uid',
			REALTY_TABLE_OBJECTS,
			'object_number="1234" AND language="XY"'
		);

		$this->assertContains(
			(string) $expectedResult['uid'],
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testNoEmailIsSentIfNoRecipientWasConfigured() {
		$this->fixture->setConfigurationValue('feEditorNotifyEmail', '');
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertEquals(
			array(),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastEmail()
		);
	}

	public function testNoEmailIsSentForExistingObject() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@valid-email.org'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertEquals(
			array(),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastEmail()
		);
	}

	/**
	 * @test
	 */
	public function sendEmailForNewObjectAndClearFrontEndCacheClearsFrontEndCacheInOldTypo3() {
		if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 4006000) {
			$this->markTestSkipped('This test is not applicable for TYPO3 >= 4.6.');
		}
		if (!TYPO3_UseCachingFramework) {
			$this->markTestSkipped('This test is not applicable if the caching framework is disabled.');
		}

		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createContentElement($pageUid, array('list_type' => 'realty_pi1'));

		/** @var $cacheFrontEnd t3lib_cache_frontend_AbstractFrontend|PHPUnit_Framework_MockObject_MockObject */
		$cacheFrontEnd = $this->getMockFor(
			't3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'flushByTags'),
			array(), '', FALSE
		);
		$cacheFrontEnd->expects($this->once())->method('getIdentifier')->will($this->returnValue('cache_pages'));
		$cacheFrontEnd->expects($this->atLeastOnce())->method('flushByTags');

		$GLOBALS['typo3CacheManager'] = new t3lib_cache_Manager();
		$GLOBALS['typo3CacheManager']->registerCache($cacheFrontEnd);

		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$GLOBALS['typo3CacheManager'] = NULL;
	}

	/**
	 * @test
	 */
	public function sendEmailForNewObjectAndClearFrontEndCacheClearsFrontEndCache() {
		if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) < 4006000) {
			$this->markTestSkipped('This test is not applicable for TYPO3 < 4.6.');
		}

		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createContentElement($pageUid, array('list_type' => 'realty_pi1'));

		/** @var $cacheFrontEnd t3lib_cache_frontend_AbstractFrontend|PHPUnit_Framework_MockObject_MockObject */
		$cacheFrontEnd = $this->getMock(
			't3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'getBackend'),
			array(), '', FALSE
		);
		$cacheFrontEnd->expects($this->once())->method('getIdentifier')->will($this->returnValue('cache_pages'));
		/** @var $cacheBackEnd t3lib_cache_backend_Backend|PHPUnit_Framework_MockObject_MockObject */
		$cacheBackEnd = $this->getMock('t3lib_cache_backend_Backend');
		$cacheFrontEnd->expects($this->any())->method('getBackend')->will($this->returnValue($cacheBackEnd));
		$cacheBackEnd->expects($this->atLeastOnce())->method('flushByTag');

		$GLOBALS['typo3CacheManager'] = new t3lib_cache_Manager();
		$GLOBALS['typo3CacheManager']->registerCache($cacheFrontEnd);

		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$GLOBALS['typo3CacheManager'] = NULL;
	}


	//////////////////////////////////////
	// Tests concerning addOnloadHandler
	//////////////////////////////////////

	public function testAddOnLoadHandlerAddsOnLoadHandler() {
		$this->fixture->addOnLoadHandler();

		$this->assertTrue(
			isset($GLOBALS['TSFE']
				->JSeventFuncCalls['onload']['tx_realty_pi1_editor']
			)
		);
	}

	public function testAddOnLoadHandlerAddsCallToUpdateHideAndShow() {
		$this->fixture->addOnLoadHandler();

		$this->assertContains(
			'updateHideAndShow();',
			$GLOBALS['TSFE']->JSeventFuncCalls['onload']['tx_realty_pi1_editor']
		);
	}


	//////////////////////////////////////
	// Tests concerning populateCityList
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function populateCityListContainsCityFromDatabase() {
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'Bonn')
		);

		$this->assertTrue(
			in_array(
				array('value' => $cityUid, 'caption' => 'Bonn'),
				tx_realty_frontEndEditor::populateCityList()
			)
		);
	}
}
?>
