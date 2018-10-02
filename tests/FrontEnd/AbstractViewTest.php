<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_AbstractViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_tests_fixtures_testingFrontEndView the fixture to test
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
        $this->fixture = new tx_realty_tests_fixtures_testingFrontEndView([], $frontEndController->cObj);
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /**
     * @test
     */
    public function renderCanReturnAViewsContent()
    {
        self::assertEquals(
            'Hi, I am the testingFrontEndView!',
            $this->fixture->render()
        );
    }
}
