<?php

use OliverKlee\PhpUnit\TestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_ContactButtonViewTest extends TestCase
{
    /**
     * @var tx_realty_pi1_ContactButtonView
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

        $this->subject = new tx_realty_pi1_ContactButtonView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $this->getFrontEndController()->cObj
        );
        $this->subject->setConfigurationValue(
            'contactPID',
            $this->testingFramework->createFrontEndPage()
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

    ////////////////////////////////////
    // Testing the contact button view
    ////////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsNonEmptyResultForZeroShowUid()
    {
        self::assertNotEquals(
            '',
            $this->subject->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNonEmptyResultForShowUidOfRealtyRecordProvided()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['title' => 'test title']);

        self::assertNotEquals(
            '',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsProvidedShowUidOfRealtyRecordAsLinkParameter()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['title' => 'test title']);

        self::assertContains(
            '=' . $realtyObject->getUid(),
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        self::assertNotContains(
            '###',
            $this->subject->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForTheCurrentPageBeingTheSameAsTheConfiguredContactPid()
    {
        $this->subject->setConfigurationValue('contactPID', $this->getFrontEndController()->id);

        self::assertEquals(
            '',
            $this->subject->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForNoContactPidConfigured()
    {
        $this->subject->setConfigurationValue('contactPID', '');

        self::assertEquals(
            '',
            $this->subject->render(['showUid' => 0])
        );
    }
}
