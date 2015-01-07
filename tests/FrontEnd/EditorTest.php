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
	private $fixture = NULL;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework = NULL;

	/**
	 * @var int UID of the dummy object
	 */
	private $dummyObjectUid = 0;

	/**
	 * @var string dummy string value
	 */
	private static $dummyStringValue = 'test value';

	/**
	 * @var t3lib_mail_Message
	 */
	private $message = NULL;

	protected function setUp() {
		tx_oelib_headerProxyFactory::getInstance()->enableTestMode();
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		tx_oelib_ConfigurationRegistry::getInstance()
			->set('plugin.tx_realty_pi1', new tx_oelib_Configuration());

		$this->createDummyRecords();

		$this->fixture = new tx_realty_frontEndEditor(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'feEditorTemplateFile' => 'EXT:realty/pi1/tx_realty_frontEndEditor.html',
			),
			$this->getFrontEndController()->cObj, 0, '', TRUE
		);

		$finalMailMessageClassName = t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 6000000
			? 'TYPO3\\CMS\\Core\\Mail\\MailMessage' : 't3lib_mail_Message';
		$this->message = $this->getMock('t3lib_mail_Message', array('send', '__destruct'));
		t3lib_div::addInstance($finalMailMessageClassName, $this->message);
	}

	protected function tearDown() {
		// Get any surplus instances added via t3lib_div::addInstance.
		t3lib_div::makeInstance('t3lib_mail_Message');

		tx_realty_cacheManager::purgeCacheManager();

		$this->testingFramework->cleanUp();
	}


	/*
	 * Utility functions.
	 */

	/**
	 * Returns the current front-end instance.
	 *
	 * @return tslib_fe
	 */
	private function getFrontEndController() {
		return $GLOBALS['TSFE'];
	}

	/**
	 * Translates a string using the sL function from the front-end controller.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	private function translate($key) {
		return $this->getFrontEndController()->sL($key);
	}

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
					'email' => 'mr-test@example.com',
					'tx_realty_openimmo_anid' => 'test-user-anid',
				)
			);
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->dummyObjectUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
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
			'city' => 'tx_realty_cities',
			'district' => 'tx_realty_districts',
		) as $key => $table) {
			$realtyObject->setProperty($key, self::$dummyStringValue);
			$this->testingFramework->markTableAsDirty($table);
		}

		$realtyObject->writeToDatabase();
	}

	/**
	 * Returns the class name of the cache backend to use.
	 *
	 * @return string
	 *
	 * @throws RuntimeException if no suitable cache backend can be found
	 */
	private function getCacheBackendClassName() {
		$classNames = array(
			'TYPO3\\CMS\\Core\\Cache\\Backend\\TaggableBackendInterface',
			'TYPO3\\CMS\\Core\\Cache\\Backend\\BackendInterface',
			't3lib_cache_backend_Backend',
		);

		$existingClassName = '';
		foreach ($classNames as $className ) {
			if (class_exists($className, TRUE) || interface_exists($className, TRUE)) {
				$existingClassName = $className;
				break;
			}
		}
		if ($existingClassName === '') {
			throw new RuntimeException('No cache backend class name found.', 1416856902);
		}

		return $existingClassName;
	}


	/*
	 * Tests concerning the basic functions
	 */

	/**
	 * @test
	 */
	public function viewIncludesMainJavaScript() {
		$this->assertTrue(
			isset($this->getFrontEndController()->additionalHeaderData[tx_realty_lightboxIncluder::PREFIX_ID])
		);
	}


	/////////////////////////////////////
	// Tests concerning deleteRecord().
	/////////////////////////////////////

	/**
	 * @test
	 */
	public function deleteRecordFromTheDatabase() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->dummyObjectUid,
			array('owner' => $this->testingFramework->createFrontEndUser())
		);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->deleteRecord();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'uid=' . $this->dummyObjectUid .
					tx_oelib_db::enableFields('tx_realty_objects')
			)
		);
	}


	////////////////////////////////////////////////////
	// Tests for the functions called in the XML form.
	////////////////////////////////////////////////////
	// * Functions concerning the rendering.
	//////////////////////////////////////////

	/**
	 * @test
	 */
	public function isObjectNumberReadonlyReturnsFalseForNewObject() {
		$this->assertFalse(
			$this->fixture->isObjectNumberReadonly()
		);
	}

	/**
	 * @test
	 */
	public function isObjectNumberReadonlyReturnsTrueForAnExistingObject() {
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

	/**
	 * @test
	 */
	public function populateListForValidTableReturnsARecordsTitleAsCaption() {
		$result = $this->fixture->populateList(
			array(), array('table' => 'tx_realty_cities')
		);

		$this->assertEquals(
			self::$dummyStringValue,
			$result[0]['caption']
		);
	}

	/**
	 * @test
	 */
	public function populateListForInvalidTableThrowsAnExeption() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"invalid_table" is not a valid table name.'
		);
		$this->fixture->populateList(
			array(), array('table' => 'invalid_table')
		);
	}

	/**
	 * @test
	 */
	public function populateListForInvalidTitleColumnThrowsAnExeption() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"foo" is not a valid column name for ' . 'tx_realty_cities' . '.'
		);
		$this->fixture->populateList(
			array(), array('title_column' => 'foo', 'table' => 'tx_realty_cities')
		);
	}

	/**
	 * @test
	 */
	public function populateListOfCountriesContainsDeutschland() {
		$this->assertContains(
			array(
				'value' => '54',
				'caption' => 'Deutschland',
			),
			$this->fixture->populateList(array(), array(
				'table' => 'static_countries',
				'title_column' => 'cn_short_local',
			))
		);
	}


	//////////////////////////////////
	// * Message creation functions.
	//////////////////////////////////

	/**
	 * @test
	 */
	public function getMessageForRealtyObjectFieldCanReturnMessageForField() {
		$this->assertEquals(
			$this->translate('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.floor') . ': ' .
				$this->fixture->translate('message_no_valid_number'),
			$this->fixture->getMessageForRealtyObjectField(
				array('fieldName' => 'floor', 'label' => 'message_no_valid_number')
			)
		);
	}

	/**
	 * @test
	 */
	public function getMessageForRealtyObjectFieldCanReturnMessageWithoutFieldName() {
		$this->assertEquals(
			$this->fixture->translate('message_no_valid_number'),
			$this->fixture->getMessageForRealtyObjectField(
				array('fieldName' => '', 'label' => 'message_no_valid_number')
			)
		);
	}

	/**
	 * @test
	 */
	public function getMessageForRealtyObjectThrowsAnExceptionForAnInvalidFieldName() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"foo" is not a valid column name for ' . 'tx_realty_objects' . '.'
		);
		$this->fixture->getMessageForRealtyObjectField(
			array('fieldName' => 'foo', 'label' => 'message_no_valid_number')
		);
	}

	/**
	 * @test
	 */
	public function getMessageForRealtyObjectFieldThrowsAnExceptionForInvalidLocallangKey() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"123" is not a valid locallang key.'
		);
		$this->fixture->getMessageForRealtyObjectField(array('label' => '123'));
	}

	/**
	 * @test
	 */
	public function getMessageForRealtyObjectFieldThrowsAnExceptionForEmptyLocallangKey() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"" is not a valid locallang key.'
		);
		$this->fixture->getMessageForRealtyObjectField(array('label' => ''));
	}

	/**
	 * @test
	 */
	public function getNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToBuy() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_SALE);

		$this->assertEquals(
			$this->translate('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.buying_price') . ': ' .
				$this->fixture->translate('message_enter_valid_non_empty_buying_price'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'buying_price'))
		);
	}

	/**
	 * @test
	 */
	public function getNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToRent() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);

		$this->assertEquals(
			$this->translate('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.buying_price') . ': ' .
				$this->fixture->translate('message_enter_valid_or_empty_buying_price'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'buying_price'))
		);
	}

	/**
	 * @test
	 */
	public function getNoValidPriceOrEmptyMessageForRentFieldsIfObjectToRent() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);

		$this->assertEquals(
			$this->translate('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rent_excluding_bills') . ': ' .
				$this->fixture->translate('message_enter_valid_non_empty_rent'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'rent_excluding_bills'))
		);
	}

	/**
	 * @test
	 */
	public function getNoValidPriceOrEmptyMessageForRentFieldsIfObjectToBuy() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_SALE);

		$this->assertEquals(
			$this->translate('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.rent_excluding_bills') . ': ' .
				$this->fixture->translate('message_enter_valid_or_empty_rent'),
			$this->fixture->getNoValidPriceOrEmptyMessage(array('fieldName' => 'rent_excluding_bills'))
		);
	}

	/**
	 * @test
	 */
	public function getInvalidObjectNumberMessageForEmptyObjectNumber() {
		$this->fixture->setFakedFormValue('object_number', '');

		$this->assertEquals(
			$this->translate('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_number') . ': ' .
				$this->fixture->translate('message_required_field'),
			$this->fixture->getInvalidObjectNumberMessage()
		);
	}

	/**
	 * @test
	 */
	public function getInvalidObjectNumberMessageForNonEmptyObjectNumber() {
		$this->fixture->setFakedFormValue('object_number', 'foo');

		$this->assertEquals(
			$this->translate('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.object_number') . ': ' .
				$this->fixture->translate('message_object_number_exists'),
			$this->fixture->getInvalidObjectNumberMessage()
		);
	}

	/**
	 * @test
	 */
	public function getInvalidOrEmptyCityMessageForEmptyCity() {
		$this->fixture->setFakedFormValue('city', 0);

		$this->assertEquals(
			$this->translate('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.city') . ': ' .
				$this->fixture->translate('message_required_field'),
			$this->fixture->getInvalidOrEmptyCityMessage()
		);
	}

	/**
	 * @test
	 */
	public function getInvalidOrEmptyCityMessageForNonEmptyCity() {
		$this->fixture->setFakedFormValue(
			'city', $this->testingFramework->createRecord(
				'tx_realty_cities', array('deleted' => 1)
			)
		);

		$this->assertEquals(
			$this->translate('LLL:EXT:realty/locallang_db.xml:tx_realty_objects.city') . ': ' .
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

	/**
	 * @test
	 */
	public function isValidNumberWithDecimalsReturnsTrueForNumberWithOneDecimal() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '1234.5'))
		);
	}

	/**
	 * @test
	 */
	public function isValidNumberWithDecimalsReturnsTrueForNumberWithOneDecimalAndSpace() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '1 234.5'))
		);
	}

	/**
	 * @test
	 */
	public function isValidNumberWithDecimalsReturnsTrueForNumberWithTwoDecimalsSeparatedByDot() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '123.45'))
		);
	}

	/**
	 * @test
	 */
	public function isValidNumberWithDecimalsReturnsTrueForNumberWithTwoDecimalsSeparatedByComma() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '123,45'))
		);
	}

	/**
	 * @test
	 */
	public function isValidNumberWithDecimalsReturnsTrueForNumberWithoutDecimals() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => '12345'))
		);
	}

	/**
	 * @test
	 */
	public function isValidNumberWithDecimalsReturnsTrueForAnEmptyString() {
		$this->assertTrue(
			$this->fixture->isValidNumberWithDecimals(array('value' => ''))
		);
	}

	/**
	 * @test
	 */
	public function isValidNumberWithDecimalsReturnsFalseForNumberWithMoreThanTwoDecimals() {
		$this->assertFalse(
			$this->fixture->isValidNumberWithDecimals(array('value' => '12.345'))
		);
	}

	/**
	 * @test
	 */
	public function isValidNumberWithDecimalsReturnsFalseForNonNumericString() {
		$this->assertFalse(
			$this->fixture->isValidNumberWithDecimals(array('value' => 'string'))
		);
	}

	/**
	 * @test
	 */
	public function isIntegerInRangeReturnsTrueForSingleAllowedInteger() {
		$this->assertTrue(
			$this->fixture->isIntegerInRange(
				array('value' => '1', 'range' => '1-2', 'multiple' => '0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isIntegerInRangeReturnsFalseForSingleIntegerBelowTheRange() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => '0', 'range' => '1-2', 'multiple' => '0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isIntegerInRangeReturnsFalseForSingleIntegerHigherThanTheRange() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => '2', 'range' => '0-1', 'multiple' => '0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isIntegerInRangeReturnsFalseForNonIntegerValue() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => 'string', 'range' => '0-1', 'multiple' => '0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isIntegerInRangeReturnsTrueForMultipleAllowedIntegers() {
		$this->assertTrue(
			$this->fixture->isIntegerInRange(
				array('value' => array(0, 1, 2), 'range' => '0-2', 'multiple' => '1')
			)
		);
	}

	/**
	 * @test
	 */
	public function isIntegerInRangeReturnsFalseForMultipleIntegersIfOneIsBelowTheRange() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => array(0, 1, 2), 'range' => '1-2', 'multiple' => '1')
			)
		);
	}

	/**
	 * @test
	 */
	public function isIntegerInRangeReturnsFalseForMultipleIntegersIfOneIsHigherThanTheRange() {
		$this->assertFalse(
			$this->fixture->isIntegerInRange(
				array('value' => array(0, 1, 2), 'range' => '0-1', 'multiple' => '1')
			)
		);
	}

	/**
	 * @test
	 */
	public function isIntegerInRangeReturnsTrueForEmptyValue() {
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
			$this->fixture->isValidYear(array('value' => date('Y', time())))
		);
	}

	/**
	 * @test
	 */
	public function isValidYearReturnsTrueForFormerYear() {
		$this->assertTrue(
			$this->fixture->isValidYear(array('value' => '2000'))
		);
	}

	/**
	 * @test
	 */
	public function isValidYearReturnsTrueForFutureYear() {
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

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForSaleIfThePriceIsValid() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForSale(
				array('value' => '1234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForSaleIfThePriceIsInvalid() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForSale(
				array('value' => 'foo')
			)
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForSaleIfThePriceIsEmpty() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForSale(
				array('value' => '')
			)
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForRentIfOnePriceIsValidAndOneEmpty() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
		$this->fixture->setFakedFormValue('year_rent', '');

		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '1234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForRentIfTheOtherPriceIsValidAndOneEmpty() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
		$this->fixture->setFakedFormValue('year_rent', '1234');

		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '')
			)
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForRentIfBothPricesAreValid() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
		$this->fixture->setFakedFormValue('year_rent', '1234');

		$this->assertTrue(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '1234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForRentIfBothPricesAreInvalid() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
		$this->fixture->setFakedFormValue('year_rent', 'foo');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => 'foo')
			)
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForRentIfBothPricesAreEmpty() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
		$this->fixture->setFakedFormValue('year_rent', '');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '')
			)
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForRentIfOnePriceIsInvalidAndOneValid() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
		$this->fixture->setFakedFormValue('year_rent', '1234');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => 'foo')
			)
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyValidPriceForObjectForRentIfTheOtherPriceIsInvalidAndOneValid() {
		$this->fixture->setFakedFormValue('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
		$this->fixture->setFakedFormValue('year_rent', 'foo');

		$this->assertFalse(
			$this->fixture->isNonEmptyValidPriceForObjectForRent(
				array('value' => '1234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isObjectNumberUniqueForLanguageForUniqueCombination() {
		// The dummy record's language is not ''. A new record's language
		// is always ''.
		$this->assertTrue(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => '1234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isObjectNumberUniqueForLanguageForHiddenRecordWithDifferensObjectNumber() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->dummyObjectUid, array('hidden' => '1')
		);

		$this->assertTrue(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => '1234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isObjectNumberUniqueForLanguageForExistentCombination() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->dummyObjectUid, array('language' => '')
		);

		$this->assertFalse(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => self::$dummyStringValue)
			)
		);
	}

	/**
	 * @test
	 */
	public function isObjectNumberUniqueForLanguageForHiddenRecordWithSameObjectNumber() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->dummyObjectUid,
			array('language' => '', 'hidden' => '1')
		);

		$this->assertFalse(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => self::$dummyStringValue)
			)
		);
	}

	/**
	 * @test
	 */
	public function isObjectNumberUniqueForLanguageForEmptyObjectNumber() {
		$this->assertFalse(
			$this->fixture->isObjectNumberUniqueForLanguage(
				array('value' => '')
			)
		);
	}

	/**
	 * @test
	 */
	public function isAllowedValueForCityReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->isAllowedValueForCity(
				array('value' => $this->testingFramework->createRecord('tx_realty_cities'))
			)
		);
	}

	/**
	 * @test
	 */
	public function isAllowedValueForCityReturnsTrueForZeroIfANewRecordTitleIsProvided() {
		$this->fixture->setFakedFormValue('new_city', 'new city');

		$this->assertTrue(
			$this->fixture->isAllowedValueForCity(
				array('value' => '0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isAllowedValueForCityReturnsFalseForZeroIfNoNewRecordTitleIsProvided() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForCity(
				array('value' => '0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isAllowedValueForCityReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->isAllowedValueForCity(
				array('value' => $this->testingFramework->createRecord(
					'tx_realty_cities', array('deleted' => 1)
				))
			)
		);
	}

	/**
	 * @test
	 */
	public function checkKeyExistsInTableReturnsTrueForAllowedValue() {
		$this->assertTrue(
			$this->fixture->checkKeyExistsInTable(
				array(
					'value' => $this->testingFramework->createRecord('tx_realty_districts'),
					'table' => 'tx_realty_districts',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function checkKeyExistsInTableReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->checkKeyExistsInTable(
				array('value' => '0', 'table' => 'tx_realty_districts')
			)
		);
	}

	/**
	 * @test
	 */
	public function checkKeyExistsInTableReturnsFalseForInvalidValue() {
		$this->assertFalse(
			$this->fixture->checkKeyExistsInTable(
				array(
					'value' => $this->testingFramework->createRecord(
						'tx_realty_districts', array('deleted' => 1)
					),
					'table' => 'tx_realty_districts'
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function checkKeyExistsInTableThrowsExceptionForInvalidTable() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"invalid_table" is not a valid table name.'
		);
		$this->fixture->checkKeyExistsInTable(array(
			'value' => 1, 'table' => 'invalid_table'
		));
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsTrueFor180WithoutDecimal() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '180')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsTrueFor180WithOneDecimal() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '180.0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsTrueFor180WithTwoDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '180.00')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsTrueForMinus180() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '-180.0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsFalseForGreater180() {
		$this->assertFalse(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '180.1')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsFalseForLowerMinus180() {
		$this->assertFalse(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '-180.1')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsTrueForValueInAllowedPositiveRangeWithManyDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '123.12345678901234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsTrueForValueInAllowedNegativeRangeWithDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '-123.12345678901234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsTrueForEmptyString() {
		$this->assertTrue(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => '')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLongitudeDegreeReturnsFalseForAlphaChars() {
		$this->assertFalse(
			$this->fixture->IsValidLongitudeDegree(
				array('value' => 'abc')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsTrueFor90WithNoDecimal() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '90')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsTrueFor90WithOneDecimal() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '90.0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsTrueForMinus90() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '-90.0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsFalseForGreater90() {
		$this->assertFalse(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '90.1')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsFalseForLowerMinus90() {
		$this->assertFalse(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '-90.1')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsTrueForValueInAllowedPositiveRangeWithDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '83.12345678901234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsTrueForValueInAllowedNegativeRangeWithDecimals() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '-83.12345678901234')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsTrueForEmptyString() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsTrueForZero() {
		$this->assertTrue(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => '0')
			)
		);
	}

	/**
	 * @test
	 */
	public function isValidLatitudeDegreeReturnsFalseForAlphaChars() {
		$this->assertFalse(
			$this->fixture->IsValidLatitudeDegree(
				array('value' => 'abc')
			)
		);
	}

	/**
	 * @test
	 */
	public function isAtMostOneValueForAuxiliaryRecordProvidedReturnsTrueForNonEmptyNewTitleAndNoExistingRecord() {
		$this->fixture->setFakedFormValue('city', 0);

		$this->assertTrue(
			$this->fixture->isAtMostOneValueForAuxiliaryRecordProvided(array(
				'value' => $this->testingFramework->createRecord('tx_realty_cities'),
				'fieldName' => 'city',
			))
		);
	}

	/**
	 * @test
	 */
	public function isAtMostOneValueForAuxiliaryRecordProvidedReturnsFalseForNonEmptyNewTitleAndExistingRecord() {
		$this->fixture->setFakedFormValue('city', $this->testingFramework->createRecord('tx_realty_cities'));

		$this->assertFalse(
			$this->fixture->isAtMostOneValueForAuxiliaryRecordProvided(array(
				'value' => $this->testingFramework->createRecord('tx_realty_cities'),
				'fieldName' => 'city'
			))
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsOwner() {
		$this->fixture->setFakedFormValue(
			'contact_data_source', tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT
		);

		$this->assertTrue(
			$this->fixture->isNonEmptyOrOwnerDataUsed(array())
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsNotOwnerAndTheValueIsNonEmpty() {
		$this->fixture->setFakedFormValue(
			'contact_data_source', tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT
		);

		$this->assertTrue(
			$this->fixture->isNonEmptyOrOwnerDataUsed(array('value' => 'foo'))
		);
	}

	/**
	 * @test
	 */
	public function isNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsNotOwnerAndTheValueIsEmpty() {
		$this->fixture->setFakedFormValue(
			'contact_data_source', tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT
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

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsTheTimeStampForExistingObject() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);

		$result = $this->fixture->modifyDataToInsert(array());
		// object type will always be added and is not needed here.
		unset($result['object_type']);

		$this->assertEquals(
			'tstamp',
			key($result)
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsTimeStampForNewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('tstamp', $this->fixture->modifyDataToInsert(array()))
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsDateForNewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('crdate', $this->fixture->modifyDataToInsert(array()))
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsPidForNewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('pid', $this->fixture->modifyDataToInsert(array()))
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsHiddenFlagForNewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('hidden', $this->fixture->modifyDataToInsert(array()))
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsObjectTypeForNewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('object_type', $this->fixture->modifyDataToInsert(array()))
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsOwnerForNewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('owner', $this->fixture->modifyDataToInsert(array()))
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsOpenImmoAnidForNewObject() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertTrue(
			in_array('openimmo_anid', $this->fixture->modifyDataToInsert(array()))
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsDefaultPidForNewObject() {
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

	/**
	 * @test
	 */
	public function addAdministrativeDataNotAddsDefaultPidForExistingObject() {
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

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsPidDerivedFromCityRecordForNewObject() {
		$systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('save_folder' => $systemFolderPid)
		);

		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array('city' => $cityUid));

		$this->assertEquals(
			$systemFolderPid,
			$result['pid']
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsPidDerivedFromCityRecordForExistentObject() {
		$systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('save_folder' => $systemFolderPid)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);

		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array('city' => $cityUid));

		$this->assertEquals(
			$systemFolderPid,
			$result['pid']
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsFrontEndUserUidForNewObject() {
		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			tx_oelib_FrontEndLoginManager::getInstance()->getLoggedInUser()
				->getUid(),
			$result['owner']
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataNotAddsFrontEndUserUidForObjectToUpdate() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertFalse(
			isset($result['owner'])
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsFrontEndUsersOpenImmoAnidForNewObject() {
		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			'test-user-anid',
			$result['openimmo_anid']
		);
	}

	/**
	 * @test
	 */
	public function addAdministrativeDataAddsEmptyOpenImmoAnidForNewObjectIfUserHasNoAnid() {
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

	/**
	 * @test
	 */
	public function addAdministrativeDataNotAddsFrontEndUsersOpenImmoAnidForAnObjectToUpdate() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertFalse(
			isset($result['openimmo_anid'])
		);
	}

	/**
	 * @test
	 */
	public function newRecordIsMarkedAsHidden() {
		$this->fixture->setRealtyObjectUid(0);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertEquals(
			1,
			$result['hidden']
		);
	}

	/**
	 * @test
	 */
	public function existingRecordIsNotMarkedAsHidden() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(array());

		$this->assertFalse(
			isset($result['hidden'])
		);
	}


	///////////////////////
	// ** unifyNumbers().
	///////////////////////

	/**
	 * @test
	 */
	public function unifyNumbersToInsertForNonNumericValues() {
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

	/**
	 * @test
	 */
	public function unifyNumbersToInsertIfSomeElementsNeedFormatting() {
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

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsDeletesNonEmptyNewCityElement() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('new_city' => 'foo',)
		);

		$this->assertFalse(
			isset($result['new_city'])
		);
	}

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsDeletesEmptyNewCityElement() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('new_city' => '')
		);

		$this->assertFalse(
			isset($result['new_city'])
		);
	}

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsDeletesNonEmptyNewDistrictElement() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('new_district' => 'foo',)
		);

		$this->assertFalse(
			isset($result['new_district'])
		);
	}

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsDeletesEmptyNewDistrictElement() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('new_district' => '')
		);

		$this->assertFalse(
			isset($result['new_district'])
		);
	}

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsNotCreatesANewRecordForAnExistingTitle() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->modifyDataToInsert(
			array('new_city' => self::$dummyStringValue)
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_cities',
				'title = "' . self::$dummyStringValue . '"'
			)
		);
	}

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsCreatesANewRecordForNewTitle() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->modifyDataToInsert(array('new_city' => 'new city'));

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_cities', 'title = "new city"'
			)
		);
	}

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsCreatesANewRecordWithCorrectPid() {
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
				'tx_realty_cities',
				'title = "new city" AND pid = ' . $pid
			)
		);
	}

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsStoresNewUidToTheFormData() {
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

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsCreatesnoNewRecordForAnEmptyTitle() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->modifyDataToInsert(array('new_city' => ''));

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords('tx_realty_cities')
		);
	}

	/**
	 * @test
	 */
	public function storeNewAuxiliaryRecordsNotCreatesARecordIfAUidIsAlreadySet() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$result = $this->fixture->modifyDataToInsert(
			array('city' => 1, 'new_city' => 'new city')
		);

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				'tx_realty_cities', 'title = "new city"'
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

	/**
	 * @test
	 */
	public function sendEmailForNewObjectSendsToTheConfiguredRecipient() {
		// This will create an empty dummy record.
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@example.com'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertArrayHasKey(
			'recipient@example.com',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailHasTheCurrentFeUserAsFrom() {
		// This will create an empty dummy record.
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@example.com'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertArrayHasKey(
			'mr-test@example.com',
			$this->message->getFrom()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsTheFeUsersName() {
		// This will create an empty dummy record.
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@example.com'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertContains(
			'Mr. Test',
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsTheFeUsersUsername() {
		// This will create an empty dummy record.
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@example.com'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertContains(
			'test_user',
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsTheNewObjectsTitle() {
		$this->fixture->setFakedFormValue('title', 'any title');
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@example.com'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertContains(
			'any title',
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsTheNewObjectsObjectNumber() {
		$this->fixture->setFakedFormValue('object_number', '1234');
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@example.com'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->assertContains(
			'1234',
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsTheNewObjectsUid() {
		// The UID is found with the help of the combination of object number
		// and language.
		$this->fixture->setFakedFormValue('object_number', '1234');
		$this->fixture->setFakedFormValue('language', 'XY');
		$this->fixture->writeFakedFormDataToDatabase();
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@example.com'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$expectedResult = tx_oelib_db::selectSingle(
			'uid',
			'tx_realty_objects',
			'object_number="1234" AND language="XY"'
		);

		$this->assertContains(
			(string) $expectedResult['uid'],
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function noEmailIsSentIfNoRecipientWasConfigured() {
		$this->fixture->setConfigurationValue('feEditorNotifyEmail', '');
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->message->expects($this->never())->method('send');
	}

	/**
	 * @test
	 */
	public function noEmailIsSentForExistingObject() {
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
		$this->fixture->setConfigurationValue(
			'feEditorNotifyEmail', 'recipient@example.com'
		);
		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

		$this->message->expects($this->never())->method('send');
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

		$cacheManager = new t3lib_cache_Manager();
		$cacheManager->registerCache($cacheFrontEnd);
		tx_realty_cacheManager::injectCacheManager($cacheManager);

		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();
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
		$cacheBackEnd = $this->getMock($this->getCacheBackendClassName());
		$cacheFrontEnd->expects($this->any())->method('getBackend')->will($this->returnValue($cacheBackEnd));
		$cacheBackEnd->expects($this->atLeastOnce())->method('flushByTag');

		$cacheManager = new t3lib_cache_Manager();
		$cacheManager->registerCache($cacheFrontEnd);
		tx_realty_cacheManager::injectCacheManager($cacheManager);

		$this->fixture->sendEmailForNewObjectAndClearFrontEndCache();
	}

	/*
	 * Tests concerning addOnloadHandler
	 */

	/**
	 * @test
	 */
	public function addOnLoadHandlerAddsOnLoadHandler() {
		$this->fixture->addOnLoadHandler();

		$this->assertTrue(
			isset($this->getFrontEndController()->JSeventFuncCalls['onload']['tx_realty_pi1_editor']
			)
		);
	}

	/**
	 * @test
	 */
	public function addOnLoadHandlerAddsCallToUpdateHideAndShow() {
		$this->fixture->addOnLoadHandler();

		$this->assertContains(
			'updateHideAndShow();',
			$this->getFrontEndController()->JSeventFuncCalls['onload']['tx_realty_pi1_editor']
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