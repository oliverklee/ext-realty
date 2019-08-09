<?php

use OliverKlee\PhpUnit\TestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_FavoritesListViewTest extends TestCase
{
    /**
     * @var tx_realty_pi1_FavoritesListView
     */
    private $subject = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int
     */
    private $systemFolderPid = 0;

    /**
     * @var int UID of the first dummy realty object
     */
    private $firstRealtyUid = 0;

    /**
     * @var string object number for the first dummy realty object
     */
    private static $firstObjectNumber = '1';

    /**
     * @var string title for the first dummy realty object
     */
    private static $firstObjectTitle = 'a title';

    /**
     * @var int a city to relate realty objects to
     */
    private $cityUid = 0;

    /**
     * @var int PID of the favorites page
     */
    private $favoritesPid = 0;

    /**
     * @var Tx_Oelib_FakeSession a fake session
     */
    private $session;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        $this->createDummyPages();
        $this->createDummyObjects();

        $this->session = new Tx_Oelib_FakeSession();
        // Ensures an empty favorites list.
        $this->session->setAsString(
            tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY,
            ''
        );
        Tx_Oelib_Session::setInstance(
            Tx_Oelib_Session::TYPE_TEMPORARY,
            $this->session
        );

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->subject = new tx_realty_pi1_FavoritesListView(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'favoritesPID' => $this->favoritesPid,
                'pages' => $this->systemFolderPid,
                'showGoogleMaps' => 0,
            ],
            $frontEndController->cObj,
            true
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    //////////////////////
    // Utility functions
    //////////////////////

    /**
     * Creates dummy FE pages for favorites list and a system folder for the
     * storage of realty objects.
     *
     * @return void
     */
    private function createDummyPages()
    {
        $this->favoritesPid = $this->testingFramework->createFrontEndPage();
        $this->systemFolderPid = $this->testingFramework->createSystemFolder(1);
    }

    /**
     * Creates dummy realty objects in the DB.
     *
     * @return void
     */
    private function createDummyObjects()
    {
        $this->cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Foo City']
        );
        $this->firstRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::$firstObjectTitle,
                'object_number' => self::$firstObjectNumber,
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
            ]
        );
    }

    /////////////////////////////////////////
    // Tests concerning the basic functions
    /////////////////////////////////////////

    /**
     * @test
     */
    public function renderForEmptyRenderedObjectHasNoUnreplacedMarkers()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->session->setAsInteger(
            tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY,
            $this->testingFramework->createRecord(
                'tx_realty_objects',
                [
                    'city' => $this->cityUid,
                    'pid' => $systemFolder,
                ]
            )
        );

        $this->subject->setConfigurationValue('pages', $systemFolder);

        self::assertNotContains(
            '###',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderForNoFavoritesShowsEmptyResultMessage()
    {
        self::assertContains(
            $this->subject->translate('message_noResultsFound_favorites'),
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderForOneFavoriteShowsFavoriteTitle()
    {
        $this->subject->addToFavorites([$this->firstRealtyUid]);

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderForTwoFavoritesShowsBothFavoritesTitles()
    {
        $secondRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'another object',
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
            ]
        );

        $this->subject->addToFavorites(
            [$this->firstRealtyUid, $secondRealtyUid]
        );
        $output = $this->subject->render();

        self::assertContains(
            self::$firstObjectTitle,
            $output
        );
        self::assertContains(
            'another object',
            $output
        );
    }

    /**
     * @test
     */
    public function renderForOneFavoriteShowsDeleteFavoriteLink()
    {
        $this->subject->addToFavorites([$this->firstRealtyUid]);
        $this->subject->render();

        $this->subject->isSubpartVisible('remove_from_favorites_button');
    }

    /**
     * @test
     */
    public function favoriteCanBeDeleted()
    {
        $this->subject->addToFavorites([$this->firstRealtyUid]);

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(
                [
                    'favorites' => [$this->firstRealtyUid],
                    'remove' => 1,
                ]
            )
        );
    }

    /**
     * @test
     */
    public function twoFavoritesCanBeDeletedAtOnce()
    {
        $secondRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'another object',
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
            ]
        );
        $this->subject->addToFavorites(
            [
                $this->firstRealtyUid,
                $secondRealtyUid,
            ]
        );

        $output = $this->subject->render(
            [
                'favorites' => [$this->firstRealtyUid, $secondRealtyUid],
                'remove' => 1,
            ]
        );

        self::assertContains(
            $this->subject->translate('message_noResultsFound_favorites'),
            $output
        );
    }

    /**
     * @test
     */
    public function renderForEnabledContactLinkAndSetContactPidShowsContactLink()
    {
        $this->subject->setConfigurationValue('showContactPageLink', 1);
        $contactPid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('contactPID', $contactPid);
        $result = $this->subject->render();

        self::assertContains(
            '?id=' . $contactPid,
            $result
        );
        self::assertContains(
            'class="button listViewContact"',
            $result
        );
    }

    /**
     * @test
     */
    public function renderForDisabledContactLinkNotShowsContactLink()
    {
        $this->subject->setConfigurationValue('showContactPageLink', 0);
        $this->subject->setConfigurationValue(
            'contactPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertNotContains(
            'class="button listViewContact"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderForEnabledContactLinkButNotSetContactPidNotShowsContactLink()
    {
        $this->subject->setConfigurationValue('showContactPageLink', 1);
        $this->subject->setConfigurationValue('contactPID', '');

        self::assertNotContains(
            'class="button listViewContact"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderForContactFormPidSameAsFavoritePidNotShowsContactLink()
    {
        $this->subject->setConfigurationValue('showContactPageLink', 1);
        $this->subject->setConfigurationValue('contactPID', $this->favoritesPid);

        self::assertNotContains(
            'class="button listViewContact"',
            $this->subject->render()
        );
    }

    ////////////////////////////////////
    // Tests concerning addToFavorites
    ////////////////////////////////////

    /**
     * @test
     */
    public function addToFavoritesWithNewItemCanAddItemToEmptySession()
    {
        $this->subject->addToFavorites([$this->firstRealtyUid]);

        self::assertEquals(
            [$this->firstRealtyUid],
            $this->session->getAsIntegerArray(
                tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY
            )
        );
    }

    /**
     * @test
     */
    public function addToFavoritesWithTwoNewItemsCanAddBothItemsToEmptySession()
    {
        $secondRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'another object',
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
            ]
        );

        $this->subject->addToFavorites(
            [$this->firstRealtyUid, $secondRealtyUid]
        );

        self::assertEquals(
            [$this->firstRealtyUid, $secondRealtyUid],
            $this->session->getAsIntegerArray(
                tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY
            )
        );
    }

    /**
     * @test
     */
    public function addToFavoritesWithNewItemCanAddItemToNonEmptySession()
    {
        $this->session->setAsInteger(
            tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY,
            $this->firstRealtyUid
        );
        $secondRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'another object',
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
            ]
        );

        $this->subject->addToFavorites([$secondRealtyUid]);

        self::assertEquals(
            [$this->firstRealtyUid, $secondRealtyUid],
            $this->session->getAsIntegerArray(
                tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY
            )
        );
    }

    /**
     * @test
     */
    public function addToFavoritesWithExistingItemDoesNotAddToSession()
    {
        $this->session->setAsInteger(
            tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY,
            $this->firstRealtyUid
        );

        $this->subject->addToFavorites([$this->firstRealtyUid]);

        self::assertEquals(
            [$this->firstRealtyUid],
            $this->session->getAsIntegerArray(
                tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY
            )
        );
    }

    /////////////////////////////////////////////////////
    // Tests for writeSummaryStringOfFavoritesToSession
    /////////////////////////////////////////////////////

    /**
     * @test
     */
    public function writeSummaryStringOfFavoritesToSessionForOneItemWritesItemsNumberAndTitleToSession()
    {
        $this->session->setAsInteger(
            tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY,
            $this->firstRealtyUid
        );
        $this->subject->writeSummaryStringOfFavoritesToSession();

        self::assertContains(
            '* ' . self::$firstObjectNumber . ' ' . self::$firstObjectTitle,
            $this->session->getAsString('summaryStringOfFavorites')
        );
    }

    /**
     * @test
     */
    public function writeSummaryStringOfFavoritesToSessionForLoggedInFrontEndUserWritesDataToTemporarySession()
    {
        Tx_Oelib_FrontEndLoginManager::getInstance()
            ->logInUser(new tx_realty_Model_FrontEndUser());

        $this->session->setAsInteger(
            tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY,
            $this->firstRealtyUid
        );
        $this->subject->writeSummaryStringOfFavoritesToSession();

        self::assertContains(
            '* ' . self::$firstObjectNumber . ' ' . self::$firstObjectTitle,
            Tx_Oelib_Session::getInstance(Tx_Oelib_Session::TYPE_TEMPORARY)
                ->getAsString('summaryStringOfFavorites')
        );
    }

    ////////////////////////////////////////////////////
    // Tests concerning createSummaryStringOfFavorites
    ////////////////////////////////////////////////////

    /**
     * @test
     */
    public function createSummaryStringOfFavoritesForNoFavoritesSetReturnsEmptyString()
    {
        self::assertEquals(
            '',
            $this->subject->createSummaryStringOfFavorites()
        );
    }

    /**
     * @test
     */
    public function createSummaryStringOfFavoritesForOneStoredFavoriteReturnsTitleOfFavorite()
    {
        $this->subject->addToFavorites([$this->firstRealtyUid]);

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->createSummaryStringOfFavorites()
        );
    }

    /**
     * @test
     */
    public function createSummaryStringOfFavoritesForOneStoredFavoriteReturnsObjectNumberOfFavorite()
    {
        $this->subject->addToFavorites([$this->firstRealtyUid]);

        self::assertContains(
            self::$firstObjectNumber,
            $this->subject->createSummaryStringOfFavorites()
        );
    }

    /**
     * @test
     */
    public function createSummaryStringOfFavoritesForTwoStoredFavoritesReturnsBothFavorites()
    {
        $secondRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'another object',
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
            ]
        );
        $this->subject->addToFavorites([$this->firstRealtyUid, $secondRealtyUid]);

        $result = $this->subject->createSummaryStringOfFavorites();

        self::assertContains(
            self::$firstObjectTitle,
            $result
        );
        self::assertContains(
            'another object',
            $result
        );
    }

    /////////////////////////////////////////////////////////
    // Tests concerning the favorites fields in the session
    /////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function favoriteFieldsInSessionForTitleDoesNotCrash()
    {
        $this->subject->addToFavorites([$this->firstRealtyUid]);
        $this->subject->setConfigurationValue('favoriteFieldsInSession', 'title');

        $this->subject->render();
    }
}
