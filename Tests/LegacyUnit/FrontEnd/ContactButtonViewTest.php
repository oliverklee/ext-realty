<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_ContactButtonViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_ContactButtonView
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

        $this->fixture = new tx_realty_pi1_ContactButtonView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $this->getFrontEndController()->cObj
        );
        $this->fixture->setConfigurationValue(
            'contactPID',
            $this->testingFramework->createFrontEndPage()
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

    ////////////////////////////////////
    // Testing the contact button view
    ////////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsNonEmptyResultForZeroShowUid()
    {
        self::assertNotEquals(
            '',
            $this->fixture->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNonEmptyResultForShowUidOfRealtyRecordProvided()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['title' => 'test title']);

        self::assertNotEquals(
            '',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsProvidedShowUidOfRealtyRecordAsLinkParameter()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['title' => 'test title']);

        self::assertContains(
            '=' . $realtyObject->getUid(),
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        self::assertNotContains(
            '###',
            $this->fixture->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForTheCurrentPageBeingTheSameAsTheConfiguredContactPid()
    {
        $this->fixture->setConfigurationValue('contactPID', $this->getFrontEndController()->id);

        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForNoContactPidConfigured()
    {
        $this->fixture->setConfigurationValue('contactPID', '');

        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => 0])
        );
    }
}
