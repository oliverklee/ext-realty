<?php

use OliverKlee\PhpUnit\TestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_ErrorViewTest extends TestCase
{
    /**
     * @var tx_realty_pi1_ErrorView
     */
    private $subject = null;

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

        $this->subject = new tx_realty_pi1_ErrorView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
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
            $this->subject->translate('message_access_denied'),
            $this->subject->render(['message_access_denied'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsLinkedPleaseLoginMessage()
    {
        $this->subject->setConfigurationValue(
            'loginPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            '<a href',
            $this->subject->render(['message_please_login'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsPleaseLoginMessageWithLoginPidWithinTheLink()
    {
        $loginPid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('loginPID', $loginPid);

        self::assertContains(
            '?id=' . $loginPid,
            $this->subject->render(['message_please_login'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsPleaseLoginMessageWithRedirectUrl()
    {
        $this->subject->setConfigurationValue(
            'loginPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            urlencode('?id=' . $this->getFrontEndController()->id),
            $this->subject->render(['message_please_login'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsWrappingErrorViewSubpart()
    {
        self::assertContains(
            'class="error"',
            $this->subject->render(['message_access_denied'])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkers()
    {
        self::assertNotContains(
            '###',
            $this->subject->render(['message_access_denied'])
        );
    }
}
