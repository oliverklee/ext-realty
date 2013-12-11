<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Saskia Metzler <saskia@merlin.owl.de>
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
 */
class tx_realty_FrontEnd_FormatterTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_Formatter
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var tx_realty_Model_RealtyObject a dummy realty object
	 */
	private $realtyObject;

	/**
	 * @var integer static_info_tables UID of Germany
	 */
	const DE = 54;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->getNewGhost();
		$this->realtyObject->setData(array('title' => 'test realty object'));

		$this->fixture = new tx_realty_pi1_Formatter(
			$this->realtyObject->getUid(),
			array(
				'defaultCountryUID' => self::DE,
				'currencyUnit' => 'EUR',
			),
			$GLOBALS['TSFE']->cObj
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->realtyObject, $this->fixture, $this->testingFramework);
	}


	//////////////////////////////
	// Tests for the constructor
	//////////////////////////////

	/**
	 * @test
	 */
	public function constructAnExceptionIfCalledWithAZeroRealtyObjectUid() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'$realtyObjectUid must be greater than zero.'
		);

		$this->fixture = new tx_realty_pi1_Formatter(
			0, array(), $GLOBALS['TSFE']->cObj
		);
	}

	/**
	 * @test
	 */
	public function constructThrowsAnExceptionIfCalledWithAUidOfADeletedRealtyObject() {
		$this->realtyObject->markAsDead();

		$this->setExpectedException(
			'InvalidArgumentException',
			'There was no realty object to load with the provided UID of ' . $this->realtyObject->getUid() .
				'. The formatter can only work for existing, non-deleted realty objects.'
		);

		new tx_realty_pi1_Formatter(
			$this->realtyObject->getUid(), array(), $GLOBALS['TSFE']->cObj
		);
	}


	///////////////////////////////////////////
	// Tests for getting formatted properties
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getPropertyThrowsExceptionForEmptyKey() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'$key must not be empty.'
		);

		$this->fixture->getProperty('');
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsTheLabelOfAValidState() {
		$this->realtyObject->setProperty('state', 8);

		$this->assertEquals(
			$this->fixture->translate('label_state_8'),
			$this->fixture->getProperty('state')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsAnEmptyStringIfTheStateIsNotSet() {
		$this->assertEquals(
			'',
			$this->fixture->getProperty('state')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsAnEmptyStringIfTheObjectHasAnInvalidValueForState() {
		$this->realtyObject->setProperty('state', 1000000);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('state')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsTheLabelOfAValidHeatingType() {
		$this->realtyObject->setProperty('heating_type', '1');

		$this->assertEquals(
			$this->fixture->translate('label_heating_type_1'),
			$this->fixture->getProperty('heating_type')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsTheLabelsOfAListOfValidHeatingTypes() {
		$this->realtyObject->setProperty('heating_type', '1,3,4');

		$this->assertEquals(
			$this->fixture->translate('label_heating_type_1') . ', ' .
				$this->fixture->translate('label_heating_type_3') . ', ' .
				$this->fixture->translate('label_heating_type_4'),
			$this->fixture->getProperty('heating_type')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsAnEmptyStringIfTheHeatingTypeIsNotSet() {
		$this->assertEquals(
			'',
			$this->fixture->getProperty('heating_type')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsAnEmptyStringIfTheObjectHasAnInvalidValueForHeatingType() {
		$this->realtyObject->setProperty('heating_type', 10000);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('heating_type')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsEmptyStringForCountrySameAsDefaultCountry() {
		$this->realtyObject->setProperty('country', self::DE);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('country')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsTheCountryNameForCountryDifferentFromDefaultCountry() {
		// randomly chosen the country UID of Australia
		$this->realtyObject->setProperty('country', 14);

		$this->assertEquals(
			'Australia',
			$this->fixture->getProperty('country')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsTitleOfCity() {
		$this->realtyObject->setProperty(
				'city',
				$this->testingFramework->createRecord(
					REALTY_TABLE_CITIES, array('title' => 'test city')
				)
			);

		$this->assertEquals(
			'test city',
			$this->fixture->getProperty('city')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsHtmlSpecialcharedTitleOfCity() {
		$this->realtyObject->setProperty(
				'city',
				$this->testingFramework->createRecord(
					REALTY_TABLE_CITIES, array('title' => 'test<br/>city')
				)
			);

		$this->assertEquals(
			htmlspecialchars('test<br/>city'),
			$this->fixture->getProperty('city')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyFormatsEstateSizeWithTwoDecimals() {
		$this->realtyObject->setProperty('estate_size', 12345.50);
		$localeConvention = localeconv();

		$this->assertEquals(
			'12345' . $localeConvention['decimal_point'] . '50&nbsp;' .
				$this->fixture->translate('label_squareMeters'),
			$this->fixture->getProperty('estate_size')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForEstateSizeCutsOffAllZeroDecimals() {
		$this->realtyObject->setProperty('estate_size', 12345.00);

		$this->assertEquals(
			'12345' . '&nbsp;' . $this->fixture->translate('label_squareMeters'),
			$this->fixture->getProperty('estate_size')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsFormatsHoaFeeFormattedWithTwoWithDecimals() {
		$this->realtyObject->setProperty('hoa_fee', 12345.67);

		$this->assertEquals(
			'&euro; 12.345,67',
			$this->fixture->getProperty('hoa_fee')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForHoaFeeAddsZeroDecimals() {
		$this->realtyObject->setProperty('hoa_fee', 12345);

		$this->assertEquals(
			'&euro; 12.345,00',
			$this->fixture->getProperty('hoa_fee')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsEmptyStringForUsableFromIfNoValueIsSet() {
		$this->assertEquals(
			'',
			$this->fixture->getProperty('usable_from')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsValueOfUsableFrom() {
		$this->realtyObject->setProperty('usable_from', '1.1.');

		$this->assertEquals(
			'1.1.',
			$this->fixture->getProperty('usable_from')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsHtmlspecialcharedValueOfUsableFrom() {
		$this->realtyObject->setProperty('usable_from', '1.<br/>1.');

		$this->assertEquals(
			htmlspecialchars('1.<br/>1.'),
			$this->fixture->getProperty('usable_from')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsNonZeroValueOfFloor() {
		$this->realtyObject->setProperty('floor', 3);

		$this->assertEquals(
			'3',
			$this->fixture->getProperty('floor')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsEmptyStringForZeroValueOfFloor() {
		$this->realtyObject->setProperty('floor', 0);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('floor')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForStatusVacantReturnsVacantLabel() {
		$this->realtyObject->setProperty(
			'status', tx_realty_Model_RealtyObject::STATUS_VACANT
		);

		$this->assertEquals(
			$this->fixture->translate('label_status_0'),
			$this->fixture->getProperty('status')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForStatusReservedReturnsReservedLabel() {
		$this->realtyObject->setProperty(
			'status', tx_realty_Model_RealtyObject::STATUS_RESERVED
		);

		$this->assertEquals(
			$this->fixture->translate('label_status_1'),
			$this->fixture->getProperty('status')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForStatusSoldReturnsSoldLabel() {
		$this->realtyObject->setProperty(
			'status', tx_realty_Model_RealtyObject::STATUS_SOLD
		);

		$this->assertEquals(
			$this->fixture->translate('label_status_2'),
			$this->fixture->getProperty('status')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForStatusRentedReturnsRentedLabel() {
		$this->realtyObject->setProperty(
			'status', tx_realty_Model_RealtyObject::STATUS_RENTED
		);

		$this->assertEquals(
			$this->fixture->translate('label_status_3'),
			$this->fixture->getProperty('status')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsAddress() {
		$this->realtyObject->setProperty('show_address', 1);
		$this->realtyObject->setProperty('street', 'Main Street');
		$this->realtyObject->setProperty('zip', '12345');
		$this->realtyObject->setProperty(
			'city',
			$this->testingFramework->createRecord(
				REALTY_TABLE_CITIES, array('title' => 'Test Town')
			)
		);

		$this->assertEquals(
			'Main Street<br />12345 Test Town',
			$this->fixture->getProperty('address')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForNumberOfRoomsWithTwoDecimalsReturnsNumberWithOneDecimal() {
		$this->realtyObject->setProperty('number_of_rooms', 5.20);

		$this->assertSame(
			'5.2',
			$this->fixture->getProperty('number_of_rooms')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForNumberOfBathroomsWithTwoDecimalsReturnsNumberWithOneDecimal() {
		$this->realtyObject->setProperty('bathrooms', 5.20);

		$this->assertSame(
			'5.2',
			$this->fixture->getProperty('bathrooms')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForNumberOfBedroomsWithTwoDecimalsReturnsNumberWithOneDecimal() {
		$this->realtyObject->setProperty('bedrooms', 5.20);

		$this->assertSame(
			'5.2',
			$this->fixture->getProperty('bedrooms')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForTotalUsableAreaReturnsItAsFormattedArea() {
		$this->realtyObject->setProperty('total_usable_area', 123.45);
		$localeConvention = localeconv();

		$this->assertEquals(
			'123' . $localeConvention['decimal_point'] . '45&nbsp;' .
				$this->fixture->translate('label_squareMeters'),
			$this->fixture->getProperty('total_usable_area')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForTotalUsableAreaCutsOffAllZeroDecimals() {
		$this->realtyObject->setProperty('total_usable_area', 123.00);

		$this->assertEquals(
			'123&nbsp;' . $this->fixture->translate('label_squareMeters'),
			$this->fixture->getProperty('total_usable_area')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForOfficeSpaceReturnsItAsFormattedArea() {
		$this->realtyObject->setProperty('office_space', 58.23);
		$localeConvention = localeconv();

		$this->assertEquals(
			'58' . $localeConvention['decimal_point'] . '23&nbsp;' .
				$this->fixture->translate('label_squareMeters'),
			$this->fixture->getProperty('office_space')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForShopAreaReturnsItAsFormattedArea() {
		$this->realtyObject->setProperty('shop_area', 12.34);
		$localeConvention = localeconv();

		$this->assertEquals(
			'12' . $localeConvention['decimal_point'] . '34&nbsp;' .
				$this->fixture->translate('label_squareMeters'),
			$this->fixture->getProperty('shop_area')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForSalesAreaReturnsItAsFormattedArea() {
		$this->realtyObject->setProperty('sales_area', 12.34);
		$localeConvention = localeconv();

		$this->assertEquals(
			'12' . $localeConvention['decimal_point'] . '34&nbsp;' .
				$this->fixture->translate('label_squareMeters'),
			$this->fixture->getProperty('sales_area')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForStorageAreaReturnsItAsFormattedArea() {
		$this->realtyObject->setProperty('storage_area', 18.4);
		$localeConvention = localeconv();

		$this->assertEquals(
			'18' . $localeConvention['decimal_point'] . '40&nbsp;' .
				$this->fixture->translate('label_squareMeters'),
			$this->fixture->getProperty('storage_area')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForOtherAreaReturnsItAsFormattedArea() {
		$this->realtyObject->setProperty('other_area', 12.34);
		$localeConvention = localeconv();

		$this->assertEquals(
			'12' . $localeConvention['decimal_point'] . '34&nbsp;' .
				$this->fixture->translate('label_squareMeters'),
			$this->fixture->getProperty('other_area')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForSiteOccupancyIndexReturnsItAsFormattedDecimal() {
		$this->realtyObject->setProperty('site_occupancy_index', 19.40);
		$localeConvention = localeconv();

		$this->assertEquals(
			'19' . $localeConvention['decimal_point'] . '40',
			$this->fixture->getProperty('site_occupancy_index')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForFloorSpaceIndexReturnsItAsFormattedDecimal() {
		$this->realtyObject->setProperty('floor_space_index', 19.48);
		$localeConvention = localeconv();

		$this->assertEquals(
			'19' . $localeConvention['decimal_point'] . '48',
			$this->fixture->getProperty('floor_space_index')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForWindowBankReturnsItAsFormattedWidth() {
		$this->realtyObject->setProperty('window_bank', 12.34);
		$localeConvention = localeconv();

		$this->assertEquals(
			'12' . $localeConvention['decimal_point'] . '34&nbsp;' .
				$this->fixture->translate('label_meter'),
			$this->fixture->getProperty('window_bank')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsRentPerSquareMeterAsFormattedPriceWithDecimals() {
		$this->realtyObject->setProperty('rent_per_square_meter', 12345.67);

		$this->assertEquals(
			'&euro; 12.345,67',
			$this->fixture->getProperty('rent_per_square_meter')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForRentPerSquareMeterAddsZeroDecimals() {
		$this->realtyObject->setProperty('rent_per_square_meter', 12345);

		$this->assertEquals(
			'&euro; 12.345,00',
			$this->fixture->getProperty('rent_per_square_meter')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsRentalIncomeTargetAsFormattedPriceWithDecimals() {
		$this->realtyObject->setProperty('rental_income_target', 12345.67);

		$this->assertEquals(
			'&euro; 12.345,67',
			$this->fixture->getProperty('rental_income_target')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsParkingSpacesAsInteger() {
		$this->realtyObject->setProperty('parking_spaces', 3);

		$this->assertEquals(
			'3',
			$this->fixture->getProperty('parking_spaces')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForExistingFurnishingCategoryReturnsCategoryLabel() {
		$this->realtyObject->setProperty('furnishing_category', 1);

		$this->assertEquals(
			$this->fixture->translate('label_furnishing_category_1'),
			$this->fixture->getProperty('furnishing_category')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForInvalidFurnishingCategoryReturnsEmptyString() {
		$this->realtyObject->setProperty('furnishing_category', 42);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('furnishing_category')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForExistingFlooringReturnsFlooringLabel() {
		$this->realtyObject->setProperty('flooring', 1);

		$this->assertEquals(
			$this->fixture->translate('label_flooring_1'),
			$this->fixture->getProperty('flooring')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForTwoFlooringsReturnsBothFlooringLabels() {
		$this->realtyObject->setProperty('flooring', '1,2');

		$property = $this->fixture->getProperty('flooring');

		$this->assertContains(
			$this->fixture->translate('label_flooring_1'),
			$property,
			'First flooring label was not found.'
		);
		$this->assertContains(
			$this->fixture->translate('label_flooring_2'),
			$property,
			'Second flooring label was not found.'
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForInvalidFlooringReturnsEmptyString() {
		$this->realtyObject->setProperty('flooring', 42);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('flooring')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsDistanceToTheSeaAsIntegerWithUnit() {
		$this->realtyObject->setDistanceToTheSea(42);

		$this->realtyObject->setProperty('window_bank', 12.34);

		$this->assertSame(
			'42&nbsp;' . $this->fixture->translate('label_meter'),
			$this->fixture->getProperty('distance_to_the_sea')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForZeroDistanceToTheSeaReturnsEmptyString() {
		$this->realtyObject->setDistanceToTheSea(0);

		$this->assertSame(
			'',
			$this->fixture->getProperty('distance_to_the_sea')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForSeaViewReturnsYes() {
		$this->realtyObject->setProperty('sea_view', 1);

		$this->assertSame(
			$this->fixture->translate('message_yes'),
			$this->fixture->getProperty('sea_view')
		);
	}

	/**
	 * @test
	 */
	public function getPropertyForNonSeaViewReturnsEmptyString() {
		$this->realtyObject->setProperty('sea_view', 0);

		$this->assertSame(
			'',
			$this->fixture->getProperty('sea_view')
		);
	}


	/////////////////////////////////////////
	// Tests concerning formatDecimal
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function formatDecimalForZeroReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->fixture->formatDecimal(0)
		);
	}

	/**
	 * @test
	 */
	public function formatDecimalForFloatWithAllZeroDecimalsReturnsNumberWithoutDecimals() {
		$this->assertSame(
			'4',
			$this->fixture->formatDecimal(4.00)
		);
	}

	/**
	 * @test
	 */
	public function formatDecimalForFloatWithOnceDecimalReturnsNumberWithTwoDecimals() {
		$this->assertSame(
			'4.50',
			$this->fixture->formatDecimal(4.50)
		);
	}

	/**
	 * @test
	 */
	public function formatDecimalForFloatWithTwoNonZeroDecimalsReturnsNumberWithBothDecimals() {
		$this->assertEquals(
			'4.55',
			$this->fixture->formatDecimal(4.55)
		);
	}

	/**
	 * @test
	 */
	public function formatDecimalForFloatWithThreeDecimalsLastDecimalLowerThanFiveReturnsNumberWithOnlyTwoDecimals() {
		$this->assertEquals(
			'4.55',
			$this->fixture->formatDecimal(4.553)
		);
	}

	/**
	 * @test
	 */
	public function formatDecimalForFloatWithThreeDecimalsLastDecimalFiveReturnsNumberWithLastDecimalRoundedUp() {
		$this->assertEquals(
			'4.56',
			$this->fixture->formatDecimal(4.555)
		);
	}

	/**
	 * @test
	 */
	public function formatDecimalCanRoundToOneDecimal() {
		$this->assertEquals(
			'4.1',
			$this->fixture->formatDecimal(4.1234, 1)
		);
	}

	/**
	 * @test
	 */
	public function formatDecimalCanRoundToTwoDecimals() {
		$this->assertEquals(
			'4.12',
			$this->fixture->formatDecimal(4.1234, 2)
		);
	}

	/**
	 * @test
	 */
	public function formatDecimalCanRoundToThreeDecimals() {
		$this->assertEquals(
			'4.123',
			$this->fixture->formatDecimal(4.1234, 3)
		);
	}
}