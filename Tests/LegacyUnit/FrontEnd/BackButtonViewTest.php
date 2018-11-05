<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_BackButtonViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_BackButtonView
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
        $this->fixture = new tx_realty_pi1_BackButtonView(
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
    public function renderReturnsButtonBack()
    {
        self::assertContains(
            'class="js-realty-back button singleViewBack"',
            $this->fixture->render(['showUid' => 0])
        );
    }

    //////////////////////////////
    // Tests concerning the link
    //////////////////////////////

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndListUidViewContainsLinkToListViewPage()
    {
        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );
        $listViewPageUid = $this->testingFramework->createFrontEndPage();
        $listUid = $this->testingFramework->createContentElement(
            $listViewPageUid
        );
        $this->fixture->piVars['listUid'] = $listUid;

        self::assertContains(
            '?id=' . $listViewPageUid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndListViewLimitationSetAddsListViewLimitationDecodedToPiVar()
    {
        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );
        $listViewPageUid = $this->testingFramework->createFrontEndPage();
        $listUid = $this->testingFramework->createContentElement(
            $listViewPageUid
        );
        $listViewLimitation = json_encode(['objectNumber' => 'foo']);
        $this->fixture->piVars['listUid'] = $listUid;
        $this->fixture->piVars['listViewLimitation'] = $listViewLimitation;

        self::assertContains('=foo', $this->fixture->render());
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndFooSetAsPiVarNotAddsFooToBackLink()
    {
        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );
        $listViewPageUid = $this->testingFramework->createFrontEndPage();
        $listUid = $this->testingFramework->createContentElement(
            $listViewPageUid
        );
        $this->fixture->piVars['listUid'] = $listUid;
        $this->fixture->piVars['foo'] = 'bar';

        self::assertNotContains(
            'foo',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndListUidSetToStringDoesNotAddListUidStringToLink()
    {
        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );
        $this->fixture->piVars['listUid'] = 'fooo';

        self::assertNotContains(
            'fooo',
            $this->fixture->render()
        );
    }
}
