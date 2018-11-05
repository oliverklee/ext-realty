<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_AddressViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_AddressView
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
        $this->fixture = new tx_realty_pi1_AddressView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $frontEndController->cObj
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /////////////////////////////
    // Testing the address view
    /////////////////////////////

    /**
     * @test
     */
    public function renderReturnsNonEmptyResultForShowUidOfExistingRecordWithZip()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['zip' => '12345']);

        self::assertNotEquals(
            '',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['zip' => '12345']);

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

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
    public function renderReturnsTheRealtyObjectsAddressForValidRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['zip' => '12345']);

        self::assertContains(
            '12345',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForEmptyAddressDataOfValidRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel([]);

        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }
}
