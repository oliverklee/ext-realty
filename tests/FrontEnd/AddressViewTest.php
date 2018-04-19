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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_AddressViewTest extends Tx_Phpunit_TestCase
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
            ['templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'],
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
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
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
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
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
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
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
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel([]);

        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }
}
