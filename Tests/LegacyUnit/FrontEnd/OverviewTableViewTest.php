<?php

use OliverKlee\PhpUnit\TestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_OverviewTableViewTest extends TestCase
{
    /**
     * @var tx_realty_pi1_OverviewTableView
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

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->subject = new tx_realty_pi1_OverviewTableView(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'fieldsInSingleViewTable',
                '',
            ],
            $frontEndController->cObj
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    ////////////////////////////////////
    // Testing the overview table view
    ////////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsNonEmptyResultForShowUidOfExistingRecord()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['object_number' => '12345']);

        self::assertNotEquals(
            '',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['object_number' => '12345']);

        $result = $this->subject->render(['showUid' => $realtyObject->getUid()]);

        self::assertNotEquals(
            '',
            $result
        );
        self::assertNotContains(
            '###',
            $result
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheRealtyObjectsObjectNumberForValidRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['object_number' => '12345']);

        self::assertContains(
            '12345',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheRealtyObjectsTitleHtmlspecialcharedForValidRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['object_number' => '12345</br>']);

        self::assertContains(
            htmlspecialchars('12345</br>'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForValidRealtyObjectWithoutData()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel([]);

        self::assertEquals(
            '',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    ///////////////////////////////////////////////
    // Testing the contents of the overview table
    ///////////////////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsHasAirConditioningRowForTrue()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['has_air_conditioning' => 1]);

        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'has_air_conditioning'
        );

        self::assertContains(
            $this->subject->translate('label_has_air_conditioning'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsHasAirConditioningRowForFalse()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['has_air_conditioning' => 0]);

        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'has_air_conditioning'
        );

        self::assertNotContains(
            $this->subject->translate('label_has_air_conditioning'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsHasPoolRowForTrue()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['has_pool' => 1]);

        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'has_pool'
        );

        self::assertContains(
            $this->subject->translate('label_has_pool'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsHasPoolRowForFalse()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['has_pool' => 0]);

        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'has_pool'
        );

        self::assertNotContains(
            $this->subject->translate('label_has_pool'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsHasCommunityPoolRowForTrue()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['has_community_pool' => 1]);

        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'has_community_pool'
        );

        self::assertContains(
            $this->subject->translate('label_has_community_pool'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsHasCommunityPoolRowForFalse()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['has_community_pool' => 0]);

        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'has_community_pool'
        );

        self::assertNotContains(
            $this->subject->translate('label_has_community_pool'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheLabelForStateIfAValidStateIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['state' => 8]);

        $this->subject->setConfigurationValue('fieldsInSingleViewTable', 'state');

        self::assertContains(
            $this->subject->translate('label_state'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheStateIfAValidStateIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['state' => 8]);

        $this->subject->setConfigurationValue('fieldsInSingleViewTable', 'state');

        self::assertContains(
            $this->subject->translate('label_state_8'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsTheLabelForStateIfNoStateIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['state' => 0]);

        $this->subject->setConfigurationValue('fieldsInSingleViewTable', 'state');

        self::assertNotContains(
            $this->subject->translate('label_state'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsTheLabelForStateIfTheStateIsInvalid()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['state' => 10000000]);

        $this->subject->setConfigurationValue('fieldsInSingleViewTable', 'state');

        self::assertNotContains(
            $this->subject->translate('label_state'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheLabelForHeatingTypeIfOneValidHeatingTypeIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['heating_type' => '1']);

        $this->subject->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

        self::assertContains(
            $this->subject->translate('label_heating_type'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheHeatingTypeIfOneValidHeatingTypeIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['heating_type' => '1']);

        $this->subject->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

        self::assertContains(
            $this->subject->translate('label_heating_type_1'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsAHeatingTypeListIfMultipleValidHeatingTypesAreSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['heating_type' => '1,3,4']);

        $this->subject->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

        self::assertContains(
            $this->subject->translate('label_heating_type_1') . ', ' .
            $this->subject->translate('label_heating_type_3') . ', ' .
            $this->subject->translate('label_heating_type_4'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsTheHeatingTypeLabelIfOnlyAnInvalidHeatingTypeIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['heating_type' => '100']);

        $this->subject->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

        self::assertNotContains(
            $this->subject->translate('label_heating_type'),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    ///////////////////////////////////
    // Tests concerning getFieldNames
    ///////////////////////////////////

    /**
     * @test
     */
    public function getFieldNamesByDefaultReturnsAllFieldsNamesFromConfiguration()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel([]);
        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,rent_with_heating_costs,extra_charges,deposit,' .
            'provision,buying_price,hoa_fee,year_rent,' .
            'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        self::assertEquals(
            [
                'heating_type',
                'rent_excluding_bills',
                'rent_with_heating_costs',
                'extra_charges',
                'deposit',
                'provision',
                'buying_price',
                'hoa_fee',
                'year_rent',
                'rent_per_square_meter',
                'garage_rent',
                'garage_price',
                'pets',
            ],
            $this->subject->getFieldNames($realtyObject->getUid())
        );
    }

    /**
     * @test
     */
    public function getFieldNamesWithPriceOnlyIfAvailableForVacantObjectReturnsAllFieldsNamesFromConfiguration()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_VACANT]
            );
        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,rent_with_heating_costs,extra_charges,deposit,' .
            'provision,buying_price,hoa_fee,year_rent,' .
            'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertEquals(
            [
                'heating_type',
                'rent_excluding_bills',
                'rent_with_heating_costs',
                'extra_charges',
                'deposit',
                'provision',
                'buying_price',
                'hoa_fee',
                'year_rent',
                'rent_per_square_meter',
                'garage_rent',
                'garage_price',
                'pets',
            ],
            $this->subject->getFieldNames($realtyObject->getUid())
        );
    }

    /**
     * @test
     */
    public function getFieldNamesWithPriceOnlyIfAvailableForReservedObjectReturnsAllFieldsNamesFromConfiguration()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_RESERVED]
            );
        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,rent_with_heating_costs,extra_charges,deposit,' .
            'provision,buying_price,hoa_fee,year_rent,' .
            'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertEquals(
            [
                'heating_type',
                'rent_excluding_bills',
                'rent_with_heating_costs',
                'extra_charges',
                'deposit',
                'provision',
                'buying_price',
                'hoa_fee',
                'year_rent',
                'rent_per_square_meter',
                'garage_rent',
                'garage_price',
                'pets',
            ],
            $this->subject->getFieldNames($realtyObject->getUid())
        );
    }

    /**
     * @test
     */
    public function getFieldNamesWithPriceOnlyIfAvailableForSoldObjectDropsPriceRelatedFields()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_SOLD]
            );
        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,rent_with_heating_costs,extra_charges,deposit,' .
            'provision,buying_price,hoa_fee,year_rent,' .
            'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertEquals(
            [
                'heating_type',
                'pets',
            ],
            $this->subject->getFieldNames($realtyObject->getUid())
        );
    }

    /**
     * @test
     */
    public function getFieldNamesWithPriceOnlyIfAvailableForRentedObjectDropsPriceRelatedFields()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_RENTED]
            );
        $this->subject->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,rent_with_heating_costs,extra_charges,deposit,' .
            'provision,buying_price,hoa_fee,year_rent,' .
            'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertEquals(
            [
                'heating_type',
                'pets',
            ],
            $this->subject->getFieldNames($realtyObject->getUid())
        );
    }
}
