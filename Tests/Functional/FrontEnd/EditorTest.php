<?php

namespace OliverKlee\Realty\Tests\Functional;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class EditorTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/oelib',
        'typo3conf/ext/static_info_tables',
        'typo3conf/ext/realty',
    ];

    /**
     * @var \tx_realty_frontEndEditor
     */
    private $fixture = null;

    /**
     * @var \Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int UID of the dummy object
     */
    private $dummyObjectUid = 0;

    /**
     * @var string dummy string value
     */
    private static $dummyStringValue = 'test value';

    /**
     * @var MailMessage|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message = null;

    protected function setUp()
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'Alex Doe';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'alex@example.com';

        \Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();
        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd($this->testingFramework->createFrontEndPage());

        \Tx_Oelib_ConfigurationRegistry::getInstance()
            ->set('plugin.tx_realty_pi1', new \Tx_Oelib_Configuration());

        $this->createDummyRecords();

        $this->fixture = new \tx_realty_frontEndEditor(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'feEditorTemplateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Editor.html',
            ],
            $this->getFrontEndController()->cObj,
            0,
            '',
            true
        );

        $this->message = $this->getMock(MailMessage::class, ['send']);
        GeneralUtility::addInstance(MailMessage::class, $this->message);
    }

    protected function tearDown()
    {
        // Get any surplus instances added via GeneralUtility::addInstance.
        GeneralUtility::makeInstance(MailMessage::class);

        \tx_realty_cacheManager::purgeCacheManager();

        $this->testingFramework->cleanUp();

        parent::tearDown();
    }

    /*
     * Utility functions.
     */

    /**
     * Returns the current front-end instance.
     *
     * @return TypoScriptFrontendController
     */
    private function getFrontEndController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Translates a string using the sL function from the front-end controller.
     *
     * @param string $key
     *
     * @return string
     */
    private function translate($key)
    {
        return $this->getFrontEndController()->sL($key);
    }

    /**
     * Creates dummy records in the DB and logs in a front-end user.
     *
     * @return void
     */
    private function createDummyRecords()
    {
        /** @var \tx_realty_Model_FrontEndUser $user */
        $user = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getLoadedTestingModel(
            [
                'username' => 'test_user',
                'name' => 'Mr. Test',
                'email' => 'mr-test@example.com',
                'tx_realty_openimmo_anid' => 'test-user-anid',
            ]
        );
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->dummyObjectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => self::$dummyStringValue,
                'language' => self::$dummyStringValue,
            ]
        );
        $this->createAuxiliaryRecords();
    }

    /**
     * Creates one dummy record in each table for auxiliary records.
     *
     * @return void
     */
    private function createAuxiliaryRecords()
    {
        $realtyObject = new \tx_realty_Model_RealtyObject(true);
        $realtyObject->loadRealtyObject($this->dummyObjectUid);

        foreach (
            [
                'city' => 'tx_realty_cities',
                'district' => 'tx_realty_districts',
            ] as $key => $table) {
            $realtyObject->setProperty($key, self::$dummyStringValue);
            $this->testingFramework->markTableAsDirty($table);
        }

        $realtyObject->writeToDatabase();
    }

    /////////////////////////////////////
    // Tests concerning deleteRecord().
    /////////////////////////////////////

    /**
     * @test
     */
    public function deleteRecordFromTheDatabase()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->dummyObjectUid,
            ['owner' => $this->testingFramework->createFrontEndUser()]
        );
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $this->fixture->deleteRecord();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'uid=' . $this->dummyObjectUid .
                \Tx_Oelib_Db::enableFields('tx_realty_objects')
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
    public function isObjectNumberReadonlyReturnsFalseForNewObject()
    {
        self::assertFalse(
            $this->fixture->isObjectNumberReadonly()
        );
    }

    /**
     * @test
     */
    public function isObjectNumberReadonlyReturnsTrueForAnExistingObject()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);

        self::assertTrue(
            $this->fixture->isObjectNumberReadonly()
        );
    }

    //////////////////////////////////////////
    // Tests concerning populateDistrictList
    //////////////////////////////////////////

    /**
     * @test
     */
    public function populateDistrictListForSelectedCityReturnsDistrictOfCity()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['city' => $cityUid, 'title' => 'Kreuzberg']
        );
        $this->fixture->setFakedFormValue('city', $cityUid);

        self::assertContains(
            ['value' => $districtUid, 'caption' => 'Kreuzberg'],
            $this->fixture->populateDistrictList()
        );
    }

    /**
     * @test
     */
    public function populateDistrictListForSelectedCityReturnsDistrictWithoutCity()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['title' => 'Kreuzberg']
        );
        $this->fixture->setFakedFormValue('city', $cityUid);

        self::assertContains(
            ['value' => $districtUid, 'caption' => 'Kreuzberg'],
            $this->fixture->populateDistrictList()
        );
    }

    /**
     * @test
     */
    public function populateDistrictListForSelectedCityNotReturnsDistrictOfOtherCity()
    {
        $cityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['city' => $otherCityUid, 'title' => 'Kreuzberg']
        );
        $this->fixture->setFakedFormValue('city', $cityUid);

        self::assertNotContains(
            ['value' => $districtUid, 'caption' => 'Kreuzberg'],
            $this->fixture->populateDistrictList()
        );
    }

    /**
     * @test
     */
    public function populateDistrictListForNoSelectedCityIsEmpty()
    {
        $this->fixture->setFakedFormValue('city', 0);

        self::assertEquals(
            [],
            $this->fixture->populateDistrictList()
        );
    }

    //////////////////////////////////
    // Tests concerning populateList
    //////////////////////////////////

    /**
     * @test
     */
    public function populateListForValidTableReturnsARecordsTitleAsCaption()
    {
        $result = $this->fixture->populateList(['table' => 'tx_realty_cities']);

        self::assertEquals(
            self::$dummyStringValue,
            $result[0]['caption']
        );
    }

    /**
     * @test
     */
    public function populateListForInvalidTableThrowsAnExeption()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->populateList(['table' => 'invalid_table']);
    }

    /**
     * @test
     */
    public function populateListForInvalidTitleColumnThrowsAnExeption()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->populateList(['title_column' => 'foo', 'table' => 'tx_realty_cities']);
    }

    /**
     * @test
     */
    public function populateListOfCountriesContainsGermany()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Countries.xml');

        self::assertContains(
            [
                'value' => '54',
                'caption' => 'Deutschland',
            ],
            $this->fixture->populateList(
                [
                    'table' => 'static_countries',
                    'title_column' => 'cn_short_local',
                ]
            )
        );
    }

    //////////////////////////////////
    // * Message creation functions.
    //////////////////////////////////

    /**
     * @test
     */
    public function getMessageForRealtyObjectFieldCanReturnMessageForField()
    {
        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.floor')
            . ': ' .
            $this->fixture->translate('message_no_valid_number'),
            $this->fixture->getMessageForRealtyObjectField(
                ['fieldName' => 'floor', 'label' => 'message_no_valid_number']
            )
        );
    }

    /**
     * @test
     */
    public function getMessageForRealtyObjectFieldCanReturnMessageWithoutFieldName()
    {
        self::assertEquals(
            $this->fixture->translate('message_no_valid_number'),
            $this->fixture->getMessageForRealtyObjectField(
                ['fieldName' => '', 'label' => 'message_no_valid_number']
            )
        );
    }

    /**
     * @test
     */
    public function getMessageForRealtyObjectThrowsAnExceptionForAnInvalidFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->getMessageForRealtyObjectField(['fieldName' => 'foo', 'label' => 'message_no_valid_number']);
    }

    /**
     * @test
     */
    public function getMessageForRealtyObjectFieldThrowsAnExceptionForInvalidLocallangKey()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->getMessageForRealtyObjectField(['label' => '123']);
    }

    /**
     * @test
     */
    public function getMessageForRealtyObjectFieldThrowsAnExceptionForEmptyLocallangKey()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->getMessageForRealtyObjectField(['label' => '']);
    }

    /**
     * @test
     */
    public function getNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToBuy()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.buying_price')
            . ': ' .
            $this->fixture->translate('message_enter_valid_non_empty_buying_price'),
            $this->fixture->getNoValidPriceOrEmptyMessage(['fieldName' => 'buying_price'])
        );
    }

    /**
     * @test
     */
    public function getNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToRent()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.buying_price')
            . ': ' .
            $this->fixture->translate('message_enter_valid_or_empty_buying_price'),
            $this->fixture->getNoValidPriceOrEmptyMessage(['fieldName' => 'buying_price'])
        );
    }

    /**
     * @test
     */
    public function getNoValidPriceOrEmptyMessageForRentFieldsIfObjectToRent()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.rent_excluding_bills')
            . ': ' .
            $this->fixture->translate('message_enter_valid_non_empty_rent'),
            $this->fixture->getNoValidPriceOrEmptyMessage(['fieldName' => 'rent_excluding_bills'])
        );
    }

    /**
     * @test
     */
    public function getNoValidPriceOrEmptyMessageForRentFieldsIfObjectToBuy()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.rent_excluding_bills')
            . ': ' .
            $this->fixture->translate('message_enter_valid_or_empty_rent'),
            $this->fixture->getNoValidPriceOrEmptyMessage(['fieldName' => 'rent_excluding_bills'])
        );
    }

    /**
     * @test
     */
    public function getInvalidObjectNumberMessageForEmptyObjectNumber()
    {
        $this->fixture->setFakedFormValue('object_number', '');

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.object_number')
            . ': ' .
            $this->fixture->translate('message_required_field'),
            $this->fixture->getInvalidObjectNumberMessage()
        );
    }

    /**
     * @test
     */
    public function getInvalidObjectNumberMessageForNonEmptyObjectNumber()
    {
        $this->fixture->setFakedFormValue('object_number', 'foo');

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.object_number')
            . ': ' .
            $this->fixture->translate('message_object_number_exists'),
            $this->fixture->getInvalidObjectNumberMessage()
        );
    }

    /**
     * @test
     */
    public function getInvalidOrEmptyCityMessageForEmptyCity()
    {
        $this->fixture->setFakedFormValue('city', 0);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.city') . ': '
            .
            $this->fixture->translate('message_required_field'),
            $this->fixture->getInvalidOrEmptyCityMessage()
        );
    }

    /**
     * @test
     */
    public function getInvalidOrEmptyCityMessageForNonEmptyCity()
    {
        $this->fixture->setFakedFormValue(
            'city',
            $this->testingFramework->createRecord(
                'tx_realty_cities',
                ['deleted' => 1]
            )
        );

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.city') . ': '
            .
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
    public function isValidNonNegativeIntegerNumberForIntegerReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->isValidNonNegativeIntegerNumber(['value' => '12345'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForIntegerWithSpaceAsThousandsSeparatorReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->isValidNonNegativeIntegerNumber(['value' => '12 345'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForEmptyStringReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->isValidNonNegativeIntegerNumber(['value' => ''])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForNumberWithDotAsDecimalSeparatorReturnsFalse()
    {
        self::assertFalse(
            $this->fixture->isValidNonNegativeIntegerNumber(['value' => '123.45'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForNumberWithCommaAsDecimalSeparatorReturnsFalse()
    {
        self::assertFalse(
            $this->fixture->isValidNonNegativeIntegerNumber(['value' => '123,45'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForNegativeIntegerReturnsFalse()
    {
        self::assertFalse(
            $this->fixture->isValidNonNegativeIntegerNumber(['value' => '-123'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForNonNumericStringReturnsFalse()
    {
        self::assertFalse(
            $this->fixture->isValidNonNegativeIntegerNumber(['value' => 'string'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForIntegerReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->isValidIntegerNumber(['value' => '12345'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForIntegerWithSpaceAsThousandsSeparatorReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->isValidIntegerNumber(['value' => '12 345'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForEmptyStringReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->isValidIntegerNumber(['value' => ''])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForNumberWithDotAsDecimalSeparatorReturnsFalse()
    {
        self::assertFalse(
            $this->fixture->isValidIntegerNumber(['value' => '123.45'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForNumberWithCommaAsDecimalSeparatorReturnsFalse()
    {
        self::assertFalse(
            $this->fixture->isValidIntegerNumber(['value' => '123,45'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForNegativeIntegerReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->isValidIntegerNumber(['value' => '-123'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForNonNumericStringReturnsFalse()
    {
        self::assertFalse(
            $this->fixture->isValidIntegerNumber(['value' => 'string'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithOneDecimal()
    {
        self::assertTrue(
            $this->fixture->isValidNumberWithDecimals(['value' => '1234.5'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithOneDecimalAndSpace()
    {
        self::assertTrue(
            $this->fixture->isValidNumberWithDecimals(['value' => '1 234.5'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithTwoDecimalsSeparatedByDot()
    {
        self::assertTrue(
            $this->fixture->isValidNumberWithDecimals(['value' => '123.45'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithTwoDecimalsSeparatedByComma()
    {
        self::assertTrue(
            $this->fixture->isValidNumberWithDecimals(['value' => '123,45'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithoutDecimals()
    {
        self::assertTrue(
            $this->fixture->isValidNumberWithDecimals(['value' => '12345'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForAnEmptyString()
    {
        self::assertTrue(
            $this->fixture->isValidNumberWithDecimals(['value' => ''])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsFalseForNumberWithMoreThanTwoDecimals()
    {
        self::assertFalse(
            $this->fixture->isValidNumberWithDecimals(['value' => '12.345'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsFalseForNonNumericString()
    {
        self::assertFalse(
            $this->fixture->isValidNumberWithDecimals(['value' => 'string'])
        );
    }

    /**
     * @test
     */
    public function isIntegerInRangeReturnsTrueForAllowedInteger()
    {
        self::assertTrue(
            $this->fixture->isIntegerInRange(
                ['value' => '1', 'range' => '1-2', 'multiple' => '0']
            )
        );
    }

    /**
     * @test
     */
    public function isIntegerInRangeReturnsFalseForBelowTheRange()
    {
        self::assertFalse(
            $this->fixture->isIntegerInRange(
                ['value' => '0', 'range' => '1-2', 'multiple' => '0']
            )
        );
    }

    /**
     * @test
     */
    public function isIntegerInRangeReturnsFalseForHigherThanTheRange()
    {
        self::assertFalse(
            $this->fixture->isIntegerInRange(
                ['value' => '2', 'range' => '0-1', 'multiple' => '0']
            )
        );
    }

    /**
     * @test
     */
    public function isIntegerInRangeReturnsFalseForNonIntegerValue()
    {
        self::assertFalse(
            $this->fixture->isIntegerInRange(
                ['value' => 'string', 'range' => '0-1', 'multiple' => '0']
            )
        );
    }

    /**
     * @test
     */
    public function isIntegerInRangeReturnsTrueForEmptyValue()
    {
        self::assertTrue(
            $this->fixture->isIntegerInRange(
                ['value' => '', 'range' => '1-2', 'multiple' => '0']
            )
        );
    }

    /**
     * @test
     */
    public function isValidYearReturnsTrueForTheCurrentYear()
    {
        self::assertTrue(
            $this->fixture->isValidYear(['value' => date('Y')])
        );
    }

    /**
     * @test
     */
    public function isValidYearReturnsTrueForFormerYear()
    {
        self::assertTrue(
            $this->fixture->isValidYear(['value' => '2000'])
        );
    }

    /**
     * @test
     */
    public function isValidYearReturnsTrueForFutureYear()
    {
        self::assertTrue(
            $this->fixture->isValidYear(['value' => '2100'])
        );
    }

    /**
     * @test
     */
    public function isValidYearReturnsFalseForNumberWithDecimals()
    {
        self::assertFalse(
            $this->fixture->isValidYear(['value' => '42,55'])
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForSaleIfThePriceIsValid()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
        self::assertTrue(
            $this->fixture->isNonEmptyValidPriceForObjectForSale(
                ['value' => '1234']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForSaleIfThePriceIsInvalid()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
        self::assertFalse(
            $this->fixture->isNonEmptyValidPriceForObjectForSale(
                ['value' => 'foo']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForSaleIfThePriceIsEmpty()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
        self::assertFalse(
            $this->fixture->isNonEmptyValidPriceForObjectForSale(
                ['value' => '']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfOnePriceIsValidAndOneEmpty()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->fixture->setFakedFormValue('year_rent', '');

        self::assertTrue(
            $this->fixture->isNonEmptyValidPriceForObjectForRent(
                ['value' => '1234']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentForEmptyValueAndValidYearRentIsTrue()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->fixture->setFakedFormValue('year_rent', '1234');

        static::assertTrue($this->fixture->isNonEmptyValidPriceForObjectForRent(['value' => '']));
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentForEmptyValueAndValidRentWithHeatingCostsRentIsTrue()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->fixture->setFakedFormValue('rent_with_heating_costs', '1234');

        static::assertTrue($this->fixture->isNonEmptyValidPriceForObjectForRent(['value' => '']));
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfBothPricesAreValid()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->fixture->setFakedFormValue('year_rent', '1234');

        self::assertTrue(
            $this->fixture->isNonEmptyValidPriceForObjectForRent(
                ['value' => '1234']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfBothPricesAreInvalid()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->fixture->setFakedFormValue('year_rent', 'foo');

        self::assertFalse(
            $this->fixture->isNonEmptyValidPriceForObjectForRent(
                ['value' => 'foo']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfBothPricesAreEmpty()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->fixture->setFakedFormValue('year_rent', '');

        self::assertFalse(
            $this->fixture->isNonEmptyValidPriceForObjectForRent(
                ['value' => '']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfOnePriceIsInvalidAndOneValid()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->fixture->setFakedFormValue('year_rent', '1234');

        self::assertFalse(
            $this->fixture->isNonEmptyValidPriceForObjectForRent(
                ['value' => 'foo']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfTheOtherPriceIsInvalidAndOneValid()
    {
        $this->fixture->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->fixture->setFakedFormValue('year_rent', 'foo');

        self::assertFalse(
            $this->fixture->isNonEmptyValidPriceForObjectForRent(
                ['value' => '1234']
            )
        );
    }

    /**
     * @test
     */
    public function isObjectNumberUniqueForLanguageForUniqueCombination()
    {
        // The dummy record's language is not ''. A new record's language
        // is always ''.
        self::assertTrue(
            $this->fixture->isObjectNumberUniqueForLanguage(
                ['value' => '1234']
            )
        );
    }

    /**
     * @test
     */
    public function isObjectNumberUniqueForLanguageForHiddenRecordWithDifferensObjectNumber()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->dummyObjectUid,
            ['hidden' => '1']
        );

        self::assertTrue(
            $this->fixture->isObjectNumberUniqueForLanguage(
                ['value' => '1234']
            )
        );
    }

    /**
     * @test
     */
    public function isObjectNumberUniqueForLanguageForExistentCombination()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->dummyObjectUid,
            ['language' => '']
        );

        self::assertFalse(
            $this->fixture->isObjectNumberUniqueForLanguage(
                ['value' => self::$dummyStringValue]
            )
        );
    }

    /**
     * @test
     */
    public function isObjectNumberUniqueForLanguageForHiddenRecordWithSameObjectNumber()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->dummyObjectUid,
            ['language' => '', 'hidden' => '1']
        );

        self::assertFalse(
            $this->fixture->isObjectNumberUniqueForLanguage(
                ['value' => self::$dummyStringValue]
            )
        );
    }

    /**
     * @test
     */
    public function isObjectNumberUniqueForLanguageForEmptyObjectNumber()
    {
        self::assertFalse(
            $this->fixture->isObjectNumberUniqueForLanguage(
                ['value' => '']
            )
        );
    }

    /**
     * @test
     */
    public function isAllowedValueForCityReturnsTrueForAllowedValue()
    {
        self::assertTrue(
            $this->fixture->isAllowedValueForCity(
                ['value' => $this->testingFramework->createRecord('tx_realty_cities')]
            )
        );
    }

    /**
     * @test
     */
    public function isAllowedValueForCityReturnsTrueForZeroIfANewRecordTitleIsProvided()
    {
        $this->fixture->setFakedFormValue('new_city', 'new city');

        self::assertTrue(
            $this->fixture->isAllowedValueForCity(
                ['value' => '0']
            )
        );
    }

    /**
     * @test
     */
    public function isAllowedValueForCityReturnsFalseForZeroIfNoNewRecordTitleIsProvided()
    {
        self::assertFalse(
            $this->fixture->isAllowedValueForCity(
                ['value' => '0']
            )
        );
    }

    /**
     * @test
     */
    public function isAllowedValueForCityReturnsFalseForInvalidValue()
    {
        self::assertFalse(
            $this->fixture->isAllowedValueForCity(
                [
                    'value' => $this->testingFramework->createRecord(
                        'tx_realty_cities',
                        ['deleted' => 1]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function checkKeyExistsInTableReturnsTrueForAllowedValue()
    {
        self::assertTrue(
            $this->fixture->checkKeyExistsInTable(
                [
                    'value' => $this->testingFramework->createRecord('tx_realty_districts'),
                    'table' => 'tx_realty_districts',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function checkKeyExistsInTableReturnsTrueForZero()
    {
        self::assertTrue(
            $this->fixture->checkKeyExistsInTable(
                ['value' => '0', 'table' => 'tx_realty_districts']
            )
        );
    }

    /**
     * @test
     */
    public function checkKeyExistsInTableReturnsFalseForInvalidValue()
    {
        self::assertFalse(
            $this->fixture->checkKeyExistsInTable(
                [
                    'value' => $this->testingFramework->createRecord(
                        'tx_realty_districts',
                        ['deleted' => 1]
                    ),
                    'table' => 'tx_realty_districts',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function checkKeyExistsInTableThrowsExceptionForInvalidTable()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->checkKeyExistsInTable([
            'value' => 1,
            'table' => 'invalid_table',
        ]);
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsTrueFor180WithoutDecimal()
    {
        self::assertTrue(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '180']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsTrueFor180WithOneDecimal()
    {
        self::assertTrue(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '180.0']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsTrueFor180WithTwoDecimals()
    {
        self::assertTrue(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '180.00']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsTrueForMinus180()
    {
        self::assertTrue(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '-180.0']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsFalseForGreater180()
    {
        self::assertFalse(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '180.1']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsFalseForLowerMinus180()
    {
        self::assertFalse(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '-180.1']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsTrueForValueInAllowedPositiveRangeWithManyDecimals()
    {
        self::assertTrue(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '123.12345678901234']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsTrueForValueInAllowedNegativeRangeWithDecimals()
    {
        self::assertTrue(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '-123.12345678901234']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsTrueForZero()
    {
        self::assertTrue(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '0']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsTrueForEmptyString()
    {
        self::assertTrue(
            $this->fixture->isValidLongitudeDegree(
                ['value' => '']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLongitudeDegreeReturnsFalseForAlphaChars()
    {
        self::assertFalse(
            $this->fixture->isValidLongitudeDegree(
                ['value' => 'abc']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsTrueFor90WithNoDecimal()
    {
        self::assertTrue(
            $this->fixture->isValidLatitudeDegree(
                ['value' => '90']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsTrueFor90WithOneDecimal()
    {
        self::assertTrue(
            $this->fixture->isValidLatitudeDegree(
                ['value' => '90.0']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsTrueForMinus90()
    {
        self::assertTrue(
            $this->fixture->isValidLatitudeDegree(
                ['value' => '-90.0']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsFalseForGreater90()
    {
        self::assertFalse(
            $this->fixture->isValidLatitudeDegree(
                ['value' => '90.1']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsFalseForLowerMinus90()
    {
        self::assertFalse(
            $this->fixture->isValidLatitudeDegree(
                ['value' => '-90.1']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsTrueForValueInAllowedPositiveRangeWithDecimals()
    {
        self::assertTrue(
            $this->fixture->isValidLatitudeDegree(
                ['value' => '83.12345678901234']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsTrueForValueInAllowedNegativeRangeWithDecimals()
    {
        self::assertTrue(
            $this->fixture->isValidLatitudeDegree(
                ['value' => '-83.12345678901234']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsTrueForEmptyString()
    {
        self::assertTrue(
            $this->fixture->isValidLatitudeDegree(
                ['value' => '']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsTrueForZero()
    {
        self::assertTrue(
            $this->fixture->isValidLatitudeDegree(
                ['value' => '0']
            )
        );
    }

    /**
     * @test
     */
    public function isValidLatitudeDegreeReturnsFalseForAlphaChars()
    {
        self::assertFalse(
            $this->fixture->isValidLatitudeDegree(
                ['value' => 'abc']
            )
        );
    }

    /**
     * @test
     */
    public function isAtMostOneValueForAuxiliaryRecordProvidedReturnsTrueForNonEmptyNewTitleAndNoExistingRecord()
    {
        $this->fixture->setFakedFormValue('city', 0);

        self::assertTrue(
            $this->fixture->isAtMostOneValueForAuxiliaryRecordProvided([
                'value' => $this->testingFramework->createRecord('tx_realty_cities'),
                'fieldName' => 'city',
            ])
        );
    }

    /**
     * @test
     */
    public function isAtMostOneValueForAuxiliaryRecordProvidedReturnsFalseForNonEmptyNewTitleAndExistingRecord()
    {
        $this->fixture->setFakedFormValue('city', $this->testingFramework->createRecord('tx_realty_cities'));

        self::assertFalse(
            $this->fixture->isAtMostOneValueForAuxiliaryRecordProvided([
                'value' => $this->testingFramework->createRecord('tx_realty_cities'),
                'fieldName' => 'city',
            ])
        );
    }

    /**
     * @test
     */
    public function isNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsOwner()
    {
        $this->fixture->setFakedFormValue(
            'contact_data_source',
            \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT
        );

        self::assertTrue(
            $this->fixture->isNonEmptyOrOwnerDataUsed([])
        );
    }

    /**
     * @test
     */
    public function isNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsNotOwnerAndTheValueIsNonEmpty()
    {
        $this->fixture->setFakedFormValue(
            'contact_data_source',
            \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT
        );

        self::assertTrue(
            $this->fixture->isNonEmptyOrOwnerDataUsed(['value' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function isNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsNotOwnerAndTheValueIsEmpty()
    {
        $this->fixture->setFakedFormValue(
            'contact_data_source',
            \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT
        );

        self::assertFalse(
            $this->fixture->isNonEmptyOrOwnerDataUsed([])
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
    public function addAdministrativeDataAddsTheTimeStampForExistingObject()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);

        $result = $this->fixture->modifyDataToInsert([]);
        // object type will always be added and is not needed here.
        unset($result['object_type']);

        self::assertEquals(
            'tstamp',
            key($result)
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsTimeStampForNewObject()
    {
        $this->fixture->setRealtyObjectUid(0);

        self::assertContains(
            'tstamp',
            $this->fixture->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsDateForNewObject()
    {
        $this->fixture->setRealtyObjectUid(0);

        self::assertContains(
            'crdate',
            $this->fixture->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsPidForNewObject()
    {
        $this->fixture->setRealtyObjectUid(0);

        self::assertContains(
            'pid',
            $this->fixture->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsHiddenFlagForNewObject()
    {
        $this->fixture->setRealtyObjectUid(0);

        self::assertContains(
            'hidden',
            $this->fixture->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsObjectTypeForNewObject()
    {
        $this->fixture->setRealtyObjectUid(0);

        self::assertContains(
            'object_type',
            $this->fixture->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsOwnerForNewObject()
    {
        $this->fixture->setRealtyObjectUid(0);

        self::assertContains(
            'owner',
            $this->fixture->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsOpenImmoAnidForNewObject()
    {
        $this->fixture->setRealtyObjectUid(0);

        self::assertContains(
            'openimmo_anid',
            $this->fixture->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsDefaultPidForNewObject()
    {
        $systemFolderPid = $this->testingFramework->createSystemFolder(1);
        $this->fixture->setConfigurationValue(
            'sysFolderForFeCreatedRecords',
            $systemFolderPid
        );
        $this->fixture->setRealtyObjectUid(0);
        $result = $this->fixture->modifyDataToInsert([]);

        self::assertEquals(
            $systemFolderPid,
            $result['pid']
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataNotAddsDefaultPidForExistingObject()
    {
        $systemFolderPid = $this->testingFramework->createSystemFolder(1);
        $this->fixture->setConfigurationValue(
            'sysFolderForFeCreatedRecords',
            $systemFolderPid
        );
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert([]);

        self::assertNotEquals(
            $systemFolderPid,
            $result['pid']
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsPidDerivedFromCityRecordForNewObject()
    {
        $systemFolderPid = $this->testingFramework->createSystemFolder(1);
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['save_folder' => $systemFolderPid]
        );

        $this->fixture->setRealtyObjectUid(0);
        $result = $this->fixture->modifyDataToInsert(['city' => $cityUid]);

        self::assertEquals(
            $systemFolderPid,
            $result['pid']
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsPidDerivedFromCityRecordForExistentObject()
    {
        $systemFolderPid = $this->testingFramework->createSystemFolder(1);
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['save_folder' => $systemFolderPid]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $cityUid]
        );

        $this->fixture->setRealtyObjectUid(0);
        $result = $this->fixture->modifyDataToInsert(['city' => $cityUid]);

        self::assertEquals(
            $systemFolderPid,
            $result['pid']
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsFrontEndUserUidForNewObject()
    {
        $this->fixture->setRealtyObjectUid(0);
        $result = $this->fixture->modifyDataToInsert([]);

        self::assertEquals(
            \Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser()
                ->getUid(),
            $result['owner']
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataNotAddsFrontEndUserUidForObjectToUpdate()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert([]);

        self::assertFalse(
            isset($result['owner'])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsFrontEndUsersOpenImmoAnidForNewObject()
    {
        $this->fixture->setRealtyObjectUid(0);
        $result = $this->fixture->modifyDataToInsert([]);

        self::assertEquals(
            'test-user-anid',
            $result['openimmo_anid']
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsEmptyOpenImmoAnidForNewObjectIfUserHasNoAnid()
    {
        $user = new \tx_realty_Model_FrontEndUser();
        $user->setData([]);
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->setRealtyObjectUid(0);
        $result = $this->fixture->modifyDataToInsert([]);

        self::assertEquals(
            '',
            $result['openimmo_anid']
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataNotAddsFrontEndUsersOpenImmoAnidForAnObjectToUpdate()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert([]);

        self::assertFalse(
            isset($result['openimmo_anid'])
        );
    }

    /**
     * @test
     */
    public function newRecordIsMarkedAsHidden()
    {
        $this->fixture->setRealtyObjectUid(0);
        $result = $this->fixture->modifyDataToInsert([]);

        self::assertEquals(
            1,
            $result['hidden']
        );
    }

    /**
     * @test
     */
    public function existingRecordIsNotMarkedAsHidden()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert([]);

        self::assertFalse(
            isset($result['hidden'])
        );
    }

    ///////////////////////
    // ** unifyNumbers().
    ///////////////////////

    /**
     * @test
     */
    public function unifyNumbersToInsertForNonNumericValues()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $formData = [
            'title' => '12,3.45',
            'employer' => 'abc,de.fgh',
        ];
        $result = $this->fixture->modifyDataToInsert($formData);
        // PID, object type and time stamp will always be added,
        // they are not needed here.
        unset($result['tstamp'], $result['pid'], $result['object_type']);

        self::assertEquals(
            $formData,
            $result
        );
    }

    /**
     * @test
     */
    public function unifyNumbersToInsertIfSomeElementsNeedFormatting()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert([
            'garage_rent' => '123,45',
            'garage_price' => '12 345',
        ]);
        // PID, object type and time stamp will always be added,
        // they are not needed here.
        unset($result['tstamp'], $result['pid'], $result['object_type']);

        self::assertEquals(
            ['garage_rent' => '123.45', 'garage_price' => '12345'],
            $result
        );
    }

    ///////////////////////////////////
    // ** storeNewAuxiliaryRecords().
    ///////////////////////////////////

    /**
     * @test
     */
    public function storeNewAuxiliaryRecordsDeletesNonEmptyNewCityElement()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert(
            ['new_city' => 'foo']
        );

        self::assertFalse(
            isset($result['new_city'])
        );
    }

    /**
     * @test
     */
    public function storeNewAuxiliaryRecordsDeletesEmptyNewCityElement()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert(
            ['new_city' => '']
        );

        self::assertFalse(
            isset($result['new_city'])
        );
    }

    /**
     * @test
     */
    public function storeNewAuxiliaryRecordsDeletesNonEmptyNewDistrictElement()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert(
            ['new_district' => 'foo']
        );

        self::assertFalse(
            isset($result['new_district'])
        );
    }

    /**
     * @test
     */
    public function storeNewAuxiliaryRecordsDeletesEmptyNewDistrictElement()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert(
            ['new_district' => '']
        );

        self::assertFalse(
            isset($result['new_district'])
        );
    }

    /**
     * @test
     */
    public function storeNewAuxiliaryRecordsNotCreatesANewRecordForAnExistingTitle()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $this->fixture->modifyDataToInsert(
            ['new_city' => self::$dummyStringValue]
        );

        self::assertEquals(
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
    public function storeNewAuxiliaryRecordsCreatesANewRecordForNewTitle()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $this->fixture->modifyDataToInsert(['new_city' => 'new city']);

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_cities',
                'title = "new city"'
            )
        );
    }

    /**
     * @test
     */
    public function storeNewAuxiliaryRecordsCreatesANewRecordWithCorrectPid()
    {
        $pid = $this->testingFramework->createSystemFolder(1);
        $configuration = new \Tx_Oelib_Configuration();
        $configuration->setData([
            'sysFolderForFeCreatedAuxiliaryRecords' => $pid,
        ]);
        \Tx_Oelib_ConfigurationRegistry::getInstance()->set(
            'plugin.tx_realty_pi1',
            $configuration
        );

        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $this->fixture->modifyDataToInsert(['new_city' => 'new city']);

        self::assertEquals(
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
    public function storeNewAuxiliaryRecordsStoresNewUidToTheFormData()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert(
            ['new_city' => 'new city']
        );

        self::assertTrue(
            isset($result['city'])
        );
        self::assertNotEquals(
            0,
            $result['city']
        );
    }

    /**
     * @test
     */
    public function storeNewAuxiliaryRecordsCreatesnoNewRecordForAnEmptyTitle()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $this->fixture->modifyDataToInsert(['new_city' => '']);

        self::assertEquals(
            1,
            $this->testingFramework->countRecords('tx_realty_cities')
        );
    }

    /**
     * @test
     */
    public function storeNewAuxiliaryRecordsNotCreatesARecordIfAUidIsAlreadySet()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->fixture->modifyDataToInsert(
            ['city' => 1, 'new_city' => 'new city']
        );

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_cities',
                'title = "new city"'
            )
        );
        self::assertEquals(
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
    public function fieldThatDoesNotExistInTheRealtyObjectsTableIsPurged()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);

        self::assertFalse(
            array_key_exists(
                'spacer_01',
                $this->fixture->modifyDataToInsert(['spacer_01' => 'blubb'])
            )
        );
        // TODO: remove the workaround when PHPUnit Bug 992 is fixed.
        // @see http://www.phpunit.de/ticket/992
    }

    /**
     * @test
     */
    public function fieldThatExitsInTheRealtyObjectsTableIsNotPurged()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);

        self::assertTrue(
            array_key_exists(
                'title',
                $this->fixture->modifyDataToInsert(['title' => 'foo'])
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
    public function sendEmailForNewObjectSendsToTheConfiguredRecipient()
    {
        // This will create an empty dummy record.
        $this->fixture->writeFakedFormDataToDatabase();
        $this->fixture->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        self::assertArrayHasKey(
            'recipient@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function sentEmailHasDefaultSenderAsFrom()
    {
        // This will create an empty dummy record.
        $this->fixture->writeFakedFormDataToDatabase();
        $this->fixture->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        self::assertArrayHasKey('alex@example.com', $this->message->getFrom());
    }

    /**
     * @test
     */
    public function sentEmailHasTheCurrentFeUserAsReplyTo()
    {
        // This will create an empty dummy record.
        $this->fixture->writeFakedFormDataToDatabase();
        $this->fixture->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        self::assertArrayHasKey('mr-test@example.com', $this->message->getReplyTo());
    }

    /**
     * @test
     */
    public function sentEmailContainsTheFeUsersName()
    {
        // This will create an empty dummy record.
        $this->fixture->writeFakedFormDataToDatabase();
        $this->fixture->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        self::assertContains(
            'Mr. Test',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function sentEmailContainsTheFeUsersUsername()
    {
        // This will create an empty dummy record.
        $this->fixture->writeFakedFormDataToDatabase();
        $this->fixture->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        self::assertContains(
            'test_user',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function sentEmailContainsTheNewObjectsTitle()
    {
        $this->fixture->setFakedFormValue('title', 'any title');
        $this->fixture->writeFakedFormDataToDatabase();
        $this->fixture->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        self::assertContains(
            'any title',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function sentEmailContainsTheNewObjectsObjectNumber()
    {
        $this->fixture->setFakedFormValue('object_number', '1234');
        $this->fixture->writeFakedFormDataToDatabase();
        $this->fixture->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        self::assertContains(
            '1234',
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function sentEmailContainsTheNewObjectsUid()
    {
        // The UID is found with the help of the combination of object number
        // and language.
        $this->fixture->setFakedFormValue('object_number', '1234');
        $this->fixture->setFakedFormValue('language', 'XY');
        $this->fixture->writeFakedFormDataToDatabase();
        $this->fixture->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        $expectedResult = \Tx_Oelib_Db::selectSingle(
            'uid',
            'tx_realty_objects',
            'object_number="1234" AND language="XY"'
        );

        self::assertContains(
            (string)$expectedResult['uid'],
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function noEmailIsSentIfNoRecipientWasConfigured()
    {
        $this->fixture->setConfigurationValue('feEditorNotifyEmail', '');
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        $this->message->expects(self::never())->method('send');
    }

    /**
     * @test
     */
    public function noEmailIsSentForExistingObject()
    {
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
        $this->fixture->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();

        $this->message->expects(self::never())->method('send');
    }

    /**
     * @test
     */
    public function sendEmailForNewObjectAndClearFrontEndCacheClearsFrontEndCache()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->testingFramework->createContentElement($pageUid, ['list_type' => 'realty_pi1']);

        /** @var AbstractFrontend|\PHPUnit_Framework_MockObject_MockObject $cacheFrontEnd */
        $cacheFrontEnd = $this->getMock(
            AbstractFrontend::class,
            ['getIdentifier', 'set', 'get', 'getByTag', 'getBackend'],
            [],
            '',
            false
        );
        $cacheFrontEnd->expects(self::once())->method('getIdentifier')->will(self::returnValue('cache_pages'));
        /** @var TaggableBackendInterface|\PHPUnit_Framework_MockObject_MockObject $cacheBackEnd */
        $cacheBackEnd = $this->getMock(TaggableBackendInterface::class);
        $cacheFrontEnd->method('getBackend')->will(self::returnValue($cacheBackEnd));
        $cacheBackEnd->expects(self::atLeastOnce())->method('flushByTag');

        $cacheManager = new CacheManager();
        $cacheManager->registerCache($cacheFrontEnd);
        \tx_realty_cacheManager::injectCacheManager($cacheManager);

        $this->fixture->sendEmailForNewObjectAndClearFrontEndCache();
    }

    //////////////////////////////////////
    // Tests concerning populateCityList
    //////////////////////////////////////

    /**
     * @test
     */
    public function populateCityListContainsCityFromDatabase()
    {
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Bonn']
        );

        self::assertContains(
            ['value' => $cityUid, 'caption' => 'Bonn'],
            \tx_realty_frontEndEditor::populateCityList()
        );
    }
}
