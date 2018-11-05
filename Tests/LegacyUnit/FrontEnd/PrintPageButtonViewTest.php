<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_PrintPageButtonViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_PrintPageButtonView
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
        $this->fixture = new tx_realty_pi1_PrintPageButtonView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $frontEndController->cObj
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    ////////////////////////////////////
    // Testing the basic functionality
    ////////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsButtonPrint()
    {
        self::assertContains(
            'class="js-realty-print button printPage"',
            $this->fixture->render(['showUid' => 0])
        );
    }
}
