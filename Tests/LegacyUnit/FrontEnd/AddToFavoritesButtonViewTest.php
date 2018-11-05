<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_AddToFavoritesButtonViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_AddToFavoritesButtonView
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
        $this->fixture = new tx_realty_pi1_AddToFavoritesButtonView(
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
    public function renderReturnsNonEmptyResultForZeroShowUidAndNoFavoritesPidConfigured()
    {
        self::assertNotEquals(
            '',
            $this->fixture->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function renderReturnsButtonAddToFavorites()
    {
        self::assertContains(
            'class="js-realty-favorites button singleViewAddToFavorites"',
            $this->fixture->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function renderReturnsProvidedShowUidOfRealtyRecordAsFormValue()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getNewGhost();

        self::assertContains(
            'value="' . $realtyObject->getUid() . '"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsConfiguredFavoritesPidAsLinkTarget()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('favoritesPID', $pageUid);

        self::assertContains(
            '?id=' . $pageUid,
            $this->fixture->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        self::assertNotContains(
            '###',
            $this->fixture->render(['showUid' => 0])
        );
    }
}
