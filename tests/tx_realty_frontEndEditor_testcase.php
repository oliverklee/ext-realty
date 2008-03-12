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
require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_frontEndEditor.php');

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

		$this->fixture = new tx_realty_frontEndEditor($this->pi1, 0, true);
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
			REALTY_TABLE_OBJECTS
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


	//////////////////////////////////////////////////////////////
	// Tests concerning the error messages returned by render().
	//////////////////////////////////////////////////////////////

	public function testRenderReturnsObjectDoesNotExistMessageForAnInvalidUidAndNoUserLoggedIn() {
		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid + 1);

		$this->assertContains(
			$this->pi1->translate('message_noResultsFound_fe_editor'),
			$this->fixture->render()
		);
	}

	public function testRenderReturnsObjectDoesNotExistMessageForAnInvalidUidAndAUserLoggedIn() {
		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->testingFramework->loginFrontEndUser($this->feUserId);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid + 1);

		$this->assertContains(
			$this->pi1->translate('message_noResultsFound_fe_editor'),
			$this->fixture->render()
		);
	}

	public function testRenderReturnsPleaseLoginMessageForANewObjectIfNoUserIsLoggedIn() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertContains(
			$this->pi1->translate('message_please_login'),
			$this->fixture->render()
		);
	}

	public function testRenderReturnsPleaseLoginMessageForAnExistingObjectIfNoUserIsLoggedIn() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$this->assertContains(
			$this->pi1->translate('message_please_login'),
			$this->fixture->render()
		);
	}

	public function testRenderReturnsAccessDeniedMessageWhenLoggedInUserAttemptsToEditAnObjectHeDoesNotOwn() {
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
			$this->fixture->render()
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


	////////////////////////////
	// * Validation functions.
	////////////////////////////

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

	public function testGetNoValidYearMessage() {
		$this->assertEquals(
			$GLOBALS['LANG']->sL('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floor').': '
				.$this->pi1->translate('message_no_valid_year'),
			$this->fixture->getNoValidYearMessage(array('fieldName' => 'construction_year'))
		);
	}

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
