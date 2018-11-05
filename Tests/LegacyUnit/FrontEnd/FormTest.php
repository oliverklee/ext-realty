<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_FormTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_frontEndForm object to be tested
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int dummy FE user UID
     */
    private $feUserUid;

    /**
     * @var int UID of the dummy object
     */
    private $dummyObjectUid = 0;

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

        $this->createDummyRecords();

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->fixture = new tx_realty_frontEndEditor(
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

    ///////////////////////
    // Utility functions.
    ///////////////////////

    /**
     * Creates dummy records in the DB.
     *
     * @return void
     */
    private function createDummyRecords()
    {
        $this->feUserUid = $this->testingFramework->createFrontEndUser();
        $this->dummyObjectUid = $this->testingFramework->createRecord(
            'tx_realty_objects'
        );
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
        $this->fixture->setConfigurationValue('feEditorRedirectPid', $fePageUid);

        self::assertContains(
            '?id=' . $fePageUid,
            $this->fixture->getRedirectUrl()
        );
    }

    /**
     * @test
     */
    public function getRedirectUrlReturnsUrlWithoutRedirectPidForMisconfiguredRedirectPid()
    {
        $nonExistingFePageUid = $this->testingFramework->createFrontEndPage(
            0,
            ['deleted' => 1]
        );
        $this->fixture->setConfigurationValue(
            'feEditorRedirectPid',
            $nonExistingFePageUid
        );

        self::assertNotContains(
            '?id=' . $nonExistingFePageUid,
            $this->fixture->getRedirectUrl()
        );
    }

    /**
     * @test
     */
    public function getRedirectUrlReturnsUrlWithoutRedirectPidForNonConfiguredRedirectPid()
    {
        $this->fixture->setConfigurationValue('feEditorRedirectPid', '0');

        self::assertNotContains(
            '?id=0',
            $this->fixture->getRedirectUrl()
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
