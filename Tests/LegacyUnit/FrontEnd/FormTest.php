<?php

use OliverKlee\PhpUnit\TestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_FormTest extends TestCase
{
    /**
     * @var tx_realty_frontEndForm object to be tested
     */
    private $subject = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        $configuration = new Tx_Oelib_Configuration();
        $configuration->setData(
            [
                'feEditorTemplateFile'
                => 'EXT:realty/Resources/Private/Templates/FrontEnd/Editor.html',
            ]
        );
        Tx_Oelib_ConfigurationRegistry::getInstance()->set(
            'plugin.tx_realty_pi1',
            $configuration
        );

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->subject = new tx_realty_frontEndEditor(
            [],
            $frontEndController->cObj,
            0,
            '',
            true
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    //////////////////////////////////////
    // Functions to be used by the form.
    //////////////////////////////////////
    // * getRedirectUrl().
    ////////////////////////

    /**
     * @test
     */
    public function getRedirectUrlReturnsUrlWithRedirectPidForConfiguredRedirectPid()
    {
        $fePageUid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('feEditorRedirectPid', $fePageUid);

        self::assertContains(
            '?id=' . $fePageUid,
            $this->subject->getRedirectUrl()
        );
    }

    /**
     * @test
     */
    public function getRedirectUrlReturnsUrlWithoutRedirectPidForMisconfiguredRedirectPid()
    {
        $nonExistingFePageUid = 999999;
        $this->subject->setConfigurationValue(
            'feEditorRedirectPid',
            $nonExistingFePageUid
        );

        self::assertNotContains(
            '?id=' . $nonExistingFePageUid,
            $this->subject->getRedirectUrl()
        );
    }

    /**
     * @test
     */
    public function getRedirectUrlReturnsUrlWithoutRedirectPidForNonConfiguredRedirectPid()
    {
        $this->subject->setConfigurationValue('feEditorRedirectPid', '0');

        self::assertNotContains(
            '?id=0',
            $this->subject->getRedirectUrl()
        );
    }

    ///////////////////////////////////////
    // Tests concerning the HTML template
    ///////////////////////////////////////

    /**
     * @test
     */
    public function getTemplatePathReturnsAbsolutePathFromTheConfiguration()
    {
        self::assertContains(
            'Resources/Private/Templates/FrontEnd/Editor.html',
            \tx_realty_frontEndForm::getTemplatePath()
        );
    }
}
