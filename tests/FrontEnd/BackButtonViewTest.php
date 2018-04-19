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
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_BackButtonViewTest extends Tx_Phpunit_TestCase
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
            ['templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'],
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
            'class="button singleViewBack"',
            $this->fixture->render(['showUid' => 0])
        );
    }

    //////////////////////////////
    // Tests concerning the link
    //////////////////////////////

    /**
     * @test
     */
    public function forPreviousNextButtonsDisabledAndNoListUidViewIsJavaScriptBack()
    {
        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            false
        );

        self::assertContains(
            'history.back();',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsDisabledAndListUidViewIsJavaScriptBack()
    {
        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            false
        );
        $listUid = $this->testingFramework->createContentElement(
                $this->testingFramework->createFrontEndPage()
        );
        $this->fixture->piVars['listUid'] = $listUid;

        self::assertContains(
            'history.back();',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsDisabledAndSingleViewPartNextPreviousButtonEnabledIsJavaScriptBack()
    {
        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            false
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );
        $listUid = $this->testingFramework->createContentElement(
                $this->testingFramework->createFrontEndPage()
        );
        $this->fixture->piVars['listUid'] = $listUid;

        self::assertContains(
            'history.back();',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndNoListUidViewIsJavaScriptBack()
    {
        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );

        self::assertContains(
            'history.back();',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndSingleViewPartNotContainsNextPreviousButtonIsJavaScriptBack()
    {
        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'backButton'
        );
        $listUid = $this->testingFramework->createContentElement(
            $this->testingFramework->createFrontEndPage()
        );
        $this->fixture->piVars['listUid'] = $listUid;

        self::assertContains(
            'history.back();',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function forPreviousNextButtonsEnabledAndListUidViewNotIsJavaScriptBack()
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

        self::assertNotContains(
            'history.back();',
            $this->fixture->render()
        );
    }

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

        self::assertContains(
            'objectNumber]=foo',
            $this->fixture->render()
        );
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
