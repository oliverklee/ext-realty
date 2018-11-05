<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_FilterFormTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_filterForm
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        $this->fixture = new tx_realty_filterForm(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'displayedSearchWidgetFields' => 'site',
            ],
            $this->getFrontEndController()->cObj
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /**
     * Returns the current front-end instance.
     *
     * @return TypoScriptFrontendController
     */
    private function getFrontEndController()
    {
        return $GLOBALS['TSFE'];
    }

    ///////////////////////////
    // Testing the rendering.
    ///////////////////////////

    /**
     * @test
     */
    public function filterFormHasSubmitButton()
    {
        self::assertContains(
            $this->fixture->translate('label_search'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function filterFormLinksToConfiguredTargetPage()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('filterTargetPID', $pageUid);

        self::assertContains(
            '?id=' . $pageUid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkers()
    {
        self::assertNotContains(
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
    public function filterFormHasSiteSearchInputIfEnabledByConfiguration()
    {
        // "showSiteSearchInFilterForm" is set to "show" during setup().

        self::assertContains(
            'id="tx_realty_pi1-site"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function filterFormHasNoSiteSearchInputIfDisabledByConfiguration()
    {
        $this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');

        self::assertNotContains(
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
    public function filterFormHasNoPricesSelectboxForUnconfiguredFilter()
    {
        self::assertNotContains(
            '<select',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function filterFormForConfiguredFilterOptionsButDisplayedSearchFieldsEmptyHidesPricesSelect()
    {
        $this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');
        $this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

        self::assertNotContains(
            '<select',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function filterFormHasPricesSelectForConfiguredFilterOptions()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'priceRanges'
        );
        $this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

        self::assertContains(
            '<select',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function priceRangeIsDisplayedWithCurrency()
    {
        $this->fixture->setConfigurationValue('displayedSearchWidgetFields', 'priceRanges');
        $this->fixture->setConfigurationValue('currencyUnit', 'EUR');
        $this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

        self::assertContains(
            '&euro;',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function optionWithLowerAndUpperPriceLimitCanBeDisplayed()
    {
        $this->fixture->setConfigurationValue('displayedSearchWidgetFields', 'priceRanges');
        $this->fixture->setConfigurationValue('currencyUnit', 'EUR');
        $this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-100');

        self::assertContains(
            '&euro; 1,00 ' . $this->fixture->translate('label_to') . ' &euro; 100,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function optionWithLowerPriceLimitCanBeDisplayed()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'priceRanges'
        );
        $this->fixture->setConfigurationValue('priceRangesForFilterForm', '1-');

        self::assertContains(
            $this->fixture->translate('label_greater_than') . ' 1',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function optionWithUpperPriceLimitCanBeDisplayed()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'priceRanges'
        );
        $this->fixture->setConfigurationValue(
            'priceRangesForFilterForm',
            '-100'
        );

        self::assertContains(
            $this->fixture->translate('label_less_than') . ' 100',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function priceRangeForNoOtherDisplayedSearchFieldsHasSubmitButton()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'priceRanges'
        );
        $this->fixture->setConfigurationValue(
            'priceRangesForFilterForm',
            '-100'
        );

        self::assertContains(
            $this->fixture->translate('label_search'),
            $this->fixture->render()
        );
    }

    ////////////////////////////////////////////
    // Testing the rendering of the UID search
    ////////////////////////////////////////////

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsEmptyHidesUidSearchField()
    {
        $this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');

        self::assertNotContains(
            'name="tx_realty_pi1[uid]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToUidContainsUidSearchField()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'uid'
        );

        self::assertContains(
            'name="tx_realty_pi1[uid]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToUidAndSetUidSetsUidAsValueForInputField()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'uid'
        );

        self::assertContains(
            'value="42"',
            $this->fixture->render(['uid' => 42])
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToUidAndUidSetAsStringSetsEmptyValue()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'uid'
        );

        self::assertContains(
            'value=""',
            $this->fixture->render(['uid' => 'foo'])
        );
    }

    //////////////////////////////////////////////////////
    // Testing the rendering of the object number search
    //////////////////////////////////////////////////////

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsEmptyHidesObjectNumberSearchField()
    {
        $this->fixture->setConfigurationValue('displayedSearchWidgetFields', '');

        self::assertNotContains(
            'name="tx_realty_pi1[objectNumber]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToObjectNumberContainsObjectNumberSearchField()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectNumber'
        );

        self::assertContains(
            'name="tx_realty_pi1[objectNumber]"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToObjectNumberAndGivenObjectNumberSetsValueOfObjectNumberField(
    ) {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectNumber'
        );

        self::assertContains(
            'value="Foo 22"',
            $this->fixture->render(['objectNumber' => 'Foo 22'])
        );
    }

    /**
     * @test
     */
    public function searchFormForEmptyDisplayedSearchWidgetFieldsIsHidden()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            ''
        );

        self::assertNotContains(
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
    public function displayedSearchWidgetSetToCitySearchShowsCitySearch()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'city'
        );

        self::assertContains(
            $this->fixture->translate('label_select_city'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function displayedSearchWidgetSetToCitySearchShowsCityOfEnteredObject()
    {
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Foo city']
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'city'
        );

        self::assertContains(
            'Foo city',
            $this->fixture->render()
        );
    }

    //////////////////////////////////////////////////////////
    // Tests concerning the rendering of the district search
    //////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function displayedSearchWidgetSetToDistrictSearcShowsDistrictSearch()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'district'
        );

        self::assertContains(
            $this->fixture->translate('label_select_district'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function displayedSearchWidgetSetToDistrictSearchShowsDistrictOfEnteredObject()
    {
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['title' => 'Foo district']
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['district' => $districtUid]
        );
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'district'
        );

        self::assertContains(
            'Foo district',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function districtSearchWithoutCitySearchShowsDistrictSearch()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'district'
        );

        self::assertContains(
            'id="tx_realty_pi1_searchWidget_district" style="display: block;"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function districtSearchWithCitySearchHidesDistrictSearch()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'city,district'
        );

        self::assertContains(
            'id="tx_realty_pi1_searchWidget_district" style="display: none;"',
            $this->fixture->render()
        );
    }

    ////////////////////////////////////////////////////////////
    // Tests concerning the rendering of the house type search
    ////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function searchFormWithDisplayedSearchWidgetSetToHouseTypeSearchShowsHouseTypeSearch()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'houseType'
        );

        self::assertContains(
            $this->fixture->translate('label_select_house_type'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function searchFormWithDisplayedSearchWidgetSetToHouseTypeSearchShowsHouseTypeOfEnteredObject()
    {
        $houseTypeUid = $this->testingFramework->createRecord(
            'tx_realty_house_types',
            ['title' => 'Foo house type']
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['title' => 'foo', 'house_type' => $houseTypeUid]
        );
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'houseType'
        );

        self::assertContains(
            'Foo house type',
            $this->fixture->render()
        );
    }

    ///////////////////////////////////////////////////////////////////
    // Tests concerning the rendering of the objectType radio buttons
    ///////////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function objectTypeSelectorForDisplayedSearchWidgetFieldsSetToObjectTypeDisplaysRadioButtons()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectType'
        );

        self::assertContains(
            $this->fixture->translate('label_select_object_type'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function objectTypeSelectorForDisplayedSearchWidgetFieldsSetToObjectTypeHasNoDefaultSelectRadioButtons()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectType'
        );

        self::assertNotContains(
            'checked="checked"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function objectTypeSelectorForObjectTypeSetToRentPreselectsRentRadioButton()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectType'
        );

        self::assertContains(
            'value="forRent" checked="checked"',
            $this->fixture->render(['objectType' => 'forRent'])
        );
    }

    /**
     * @test
     */
    public function objectTypeSelectorForObjectTypeSetToSalePreselectsSaleRadioButton()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectType'
        );

        self::assertContains(
            'value="forSale" checked="checked"',
            $this->fixture->render(['objectType' => 'forSale'])
        );
    }

    /////////////////////////////////////////////////////////////////////////
    // Tests concerning the rendering of the rent/buying price input fields
    /////////////////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function searchFormForSetRentInputFieldsDisplaysRentInputFields()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'rent'
        );

        self::assertContains(
            $this->fixture->translate('label_enter_rent'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForSetRentInputFieldsAndSentDataEntersSentDataIntoInputFields()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'rent'
        );

        $output = $this->fixture->render(
            ['rentFrom' => '42', 'rentTo' => '100']
        );

        self::assertContains(
            'value="42"',
            $output
        );
        self::assertContains(
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
    public function searchFormForSetLivingAreaInputFieldsDisplaysLivingAreaInputFields()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'livingArea'
        );

        self::assertContains(
            $this->fixture->translate('label_enter_living_area'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForSetLivingAreaInputFieldsAndSentDataEntersSentDataIntoInputFields()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'livingArea'
        );

        $output = $this->fixture->render(
            ['livingAreaFrom' => '42', 'livingAreaTo' => '100']
        );

        self::assertContains(
            'value="42"',
            $output
        );
        self::assertContains(
            'value="100"',
            $output
        );
    }

    /*
     * Tests for the WHERE clause part
     */

    /**
     * @test
     */
    public function getWhereClausePartForEmptyFilterDataReturnsEmptyString()
    {
        $result = $this->fixture->getWhereClausePart([]);

        static::assertSame('', $result);
    }

    /**
     * @test
     */
    public function whereClauseOnlyForLowerPriceLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND ((tx_realty_objects.rent_excluding_bills >= 1) ' .
            'OR (tx_realty_objects.buying_price >= 1))',
            $this->fixture->getWhereClausePart(['priceRange' => '1-'])
        );
    }

    /**
     * @test
     */
    public function whereClauseOnlyForUpperPriceLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND ((tx_realty_objects.rent_excluding_bills > 0 ' .
            'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
            'OR (tx_realty_objects.buying_price > 0 ' .
            'AND tx_realty_objects.buying_price <= 10) ' .
            'OR (tx_realty_objects.rent_excluding_bills = 0 ' .
            'AND tx_realty_objects.buying_price = 0))',
            $this->fixture->getWhereClausePart(['priceRange' => '-10'])
        );
    }

    /**
     * @test
     */
    public function whereClauseForUpperPlusLowerPriceLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND ((tx_realty_objects.rent_excluding_bills >= 1 ' .
            'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
            'OR (tx_realty_objects.buying_price >= 1 ' .
            'AND tx_realty_objects.buying_price <= 10))',
            $this->fixture->getWhereClausePart(['priceRange' => '1-10'])
        );
    }

    /**
     * @test
     */
    public function searchStringForZipIsNotLongerThanTwoCharacters()
    {
        self::assertContains(
            'tx_realty_objects.zip LIKE "fo%"',
            $this->fixture->getWhereClausePart(['site' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function searchStringForSiteIsEscapedForLike()
    {
        self::assertContains(
            'tx_realty_cities.title LIKE "%f\\\\%oo%")',
            $this->fixture->getWhereClausePart(['site' => 'f%oo'])
        );
    }

    /**
     * @test
     */
    public function whereClauseForPriceRangeCanBeAppendedToSiteSearchWhereClause()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.zip LIKE "fo%" ' .
            'OR tx_realty_cities.title LIKE "%foo%") ' .
            'AND ((tx_realty_objects.rent_excluding_bills >= 1 ' .
            'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
            'OR (tx_realty_objects.buying_price >= 1 ' .
            'AND tx_realty_objects.buying_price <= 10))',
            $this->fixture->getWhereClausePart(
                ['site' => 'foo', 'priceRange' => '1-10']
            )
        );
    }

    /**
     * @test
     */
    public function whereClauseIsEmptyForInvalidNumericKeyForPriceRange()
    {
        self::assertEquals(
            '',
            $this->fixture->getWhereClausePart(['priceRange' => '-100-'])
        );
    }

    /**
     * @test
     */
    public function whereClauseIsEmptyForInvalidStringKeyForPriceRange()
    {
        self::assertEquals(
            '',
            $this->fixture->getWhereClausePart(['priceRange' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function whereClauseIsEmptyForEmptySite()
    {
        self::assertEquals(
            '',
            $this->fixture->getWhereClausePart(['site' => ''])
        );
    }

    /**
     * @test
     */
    public function whereClauseForUidSearchWithNonZeroUidCanBeCreated()
    {
        $this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'uid');

        self::assertEquals(
            ' AND tx_realty_objects.uid=1',
            $this->fixture->getWhereClausePart(['uid' => 1])
        );
    }

    /**
     * @test
     */
    public function whereClauseForUidSearchWithZeroUidIsEmpty()
    {
        $this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'uid');

        self::assertEquals(
            '',
            $this->fixture->getWhereClausePart(['uid' => 0])
        );
    }

    /**
     * @test
     */
    public function whereClauseForCitySearchWithNonZeroCityCanBeCreated()
    {
        $this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'city');

        self::assertEquals(
            ' AND tx_realty_objects.city = 1',
            $this->fixture->getWhereClausePart(['city' => 1])
        );
    }

    /**
     * @test
     */
    public function whereClauseForDistrictSearchWithNonZeroDistrictCanBeCreated()
    {
        $this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'district');

        self::assertEquals(
            ' AND tx_realty_objects.district = 1',
            $this->fixture->getWhereClausePart(['district' => 1])
        );
    }

    /**
     * @test
     */
    public function whereClauseForHouseTypeSearchWithNonZeroHouseTypeCanBeCreated()
    {
        $this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'houseType');

        self::assertEquals(
            ' AND tx_realty_objects.house_type = 1',
            $this->fixture->getWhereClausePart(['houseType' => 1])
        );
    }

    /**
     * @test
     */
    public function whereClauseForCitySearchWithZeroCityIsEmpty()
    {
        $this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'city');

        self::assertEquals(
            '',
            $this->fixture->getWhereClausePart(['city' => 0])
        );
    }

    /**
     * @test
     */
    public function whereClauseForHouseTypeSearchWithZeroHouseTypeIsEmpty()
    {
        $this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'houseType');

        self::assertEquals(
            '',
            $this->fixture->getWhereClausePart(['houseType' => 0])
        );
    }

    /**
     * @test
     */
    public function whereClauseForObjectNumberSearchWithNonEmptyObjectNumberCanBeCreated()
    {
        $this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

        self::assertEquals(
            ' AND tx_realty_objects.object_number="foo"',
            $this->fixture->getWhereClausePart(['objectNumber' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function whereClauseForObjectNumberSearchWithEmptyObjectNumberIsEmpty()
    {
        $this->fixture->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

        self::assertEquals(
            '',
            $this->fixture->getWhereClausePart(['objectNumber' => ''])
        );
    }

    /**
     * @test
     */
    public function getWhereClausePartForObjectTypeSelectorWithSaleSelectedReturnsSaleWhereClausePart()
    {
        self::assertEquals(
            ' AND tx_realty_objects.object_type = ' .
            tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
            $this->fixture->getWhereClausePart(['objectType' => 'forSale'])
        );
    }

    /**
     * @test
     */
    public function getWhereClausePartForObjectTypeSelectorWithRentSelectedReturnsRentWhereClausePart()
    {
        self::assertEquals(
            ' AND tx_realty_objects.object_type = ' .
            tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
            $this->fixture->getWhereClausePart(['objectType' => 'forRent'])
        );
    }

    /**
     * @test
     */
    public function getWhereClausePartForObjectTypeSelectorWithNothingSelectedReturnsEmptyString()
    {
        self::assertEquals(
            '',
            $this->fixture->getWhereClausePart(['objectType' => ''])
        );
    }

    /**
     * @test
     */
    public function whereClauseOnlyForLowerRentLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND ((tx_realty_objects.rent_excluding_bills >= 1) ' .
            'OR (tx_realty_objects.buying_price >= 1))',
            $this->fixture->getWhereClausePart(['rentFrom' => '1'])
        );
    }

    /**
     * @test
     */
    public function whereClauseOnlyForUpperRentLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND ((tx_realty_objects.rent_excluding_bills > 0 ' .
            'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
            'OR (tx_realty_objects.buying_price > 0 ' .
            'AND tx_realty_objects.buying_price <= 10) ' .
            'OR (tx_realty_objects.rent_excluding_bills = 0 ' .
            'AND tx_realty_objects.buying_price = 0))',
            $this->fixture->getWhereClausePart(['rentTo' => '10'])
        );
    }

    /**
     * @test
     */
    public function whereClauseForUpperPlusLowerRentLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND ((tx_realty_objects.rent_excluding_bills >= 1 ' .
            'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
            'OR (tx_realty_objects.buying_price >= 1 ' .
            'AND tx_realty_objects.buying_price <= 10))',
            $this->fixture->getWhereClausePart(
                ['rentFrom' => '1', 'rentTo' => '10']
            )
        );
    }

    /**
     * @test
     */
    public function whereClauseForUpperPlusLowerRentAndPriceLimitOverwritesPriceLimitWithRentLimit()
    {
        self::assertEquals(
            ' AND ((tx_realty_objects.rent_excluding_bills >= 1 ' .
            'AND tx_realty_objects.rent_excluding_bills <= 10) ' .
            'OR (tx_realty_objects.buying_price >= 1 ' .
            'AND tx_realty_objects.buying_price <= 10))',
            $this->fixture->getWhereClausePart(
                ['rentFrom' => '1', 'rentTo' => '10', 'priceRange' => '100-1000']
            )
        );
    }

    ///////////////////////////////////////////////////////////////
    // Tests concerning the WHERE clause part for the living area
    ///////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function whereClauseOnlyForLowerLivingAreaLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.living_area >= 1)',
            $this->fixture->getWhereClausePart(['livingAreaFrom' => '1'])
        );
    }

    /**
     * @test
     */
    public function whereClauseOnlyForUpperLivingAreaLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.living_area <= 10)',
            $this->fixture->getWhereClausePart(['livingAreaTo' => '10'])
        );
    }

    /**
     * @test
     */
    public function whereClauseForUpperPlusLowerLivingAreaLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.living_area >= 1)' .
            ' AND (tx_realty_objects.living_area <= 10)',
            $this->fixture->getWhereClausePart(
                ['livingAreaFrom' => '1', 'livingAreaTo' => '10']
            )
        );
    }

    /////////////////////////////////////////////////////////////////////
    // Tests concerning the rendering of the numberOfRooms input fields
    /////////////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function searchFormForSetNumberOfRoomsInputFieldsDisplaysNumberOfRoomsInputFields()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        self::assertContains(
            $this->fixture->translate('label_enter_number_of_rooms'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForSetNumberOfRoomsInputFieldsAndRoomsFromSetEntersSentDataIntoInputFields()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        self::assertContains(
            'value="1"',
            $this->fixture->render(['numberOfRoomsFrom' => '1'])
        );
    }

    /**
     * @test
     */
    public function searchFormForSetNumberOfRoomsInputFieldsAndRoomsToSetEntersSentDataIntoInputFields()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        self::assertContains(
            'value="2"',
            $this->fixture->render(['numberOfRoomsTo' => '2'])
        );
    }

    /**
     * @test
     */
    public function searchFormForSetNumberOfRoomsInputFieldsAndRoomsFromZeroSetsEmptyValueForRoomsFromInput()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        $output = $this->fixture->render(
            ['numberOfRoomsFrom' => '0']
        );

        self::assertContains(
            'value=""',
            $output
        );
    }

    /**
     * @test
     */
    public function searchFormForNumberOfRoomsWithTwoDecimalsRoundsToOneDecimal()
    {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        $output = $this->fixture->render(
            ['numberOfRoomsTo' => '15.22']
        );

        self::assertContains(
            'value="15.2"',
            $output
        );
    }

    /**
     * @test
     */
    public function searchFormForSetNumberOfRoomsInputFieldsAndDataWithCommaAsDecimalSeparatorKeepsDecimalAfterSeparator(
    ) {
        $this->fixture->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        $output = $this->fixture->render(
            ['numberOfRoomsTo' => '15,2']
        );

        self::assertContains(
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
    public function whereClauseOnlyForLowerNumberOfRoomsLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.number_of_rooms >= 1)',
            $this->fixture->getWhereClausePart(
                ['numberOfRoomsFrom' => 1]
            )
        );
    }

    /**
     * @test
     */
    public function whereClauseOnlyForUpperNumberOfRoomsLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.number_of_rooms <= 10)',
            $this->fixture->getWhereClausePart(
                ['numberOfRoomsTo' => 10]
            )
        );
    }

    /**
     * @test
     */
    public function whereClauseForUpperPlusLowerNumberOfRoomsLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.number_of_rooms >= 1)' .
            ' AND (tx_realty_objects.number_of_rooms <= 10)',
            $this->fixture->getWhereClausePart(
                ['numberOfRoomsFrom' => 1, 'numberOfRoomsTo' => 10]
            )
        );
    }

    /**
     * @test
     */
    public function whereClauseForLowerNumberOfRoomsLimitWithDecimalsCreatesWhereClauseWithCompleteNumber()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.number_of_rooms >= 1.5)',
            $this->fixture->getWhereClausePart(
                ['numberOfRoomsFrom' => 1.5]
            )
        );
    }

    /**
     * @test
     */
    public function whereClauseForLowerNumberOfRoomsLimitStringDoesNotAddWhereClause()
    {
        self::assertEquals(
            '',
            $this->fixture->getWhereClausePart(
                ['numberOfRoomsFrom' => 'foo']
            )
        );
    }

    /**
     * @test
     */
    public function whereClauseForLowerNumberOfRoomsLimitWithCommaAsDecimalSeparatorReplacesCommaWithDot()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.number_of_rooms >= 1.8)',
            $this->fixture->getWhereClausePart(
                ['numberOfRoomsFrom' => '1,8']
            )
        );
    }

    /*
     * Tests concerning createDropDownItems
     *
     * Note: We test only the details for cities. The districts work the same.
     */

    /**
     * @test
     */
    public function createDropDownItemsWithInvalidTypeThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->createDropDownItems('foo');
    }

    /**
     * @test
     */
    public function createDropDownItemsForCityWithObjectContainsItemWithCityName()
    {
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Foo city']
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );

        self::assertContains(
            'Foo city',
            $this->fixture->createDropDownItems('city')
        );
    }

    /**
     * @test
     */
    public function createDropDownItemsForCityWithObjectContainsItemWithCityUid()
    {
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Foo city']
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );

        self::assertContains(
            'value="' . $cityUid . '"',
            $this->fixture->createDropDownItems('city')
        );
    }

    /**
     * @test
     */
    public function createDropDownItemsForCityWithObjectContainsItemWithCountOfMatches()
    {
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Foo city']
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );

        self::assertContains(
            'Foo city (2)',
            $this->fixture->createDropDownItems('city')
        );
    }

    /**
     * @test
     */
    public function createDropDownItemsForCityWithObjectWithStaticSqlFilterContainsItemWithCountOfMatches()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Foo city']);
        $this->testingFramework->createRecord('tx_realty_objects', ['city' => $cityUid, 'title' => 'House']);
        $this->testingFramework->createRecord('tx_realty_objects', ['city' => $cityUid, 'title' => 'Flat']);

        $this->fixture->setConfigurationValue('staticSqlFilter', '(title = "House")');

        $result = $this->fixture->createDropDownItems('city');

        self::assertContains('Foo city (1)', $result);
    }

    /**
     * @test
     */
    public function createDropDownItemsForCityNotContainsCityWithoutMatches()
    {
        $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Foo city']
        );

        self::assertNotContains(
            'Foo city',
            $this->fixture->createDropDownItems('city')
        );
    }

    /**
     * @test
     */
    public function createDropDownItemsForCityWithSelectedZeroNotContainsSelected()
    {
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Foo city']
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );

        self::assertNotContains(
            'selected="selected"',
            $this->fixture->createDropDownItems('city', 0)
        );
    }

    /**
     * @test
     */
    public function createDropDownItemsForCityWithSelectedUidOfSelectsCityWithThatUid()
    {
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Foo city']
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );

        self::assertContains(
            'value="' . $cityUid . '" selected="selected"',
            $this->fixture->createDropDownItems('city', $cityUid)
        );
    }

    /**
     * @test
     */
    public function createDropDownItemsForDistrictWithObjectContainsItemWithDistrictName()
    {
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['title' => 'Foo district']
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['district' => $districtUid]
        );

        self::assertContains(
            'Foo district',
            $this->fixture->createDropDownItems('district')
        );
    }

    /**
     * @test
     */
    public function createDropDownItemsForDistrictNotContainsDistrictWithoutMatches()
    {
        $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['title' => 'Foo district']
        );

        self::assertNotContains(
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
    public function getPiVarKeysReturnsAnArray()
    {
        self::assertInternalType(
            'array',
            tx_realty_filterForm::getPiVarKeys()
        );
    }

    /**
     * @test
     */
    public function getPiVarKeysReturnsNonEmptyArray()
    {
        $result = tx_realty_filterForm::getPiVarKeys();

        self::assertNotEmpty(
            $result
        );
    }
}
