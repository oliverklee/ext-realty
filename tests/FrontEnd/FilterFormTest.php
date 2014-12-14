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
class tx_realty_FrontEnd_FilterFormTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_filterForm
	 */
	private $fixture;
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	protected function setUp() {
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

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	///////////////////////////
	// Testing the rendering.
	///////////////////////////

	/**
	 * @test
	 */
	public function filterFormHasSubmitButton() {
		$this->assertContains(
			$this->fixture->translate('label_search'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function filterFormLinksToConfiguredTargetPage() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('filterTargetPID', $pageUid);

		$this->assertContains(
			'?id=' . $pageUid,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNoUnreplacedMarkers() {
		$this->assertNotContains(
			'###',
			$this->fixture->render()
		);
	}


	//////////////////////////////////////////////
	// Testing the rendering of the site search.
	//////////////////////////////////////////////

	/**
	 * @test
	 */
	public function filterFormHasSiteSearchInputIfEnabledByConfiguration() {
		// "showSiteSearchInFilterForm" is set to "show" during setup().

		$this->assertContains(
			'id="tx_realty_pi1-site"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function filterFormHasNoSiteSearchInputIfDisabledByConfiguration() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');

		$this->assertNotContains(
			'id="tx_realty_pi1-site"',
			$this->fixture->render()
		);
	}


	///////////////////////////////////////////////
	// Testing the rendering of the price filter.
	///////////////////////////////////////////////

	/**
	 * @test
	 */
	public function filterFormHasNoPricesSelectboxForUnconfiguredFilter() {
		$this->assertNotContains(
			'<select',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function filterFormForConfiguredFilterOptionsButDisplayedSearchFieldsEmptyHidesPricesSelect() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');
		$this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertNotContains(
			'<select',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function filterFormHasPricesSelectForConfiguredFilterOptions() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertContains(
			'<select',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function priceRangeIsDisplayedWithCurrency() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', 'priceRanges');
		$this->fixture->setConfigurationValue('currencyUnit', 'EUR');
		$this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertContains(
			'&euro;',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function optionWithLowerAndUpperPriceLimitCanBeDisplayed() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', 'priceRanges');
		$this->fixture->setConfigurationValue('currencyUnit', 'EUR');
		$this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertContains(
			'&euro; 1,00 ' . $this->fixture->translate('label_to') . ' &euro; 100,00',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function optionWithLowerPriceLimitCanBeDisplayed() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-');

		$this->assertContains(
			$this->fixture->translate('label_greater_than') . ' 1',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function optionWithUpperPriceLimitCanBeDisplayed() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue(
			'priceRangesForFilterForm', '-100'
		);

		$this->assertContains(
			$this->fixture->translate('label_less_than') . ' 100',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function priceRangeForNoOtherDisplayedSearchFieldsGetsOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue(
			'priceRangesForFilterForm', '-100'
		);

		$this->assertContains(
			'onchange="',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function priceRangeForNoOtherDisplayedSearchFieldsHasSubmitButton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges'
		);
		$this->fixture->setConfigurationValue(
			'priceRangesForFilterForm', '-100'
		);

		$this->assertContains(
			$this->fixture->translate('label_search'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function priceRangeForOtherDisplayedSearchFieldDoesNotHaveOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'priceRanges, uid'
		);
		$this->fixture->setConfigurationValue(
			'priceRangesForFilterForm', '-100'
		);

		$this->assertNotContains(
			'onchange="',
			$this->fixture->render()
		);
	}


	////////////////////////////////////////////
	// Testing the rendering of the UID search
	////////////////////////////////////////////

	/**
	 * @test
	 */
	public function searchFormForDisplayedSearchWidgetFieldsEmptyHidesUidSearchField() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');

		$this->assertNotContains(
			'name="tx_realty_pi1[uid]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function searchFormForDisplayedSearchWidgetFieldsSetToUidContainsUidSearchField() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'uid'
		);

		$this->assertContains(
			'name="tx_realty_pi1[uid]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function searchFormForDisplayedSearchWidgetFieldsSetToUidAndSetUidSetsUidAsValueForInputField() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'uid'
		);

		$this->assertContains(
			'value="42"',
			$this->fixture->render(array('uid' => 42))
		);
	}

	/**
	 * @test
	 */
	public function searchFormForDisplayedSearchWidgetFieldsSetToUidAndUidSetAsStringSetsEmptyValue() {
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

	/**
	 * @test
	 */
	public function searchFormForDisplayedSearchWidgetFieldsEmptyHidesObjectNumberSearchField() {
		$this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');

		$this->assertNotContains(
			'name="tx_realty_pi1[objectNumber]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function searchFormForDisplayedSearchWidgetFieldsSetToObjectNumberContainsObjectNumberSearchField() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectNumber'
		);

		$this->assertContains(
			'name="tx_realty_pi1[objectNumber]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function searchFormForDisplayedSearchWidgetFieldsSetToObjectNumberAndGivenObjectNumberSetsValueOfObjectNumberField() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectNumber'
		);

		$this->assertContains(
			'value="Foo 22"',
			$this->fixture->render(array('objectNumber' => 'Foo 22'))
		);
	}

	/**
	 * @test
	 */
	public function searchFormForEmptyDisplayedSearchWidgetFieldsIsHidden() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', ''
		);

		$this->assertNotContains(
			'id="tx_realty_pi1-idsearch"',
			$this->fixture->render()
		);
	}


	//////////////////////////////////////////////////////
	// Tests concerning the rendering of the city search
	//////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function displayedSearchWidgetSetToCitySearchShowsCitySearch() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city'
		);

		$this->assertContains(
			$this->fixture->translate('label_select_city'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function displayedSearchWidgetSetToCitySearchShowsCityOfEnteredObject() {
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'Foo city')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city'
		);

		$this->assertContains(
			'Foo city',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function citySelectorForNoOtherDisplayedSearchFieldsGetsOnChangeSubmit() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city'
		);

		$this->assertContains(
			'onchange="document.forms',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function citySelectorForOtherDisplayedSearchFieldDoesNotHaveOnChangeSubmit() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city, priceRanges'
		);

		$this->assertNotContains(
			'onchange="document.forms',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function citySelectorWithDistrictSelectorHasOnChangeUpdateDistricts() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city, district'
		);

		$this->assertContains(
			'onchange="updateDistrictsInSearchWidget',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function citySelectorWithoutDistrictSelectorNotIncludesPrototype() {
		$this->testingFramework->createRecord('tx_realty_cities');
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city'
		);

		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']->additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			])
		);
	}

	/**
	 * @test
	 */
	public function citySelectorWithUidSelectorNotIncludesPrototype() {
		$this->testingFramework->createRecord('tx_realty_cities');
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city,uid'
		);

		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']->additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			])
		);
	}

	/**
	 * @test
	 */
	public function citySelectorWithDistrictSelectorIncludesPrototype() {
		$this->testingFramework->createRecord('tx_realty_cities');
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city,district'
		);

		$this->fixture->render();

		$this->assertTrue(
			isset($GLOBALS['TSFE']->additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			])
		);
	}

	/**
	 * @test
	 */
	public function citySelectorWithoutDistrictSelectorsNotIncludesMainJavaScript() {
		$this->testingFramework->createRecord('tx_realty_cities');
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city'
		);

		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']->additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID
			])
		);
	}

	/**
	 * @test
	 */
	public function citySelectorWithUidSelectorsNotIncludesMainJavaScript() {
		$this->testingFramework->createRecord('tx_realty_cities');
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city,uid'
		);

		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']->additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID
			])
		);
	}

	/**
	 * @test
	 */
	public function citySelectorWithDistrictSelectorIncludesMainJavaScript() {
		$this->testingFramework->createRecord('tx_realty_cities');
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city,district'
		);

		$this->fixture->render();

		$this->assertTrue(
			isset($GLOBALS['TSFE']->additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID
			])
		);
	}


	//////////////////////////////////////////////////////////
	// Tests concerning the rendering of the district search
	//////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function displayedSearchWidgetSetToDistrictSearcShowsDistrictSearch() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'district'
		);

		$this->assertContains(
			$this->fixture->translate('label_select_district'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function displayedSearchWidgetSetToDistrictSearchShowsDistrictOfEnteredObject() {
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('title' => 'Foo district')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('district' => $districtUid)
		);
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'district'
		);

		$this->assertContains(
			'Foo district',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function districtSelectorForNoOtherDisplayedSearchFieldsGetsOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'district'
		);

		$this->assertContains(
			'onchange="',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function districtSelectorForOtherDisplayedSearchFieldDoesNotHaveOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'district, priceRanges'
		);

		$this->assertNotContains(
			'onchange="',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function districtSearchWithoutCitySearchShowsDistrictSearch() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'district'
		);

		$this->assertContains(
			'id="tx_realty_pi1_searchWidget_district" style="display: block;"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function districtSearchWithCitySearchHidesDistrictSearch() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'city,district'
		);

		$this->assertContains(
			'id="tx_realty_pi1_searchWidget_district" style="display: none;"',
			$this->fixture->render()
		);
	}
	/**
	 * @test
	 */
	public function districtSelectorWithoutOtherSelectorsNotIncludesPrototype() {
		$this->testingFramework->createRecord(
			'tx_realty_districts'
		);
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'district'
		);

		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']->additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			])
		);
	}


	////////////////////////////////////////////////////////////
	// Tests concerning the rendering of the house type search
	////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function searchFormWithDisplayedSearchWidgetSetToHouseTypeSearchShowsHouseTypeSearch() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'houseType'
		);

		$this->assertContains(
			$this->fixture->translate('label_select_house_type'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function searchFormWithDisplayedSearchWidgetSetToHouseTypeSearchShowsHouseTypeOfEnteredObject() {
		$houseTypeUid = $this->testingFramework->createRecord(
			'tx_realty_house_types', array('title' => 'Foo house type')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('title' => 'foo', 'house_type' => $houseTypeUid)
		);
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'houseType'
		);

		$this->assertContains(
			'Foo house type',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function houseTypeSelectorForNoOtherDisplayedSearchFieldsGetsOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'houseType'
		);

		$this->assertContains(
			'onchange="',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function houseTypeSelectorForOtherDisplayedSearchFieldDoesNotHaveOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'houseType, priceRanges'
		);

		$this->assertNotContains(
			'onchange="',
			$this->fixture->render()
		);
	}


	///////////////////////////////////////////////////////////////////
	// Tests concerning the rendering of the objectType radio buttons
	///////////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function objectTypeSelectorForDisplayedSearchWidgetFieldsSetToObjectTypeDisplaysRadioButtons() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			$this->fixture->translate('label_select_object_type'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function objectTypeSelectorForDisplayedSearchWidgetFieldsSetToObjectTypeHasNoDefaultSelectRadioButtons() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertNotContains(
			'checked="checked"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function objectTypeSelectorForObjectTypeSetToRentPreselectsRentRadioButton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'value="forRent" checked="checked"',
			$this->fixture->render(array('objectType' => 'forRent'))
		);
	}

	/**
	 * @test
	 */
	public function objectTypeSelectorForObjectTypeSetToSalePreselectsSaleRadioButton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'value="forSale" checked="checked"',
			$this->fixture->render(array('objectType' => 'forSale'))
		);
	}

	/**
	 * @test
	 */
	public function objectTypeSelectorForNoOtherDisplayedSearchFieldsGetsOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'onchange="',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function objectTypeSelectorForOtherDisplayedSearchFieldsDoesNotHaveOnChangeAttribute() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType,city'
		);

		$this->assertNotContains(
			'onchange="',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function objectTypeSelectorForNoOtherDisplayedSearchFieldsGetsOnChangeAttributeOnForRentRadioButton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'value="forRent" onchange="',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function objectTypeSelectorForNoOtherDisplayedSearchFieldsGetsOnChangeAttributeOnForSaleRadioButton() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'objectType'
		);

		$this->assertContains(
			'value="forSale" onchange="',
			$this->fixture->render()
		);
	}


	/////////////////////////////////////////////////////////////////////////
	// Tests concerning the rendering of the rent/buying price input fields
	/////////////////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function searchFormForSetRentInputFieldsDisplaysRentInputFields() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'rent'
		);

		$this->assertContains(
			$this->fixture->translate('label_enter_rent'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function searchFormForSetRentInputFieldsAndSentDataEntersSentDataIntoInputFields() {
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

	/**
	 * @test
	 */
	public function searchFormForSetLivingAreaInputFieldsDisplaysLivingAreaInputFields() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'livingArea'
		);

		$this->assertContains(
			$this->fixture->translate('label_enter_living_area'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function searchFormForSetLivingAreaInputFieldsAndSentDataEntersSentDataIntoInputFields() {
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

	/**
	 * @test
	 */
	public function whereClauseOnlyForLowerPriceLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((tx_realty_objects.rent_excluding_bills >= 1) ' .
				'OR (tx_realty_objects.buying_price >= 1))',
			$this->fixture->getWhereClausePart(array('priceRange' => '1-'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseOnlyForUpperPriceLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((tx_realty_objects.rent_excluding_bills > 0 ' .
				'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
				'OR (tx_realty_objects.buying_price > 0 ' .
				'AND tx_realty_objects.buying_price <= 10) ' .
				'OR (tx_realty_objects.rent_excluding_bills = 0 ' .
				'AND tx_realty_objects.buying_price = 0))',
			$this->fixture->getWhereClausePart(array('priceRange' => '-10'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForUpperPlusLowerPriceLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((tx_realty_objects.rent_excluding_bills >= 1 ' .
				'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
				'OR (tx_realty_objects.buying_price >= 1 ' .
				'AND tx_realty_objects.buying_price <= 10))',
			$this->fixture->getWhereClausePart(array('priceRange' => '1-10'))
		);
	}

	/**
	 * @test
	 */
	public function searchStringForZipIsNotLongerThanTwoCharacters() {
		$this->assertContains(
			'tx_realty_objects.zip LIKE "fo%"',
			$this->fixture->getWhereClausePart(array('site' => 'foo'))
		);
	}

	/**
	 * @test
	 */
	public function searchStringForSiteIsEscapedForLike() {
		$this->assertContains(
			'tx_realty_cities.title LIKE "%f\\\%oo%")',
			$this->fixture->getWhereClausePart(array('site' => 'f%oo'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForPriceRangeCanBeAppendedToSiteSearchWhereClause() {
		$this->assertEquals(
			' AND (tx_realty_objects.zip LIKE "fo%" ' .
				'OR tx_realty_cities.title LIKE "%foo%") ' .
				'AND ((tx_realty_objects.rent_excluding_bills >= 1 ' .
				'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
				'OR (tx_realty_objects.buying_price >= 1 ' .
				'AND tx_realty_objects.buying_price <= 10))',
			$this->fixture->getWhereClausePart(
				array('site' => 'foo', 'priceRange' => '1-10')
			)
		);
	}

	/**
	 * @test
	 */
	public function whereClauseIsEmptyForInvalidNumericKeyForPriceRange() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('priceRange' => '-100-'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseIsEmptyForInvalidStringKeyForPriceRange() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('priceRange' => 'foo'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseIsEmptyForEmptySite() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('site' => ''))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForUidSearchWithNonZeroUidCanBeCreated() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'uid');

		$this->assertEquals(
			' AND tx_realty_objects.uid=1',
			$this->fixture->getWhereClausePart(array('uid' => 1))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForUidSearchWithZeroUidIsEmpty() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'uid');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('uid' => 0))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForCitySearchWithNonZeroCityCanBeCreated() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'city');

		$this->assertEquals(
			' AND tx_realty_objects.city = 1',
			$this->fixture->getWhereClausePart(array('city' => 1))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForDistrictSearchWithNonZeroDistrictCanBeCreated() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'district');

		$this->assertEquals(
			' AND tx_realty_objects.district = 1',
			$this->fixture->getWhereClausePart(array('district' => 1))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForHouseTypeSearchWithNonZeroHouseTypeCanBeCreated() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'houseType');

		$this->assertEquals(
			' AND tx_realty_objects.house_type = 1',
			$this->fixture->getWhereClausePart(array('houseType' => 1))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForCitySearchWithZeroCityIsEmpty() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'city');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('city' => 0))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForHouseTypeSearchWithZeroHouseTypeIsEmpty() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'houseType');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('houseType' => 0))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForObjectNumberSearchWithNonEmptyObjectNumberCanBeCreated() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

		$this->assertEquals(
			' AND tx_realty_objects.object_number="foo"',
			$this->fixture->getWhereClausePart(array('objectNumber' => 'foo'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForObjectNumberSearchWithEmptyObjectNumberIsEmpty() {
		$this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('objectNumber' => ''))
		);
	}

	/**
	 * @test
	 */
	public function getWhereClausePartForObjectTypeSelectorWithSaleSelectedReturnsSaleWhereClausePart() {
		$this->assertEquals(
			' AND tx_realty_objects.object_type = ' .
				tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
			$this->fixture->getWhereClausePart(array('objectType' => 'forSale'))
		);
	}

	/**
	 * @test
	 */
	public function getWhereClausePartForObjectTypeSelectorWithRentSelectedReturnsRentWhereClausePart() {
		$this->assertEquals(
			' AND tx_realty_objects.object_type = ' .
				tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
			$this->fixture->getWhereClausePart(array('objectType' => 'forRent'))
		);
	}

	/**
	 * @test
	 */
	public function getWhereClausePartForObjectTypeSelectorWithNothingSelectedReturnsEmptyString() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('objectType' => ''))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseOnlyForLowerRentLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((tx_realty_objects.rent_excluding_bills >= 1) ' .
				'OR (tx_realty_objects.buying_price >= 1))',
			$this->fixture->getWhereClausePart(array('rentFrom' => '1'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseOnlyForUpperRentLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((tx_realty_objects.rent_excluding_bills > 0 ' .
				'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
				'OR (tx_realty_objects.buying_price > 0 ' .
				'AND tx_realty_objects.buying_price <= 10) ' .
				'OR (tx_realty_objects.rent_excluding_bills = 0 ' .
				'AND tx_realty_objects.buying_price = 0))',
			$this->fixture->getWhereClausePart(array('rentTo' => '10'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForUpperPlusLowerRentLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((tx_realty_objects.rent_excluding_bills >= 1 ' .
				'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
				'OR (tx_realty_objects.buying_price >= 1 ' .
				'AND tx_realty_objects.buying_price <= 10))',
			$this->fixture->getWhereClausePart(
				array('rentFrom' => '1', 'rentTo' => '10')
			)
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForUpperPlusLowerRentAndPriceLimitOverwritesPriceLimitWithRentLimit() {
		$this->assertEquals(
			' AND ((tx_realty_objects.rent_excluding_bills >= 1 ' .
				'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
				'OR (tx_realty_objects.buying_price >= 1 ' .
				'AND tx_realty_objects.buying_price <= 10))',
			$this->fixture->getWhereClausePart(
				array('rentFrom' => '1', 'rentTo' => '10', 'priceRange' => '100-1000')
			)
		);
	}


	///////////////////////////////////////////////////////////////
	// Tests concerning the WHERE clause part for the living area
	///////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function whereClauseOnlyForLowerLivingAreaLimitCanBeCreated() {
		$this->assertEquals(
			' AND (tx_realty_objects.living_area >= 1)',
			$this->fixture->getWhereClausePart(array('livingAreaFrom' => '1'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseOnlyForUpperLivingAreaLimitCanBeCreated() {
		$this->assertEquals(
			' AND (tx_realty_objects.living_area <= 10)',
			$this->fixture->getWhereClausePart(array('livingAreaTo' => '10'))
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForUpperPlusLowerLivingAreaLimitCanBeCreated() {
		$this->assertEquals(
			' AND (tx_realty_objects.living_area >= 1)' .
			' AND (tx_realty_objects.living_area <= 10)',
			$this->fixture->getWhereClausePart(
				array('livingAreaFrom' => '1', 'livingAreaTo' => '10')
			)
		);
	}


	/////////////////////////////////////////////////////////////////////
	// Tests concerning the rendering of the numberOfRooms input fields
	/////////////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function searchFormForSetNumberOfRoomsInputFieldsDisplaysNumberOfRoomsInputFields() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'numberOfRooms'
		);

		$this->assertContains(
			$this->fixture->translate('label_enter_number_of_rooms'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function searchFormForSetNumberOfRoomsInputFieldsAndRoomsFromSetEntersSentDataIntoInputFields() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'numberOfRooms'
		);

		$this->assertContains(
			'value="1"',
			$this->fixture->render(array('numberOfRoomsFrom' => '1'))
		);
	}

	/**
	 * @test
	 */
	public function searchFormForSetNumberOfRoomsInputFieldsAndRoomsToSetEntersSentDataIntoInputFields() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'numberOfRooms'
		);

		$this->assertContains(
			'value="2"',
			$this->fixture->render(array('numberOfRoomsTo' => '2'))
		);
	}

	/**
	 * @test
	 */
	public function searchFormForSetNumberOfRoomsInputFieldsAndRoomsFromZeroSetsEmptyValueForRoomsFromInput() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'numberOfRooms'
		);

		$output = $this->fixture->render(
			array('numberOfRoomsFrom' => '0')
		);

		$this->assertContains(
			'value=""',
			$output
		);
	}

	/**
	 * @test
	 */
	public function searchFormForNumberOfRoomsWithTwoDecimalsRoundsToOneDecimal() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'numberOfRooms'
		);

		$output = $this->fixture->render(
			array('numberOfRoomsTo' => '15.22')
		);

		$this->assertContains(
			'value="15.2"',
			$output
		);
	}

	/**
	 * @test
	 */
	public function searchFormForSetNumberOfRoomsInputFieldsAndDataWithCommaAsDecimalSeparatorKeepsDecimalAfterSeparator() {
		$this->fixture->setConfigurationValue(
			'displayedSearchWidgetFields', 'numberOfRooms'
		);

		$output = $this->fixture->render(
			array('numberOfRoomsTo' => '15,2')
		);

		$this->assertContains(
			'value="15.2"',
			$output
		);
	}


	///////////////////////////////////////////////////////////////////
	// Tests concerning the WHERE clause part for the number of rooms
	///////////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function whereClauseOnlyForLowerNumberOfRoomsLimitCanBeCreated() {
		$this->assertEquals(
			' AND (tx_realty_objects.number_of_rooms >= 1)',
			$this->fixture->getWhereClausePart(
				array('numberOfRoomsFrom' => 1)
			)
		);
	}

	/**
	 * @test
	 */
	public function whereClauseOnlyForUpperNumberOfRoomsLimitCanBeCreated() {
		$this->assertEquals(
			' AND (tx_realty_objects.number_of_rooms <= 10)',
			$this->fixture->getWhereClausePart(
				array('numberOfRoomsTo' => 10)
			)
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForUpperPlusLowerNumberOfRoomsLimitCanBeCreated() {
		$this->assertEquals(
			' AND (tx_realty_objects.number_of_rooms >= 1)' .
			' AND (tx_realty_objects.number_of_rooms <= 10)',
			$this->fixture->getWhereClausePart(
				array('numberOfRoomsFrom' => 1, 'numberOfRoomsTo' => 10)
			)
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForLowerNumberOfRoomsLimitWithDecimalsCreatesWhereClauseWithCompleteNumber() {
		$this->assertEquals(
			' AND (tx_realty_objects.number_of_rooms >= 1.5)',
			$this->fixture->getWhereClausePart(
				array('numberOfRoomsFrom' => 1.5)
			)
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForLowerNumberOfRoomsLimitStringDoesNotAddWhereClause() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(
				array('numberOfRoomsFrom' => 'foo')
			)
		);
	}

	/**
	 * @test
	 */
	public function whereClauseForLowerNumberOfRoomsLimitWithCommaAsDecimalSeparatorReplacesCommaWithDot() {
		$this->assertEquals(
			' AND (tx_realty_objects.number_of_rooms >= 1.8)',
			$this->fixture->getWhereClausePart(
				array('numberOfRoomsFrom' => '1,8')
			)
		);
	}


	/////////////////////////////////////////
	// Tests concerning createDropDownItems
	/////////////////////////////////////////
	// Note: We test only the details for cities. The districts work the same.

	/**
	 * @test
	 */
	public function createDropDownItemsWithInvalidTypeThrowsException() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'"foo" is not a valid type.'
		);

		$this->fixture->createDropDownItems('foo');
	}

	/**
	 * @test
	 */
	public function createDropDownItemsForCityWithObjectContainsItemWithCityName() {
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'Foo city')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);

		$this->assertContains(
			'Foo city',
			$this->fixture->createDropDownItems('city')
		);
	}

	/**
	 * @test
	 */
	public function createDropDownItemsForCityWithObjectContainsItemWithCityUid() {
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'Foo city')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);

		$this->assertContains(
			'value="' . $cityUid . '"',
			$this->fixture->createDropDownItems('city')
		);
	}

	/**
	 * @test
	 */
	public function createDropDownItemsForCityWithObjectContainsItemWithCountOfMatches() {
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'Foo city')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);

		$this->assertContains(
			'Foo city (2)',
			$this->fixture->createDropDownItems('city')
		);
	}

	/**
	 * @test
	 */
	public function createDropDownItemsForCityNotContainsCityWithoutMatches() {
		$this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'Foo city')
		);

		$this->assertNotContains(
			'Foo city',
			$this->fixture->createDropDownItems('city')
		);
	}

	/**
	 * @test
	 */
	public function createDropDownItemsForCityWithSelectedZeroNotContainsSelected() {
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'Foo city')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);

		$this->assertNotContains(
			'selected="selected"',
			$this->fixture->createDropDownItems('city', 0)
		);
	}

	/**
	 * @test
	 */
	public function createDropDownItemsForCityWithSelectedUidOfSelectsCityWithThatUid() {
		$cityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'Foo city')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $cityUid)
		);

		$this->assertContains(
			'value="' . $cityUid . '" selected="selected"',
			$this->fixture->createDropDownItems('city', $cityUid)
		);
	}

	/**
	 * @test
	 */
	public function createDropDownItemsForDistrictWithObjectContainsItemWithDistrictName() {
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('title' => 'Foo district')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('district' => $districtUid)
		);

		$this->assertContains(
			'Foo district',
			$this->fixture->createDropDownItems('district')
		);
	}

	/**
	 * @test
	 */
	public function createDropDownItemsForDistrictNotContainsDistrictWithoutMatches() {
		$this->testingFramework->createRecord(
			'tx_realty_districts', array('title' => 'Foo district')
		);

		$this->assertNotContains(
			'Foo district',
			$this->fixture->createDropDownItems('district')
		);
	}

	//////////////////////////////////
	// Tests concerning getPiVarKeys
	//////////////////////////////////

	/**
	 * @test
	 */
	public function getPiVarKeysReturnsAnArray() {
		$this->assertTrue(
			is_array(tx_realty_filterForm::getPiVarKeys())
		);
	}

	/**
	 * @test
	 */
	public function getPiVarKeysReturnsNonEmptyArray() {
		$result = tx_realty_filterForm::getPiVarKeys();

		$this->assertTrue(
			!empty($result)
		);
	}
}