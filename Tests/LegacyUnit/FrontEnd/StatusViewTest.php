<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_StatusViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_StatusView
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

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->fixture = new tx_realty_pi1_StatusView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $frontEndController->cObj
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    //////////////////////////////////
    // Tests for the basic functions
    //////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkers()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel([]);

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertNotContains(
            '###',
            $result
        );
    }

    ////////////////////////////////
    // Tests for the render result
    ////////////////////////////////

    /**
     * @test
     */
    public function renderForVacantStatusContainsVacantLabel()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_VACANT]
            );

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertContains(
            $this->fixture->translate('label_status_0'),
            $result
        );
    }

    /**
     * @test
     */
    public function renderForRentedStatusContainsRentedLabel()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_RENTED]
            );

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertContains(
            $this->fixture->translate('label_status_3'),
            $result
        );
    }

    /**
     * @test
     */
    public function renderForVacantStatusContainsVacantClass()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_VACANT]
            );

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertContains(
            'class="status_vacant"',
            $result
        );
    }

    /**
     * @test
     */
    public function renderForReservedStatusContainsReservedClass()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_RESERVED]
            );

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertContains(
            'class="status_reserved"',
            $result
        );
    }

    /**
     * @test
     */
    public function renderForSoldStatusContainsSoldClass()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_SOLD]
            );

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertContains(
            'class="status_sold"',
            $result
        );
    }

    /**
     * @test
     */
    public function renderForRentedStatusContainsRentedClass()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(
                ['status' => tx_realty_Model_RealtyObject::STATUS_RENTED]
            );

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertContains(
            'class="status_rented"',
            $result
        );
    }
}
