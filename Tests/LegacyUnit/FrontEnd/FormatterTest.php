<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_FormatterTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_Formatter
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var tx_realty_Model_RealtyObject a dummy realty object
     */
    private $realtyObject;

    /**
     * @var int static_info_tables UID of Germany
     */
    const DE = 54;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        $this->realtyObject = Tx_Oelib_MapperRegistry
            ::get(\tx_realty_Mapper_RealtyObject::class)->getNewGhost();
        $this->realtyObject->setData(['title' => 'test realty object']);

        $this->fixture = new tx_realty_pi1_Formatter(
            $this->realtyObject->getUid(),
            [
                'defaultCountryUID' => self::DE,
                'currencyUnit' => 'EUR',
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

    //////////////////////////////
    // Tests for the constructor
    //////////////////////////////

    /**
     * @test
     */
    public function constructAnExceptionIfCalledWithAZeroRealtyObjectUid()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture = new tx_realty_pi1_Formatter(0, [], $this->getFrontEndController()->cObj);
    }

    /**
     * @test
     */
    public function constructThrowsAnExceptionIfCalledWithAUidOfADeletedRealtyObject()
    {
        $this->realtyObject->markAsDead();

        $this->expectException(\InvalidArgumentException::class);

        new tx_realty_pi1_Formatter($this->realtyObject->getUid(), [], $this->getFrontEndController()->cObj);
    }

    ///////////////////////////////////////////
    // Tests for getting formatted properties
    ///////////////////////////////////////////

    /**
     * @test
     */
    public function getPropertyThrowsExceptionForEmptyKey()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->getProperty('');
    }

    /**
     * @test
     */
    public function getPropertyReturnsTheLabelOfAValidState()
    {
        $this->realtyObject->setProperty('state', 8);

        self::assertEquals(
            $this->fixture->translate('label_state_8'),
            $this->fixture->getProperty('state')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsAnEmptyStringIfTheStateIsNotSet()
    {
        self::assertEquals(
            '',
            $this->fixture->getProperty('state')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsAnEmptyStringIfTheObjectHasAnInvalidValueForState()
    {
        $this->realtyObject->setProperty('state', 1000000);

        self::assertEquals(
            '',
            $this->fixture->getProperty('state')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsTheLabelOfAValidHeatingType()
    {
        $this->realtyObject->setProperty('heating_type', '1');

        self::assertEquals(
            $this->fixture->translate('label_heating_type_1'),
            $this->fixture->getProperty('heating_type')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsTheLabelsOfAListOfValidHeatingTypes()
    {
        $this->realtyObject->setProperty('heating_type', '1,3,4');

        self::assertEquals(
            $this->fixture->translate('label_heating_type_1') . ', ' .
            $this->fixture->translate('label_heating_type_3') . ', ' .
            $this->fixture->translate('label_heating_type_4'),
            $this->fixture->getProperty('heating_type')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsAnEmptyStringIfTheHeatingTypeIsNotSet()
    {
        self::assertEquals(
            '',
            $this->fixture->getProperty('heating_type')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsAnEmptyStringIfTheObjectHasAnInvalidValueForHeatingType()
    {
        $this->realtyObject->setProperty('heating_type', 10000);

        self::assertEquals(
            '',
            $this->fixture->getProperty('heating_type')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsEmptyStringForCountrySameAsDefaultCountry()
    {
        $this->realtyObject->setProperty('country', self::DE);

        self::assertEquals(
            '',
            $this->fixture->getProperty('country')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsTheCountryNameForCountryDifferentFromDefaultCountry()
    {
        // randomly chosen the country UID of Australia
        $this->realtyObject->setProperty('country', 14);

        self::assertEquals(
            'Australia',
            $this->fixture->getProperty('country')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsTitleOfCity()
    {
        $this->realtyObject->setProperty(
            'city',
            $this->testingFramework->createRecord(
                'tx_realty_cities',
                ['title' => 'test city']
            )
        );

        self::assertEquals(
            'test city',
            $this->fixture->getProperty('city')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsHtmlSpecialcharedTitleOfCity()
    {
        $this->realtyObject->setProperty(
            'city',
            $this->testingFramework->createRecord(
                'tx_realty_cities',
                ['title' => 'test<br/>city']
            )
        );

        self::assertEquals(
            htmlspecialchars('test<br/>city'),
            $this->fixture->getProperty('city')
        );
    }

    /**
     * @test
     */
    public function getPropertyFormatsEstateSizeWithTwoDecimals()
    {
        $this->realtyObject->setProperty('estate_size', 123.40);

        self::assertSame(
            '123.40&nbsp;' . $this->fixture->translate('label_squareMeters'),
            $this->fixture->getProperty('estate_size')
        );
    }

    /**
     * @test
     */
    public function getPropertyFormatsEstateSizeWithThousandSeparator()
    {
        $this->realtyObject->setProperty('estate_size', 12345.00);

        self::assertContains(
            '12&#x202f;345',
            $this->fixture->getProperty('estate_size')
        );
    }

    /**
     * @test
     */
    public function getPropertyForEstateSizeCutsOffAllZeroDecimals()
    {
        $this->realtyObject->setProperty('estate_size', 123.00);

        self::assertEquals(
            '123&nbsp;' . $this->fixture->translate('label_squareMeters'),
            $this->fixture->getProperty('estate_size')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsEmptyStringForUsableFromIfNoValueIsSet()
    {
        self::assertEquals(
            '',
            $this->fixture->getProperty('usable_from')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsValueOfUsableFrom()
    {
        $this->realtyObject->setProperty('usable_from', '1.1.');

        self::assertEquals(
            '1.1.',
            $this->fixture->getProperty('usable_from')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsHtmlspecialcharedValueOfUsableFrom()
    {
        $this->realtyObject->setProperty('usable_from', '1.<br/>1.');

        self::assertEquals(
            htmlspecialchars('1.<br/>1.'),
            $this->fixture->getProperty('usable_from')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsNonZeroValueOfFloor()
    {
        $this->realtyObject->setProperty('floor', 3);

        self::assertEquals(
            '3',
            $this->fixture->getProperty('floor')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsEmptyStringForZeroValueOfFloor()
    {
        $this->realtyObject->setProperty('floor', 0);

        self::assertEquals(
            '',
            $this->fixture->getProperty('floor')
        );
    }

    /**
     * @test
     */
    public function getPropertyForStatusVacantReturnsVacantLabel()
    {
        $this->realtyObject->setProperty(
            'status',
            tx_realty_Model_RealtyObject::STATUS_VACANT
        );

        self::assertEquals(
            $this->fixture->translate('label_status_0'),
            $this->fixture->getProperty('status')
        );
    }

    /**
     * @test
     */
    public function getPropertyForStatusReservedReturnsReservedLabel()
    {
        $this->realtyObject->setProperty(
            'status',
            tx_realty_Model_RealtyObject::STATUS_RESERVED
        );

        self::assertEquals(
            $this->fixture->translate('label_status_1'),
            $this->fixture->getProperty('status')
        );
    }

    /**
     * @test
     */
    public function getPropertyForStatusSoldReturnsSoldLabel()
    {
        $this->realtyObject->setProperty(
            'status',
            tx_realty_Model_RealtyObject::STATUS_SOLD
        );

        self::assertEquals(
            $this->fixture->translate('label_status_2'),
            $this->fixture->getProperty('status')
        );
    }

    /**
     * @test
     */
    public function getPropertyForStatusRentedReturnsRentedLabel()
    {
        $this->realtyObject->setProperty(
            'status',
            tx_realty_Model_RealtyObject::STATUS_RENTED
        );

        self::assertEquals(
            $this->fixture->translate('label_status_3'),
            $this->fixture->getProperty('status')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsAddress()
    {
        $this->realtyObject->setProperty('show_address', 1);
        $this->realtyObject->setProperty('street', 'Main Street');
        $this->realtyObject->setProperty('zip', '12345');
        $this->realtyObject->setProperty(
            'city',
            $this->testingFramework->createRecord(
                'tx_realty_cities',
                ['title' => 'Test Town']
            )
        );

        self::assertEquals(
            'Main Street<br />12345 Test Town',
            $this->fixture->getProperty('address')
        );
    }

    /**
     * @test
     */
    public function getPropertyForNumberOfRoomsWithTwoDecimalsReturnsNumberWithOneDecimal()
    {
        $this->realtyObject->setProperty('number_of_rooms', 5.20);

        self::assertSame(
            '5.2',
            $this->fixture->getProperty('number_of_rooms')
        );
    }

    /**
     * @test
     */
    public function getPropertyForNumberOfBathroomsWithTwoDecimalsReturnsNumberWithOneDecimal()
    {
        $this->realtyObject->setProperty('bathrooms', 5.20);

        self::assertSame(
            '5.2',
            $this->fixture->getProperty('bathrooms')
        );
    }

    /**
     * @test
     */
    public function getPropertyForNumberOfBedroomsWithTwoDecimalsReturnsNumberWithOneDecimal()
    {
        $this->realtyObject->setProperty('bedrooms', 5.20);

        self::assertSame(
            '5.2',
            $this->fixture->getProperty('bedrooms')
        );
    }

    /**
     * @test
     */
    public function getPropertyForTotalUsableAreaReturnsItAsFormattedArea()
    {
        $this->realtyObject->setProperty('total_usable_area', 123.45);

        self::assertSame(
            '123.45&nbsp;' . $this->fixture->translate('label_squareMeters'),
            $this->fixture->getProperty('total_usable_area')
        );
    }

    /**
     * @test
     */
    public function getPropertyForTotalUsableAreaCutsOffAllZeroDecimals()
    {
        $this->realtyObject->setProperty('total_usable_area', 123.00);

        self::assertEquals(
            '123&nbsp;' . $this->fixture->translate('label_squareMeters'),
            $this->fixture->getProperty('total_usable_area')
        );
    }

    /**
     * @test
     */
    public function getPropertyForOfficeSpaceReturnsItAsFormattedArea()
    {
        $this->realtyObject->setProperty('office_space', 58.23);

        self::assertSame(
            '58.23&nbsp;' . $this->fixture->translate('label_squareMeters'),
            $this->fixture->getProperty('office_space')
        );
    }

    /**
     * @test
     */
    public function getPropertyForShopAreaReturnsItAsFormattedArea()
    {
        $this->realtyObject->setProperty('shop_area', 12.34);

        self::assertSame(
            '12.34&nbsp;' . $this->fixture->translate('label_squareMeters'),
            $this->fixture->getProperty('shop_area')
        );
    }

    /**
     * @test
     */
    public function getPropertyForSalesAreaReturnsItAsFormattedArea()
    {
        $this->realtyObject->setProperty('sales_area', 12.34);

        self::assertSame(
            '12.34&nbsp;' . $this->fixture->translate('label_squareMeters'),
            $this->fixture->getProperty('sales_area')
        );
    }

    /**
     * @test
     */
    public function getPropertyForStorageAreaReturnsItAsFormattedArea()
    {
        $this->realtyObject->setProperty('storage_area', 18.4);

        self::assertSame(
            '18.40&nbsp;' . $this->fixture->translate('label_squareMeters'),
            $this->fixture->getProperty('storage_area')
        );
    }

    /**
     * @test
     */
    public function getPropertyForOtherAreaReturnsItAsFormattedArea()
    {
        $this->realtyObject->setProperty('other_area', 12.34);

        self::assertSame(
            '12.34&nbsp;' . $this->fixture->translate('label_squareMeters'),
            $this->fixture->getProperty('other_area')
        );
    }

    /**
     * @test
     */
    public function getPropertyForSiteOccupancyIndexReturnsItAsFormattedDecimal()
    {
        $this->realtyObject->setProperty('site_occupancy_index', 19.40);

        self::assertSame(
            '19.40',
            $this->fixture->getProperty('site_occupancy_index')
        );
    }

    /**
     * @test
     */
    public function getPropertyForFloorSpaceIndexReturnsItAsFormattedDecimal()
    {
        $this->realtyObject->setProperty('floor_space_index', 19.48);

        self::assertSame(
            '19.48',
            $this->fixture->getProperty('floor_space_index')
        );
    }

    /**
     * @test
     */
    public function getPropertyForWindowBankReturnsItAsFormattedWidth()
    {
        $this->realtyObject->setProperty('window_bank', 12.34);

        self::assertSame(
            '12.34&nbsp;' . $this->fixture->translate('label_meter'),
            $this->fixture->getProperty('window_bank')
        );
    }

    /**
     * @return string[][]
     */
    public function decimalPricePropertyDataProvider()
    {
        return [
            'rent_excluding_bills' => ['rent_excluding_bills'],
            'extra_charges' => ['extra_charges'],
            'buying_price' => ['buying_price'],
            'year_rent' => ['year_rent'],
            'rental_income_target' => ['rental_income_target'],
            'garage_rent' => ['garage_rent'],
            'hoa_fee' => ['hoa_fee'],
            'rent_per_square_meter' => ['rent_per_square_meter'],
            'garage_price' => ['garage_price'],
            'rent_with_heating_costs' => ['rent_with_heating_costs'],
            'deposit' => ['deposit'],
        ];
    }

    /**
     * @test
     *
     * @param string $key
     *
     * @dataProvider decimalPricePropertyDataProvider
     */
    public function getPropertyForDecimalPriceValuesFormatsValueAsDecimalWithCurrency($key)
    {
        $this->realtyObject->setProperty($key, 12345.67);

        self::assertSame('&euro; 12.345,67', $this->fixture->getProperty($key));
    }

    /**
     * @test
     *
     * @param string $key
     *
     * @dataProvider decimalPricePropertyDataProvider
     */
    public function getPropertyForDecimalPriceValuesAddMissingDecimals($key)
    {
        $this->realtyObject->setProperty($key, 12345);

        self::assertSame('&euro; 12.345,00', $this->fixture->getProperty($key));
    }

    /**
     * @test
     */
    public function getPropertyReturnsParkingSpacesAsInteger()
    {
        $this->realtyObject->setProperty('parking_spaces', 3);

        self::assertEquals(
            '3',
            $this->fixture->getProperty('parking_spaces')
        );
    }

    /**
     * @test
     */
    public function getPropertyForExistingFurnishingCategoryReturnsCategoryLabel()
    {
        $this->realtyObject->setProperty('furnishing_category', 1);

        self::assertEquals(
            $this->fixture->translate('label_furnishing_category_1'),
            $this->fixture->getProperty('furnishing_category')
        );
    }

    /**
     * @test
     */
    public function getPropertyForInvalidFurnishingCategoryReturnsEmptyString()
    {
        $this->realtyObject->setProperty('furnishing_category', 42);

        self::assertEquals(
            '',
            $this->fixture->getProperty('furnishing_category')
        );
    }

    /**
     * @test
     */
    public function getPropertyForExistingFlooringReturnsFlooringLabel()
    {
        $this->realtyObject->setProperty('flooring', 1);

        self::assertEquals(
            $this->fixture->translate('label_flooring_1'),
            $this->fixture->getProperty('flooring')
        );
    }

    /**
     * @test
     */
    public function getPropertyForTwoFlooringsReturnsBothFlooringLabels()
    {
        $this->realtyObject->setProperty('flooring', '1,2');

        $property = $this->fixture->getProperty('flooring');

        self::assertContains(
            $this->fixture->translate('label_flooring_1'),
            $property,
            'First flooring label was not found.'
        );
        self::assertContains(
            $this->fixture->translate('label_flooring_2'),
            $property,
            'Second flooring label was not found.'
        );
    }

    /**
     * @test
     */
    public function getPropertyForInvalidFlooringReturnsEmptyString()
    {
        $this->realtyObject->setProperty('flooring', 42);

        self::assertEquals(
            '',
            $this->fixture->getProperty('flooring')
        );
    }

    /**
     * @test
     */
    public function getPropertyReturnsDistanceToTheSeaAsIntegerWithUnit()
    {
        $this->realtyObject->setDistanceToTheSea(42);

        $this->realtyObject->setProperty('window_bank', 12.34);

        self::assertSame(
            '42&nbsp;' . $this->fixture->translate('label_meter'),
            $this->fixture->getProperty('distance_to_the_sea')
        );
    }

    /**
     * @test
     */
    public function getPropertyForZeroDistanceToTheSeaReturnsEmptyString()
    {
        $this->realtyObject->setDistanceToTheSea(0);

        self::assertSame(
            '',
            $this->fixture->getProperty('distance_to_the_sea')
        );
    }

    /**
     * @return string[][][]
     */
    public function booleanPropertyDataProvider()
    {
        return [
            'heating_included' => ['heating_included'],
            'has_air_conditioning' => ['has_air_conditioning'],
            'has_pool' => ['has_pool'],
            'has_community_pool' => ['has_community_pool'],
            'balcony' => ['balcony'],
            'garden' => ['garden'],
            'elevator' => ['elevator'],
            'barrier_free' => ['barrier_free'],
            'assisted_living' => ['assisted_living'],
            'fitted_kitchen' => ['fitted_kitchen'],
            'with_hot_water' => ['with_hot_water'],
            'sea_view' => ['sea_view'],
            'wheelchair_accessible' => ['wheelchair_accessible'],
            'ramp' => ['ramp'],
            'lifting_platform' => ['lifting_platform'],
            'suitable_for_the_elderly' => ['suitable_for_the_elderly'],
        ];
    }

    /**
     * @test
     *
     * @param string $key
     *
     * @dataProvider booleanPropertyDataProvider
     */
    public function getPropertyForBooleanPropertyYesReturnsYes($key)
    {
        $this->realtyObject->setProperty($key, 1);

        self::assertSame($this->fixture->translate('message_yes'), $this->fixture->getProperty($key));
    }

    /**
     * @test
     *
     * @param string $key
     *
     * @dataProvider booleanPropertyDataProvider
     */
    public function getPropertyForBooleanPropertyNoReturnsEmptyString($key)
    {
        $this->realtyObject->setProperty($key, 0);

        self::assertSame('', $this->fixture->getProperty($key));
    }

    /**
     * @test
     */
    public function getPropertyFormatsProvisionAsEncodedText()
    {
        $provision = '3,57 % Inkl. MwSt. & Kaffee';
        $this->realtyObject->setProperty('provision', $provision);

        self::assertSame(
            htmlspecialchars($provision),
            $this->fixture->getProperty('provision')
        );
    }

    /**
     * @return string[][]
     */
    public function richTextFieldDataProvider()
    {
        return [
            'description' => ['description'],
            'teaser' => ['teaser'],
            'equipment' => ['equipment'],
            'layout' => ['layout'],
            'location' => ['location'],
            'misc' => ['misc'],
        ];
    }

    /**
     * @test
     *
     * @param string $fieldName
     * @dataProvider richTextFieldDataProvider
     */
    public function getPropertyFormatsAsRichText($fieldName)
    {
        $text = '<strong>Hello</strong>';
        $this->realtyObject->setProperty($fieldName, $text);

        self::assertSame($text, $this->fixture->getProperty($fieldName));
    }

    /////////////////////////////////////////
    // Tests concerning formatDecimal
    /////////////////////////////////////////

    /**
     * @test
     */
    public function formatDecimalForZeroReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->fixture->formatDecimal(0)
        );
    }

    /**
     * @test
     */
    public function formatDecimalForFloatWithAllZeroDecimalsReturnsNumberWithoutDecimals()
    {
        self::assertSame(
            '4',
            $this->fixture->formatDecimal(4.00)
        );
    }

    /**
     * @test
     */
    public function formatDecimalForFloatWithOnceDecimalReturnsNumberWithTwoDecimals()
    {
        self::assertSame(
            '4.50',
            $this->fixture->formatDecimal(4.50)
        );
    }

    /**
     * @test
     */
    public function formatDecimalForFloatWithTwoNonZeroDecimalsReturnsNumberWithBothDecimals()
    {
        self::assertEquals(
            '4.55',
            $this->fixture->formatDecimal(4.55)
        );
    }

    /**
     * @test
     */
    public function formatDecimalForFloatWithThreeDecimalsLastDecimalLowerThanFiveReturnsNumberWithOnlyTwoDecimals()
    {
        self::assertEquals(
            '4.55',
            $this->fixture->formatDecimal(4.553)
        );
    }

    /**
     * @test
     */
    public function formatDecimalForFloatWithThreeDecimalsLastDecimalFiveReturnsNumberWithLastDecimalRoundedUp()
    {
        self::assertEquals(
            '4.56',
            $this->fixture->formatDecimal(4.555)
        );
    }

    /**
     * @test
     */
    public function formatDecimalCanRoundToOneDecimal()
    {
        self::assertEquals(
            '4.1',
            $this->fixture->formatDecimal(4.1234, 1)
        );
    }

    /**
     * @test
     */
    public function formatDecimalCanRoundToTwoDecimals()
    {
        self::assertEquals(
            '4.12',
            $this->fixture->formatDecimal(4.1234, 2)
        );
    }

    /**
     * @test
     */
    public function formatDecimalCanRoundToThreeDecimals()
    {
        self::assertEquals(
            '4.123',
            $this->fixture->formatDecimal(4.1234, 3)
        );
    }
}
