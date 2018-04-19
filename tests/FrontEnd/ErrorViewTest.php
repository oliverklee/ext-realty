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
class tx_realty_FrontEnd_ErrorViewTest extends Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_ErrorView
     */
    private $fixture = null;
    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd(
            $this->testingFramework->createFrontEndPage()
        );

        $this->fixture = new tx_realty_pi1_ErrorView(
            ['templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'],
            $this->getFrontEndController()->cObj
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

    /**
     * @test
     */
    public function renderReturnsTranslatedMessage()
    {
        self::assertContains(
            $this->fixture->translate('message_access_denied'),
            $this->fixture->render(['message_access_denied'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsLinkedPleaseLoginMessage()
    {
        $this->fixture->setConfigurationValue(
            'loginPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            '<a href',
            $this->fixture->render(['message_please_login'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsPleaseLoginMessageWithLoginPidWithinTheLink()
    {
        $loginPid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('loginPID', $loginPid);

        self::assertContains(
            '?id=' . $loginPid,
            $this->fixture->render(['message_please_login'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsPleaseLoginMessageWithRedirectUrl()
    {
        $this->fixture->setConfigurationValue(
            'loginPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            urlencode('?id=' . $this->getFrontEndController()->id),
            $this->fixture->render(['message_please_login'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsWrappingErrorViewSubpart()
    {
        self::assertContains(
            'class="error"',
            $this->fixture->render(['message_access_denied'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkers()
    {
        self::assertNotContains(
            '###',
            $this->fixture->render(['message_access_denied'])
        );
    }
}
