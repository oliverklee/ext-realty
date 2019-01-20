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
        $this->subject = new tx_realty_pi1_BackButtonView(
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
            $this->subject->render(['showUid' => 0])
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
        $this->subject->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->subject->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );
        $listViewPageUid = $this->testingFramework->createFrontEndPage();
        $listUid = $this->testingFramework->createContentElement(
            $listViewPageUid
        );
        $this->subject->piVars['listUid'] = $listUid;

        self::assertContains(
            '?id=' . $listViewPageUid,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndListViewLimitationSetAddsListViewLimitationDecodedToPiVar()
    {
        $this->subject->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->subject->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );
        $listViewPageUid = $this->testingFramework->createFrontEndPage();
        $listUid = $this->testingFramework->createContentElement(
            $listViewPageUid
        );
        $listViewLimitation = json_encode(['objectNumber' => 'foo']);
        $this->subject->piVars['listUid'] = $listUid;
        $this->subject->piVars['listViewLimitation'] = $listViewLimitation;

        self::assertContains('=foo', $this->subject->render());
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndFooSetAsPiVarNotAddsFooToBackLink()
    {
        $this->subject->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->subject->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );
        $listViewPageUid = $this->testingFramework->createFrontEndPage();
        $listUid = $this->testingFramework->createContentElement(
            $listViewPageUid
        );
        $this->subject->piVars['listUid'] = $listUid;
        $this->subject->piVars['foo'] = 'bar';

        self::assertNotContains(
            'foo',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndListUidSetToStringDoesNotAddListUidStringToLink()
    {
        $this->subject->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->subject->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );
        $this->subject->piVars['listUid'] = 'fooo';

        self::assertNotContains(
            'fooo',
            $this->subject->render()
        );
    }
}
