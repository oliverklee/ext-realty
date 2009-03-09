<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_Autoloader.php');

require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_filterForm.php');

/**
 * Unit tests for the tx_realty_filterForm class in the 'realty' extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_filterForm_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_filterForm
	 */
	private $fixture;
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_filterForm(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'displayedSearchWidgetFields' => 'site',
			),
			$GLOBALS['TSFE']->cObj
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////////
	// Testing the rendering.
	///////////////////////////

	public function testFilterFormHasSubmitButton() {
		$this->assertContains(
			$this->fixture->translate('label_search'),
			$this->fixture->render(array())
		);
	}

	public function testFilterFormLinksToConfiguredTargetPage() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('filterTargetPID', $pageUid);

		$this->assertContains(
			'?id=' . $pageUid,
			$this->fixture->render(array())
		);
	}

	public function testRenderReturnsNoUnreplacedMarkers() {
		$this->assertNotContains(
			'###',
			$this->fixture->render(array())
		);
	}


	//////////////////////////////////////////////
	// Testing the rendering of the site search.
	//////////////////////////////////////////////

	public function testFilterFormHasSiteSearchInputIfEnabledByConfiguration() {
		// "showSiteSearchInFilterForm" is set to "show" during setup().

		$this->assertContains(
			'id="tx_realty_pi1-site"',
			$this->fixture->render(array())
		);
	}

	public function testFilterFormHasNoSiteSearchInputIfDisabledByConfiguration() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');

		$this->assertNotContains(
			'id="tx_realty_pi1-site"',
			$this->fixture->render(array())
		);
	}


	///////////////////////////////////////////////
	// Testing the rendering of the price filter.
	///////////////////////////////////////////////

	public function testFilterFormHasNoPricesSelectboxForUnconfiguredFilter() {
		$this->assertNotContains(
			'<select',
			$this->fixture->render(array())
		);
	}

	public function testFilterForm_ForConfiguredFilterOptionsButDisplayedSearchFieldsEmpty_HidesPricesSelectbox() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');
		$this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertNotContains(
			'<select',
			$this->fixture->render(array())
		);
	}

	public function testFilterFormHasPricesSelectboxForConfiguredFilterOptions() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertContains(
			'<select',
			$this->fixture->render(array())
		);
	}

	public function testPriceRangeIsDisplayedWithCurrency() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue('currencyUnit', '&euro;');
		$this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertContains(
			'&euro;',
			$this->fixture->render(array())
		);
	}

	public function testOptionWithLowerAndUpperPriceLimitCanBeDisplayed() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue(
			'priceRangesForFilterForm', '1-100'
		);

		$this->assertContains(
			'1 ' . $this->fixture->translate('label_to') . ' 100',
			$this->fixture->render(array())
		);
	}

	public function testOptionWithLowerPriceLimitCanBeDisplayed() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-');

		$this->assertContains(
			$this->fixture->translate('label_greater_than') . ' 1',
			$this->fixture->render(array())
		);
	}

	public function testOptionWithUpperPriceLimitCanBeDisplayed() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue(
			'priceRangesForFilterForm', '-100'
		);

		$this->assertContains(
			$this->fixture->translate('label_less_than') . ' 100',
			$this->fixture->render(array())
		);
	}

	public function test_PriceRange_ForNoOtherDisplayedSearchFields_GetsOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue(
			'priceRangesForFilterForm', '-100'
		);

		$this->assertContains(
			'onchange="',
			$this->fixture->render(array())
		);
	}

	public function test_PriceRange_ForNoOtherDisplayedSearchFields_HasSubmitButton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue(
			'priceRangesForFilterForm', '-100'
		);

		$this->assertContains(
			$this->fixture->translate('label_search'),
			$this->fixture->render(array())
		);
	}

	public function test_PriceRange_ForOtherDisplayedSearchField_DoesNotHaveOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges, uid'
		);
		$this->fixture->setConfigurationValue(
			'priceRangesForFilterForm', '-100'
		);

		$this->assertNotContains(
			'onchange="',
			$this->fixture->render(array())
		);
	}


	////////////////////////////////////////////
	// Testing the rendering of the UID search
	////////////////////////////////////////////

	public function test_SearchForm_ForDisplayedSearchWidgetFieldsEmpty_HidesUidSearchField() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');

		$this->assertNotContains(
			'name="tx_realty_pi1[uid]"',
			$this->fixture->render(array())
		);
	}

	public function test_SearchForm_ForDisplayedSearchWidgetFieldsSetToUid_ContainsUidSearchField() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'uid'
		);

		$this->assertContains(
			'name="tx_realty_pi1[uid]"',
			$this->fixture->render(array())
		);
	}

	public function test_SearchForm_ForDisplayedSearchWidgetFieldsSetToUidAndSetUid_SetsUidAsValueForInputField() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'uid'
		);

		$this->assertContains(
			'value="42"',
			$this->fixture->render(array('uid' => 42))
		);
	}

	public function test_SearchForm_ForDisplayedSearchWidgetFieldsSetToUidAndUidSetAsString_SetsEmptyValue() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'uid'
		);

		$this->assertContains(
			'value=""',
			$this->fixture->render(array('uid' => 'foo'))
		);
	}


	//////////////////////////////////////////////////////
	// Testing the rendering of the object number search
	//////////////////////////////////////////////////////

	public function test_SearchForm_ForDisplayedSearchWidgetFieldsEmpty_HidesObjectNumberSearchField() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');

		$this->assertNotContains(
			'name="tx_realty_pi1[objectNumber]"',
			$this->fixture->render(array())
		);
	}

	public function test_SearchForm_ForDisplayedSearchWidgetFieldsSetToObjectNumber_ContainsObjectNumberSearchField() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectNumber'
		);

		$this->assertContains(
			'name="tx_realty_pi1[objectNumber]"',
			$this->fixture->render(array())
		);
	}

	public function test_SearchForm_ForDisplayedSearchWidgetFieldsSetToObjectNumberAndGivenObjectNumber_SetsValueOfObjectNumberField() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectNumber'
		);

		$this->assertContains(
			'value="Foo 22"',
			$this->fixture->render(array('objectNumber' => 'Foo 22'))
		);
	}

	public function test_SearchForm_ForEmptydisplayedSearchWidgetFields_IsHidden() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', ''
		);

		$this->assertNotContains(
			'id="tx_realty_pi1-idsearch"',
			$this->fixture->render(array())
		);
	}


	//////////////////////////////////////////////////////
	// Tests concerning the rendering of the city search
	//////////////////////////////////////////////////////

	public function test_SearchForm_DisplayedSearchWidgetSetToCitySearch_ShowsCitySearch() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city'
		);

		$this->assertContains(
			$this->fixture->translate('label_select_city'),
			$this->fixture->render(array())
		);
	}

	public function test_SearchForm_DisplayedSearchWidgetSetToCitySearch_ShowsCityOfEnteredObject() {
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('title' => 'Foo city')
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('title' => 'foo', 'city' => $cityUid)
		);
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city'
		);

		$this->assertContains(
			'Foo city',
			$this->fixture->render(array())
		);
	}

		public function test_CitySelector_ForNoOtherDisplayedSearchFields_GetsOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city'
		);

		$this->assertContains(
			'onchange="',
			$this->fixture->render(array())
		);
	}

	public function test_CitySelector_ForOtherDisplayedSearchField_DoesNotHaveOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city, priceRanges'
		);

		$this->assertNotContains(
			'onchange="',
			$this->fixture->render(array())
		);
	}


	///////////////////////////////////////////////////////////////////
	// Tests concerning the rendering of the objectType radio buttons
	///////////////////////////////////////////////////////////////////

	public function test_ObjectTypeSelector_ForDisplayedSeachrWidgetFieldsSetToObjectType_DisplaysRadioButtons() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			$this->fixture->translate('label_select_object_type'),
			$this->fixture->render(array())
		);
	}

	public function test_ObjectTypeSelector_ForDisplayedSeachrWidgetFieldsSetToObjectType_HasNoDefaultSelectRadiobuttons() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertNotContains(
			'checked="checked"',
			$this->fixture->render(array())
		);
	}

	public function test_ObjectTypeSelector_ForObjectTypeSetToRent_PreselectsRentRadiobutton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'value="forRent" checked="checked"',
			$this->fixture->render(array('objectType' => 'forRent'))
		);
	}

	public function test_ObjectTypeSelector_ForObjectTypeSetToSale_PreselectsSaleRadiobutton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'value="forSale" checked="checked"',
			$this->fixture->render(array('objectType' => 'forSale'))
		);
	}

	public function test_ObjectTypeSelector_ForNoOtherDisplayedSearchFields_GetsOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'onchange="',
			$this->fixture->render(array())
		);
	}

	public function test_ObjectTypeSelector_ForOtherDisplayedSearchFields_DoesNotHaveOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType,city'
		);

		$this->assertNotContains(
			'onchange="',
			$this->fixture->render(array())
		);
	}

	public function test_ObjectTypeSelector_ForNoOtherDisplayedSearchFields_GetsOnChangeAttributeOnForRentRadiobutton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'value="forRent" onchange="',
			$this->fixture->render(array())
		);
	}

	public function test_ObjectTypeSelector_ForNoOtherDisplayedSearchFields_GetsOnChangeAttributeOnForSaleRadiobutton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'value="forSale" onchange="',
			$this->fixture->render(array())
		);
	}


	/////////////////////////////////////////////////////////////////////////
	// Tests concerning the rendering of the rent/buying price input fields
	/////////////////////////////////////////////////////////////////////////

	public function test_SearchForm_ForSetRentInputFields_DisplaysRentInputFields() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'rent'
		);

		$this->assertContains(
			$this->fixture->translate('label_enter_rent'),
			$this->fixture->render(array())
		);
	}

	public function test_SearchForm_ForSetRentInputFieldsAndSentData_EntersSentDataIntoInputFields() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'rent'
		);

		$output = $this->fixture->render(
			array('rentFrom' => '42', 'rentTo' => '100')
		);

		$this->assertContains(
			'value="42"',
			$output
		);
		$this->assertContains(
			'value="100"',
			$output
		);
	}


	///////////////////////////////////////////////////////////////////
	// Tests concerning the rendering of the living area input fields
	///////////////////////////////////////////////////////////////////

	public function test_SearchForm_ForSetLivingAreaInputFields_DisplaysLivingAreaInputFields() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'livingArea'
		);

		$this->assertContains(
			$this->fixture->translate('label_enter_living_area'),
			$this->fixture->render(array())
		);
	}

	public function test_SearchForm_ForSetLivingAreaInputFieldsAndSentData_EntersSentDataIntoInputFields() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'livingArea'
		);

		$output = $this->fixture->render(
			array('livingAreaFrom' => '42', 'livingAreaTo' => '100')
		);

		$this->assertContains(
			'value="42"',
			$output
		);
		$this->assertContains(
			'value="100"',
			$output
		);
	}

	///////////////////////////////////////////////////
	// Testing the filter form's WHERE clause parts.
	///////////////////////////////////////////////////

	public function testWhereClauseOnlyForLowerPriceLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1))',
			$this->fixture->getWhereClausePart(array('priceRange' => '1-'))
		);
	}

	public function testWhereClauseOnlyForUpperPriceLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills > 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price > 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills = 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price = 0))',
			$this->fixture->getWhereClausePart(array('priceRange' => '-10'))
		);
	}

	public function testWhereClauseForUpperPlusLowerPriceLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10))',
			$this->fixture->getWhereClausePart(array('priceRange' => '1-10'))
		);
	}

	public function testSearchStringForZipIsNotLongerThanTwoCharacters() {
		$this->assertContains(
			REALTY_TABLE_OBJECTS . '.zip LIKE "fo%"',
			$this->fixture->getWhereClausePart(array('site' => 'foo'))
		);
	}

	public function testSearchStringForSiteIsEscapedForLike() {
		$this->assertContains(
			REALTY_TABLE_CITIES . '.title LIKE "%f\\\%oo%")',
			$this->fixture->getWhereClausePart(array('site' => 'f%oo'))
		);
	}

	public function testWhereClauseForSiteSearchIsCanBeAppendedToPriceRangeWhereClause() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10)) ' .
				'AND (' . REALTY_TABLE_OBJECTS . '.zip LIKE "fo%" ' .
				'OR ' . REALTY_TABLE_CITIES . '.title LIKE "%foo%")',
			$this->fixture->getWhereClausePart(
				array('site' => 'foo', 'priceRange' => '1-10')
			)
		);
	}

	public function testWhereClauseIsEmptyForInvalidNumericKeyForPriceRange() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('priceRange' => '-100-'))
		);
	}

	public function testWhereClauseIsEmptyForInvalidStringKeyForPriceRange() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('priceRange' => 'foo'))
		);
	}

	public function testWhereClauseIsEmptyForEmptySite() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('site' => ''))
		);
	}

	public function testWhereClauseForUidSearchWithNonZeroUidCanBeCreated() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'uid');

		$this->assertEquals(
			' AND ' . REALTY_TABLE_OBJECTS . '.uid=1',
			$this->fixture->getWhereClausePart(array('uid' => 1))
		);
	}

	public function testWhereClauseForUidSearchWithZeroUidIsEmpty() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'uid');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('uid' => 0))
		);
	}

	public function testWhereClauseForObjectNumberSearchWithNonEmptyObjectNumberCanBeCreated() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

		$this->assertEquals(
			' AND ' . REALTY_TABLE_OBJECTS . '.object_number="foo"',
			$this->fixture->getWhereClausePart(array('objectNumber' => 'foo'))
		);
	}

	public function testWhereClauseForObjectNumberSearchWithEmptyObjectNumberIsEmpty() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('objectNumber' => ''))
		);
	}

	public function test_GetWhereClausePartForObjectTypeSelector_WithSaleSelected_ReturnsSaleWhereclausePart() {
		$this->assertEquals(
			' AND ' . REALTY_TABLE_OBJECTS . '.object_type = ' .
				REALTY_FOR_SALE,
			$this->fixture->getWhereClausePart(array('objectType' => 'forSale'))
		);
	}

	public function test_GetWhereClausePartForObjectTypeSelector_WithRentSelected_ReturnsRentWhereclausePart() {
		$this->assertEquals(
			' AND ' . REALTY_TABLE_OBJECTS . '.object_type = ' .
				REALTY_FOR_RENTING,
			$this->fixture->getWhereClausePart(array('objectType' => 'forRent'))
		);
	}

	public function test_GetWhereClausePartForObjectTypeSelector_WithNothingSelected_ReturnsEmptyString() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('objectType' => ''))
		);
	}

	public function test_WhereClause_OnlyForLowerRentLimit_CanBeCreated() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1))',
			$this->fixture->getWhereClausePart(array('rentFrom' => '1'))
		);
	}

	public function test_WhereClause_OnlyForUpperRentLimit_CanBeCreated() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills > 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price > 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills = 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price = 0))',
			$this->fixture->getWhereClausePart(array('rentTo' => '10'))
		);
	}

	public function test_WhereClause_ForUpperPlusLowerRentLimit_CanBeCreated() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10))',
			$this->fixture->getWhereClausePart(
				array('rentFrom' => '1', 'rentTo' => '10')
			)
		);
	}

	public function test_WhereClause_ForUpperPlusLowerRentAndPriceLimit_OverwritesPriceLimitWithRentLimit() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10))',
			$this->fixture->getWhereClausePart(
				array('rentFrom' => '1', 'rentTo' => '10', 'priceRange' => '100-1000')
			)
		);
	}


	///////////////////////////////////////////////////////////////
	// Tests concerning the WHERE clause part for the living area
	///////////////////////////////////////////////////////////////

	public function test_WhereClause_OnlyForLowerLivingAreaLimit_CanBeCreated() {
		$this->assertEquals(
			' AND (' . REALTY_TABLE_OBJECTS . '.living_area >= 1)',
			$this->fixture->getWhereClausePart(array('livingAreaFrom' => '1'))
		);
	}

	public function test_WhereClause_OnlyForUpperLivingAreaLimit_CanBeCreated() {
		$this->assertEquals(
			' AND (' . REALTY_TABLE_OBJECTS . '.living_area <= 10)',
			$this->fixture->getWhereClausePart(array('livingAreaTo' => '10'))
		);
	}

	public function test_WhereClause_ForUpperPlusLowerLivingAreaLimit_CanBeCreated() {
		$this->assertEquals(
			' AND (' . REALTY_TABLE_OBJECTS . '.living_area >= 1)' .
			' AND (' . REALTY_TABLE_OBJECTS . '.living_area <= 10)',
			$this->fixture->getWhereClausePart(
				array('livingAreaFrom' => '1', 'livingAreaTo' => '10')
			)
		);
	}
}
?>