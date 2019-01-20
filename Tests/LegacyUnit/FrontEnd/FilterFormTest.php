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
    private $subject = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        $this->subject = new tx_realty_filterForm(
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
            $this->subject->translate('label_search'),
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function filterFormLinksToConfiguredTargetPage()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('filterTargetPID', $pageUid);

        self::assertContains(
            '?id=' . $pageUid,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkers()
    {
        self::assertNotContains(
            '###',
            $this->subject->render()
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function filterFormHasNoSiteSearchInputIfDisabledByConfiguration()
    {
        $this->subject->setConfigurationValue('displayedSearchWidgetFields', '');

        self::assertNotContains(
            'id="tx_realty_pi1-site"',
            $this->subject->render()
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function filterFormForConfiguredFilterOptionsButDisplayedSearchFieldsEmptyHidesPricesSelect()
    {
        $this->subject->setConfigurationValue('displayedSearchWidgetFields', '');
        $this->subject->setConfigurationValue('priceRangesForFilterForm', '1-100');

        self::assertNotContains(
            '<select',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function filterFormHasPricesSelectForConfiguredFilterOptions()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'priceRanges'
        );
        $this->subject->setConfigurationValue('priceRangesForFilterForm', '1-100');

        self::assertContains(
            '<select',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function priceRangeIsDisplayedWithCurrency()
    {
        $this->subject->setConfigurationValue('displayedSearchWidgetFields', 'priceRanges');
        $this->subject->setConfigurationValue('currencyUnit', 'EUR');
        $this->subject->setConfigurationValue('priceRangesForFilterForm', '1-100');

        self::assertContains(
            '&euro;',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function optionWithLowerAndUpperPriceLimitCanBeDisplayed()
    {
        $this->subject->setConfigurationValue('displayedSearchWidgetFields', 'priceRanges');
        $this->subject->setConfigurationValue('currencyUnit', 'EUR');
        $this->subject->setConfigurationValue('priceRangesForFilterForm', '1-100');

        self::assertContains(
            '&euro; 1,00 ' . $this->subject->translate('label_to') . ' &euro; 100,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function optionWithLowerPriceLimitCanBeDisplayed()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'priceRanges'
        );
        $this->subject->setConfigurationValue('priceRangesForFilterForm', '1-');

        self::assertContains(
            $this->subject->translate('label_greater_than') . ' 1',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function optionWithUpperPriceLimitCanBeDisplayed()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'priceRanges'
        );
        $this->subject->setConfigurationValue(
            'priceRangesForFilterForm',
            '-100'
        );

        self::assertContains(
            $this->subject->translate('label_less_than') . ' 100',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function priceRangeForNoOtherDisplayedSearchFieldsHasSubmitButton()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'priceRanges'
        );
        $this->subject->setConfigurationValue(
            'priceRangesForFilterForm',
            '-100'
        );

        self::assertContains(
            $this->subject->translate('label_search'),
            $this->subject->render()
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
        $this->subject->setConfigurationValue('displayedSearchWidgetFields', '');

        self::assertNotContains(
            'name="tx_realty_pi1[uid]"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToUidContainsUidSearchField()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'uid'
        );

        self::assertContains(
            'name="tx_realty_pi1[uid]"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToUidAndSetUidSetsUidAsValueForInputField()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'uid'
        );

        self::assertContains(
            'value="42"',
            $this->subject->render(['uid' => 42])
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToUidAndUidSetAsStringSetsEmptyValue()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'uid'
        );

        self::assertContains(
            'value=""',
            $this->subject->render(['uid' => 'foo'])
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
        $this->subject->setConfigurationValue('displayedSearchWidgetFields', '');

        self::assertNotContains(
            'name="tx_realty_pi1[objectNumber]"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToObjectNumberContainsObjectNumberSearchField()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectNumber'
        );

        self::assertContains(
            'name="tx_realty_pi1[objectNumber]"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForDisplayedSearchWidgetFieldsSetToObjectNumberAndGivenObjectNumberSetsValueOfObjectNumberField(
    ) {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectNumber'
        );

        self::assertContains(
            'value="Foo 22"',
            $this->subject->render(['objectNumber' => 'Foo 22'])
        );
    }

    /**
     * @test
     */
    public function searchFormForEmptyDisplayedSearchWidgetFieldsIsHidden()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            ''
        );

        self::assertNotContains(
            'id="tx_realty_pi1-idsearch"',
            $this->subject->render()
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'city'
        );

        self::assertContains(
            $this->subject->translate('label_select_city'),
            $this->subject->render()
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'city'
        );

        self::assertContains(
            'Foo city',
            $this->subject->render()
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'district'
        );

        self::assertContains(
            $this->subject->translate('label_select_district'),
            $this->subject->render()
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'district'
        );

        self::assertContains(
            'Foo district',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function districtSearchWithoutCitySearchShowsDistrictSearch()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'district'
        );

        self::assertContains(
            'id="tx_realty_pi1_searchWidget_district" style="display: block;"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function districtSearchWithCitySearchHidesDistrictSearch()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'city,district'
        );

        self::assertContains(
            'id="tx_realty_pi1_searchWidget_district" style="display: none;"',
            $this->subject->render()
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'houseType'
        );

        self::assertContains(
            $this->subject->translate('label_select_house_type'),
            $this->subject->render()
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'houseType'
        );

        self::assertContains(
            'Foo house type',
            $this->subject->render()
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectType'
        );

        self::assertContains(
            $this->subject->translate('label_select_object_type'),
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function objectTypeSelectorForDisplayedSearchWidgetFieldsSetToObjectTypeHasNoDefaultSelectRadioButtons()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectType'
        );

        self::assertNotContains(
            'checked="checked"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function objectTypeSelectorForObjectTypeSetToRentPreselectsRentRadioButton()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectType'
        );

        self::assertContains(
            'value="forRent" checked="checked"',
            $this->subject->render(['objectType' => 'forRent'])
        );
    }

    /**
     * @test
     */
    public function objectTypeSelectorForObjectTypeSetToSalePreselectsSaleRadioButton()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'objectType'
        );

        self::assertContains(
            'value="forSale" checked="checked"',
            $this->subject->render(['objectType' => 'forSale'])
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'rent'
        );

        self::assertContains(
            $this->subject->translate('label_enter_rent'),
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForSetRentInputFieldsAndSentDataEntersSentDataIntoInputFields()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'rent'
        );

        $output = $this->subject->render(
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'livingArea'
        );

        self::assertContains(
            $this->subject->translate('label_enter_living_area'),
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForSetLivingAreaInputFieldsAndSentDataEntersSentDataIntoInputFields()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'livingArea'
        );

        $output = $this->subject->render(
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
        $result = $this->subject->getWhereClausePart([]);

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
            $this->subject->getWhereClausePart(['priceRange' => '1-'])
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
            $this->subject->getWhereClausePart(['priceRange' => '-10'])
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
            $this->subject->getWhereClausePart(['priceRange' => '1-10'])
        );
    }

    /**
     * @test
     */
    public function searchStringForZipIsNotLongerThanTwoCharacters()
    {
        self::assertContains(
            'tx_realty_objects.zip LIKE "fo%"',
            $this->subject->getWhereClausePart(['site' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function searchStringForSiteIsEscapedForLike()
    {
        self::assertContains(
            'tx_realty_cities.title LIKE "%f\\\\%oo%")',
            $this->subject->getWhereClausePart(['site' => 'f%oo'])
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
            $this->subject->getWhereClausePart(
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
            $this->subject->getWhereClausePart(['priceRange' => '-100-'])
        );
    }

    /**
     * @test
     */
    public function whereClauseIsEmptyForInvalidStringKeyForPriceRange()
    {
        self::assertEquals(
            '',
            $this->subject->getWhereClausePart(['priceRange' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function whereClauseIsEmptyForEmptySite()
    {
        self::assertEquals(
            '',
            $this->subject->getWhereClausePart(['site' => ''])
        );
    }

    /**
     * @test
     */
    public function whereClauseForUidSearchWithNonZeroUidCanBeCreated()
    {
        $this->subject->setConfigurationValue('showIdSearchInFilterForm', 'uid');

        self::assertEquals(
            ' AND tx_realty_objects.uid=1',
            $this->subject->getWhereClausePart(['uid' => 1])
        );
    }

    /**
     * @test
     */
    public function whereClauseForUidSearchWithZeroUidIsEmpty()
    {
        $this->subject->setConfigurationValue('showIdSearchInFilterForm', 'uid');

        self::assertEquals(
            '',
            $this->subject->getWhereClausePart(['uid' => 0])
        );
    }

    /**
     * @test
     */
    public function whereClauseForCitySearchWithNonZeroCityCanBeCreated()
    {
        $this->subject->setConfigurationValue('showIdSearchInFilterForm', 'city');

        self::assertEquals(
            ' AND tx_realty_objects.city = 1',
            $this->subject->getWhereClausePart(['city' => 1])
        );
    }

    /**
     * @test
     */
    public function whereClauseForDistrictSearchWithNonZeroDistrictCanBeCreated()
    {
        $this->subject->setConfigurationValue('showIdSearchInFilterForm', 'district');

        self::assertEquals(
            ' AND tx_realty_objects.district = 1',
            $this->subject->getWhereClausePart(['district' => 1])
        );
    }

    /**
     * @test
     */
    public function whereClauseForHouseTypeSearchWithNonZeroHouseTypeCanBeCreated()
    {
        $this->subject->setConfigurationValue('showIdSearchInFilterForm', 'houseType');

        self::assertEquals(
            ' AND tx_realty_objects.house_type = 1',
            $this->subject->getWhereClausePart(['houseType' => 1])
        );
    }

    /**
     * @test
     */
    public function whereClauseForCitySearchWithZeroCityIsEmpty()
    {
        $this->subject->setConfigurationValue('showIdSearchInFilterForm', 'city');

        self::assertEquals(
            '',
            $this->subject->getWhereClausePart(['city' => 0])
        );
    }

    /**
     * @test
     */
    public function whereClauseForHouseTypeSearchWithZeroHouseTypeIsEmpty()
    {
        $this->subject->setConfigurationValue('showIdSearchInFilterForm', 'houseType');

        self::assertEquals(
            '',
            $this->subject->getWhereClausePart(['houseType' => 0])
        );
    }

    /**
     * @test
     */
    public function whereClauseForObjectNumberSearchWithNonEmptyObjectNumberCanBeCreated()
    {
        $this->subject->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

        self::assertEquals(
            ' AND tx_realty_objects.object_number="foo"',
            $this->subject->getWhereClausePart(['objectNumber' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function whereClauseForObjectNumberSearchWithEmptyObjectNumberIsEmpty()
    {
        $this->subject->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

        self::assertEquals(
            '',
            $this->subject->getWhereClausePart(['objectNumber' => ''])
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
            $this->subject->getWhereClausePart(['objectType' => 'forSale'])
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
            $this->subject->getWhereClausePart(['objectType' => 'forRent'])
        );
    }

    /**
     * @test
     */
    public function getWhereClausePartForObjectTypeSelectorWithNothingSelectedReturnsEmptyString()
    {
        self::assertEquals(
            '',
            $this->subject->getWhereClausePart(['objectType' => ''])
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
            $this->subject->getWhereClausePart(['rentFrom' => '1'])
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
            $this->subject->getWhereClausePart(['rentTo' => '10'])
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
            $this->subject->getWhereClausePart(
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
            $this->subject->getWhereClausePart(
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
            $this->subject->getWhereClausePart(['livingAreaFrom' => '1'])
        );
    }

    /**
     * @test
     */
    public function whereClauseOnlyForUpperLivingAreaLimitCanBeCreated()
    {
        self::assertEquals(
            ' AND (tx_realty_objects.living_area <= 10)',
            $this->subject->getWhereClausePart(['livingAreaTo' => '10'])
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
            $this->subject->getWhereClausePart(
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        self::assertContains(
            $this->subject->translate('label_enter_number_of_rooms'),
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function searchFormForSetNumberOfRoomsInputFieldsAndRoomsFromSetEntersSentDataIntoInputFields()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        self::assertContains(
            'value="1"',
            $this->subject->render(['numberOfRoomsFrom' => '1'])
        );
    }

    /**
     * @test
     */
    public function searchFormForSetNumberOfRoomsInputFieldsAndRoomsToSetEntersSentDataIntoInputFields()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        self::assertContains(
            'value="2"',
            $this->subject->render(['numberOfRoomsTo' => '2'])
        );
    }

    /**
     * @test
     */
    public function searchFormForSetNumberOfRoomsInputFieldsAndRoomsFromZeroSetsEmptyValueForRoomsFromInput()
    {
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        $output = $this->subject->render(
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        $output = $this->subject->render(
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
        $this->subject->setConfigurationValue(
            'displayedSearchWidgetFields',
            'numberOfRooms'
        );

        $output = $this->subject->render(
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
            $this->subject->getWhereClausePart(
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
            $this->subject->getWhereClausePart(
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
            $this->subject->getWhereClausePart(
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
            $this->subject->getWhereClausePart(
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
            $this->subject->getWhereClausePart(
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
            $this->subject->getWhereClausePart(
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

        $this->subject->createDropDownItems('foo');
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
            $this->subject->createDropDownItems('city')
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
            $this->subject->createDropDownItems('city')
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
            $this->subject->createDropDownItems('city')
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

        $this->subject->setConfigurationValue('staticSqlFilter', '(title = "House")');

        $result = $this->subject->createDropDownItems('city');

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
            $this->subject->createDropDownItems('city')
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
            $this->subject->createDropDownItems('city', 0)
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
            $this->subject->createDropDownItems('city', $cityUid)
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
            $this->subject->createDropDownItems('district')
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
            $this->subject->createDropDownItems('district')
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
