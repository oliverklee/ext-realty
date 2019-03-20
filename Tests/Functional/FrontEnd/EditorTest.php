<?php

namespace OliverKlee\Realty\Tests\Functional\FrontEnd;

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
        'typo3conf/ext/static_info_tables',
        'typo3conf/ext/oelib',
        'typo3conf/ext/realty',
    ];

    /**
     * @var \tx_realty_frontEndEditor
     */
    private $subject = null;

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

        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->setResetAutoIncrementThreshold(99999999);
        $this->testingFramework->createFakeFrontEnd($this->testingFramework->createFrontEndPage());
        \Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();

        \Tx_Oelib_ConfigurationRegistry::getInstance()
            ->set('plugin.tx_realty_pi1', new \Tx_Oelib_Configuration());

        $this->createDummyRecords();

        $this->subject = new \tx_realty_frontEndEditor(
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
            ['city' => 'tx_realty_cities', 'district' => 'tx_realty_districts'] as $key => $table
        ) {
            $realtyObject->setProperty($key, self::$dummyStringValue);
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $this->subject->deleteRecord();

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
            $this->subject->isObjectNumberReadonly()
        );
    }

    /**
     * @test
     */
    public function isObjectNumberReadonlyReturnsTrueForAnExistingObject()
    {
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);

        self::assertTrue(
            $this->subject->isObjectNumberReadonly()
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
        $this->subject->setFakedFormValue('city', $cityUid);

        self::assertContains(
            ['value' => $districtUid, 'caption' => 'Kreuzberg'],
            $this->subject->populateDistrictList()
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
        $this->subject->setFakedFormValue('city', $cityUid);

        self::assertNotContains(
            ['value' => $districtUid, 'caption' => 'Kreuzberg'],
            $this->subject->populateDistrictList()
        );
    }

    /**
     * @test
     */
    public function populateDistrictListForNoSelectedCityIsEmpty()
    {
        $this->subject->setFakedFormValue('city', 0);

        self::assertEquals(
            [],
            $this->subject->populateDistrictList()
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
        $result = $this->subject->populateList(['table' => 'tx_realty_cities']);

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

        $this->subject->populateList(['table' => 'invalid_table']);
    }

    /**
     * @test
     */
    public function populateListForInvalidTitleColumnThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->populateList(['title_column' => 'foo', 'table' => 'tx_realty_cities']);
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
            $this->subject->populateList(
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
            $this->subject->translate('message_no_valid_number'),
            $this->subject->getMessageForRealtyObjectField(
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
            $this->subject->translate('message_no_valid_number'),
            $this->subject->getMessageForRealtyObjectField(
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

        $this->subject->getMessageForRealtyObjectField(['fieldName' => 'foo', 'label' => 'message_no_valid_number']);
    }

    /**
     * @test
     */
    public function getMessageForRealtyObjectFieldThrowsAnExceptionForInvalidLocallangKey()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->getMessageForRealtyObjectField(['label' => '123']);
    }

    /**
     * @test
     */
    public function getMessageForRealtyObjectFieldThrowsAnExceptionForEmptyLocallangKey()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->getMessageForRealtyObjectField(['label' => '']);
    }

    /**
     * @test
     */
    public function getNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToBuy()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.buying_price')
            . ': ' .
            $this->subject->translate('message_enter_valid_non_empty_buying_price'),
            $this->subject->getNoValidPriceOrEmptyMessage(['fieldName' => 'buying_price'])
        );
    }

    /**
     * @test
     */
    public function getNoValidPriceOrEmptyMessageForBuyingPriceFieldIfObjectToRent()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.buying_price')
            . ': ' .
            $this->subject->translate('message_enter_valid_or_empty_buying_price'),
            $this->subject->getNoValidPriceOrEmptyMessage(['fieldName' => 'buying_price'])
        );
    }

    /**
     * @test
     */
    public function getNoValidPriceOrEmptyMessageForRentFieldsIfObjectToRent()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.rent_excluding_bills')
            . ': ' .
            $this->subject->translate('message_enter_valid_non_empty_rent'),
            $this->subject->getNoValidPriceOrEmptyMessage(['fieldName' => 'rent_excluding_bills'])
        );
    }

    /**
     * @test
     */
    public function getNoValidPriceOrEmptyMessageForRentFieldsIfObjectToBuy()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.rent_excluding_bills')
            . ': ' .
            $this->subject->translate('message_enter_valid_or_empty_rent'),
            $this->subject->getNoValidPriceOrEmptyMessage(['fieldName' => 'rent_excluding_bills'])
        );
    }

    /**
     * @test
     */
    public function getInvalidObjectNumberMessageForEmptyObjectNumber()
    {
        $this->subject->setFakedFormValue('object_number', '');

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.object_number')
            . ': ' .
            $this->subject->translate('message_required_field'),
            $this->subject->getInvalidObjectNumberMessage()
        );
    }

    /**
     * @test
     */
    public function getInvalidObjectNumberMessageForNonEmptyObjectNumber()
    {
        $this->subject->setFakedFormValue('object_number', 'foo');

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.object_number')
            . ': ' .
            $this->subject->translate('message_object_number_exists'),
            $this->subject->getInvalidObjectNumberMessage()
        );
    }

    /**
     * @test
     */
    public function getInvalidOrEmptyCityMessageForEmptyCity()
    {
        $this->subject->setFakedFormValue('city', 0);

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.city') . ': '
            .
            $this->subject->translate('message_required_field'),
            $this->subject->getInvalidOrEmptyCityMessage()
        );
    }

    /**
     * @test
     */
    public function getInvalidOrEmptyCityMessageForNonEmptyCity()
    {
        $this->subject->setFakedFormValue(
            'city',
            $this->testingFramework->createRecord(
                'tx_realty_cities',
                ['deleted' => 1]
            )
        );

        self::assertEquals(
            $this->translate('LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tx_realty_objects.city') . ': '
            .
            $this->subject->translate('message_value_not_allowed'),
            $this->subject->getInvalidOrEmptyCityMessage()
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
            $this->subject->isValidNonNegativeIntegerNumber(['value' => '12345'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForIntegerWithSpaceAsThousandsSeparatorReturnsTrue()
    {
        self::assertTrue(
            $this->subject->isValidNonNegativeIntegerNumber(['value' => '12 345'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForEmptyStringReturnsTrue()
    {
        self::assertTrue(
            $this->subject->isValidNonNegativeIntegerNumber(['value' => ''])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForNumberWithDotAsDecimalSeparatorReturnsFalse()
    {
        self::assertFalse(
            $this->subject->isValidNonNegativeIntegerNumber(['value' => '123.45'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForNumberWithCommaAsDecimalSeparatorReturnsFalse()
    {
        self::assertFalse(
            $this->subject->isValidNonNegativeIntegerNumber(['value' => '123,45'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForNegativeIntegerReturnsFalse()
    {
        self::assertFalse(
            $this->subject->isValidNonNegativeIntegerNumber(['value' => '-123'])
        );
    }

    /**
     * @test
     */
    public function isValidNonNegativeIntegerNumberForNonNumericStringReturnsFalse()
    {
        self::assertFalse(
            $this->subject->isValidNonNegativeIntegerNumber(['value' => 'string'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForIntegerReturnsTrue()
    {
        self::assertTrue(
            $this->subject->isValidIntegerNumber(['value' => '12345'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForIntegerWithSpaceAsThousandsSeparatorReturnsTrue()
    {
        self::assertTrue(
            $this->subject->isValidIntegerNumber(['value' => '12 345'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForEmptyStringReturnsTrue()
    {
        self::assertTrue(
            $this->subject->isValidIntegerNumber(['value' => ''])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForNumberWithDotAsDecimalSeparatorReturnsFalse()
    {
        self::assertFalse(
            $this->subject->isValidIntegerNumber(['value' => '123.45'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForNumberWithCommaAsDecimalSeparatorReturnsFalse()
    {
        self::assertFalse(
            $this->subject->isValidIntegerNumber(['value' => '123,45'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForNegativeIntegerReturnsTrue()
    {
        self::assertTrue(
            $this->subject->isValidIntegerNumber(['value' => '-123'])
        );
    }

    /**
     * @test
     */
    public function isValidIntegerNumberForNonNumericStringReturnsFalse()
    {
        self::assertFalse(
            $this->subject->isValidIntegerNumber(['value' => 'string'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithOneDecimal()
    {
        self::assertTrue(
            $this->subject->isValidNumberWithDecimals(['value' => '1234.5'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithOneDecimalAndSpace()
    {
        self::assertTrue(
            $this->subject->isValidNumberWithDecimals(['value' => '1 234.5'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithTwoDecimalsSeparatedByDot()
    {
        self::assertTrue(
            $this->subject->isValidNumberWithDecimals(['value' => '123.45'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithTwoDecimalsSeparatedByComma()
    {
        self::assertTrue(
            $this->subject->isValidNumberWithDecimals(['value' => '123,45'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForNumberWithoutDecimals()
    {
        self::assertTrue(
            $this->subject->isValidNumberWithDecimals(['value' => '12345'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsTrueForAnEmptyString()
    {
        self::assertTrue(
            $this->subject->isValidNumberWithDecimals(['value' => ''])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsFalseForNumberWithMoreThanTwoDecimals()
    {
        self::assertFalse(
            $this->subject->isValidNumberWithDecimals(['value' => '12.345'])
        );
    }

    /**
     * @test
     */
    public function isValidNumberWithDecimalsReturnsFalseForNonNumericString()
    {
        self::assertFalse(
            $this->subject->isValidNumberWithDecimals(['value' => 'string'])
        );
    }

    /**
     * @test
     */
    public function isIntegerInRangeReturnsTrueForAllowedInteger()
    {
        self::assertTrue(
            $this->subject->isIntegerInRange(
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
            $this->subject->isIntegerInRange(
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
            $this->subject->isIntegerInRange(
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
            $this->subject->isIntegerInRange(
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
            $this->subject->isIntegerInRange(
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
            $this->subject->isValidYear(['value' => date('Y')])
        );
    }

    /**
     * @test
     */
    public function isValidYearReturnsTrueForFormerYear()
    {
        self::assertTrue(
            $this->subject->isValidYear(['value' => '2000'])
        );
    }

    /**
     * @test
     */
    public function isValidYearReturnsTrueForFutureYear()
    {
        self::assertTrue(
            $this->subject->isValidYear(['value' => '2100'])
        );
    }

    /**
     * @test
     */
    public function isValidYearReturnsFalseForNumberWithDecimals()
    {
        self::assertFalse(
            $this->subject->isValidYear(['value' => '42,55'])
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForSaleIfThePriceIsValid()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
        self::assertTrue(
            $this->subject->isNonEmptyValidPriceForObjectForSale(
                ['value' => '1234']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForSaleIfThePriceIsInvalid()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
        self::assertFalse(
            $this->subject->isNonEmptyValidPriceForObjectForSale(
                ['value' => 'foo']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForSaleIfThePriceIsEmpty()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
        self::assertFalse(
            $this->subject->isNonEmptyValidPriceForObjectForSale(
                ['value' => '']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfOnePriceIsValidAndOneEmpty()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->subject->setFakedFormValue('year_rent', '');

        self::assertTrue(
            $this->subject->isNonEmptyValidPriceForObjectForRent(
                ['value' => '1234']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentForEmptyValueAndValidYearRentIsTrue()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->subject->setFakedFormValue('year_rent', '1234');

        static::assertTrue($this->subject->isNonEmptyValidPriceForObjectForRent(['value' => '']));
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentForEmptyValueAndValidRentWithHeatingCostsRentIsTrue()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->subject->setFakedFormValue('rent_with_heating_costs', '1234');

        static::assertTrue($this->subject->isNonEmptyValidPriceForObjectForRent(['value' => '']));
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfBothPricesAreValid()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->subject->setFakedFormValue('year_rent', '1234');

        self::assertTrue(
            $this->subject->isNonEmptyValidPriceForObjectForRent(
                ['value' => '1234']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfBothPricesAreInvalid()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->subject->setFakedFormValue('year_rent', 'foo');

        self::assertFalse(
            $this->subject->isNonEmptyValidPriceForObjectForRent(
                ['value' => 'foo']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfBothPricesAreEmpty()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->subject->setFakedFormValue('year_rent', '');

        self::assertFalse(
            $this->subject->isNonEmptyValidPriceForObjectForRent(
                ['value' => '']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfOnePriceIsInvalidAndOneValid()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->subject->setFakedFormValue('year_rent', '1234');

        self::assertFalse(
            $this->subject->isNonEmptyValidPriceForObjectForRent(
                ['value' => 'foo']
            )
        );
    }

    /**
     * @test
     */
    public function isNonEmptyValidPriceForObjectForRentIfTheOtherPriceIsInvalidAndOneValid()
    {
        $this->subject->setFakedFormValue('object_type', \tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
        $this->subject->setFakedFormValue('year_rent', 'foo');

        self::assertFalse(
            $this->subject->isNonEmptyValidPriceForObjectForRent(
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
            $this->subject->isObjectNumberUniqueForLanguage(
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
            $this->subject->isObjectNumberUniqueForLanguage(
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
            $this->subject->isObjectNumberUniqueForLanguage(
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
            $this->subject->isObjectNumberUniqueForLanguage(
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
            $this->subject->isObjectNumberUniqueForLanguage(
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
            $this->subject->isAllowedValueForCity(
                ['value' => $this->testingFramework->createRecord('tx_realty_cities')]
            )
        );
    }

    /**
     * @test
     */
    public function isAllowedValueForCityReturnsTrueForZeroIfANewRecordTitleIsProvided()
    {
        $this->subject->setFakedFormValue('new_city', 'new city');

        self::assertTrue(
            $this->subject->isAllowedValueForCity(
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
            $this->subject->isAllowedValueForCity(
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
            $this->subject->isAllowedValueForCity(
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
            $this->subject->checkKeyExistsInTable(
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
            $this->subject->checkKeyExistsInTable(
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
            $this->subject->checkKeyExistsInTable(
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

        $this->subject->checkKeyExistsInTable([
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLongitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
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
            $this->subject->isValidLatitudeDegree(
                ['value' => 'abc']
            )
        );
    }

    /**
     * @test
     */
    public function isAtMostOneValueForAuxiliaryRecordProvidedReturnsTrueForNonEmptyNewTitleAndNoExistingRecord()
    {
        $this->subject->setFakedFormValue('city', 0);

        self::assertTrue(
            $this->subject->isAtMostOneValueForAuxiliaryRecordProvided([
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
        $this->subject->setFakedFormValue('city', $this->testingFramework->createRecord('tx_realty_cities'));

        self::assertFalse(
            $this->subject->isAtMostOneValueForAuxiliaryRecordProvided([
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
        $this->subject->setFakedFormValue(
            'contact_data_source',
            \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT
        );

        self::assertTrue(
            $this->subject->isNonEmptyOrOwnerDataUsed([])
        );
    }

    /**
     * @test
     */
    public function isNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsNotOwnerAndTheValueIsNonEmpty()
    {
        $this->subject->setFakedFormValue(
            'contact_data_source',
            \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT
        );

        self::assertTrue(
            $this->subject->isNonEmptyOrOwnerDataUsed(['value' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function isNonEmptyOrOwnerDataUsedIfTheContactDataSourceIsNotOwnerAndTheValueIsEmpty()
    {
        $this->subject->setFakedFormValue(
            'contact_data_source',
            \tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT
        );

        self::assertFalse(
            $this->subject->isNonEmptyOrOwnerDataUsed([])
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);

        $result = $this->subject->modifyDataToInsert([]);
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
        $this->subject->setRealtyObjectUid(0);

        self::assertContains(
            'tstamp',
            $this->subject->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsDateForNewObject()
    {
        $this->subject->setRealtyObjectUid(0);

        self::assertContains(
            'crdate',
            $this->subject->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsPidForNewObject()
    {
        $this->subject->setRealtyObjectUid(0);

        self::assertContains(
            'pid',
            $this->subject->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsHiddenFlagForNewObject()
    {
        $this->subject->setRealtyObjectUid(0);

        self::assertContains(
            'hidden',
            $this->subject->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsObjectTypeForNewObject()
    {
        $this->subject->setRealtyObjectUid(0);

        self::assertContains(
            'object_type',
            $this->subject->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsOwnerForNewObject()
    {
        $this->subject->setRealtyObjectUid(0);

        self::assertContains(
            'owner',
            $this->subject->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsOpenImmoAnidForNewObject()
    {
        $this->subject->setRealtyObjectUid(0);

        self::assertContains(
            'openimmo_anid',
            $this->subject->modifyDataToInsert([])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsDefaultPidForNewObject()
    {
        $systemFolderPid = $this->testingFramework->createSystemFolder(1);
        $this->subject->setConfigurationValue(
            'sysFolderForFeCreatedRecords',
            $systemFolderPid
        );
        $this->subject->setRealtyObjectUid(0);
        $result = $this->subject->modifyDataToInsert([]);

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
        $this->subject->setConfigurationValue(
            'sysFolderForFeCreatedRecords',
            $systemFolderPid
        );
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert([]);

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

        $this->subject->setRealtyObjectUid(0);
        $result = $this->subject->modifyDataToInsert(['city' => $cityUid]);

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

        $this->subject->setRealtyObjectUid(0);
        $result = $this->subject->modifyDataToInsert(['city' => $cityUid]);

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
        $this->subject->setRealtyObjectUid(0);
        $result = $this->subject->modifyDataToInsert([]);

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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert([]);

        self::assertFalse(
            isset($result['owner'])
        );
    }

    /**
     * @test
     */
    public function addAdministrativeDataAddsFrontEndUsersOpenImmoAnidForNewObject()
    {
        $this->subject->setRealtyObjectUid(0);
        $result = $this->subject->modifyDataToInsert([]);

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

        $this->subject->setRealtyObjectUid(0);
        $result = $this->subject->modifyDataToInsert([]);

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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert([]);

        self::assertFalse(
            isset($result['openimmo_anid'])
        );
    }

    /**
     * @test
     */
    public function newRecordIsMarkedAsHidden()
    {
        $this->subject->setRealtyObjectUid(0);
        $result = $this->subject->modifyDataToInsert([]);

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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert([]);

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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $formData = [
            'title' => '12,3.45',
            'employer' => 'abc,de.fgh',
        ];
        $result = $this->subject->modifyDataToInsert($formData);
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert([
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert(
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert(
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert(
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert(
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $this->subject->modifyDataToInsert(
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $this->subject->modifyDataToInsert(['new_city' => 'new city']);

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

        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $this->subject->modifyDataToInsert(['new_city' => 'new city']);

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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert(
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $this->subject->modifyDataToInsert(['new_city' => '']);

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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $result = $this->subject->modifyDataToInsert(
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);

        self::assertFalse(
            array_key_exists(
                'spacer_01',
                $this->subject->modifyDataToInsert(['spacer_01' => 'blubb'])
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
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);

        self::assertTrue(
            array_key_exists(
                'title',
                $this->subject->modifyDataToInsert(['title' => 'foo'])
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
        $this->subject->writeFakedFormDataToDatabase();
        $this->subject->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

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
        $this->subject->writeFakedFormDataToDatabase();
        $this->subject->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

        self::assertArrayHasKey('alex@example.com', $this->message->getFrom());
    }

    /**
     * @test
     */
    public function sentEmailHasTheCurrentFeUserAsReplyTo()
    {
        // This will create an empty dummy record.
        $this->subject->writeFakedFormDataToDatabase();
        $this->subject->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

        self::assertArrayHasKey('mr-test@example.com', $this->message->getReplyTo());
    }

    /**
     * @test
     */
    public function sentEmailContainsTheFeUsersName()
    {
        // This will create an empty dummy record.
        $this->subject->writeFakedFormDataToDatabase();
        $this->subject->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

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
        $this->subject->writeFakedFormDataToDatabase();
        $this->subject->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

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
        $this->subject->setFakedFormValue('title', 'any title');
        $this->subject->writeFakedFormDataToDatabase();
        $this->subject->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

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
        $this->subject->setFakedFormValue('object_number', '1234');
        $this->subject->writeFakedFormDataToDatabase();
        $this->subject->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

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
        $this->subject->setFakedFormValue('object_number', '1234');
        $this->subject->setFakedFormValue('language', 'XY');
        $this->subject->writeFakedFormDataToDatabase();
        $this->subject->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

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
        $this->subject->setConfigurationValue('feEditorNotifyEmail', '');
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

        $this->message->expects(self::never())->method('send');
    }

    /**
     * @test
     */
    public function noEmailIsSentForExistingObject()
    {
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
        $this->subject->setConfigurationValue(
            'feEditorNotifyEmail',
            'recipient@example.com'
        );
        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();

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

        $this->subject->sendEmailForNewObjectAndClearFrontEndCache();
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
