<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_ErrorViewTest extends \Tx_Phpunit_TestCase
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
