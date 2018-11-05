<?php

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_AbstractListViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var int static_info_tables UID of Germany
     */
    const DE = 54;

    /**
     * @var string
     */
    const TX_REALTY_EXTERNAL_SINGLE_PAGE = 'www.oliverklee.de/';

    /**
     * @var tx_realty_tests_fixtures_TestingListView
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

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
     * @var int second dummy realty object
     */
    private $secondRealtyUid = 0;

    /**
     * @var string object number for the second dummy realty object
     */
    private static $secondObjectNumber = '2';

    /**
     * @var string title for the second dummy realty object
     */
    private static $secondObjectTitle = 'another title';

    /**
     * @var int first dummy city UID
     */
    private $firstCityUid = 0;

    /**
     * @var string title for the first dummy city
     */
    private static $firstCityTitle = 'Bonn';

    /**
     * @var int second dummy city UID
     */
    private $secondCityUid = 0;

    /**
     * @var string title for the second dummy city
     */
    private static $secondCityTitle = 'bar city';

    /**
     * @var int PID of the single view page
     */
    private $singlePid = 0;

    /**
     * @var int PID of the alternate single view page
     */
    private $otherSinglePid = 0;

    /**
     * @var int PID of the favorites page
     */
    private $favoritesPid = 0;

    /**
     * @var int login PID
     */
    private $loginPid = 0;

    /**
     * @var int system folder PID
     */
    private $systemFolderPid = 0;

    /**
     * @var int sub-system folder PID
     */
    private $subSystemFolderPid = 0;

    /**
     * @var ContentObjectRenderer|PHPUnit_Framework_MockObject_MockObject
     */
    private $contentObject = null;

    /**
     * @var array[]
     */
    private $imageConfigurations = [];

    protected function setUp()
    {
        Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();
        $this->createContentMock();

        $this->createDummyPages();
        $this->createDummyObjects();

        $this->fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'singlePID' => $this->singlePid,
                'favoritesPID' => $this->favoritesPid,
                'pages' => $this->systemFolderPid,
                'showGoogleMaps' => 0,
                'defaultCountryUID' => self::DE,
                'currencyUnit' => 'EUR',
                'priceOnlyIfAvailable' => false,
            ],
            $this->contentObject,
            true
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /*
     * Utility functions.
     */

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
     * Creates dummy realty objects in the DB.
     *
     * @return void
     */
    private function createDummyObjects()
    {
        $this->createDummyCities();
        $this->firstRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::$firstObjectTitle,
                'object_number' => self::$firstObjectNumber,
                'pid' => $this->systemFolderPid,
                'city' => $this->firstCityUid,
                'teaser' => '',
                'has_air_conditioning' => '0',
                'has_pool' => '0',
                'has_community_pool' => '0',
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
            ]
        );
        $this->secondRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::$secondObjectTitle,
                'object_number' => self::$secondObjectNumber,
                'pid' => $this->systemFolderPid,
                'city' => $this->secondCityUid,
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
            ]
        );
    }

    /**
     * Creates dummy city records in the DB.
     *
     * @return void
     */
    private function createDummyCities()
    {
        $this->firstCityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => self::$firstCityTitle]
        );
        $this->secondCityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => self::$secondCityTitle]
        );
    }

    /**
     * Creates dummy FE pages (like login and single view).
     *
     * @return void
     */
    private function createDummyPages()
    {
        $this->loginPid = $this->testingFramework->createFrontEndPage();
        $this->singlePid = $this->testingFramework->createFrontEndPage();
        $this->otherSinglePid = $this->testingFramework->createFrontEndPage();
        $this->favoritesPid = $this->testingFramework->createFrontEndPage();
        $this->systemFolderPid = $this->testingFramework->createSystemFolder(1);
        $this->subSystemFolderPid = $this->testingFramework->createSystemFolder($this->systemFolderPid);
    }

    /**
     * Denies access to the details page by requiring logon to display that page
     * and then logging out any logged-in FE users.
     *
     * @return void
     */
    private function denyAccess()
    {
        $this->fixture->setConfigurationValue('requireLoginForSingleViewPage', true);
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser(null);
    }

    /**
     * Allows access to the details page by not requiring logon to display that page.
     *
     * @return void
     */
    private function allowAccess()
    {
        $this->fixture->setConfigurationValue('requireLoginForSingleViewPage', false);
    }

    /**
     * Creates a mock content object that can create URLs in the following form:
     *
     * index.php?id=42
     *
     * The page ID isn't checked for existence. So any page ID can be used.
     *
     * @return void
     */
    private function createContentMock()
    {
        $this->contentObject = $this->getMock(ContentObjectRenderer::class, ['typoLink_URL', 'cObjGetSingle']);
        $this->contentObject->method('cObjGetSingle')->will(self::returnCallback([$this, 'imageCallback']));
        $this->contentObject->method('typoLink_URL')->will(self::returnCallback([$this, 'getTypoLinkUrl']));
    }

    /**
     * Callback function for creating mock typolink URLs.
     *
     * @param string[] $linkProperties
     *        TypoScript properties for "typolink", must at least contain the
     *        key 'parameter'
     *
     * @return string faked URL, will not be empty
     */
    public function getTypoLinkUrl(array $linkProperties)
    {
        $pageId = $linkProperties['parameter'];
        if (isset($linkProperties['additionalParams'])) {
            $additionalParameters = $linkProperties['additionalParams'];
        } else {
            $additionalParameters = '';
        }

        return 'index.php?id=' . $pageId . $additionalParameters;
    }

    /**
     * Callback function for creating mock IMAGEs in TYPO3 6.2-7.6.
     *
     * @param string[] $imageConfiguration
     *
     * @return string faked image, will not be empty
     */
    public function oldImageCallback(array $imageConfiguration)
    {
        $this->imageConfigurations[] = $imageConfiguration;
        return htmlspecialchars($imageConfiguration['altText']);
    }

    /**
     * Callback function for creating mock IMAGEs in TYPO3 >= 7.6.
     *
     * @param string $type must be IMAGE, unused
     * @param string[] $imageConfiguration
     *
     * @return string faked image, will not be empty
     */
    public function imageCallback($type, array $imageConfiguration)
    {
        $this->imageConfigurations[] = $imageConfiguration;
        return htmlspecialchars($imageConfiguration['altText']);
    }

    /*
     * Tests for the utility functions
     */

    /**
     * @test
     */
    public function createTypoLinkInContentMockCreatesLinkToPageId()
    {
        self::assertContains(
            'index.php?id=42',
            $this->contentObject->typoLink_URL(['parameter' => 42, 'useCacheHash' => true])
        );
    }

    /**
     * @test
     */
    public function createTypoLinkInContentMockAddsParameters()
    {
        self::assertContains(
            '&tx_seminars_pi1[seminar]=42',
            $this->contentObject->typoLink_URL(
                [
                    'parameter' => 1,
                    'additionalParams' => '&tx_seminars_pi1[seminar]=42',
                    'useCacheHash' => true,
                ]
            )
        );
    }

    /*
     * Tests concerning the pagination
     */

    /**
     * @test
     */
    public function internalResultCounterForListOfTwoIsTwo()
    {
        $this->fixture->render();

        self::assertEquals(
            2,
            $this->fixture->internal['res_count']
        );
    }

    /**
     * @test
     */
    public function paginationIsNotEmptyForMoreObjectsThanFitOnOnePage()
    {
        $this->fixture->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->fixture->render();

        self::assertNotEquals(
            '',
            $this->fixture->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationIsEmptyIfObjectsJustFitOnOnePage()
    {
        $this->fixture->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 2]
        );
        $this->fixture->render();

        self::assertEquals(
            '',
            $this->fixture->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationIsEmptyIfObjectsNotFillOnePage()
    {
        $this->fixture->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 3]
        );
        $this->fixture->render();

        self::assertEquals(
            '',
            $this->fixture->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationForMoreThanOnePageContainsNumberOfTotalResults()
    {
        $this->fixture->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->fixture->render();

        self::assertContains(
            '(2 ',
            $this->fixture->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationForTwoPagesLinksFromFirstPageToSecondPage()
    {
        $this->fixture->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->fixture->render();

        self::assertContains(
            'tx_realty_pi1[pointer]=1',
            $this->fixture->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationForTwoPagesNotLinksFromFirstPageToFirstPage()
    {
        $this->fixture->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->fixture->render();

        self::assertNotContains(
            'tx_realty_pi1[pointer]=0',
            $this->fixture->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationHtmlspecialcharsUrl()
    {
        $this->fixture->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->fixture->render();

        self::assertContains(
            '&amp;tx_realty_pi1',
            $this->fixture->getSubpart('PAGINATION')
        );
        self::assertNotContains(
            '&tx_realty_pi1',
            $this->fixture->getSubpart('PAGINATION')
        );
    }

    /*
     * Tests for the images in the list view
     */

    /**
     * @test
     */
    public function listViewContainsRelatedImage()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'test image',
                'image' => 'foo.jpg',
                'object' => $this->firstRealtyUid,
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['images' => 1]
        );

        self::assertContains(
            'test image',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewNotContainsRelatedDeletedImage()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'test image',
                'object' => $this->firstRealtyUid,
                'deleted' => 1,
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['images' => 1]
        );

        self::assertNotContains(
            'test image',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewNotContainsRelatedHiddenImage()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'test image',
                'object' => $this->firstRealtyUid,
                'hidden' => 1,
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['images' => 1]
        );

        self::assertNotContains(
            'test image',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function imagesInTheListViewAreLinkedToTheSingleView()
    {
        // Titles are set to '' to ensure there are no other links to the
        // single view page in the result.
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['title' => '']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['images' => 1, 'title' => '']
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'foo',
                'image' => 'foo.jpg',
                'object' => $this->firstRealtyUid,
            ]
        );
        $output = $this->fixture->render();

        self::assertContains('=' . $this->firstRealtyUid, $output);
        self::assertContains('?id=' . $this->singlePid, $output);
    }

    /**
     * @test
     */
    public function listViewForOneImagePutsImageInRightPosition()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'single test image',
                'image' => 'single.jpg',
                'object' => $this->firstRealtyUid,
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['images' => 1]
        );

        self::assertRegExp(
            '/<td class="image imageRight"><a [^>]+>single test image<\\/a><\\/td>/',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForTwoImagesPutsFirstImageInLeftPosition()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'first image',
                'image' => 'first.jpg',
                'object' => $this->firstRealtyUid,
                'sorting' => 1,
            ]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'second image',
                'image' => 'second.jpg',
                'object' => $this->firstRealtyUid,
                'sorting' => 2,
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['images' => 2]
        );

        self::assertRegExp(
            '/<td class="image imageLeft"><a [^>]+>first image<\\/a><\\/td>/',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForTwoImagesPutsSecondImageInRightPosition()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'first image',
                'image' => 'first.jpg',
                'object' => $this->firstRealtyUid,
                'sorting' => 1,
            ]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'second image',
                'image' => 'second.jpg',
                'object' => $this->firstRealtyUid,
                'sorting' => 2,
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['images' => 2]
        );

        self::assertRegExp(
            '/<td class="image imageRight"><a [^>]+>second image<\\/a><\\/td>/',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForRelatedImageWithoutThumbnailFileUsesImageFile()
    {
        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'pages' => $this->systemFolderPid,
                'listImageMaxX' => 98,
                'listImageMaxY' => 100,
            ],
            $this->contentObject,
            true
        );

        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'test image',
                'object' => $this->firstRealtyUid,
                'image' => 'foo.jpg',
                'thumbnail' => '',
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['images' => 1]
        );

        $fixture->render();
        self::assertSame(
            [
                'altText' => 'test image',
                'titleText' => 'test image',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'width' => '98c',
                    'height' => '100c',
                ],
            ],
            $this->imageConfigurations[0]
        );
    }

    /**
     * @test
     */
    public function listViewForRelatedImageWithThumbnailFileUsesThumbnailFile()
    {
        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'pages' => $this->systemFolderPid,
                'listImageMaxX' => 98,
                'listImageMaxY' => 100,
            ],
            $this->contentObject,
            true
        );

        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'test image',
                'object' => $this->firstRealtyUid,
                'image' => 'foo.jpg',
                'thumbnail' => 'thumbnail.jpg',
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['images' => 1]
        );

        $fixture->render();
        self::assertSame(
            [
                'altText' => 'test image',
                'titleText' => 'test image',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'thumbnail.jpg',
                'file.' => [
                    'width' => '98c',
                    'height' => '100c',
                ],
            ],
            $this->imageConfigurations[0]
        );
    }

    /*
     * Tests for data in the list view
     */

    /**
     * @test
     */
    public function listViewDisplaysNoMarkersForEmptyRenderedObject()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
            ]
        );

        $this->fixture->setConfigurationValue('pages', $systemFolder);

        self::assertNotContains(
            '###',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewHtmlSpecialCharsObjectTitles()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'title' => 'a & " >',
            ]
        );

        $this->fixture->setConfigurationValue('pages', $systemFolder);

        self::assertContains(
            'a &amp; &quot; &gt;',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewFillsFloorMarkerWithFloor()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'floor' => 3,
            ]
        );

        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Tests/LegacyUnit/fixtures/listViewWithFloor.html',
                'showGoogleMaps' => 0,
                'pages' => $systemFolder,
            ],
            $this->contentObject,
            true
        );

        self::assertContains(
            $fixture->translate('label_floor') . ' 3',
            $fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForNegativeFloorShowsFloor()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'floor' => -3,
            ]
        );

        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Tests/LegacyUnit/fixtures/listViewWithFloor.html',
                'pages' => $systemFolder,
            ],
            $this->contentObject,
            true
        );

        self::assertContains(
            $fixture->translate('label_floor') . ' -3',
            $fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForZeroFloorNotContainsFloorLabel()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'floor' => 0,
            ]
        );

        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Tests/LegacyUnit/fixtures/listViewWithFloor.html',
                'showGoogleMaps' => 0,
                'pages' => $systemFolder,
            ],
            $this->contentObject,
            true
        );

        self::assertNotContains(
            $fixture->translate('label_floor'),
            $fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewWithTwoObjectsOneWithOneWithoutFloorShowsFloorOfSecondObject()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'floor' => 0,
            ]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'floor' => 3,
            ]
        );

        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Tests/LegacyUnit/fixtures/listViewWithFloor.html',
                'pages' => $systemFolder,
            ],
            $this->contentObject,
            true
        );

        self::assertContains(
            $fixture->translate('label_floor') . ' 3',
            $fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewFillsMarkerForObjectNumber()
    {
        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);
        $this->fixture->render();

        self::assertEquals(
            self::$secondObjectNumber,
            $this->fixture->getMarker('object_number')
        );
    }

    /**
     * @test
     */
    public function listViewFillsStatusMarkerWithStatusLabel()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => tx_realty_Model_RealtyObject::STATUS_RENTED,
            ]
        );

        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Tests/LegacyUnit/fixtures/listViewWithStatus.html',
                'showGoogleMaps' => 0,
                'pages' => $systemFolder,
            ],
            $this->contentObject,
            true
        );

        self::assertContains(
            $fixture->translate('label_status_3'),
            $fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForVacantStatusSetsVacantStatusClass()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => tx_realty_Model_RealtyObject::STATUS_VACANT,
            ]
        );

        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Tests/LegacyUnit/fixtures/listViewWithStatus.html',
                'showGoogleMaps' => 0,
                'pages' => $systemFolder,
            ],
            $this->contentObject,
            true
        );

        self::assertContains(
            'class="status_vacant"',
            $fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForReservedStatusSetsReservedStatusClass()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => tx_realty_Model_RealtyObject::STATUS_RESERVED,
            ]
        );

        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Tests/LegacyUnit/fixtures/listViewWithStatus.html',
                'showGoogleMaps' => 0,
                'pages' => $systemFolder,
            ],
            $this->contentObject,
            true
        );

        self::assertContains(
            'class="status_reserved"',
            $fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForSoldStatusSetsSoldStatusClass()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => tx_realty_Model_RealtyObject::STATUS_SOLD,
            ]
        );

        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Tests/LegacyUnit/fixtures/listViewWithStatus.html',
                'showGoogleMaps' => 0,
                'pages' => $systemFolder,
            ],
            $this->contentObject,
            true
        );

        self::assertContains(
            'class="status_sold"',
            $fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForRentedStatusSetsRentedStatusClass()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => tx_realty_Model_RealtyObject::STATUS_RENTED,
            ]
        );

        $fixture = new tx_realty_tests_fixtures_TestingListView(
            [
                'templateFile' => 'EXT:realty/Tests/LegacyUnit/fixtures/listViewWithStatus.html',
                'showGoogleMaps' => 0,
                'pages' => $systemFolder,
            ],
            $this->contentObject,
            true
        );

        self::assertContains(
            'class="status_rented"',
            $fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForRentedObjectWithRentShowsRentByDefault()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => tx_realty_Model_RealtyObject::STATUS_RENTED,
                'rent_excluding_bills' => '123',
            ]
        );

        self::assertContains(
            '&euro; 123,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewWithPriceOnlyIfAvailableForVacantObjectWithRentShowsRent()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => tx_realty_Model_RealtyObject::STATUS_VACANT,
                'rent_excluding_bills' => '123',
            ]
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertContains(
            '&euro; 123,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewWithPriceOnlyIfAvailableForRentedObjectWithRentNotShowsRent()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => tx_realty_Model_RealtyObject::STATUS_RENTED,
                'rent_excluding_bills' => '134',
            ]
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertNotContains(
            '&euro; 134,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForRentedObjectWithExtraChargesShowsExtraChargesByDefault()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => tx_realty_Model_RealtyObject::STATUS_RENTED,
                'extra_charges' => '281',
            ]
        );

        self::assertContains(
            '&euro; 281,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewWithPriceOnlyIfAvailableForVacantObjectWithExtraChargesShowsExtraCharges()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => tx_realty_Model_RealtyObject::STATUS_VACANT,
                'extra_charges' => '281',
            ]
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertContains(
            '&euro; 281,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewWithPriceOnlyIfAvailableForRentedObjectWithExtraChargesNotShowsExtraCharges()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => tx_realty_Model_RealtyObject::STATUS_RENTED,
                'extra_charges' => '281',
            ]
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertNotContains(
            '&euro; 281,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForSoldObjectWithBuyingPriceShowsBuyingPriceByDefault()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
                'status' => tx_realty_Model_RealtyObject::STATUS_SOLD,
                'buying_price' => '504',
            ]
        );

        self::assertContains(
            '&euro; 504,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewWithPriceOnlyIfAvailableForVacantObjectWithBuyingPriceShowsBuyingPrice()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
                'status' => tx_realty_Model_RealtyObject::STATUS_VACANT,
                'buying_price' => '504',
            ]
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertContains(
            '&euro; 504,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewWithPriceOnlyIfAvailableForSoldObjectWithBuyingPriceNotShowsBuyingPrice()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
                'status' => tx_realty_Model_RealtyObject::STATUS_SOLD,
                'buying_price' => '504',
            ]
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertNotContains(
            '&euro; 504,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function createListViewReturnsPricesWithTheCurrencyProvidedByTheObjectIfNoCurrencyIsSetInTsSetup()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => '9', 'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE, 'currency' => 'EUR']
        );
        $this->fixture->setConfigurationValue('currencyUnit', '');

        self::assertContains(
            '&euro;',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function createListViewReturnsPricesWithTheCurrencyProvidedByTheObjectAlthoughCurrencyIsSetInTsSetup()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => '9', 'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE, 'currency' => 'EUR']
        );
        $this->fixture->setConfigurationValue('currencyUnit', 'foo');

        self::assertContains(
            '&euro;',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function createListViewReturnsPricesWithTheCurrencyFromTsSetupIfTheObjectDoesNotProvideACurrency()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => '9', 'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE]
        );
        $this->fixture->setConfigurationValue('currencyUnit', 'EUR');

        self::assertContains(
            '&euro;',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewFormatsPriceUsingThousandsSeparator()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 1234567.00, 'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE]
        );

        self::assertContains(
            '1.234.567,00',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function createListViewReturnsListOfRecords()
    {
        $output = $this->fixture->render();

        self::assertContains(
            self::$firstObjectTitle,
            $output
        );
        self::assertContains(
            self::$secondObjectTitle,
            $output
        );
    }

    /**
     * @test
     */
    public function createListViewReturnsMainSysFolderRecordsAndSubFolderRecordsIfRecursionIsEnabled()
    {
        $this->fixture->setConfigurationValue('recursive', '1');

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['pid' => $this->subSystemFolderPid]
        );

        $output = $this->fixture->render();

        self::assertContains(
            self::$firstObjectTitle,
            $output
        );
        self::assertContains(
            self::$secondObjectTitle,
            $output
        );
    }

    /**
     * @test
     */
    public function createListViewNotReturnsSubFolderRecordsIfRecursionIsDisabled()
    {
        $this->fixture->setConfigurationValue('recursive', '0');

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['pid' => $this->subSystemFolderPid]
        );

        $output = $this->fixture->render();

        self::assertNotContains(
            self::$firstObjectTitle,
            $output
        );
        self::assertContains(
            self::$secondObjectTitle,
            $output
        );
    }

    /**
     * @test
     */
    public function listViewForNonEmptyTeaserShowsTeaserText()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['teaser' => 'teaser text']
        );

        self::assertContains(
            'teaser text',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForEmptyTeaserHidesTeaserSubpart()
    {
        self::assertNotContains(
            '###TEASER###',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewDisplaysTheSecondObjectsTeaserIfTheFirstOneDoesNotHaveATeaser()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['teaser' => 'test teaser']
        );

        self::assertContains(
            'test teaser',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewDisplaysFeatureParagraphForListItemWithFeatures()
    {
        // Among other things, the object number is rendered within this paragraph.
        self::assertContains(
            '<p class="details">',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewDoesNotDisplayFeatureParagraphForListItemWithoutFeatures()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
            ]
        );

        $this->fixture->setConfigurationValue('pages', $systemFolder);

        self::assertNotContains(
            '<p class="details">',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewWithOneRecordDueToTheAppliedUidFilterRedirectsToSingleView()
    {
        $this->fixture->render(['uid' => $this->firstRealtyUid]);

        self::assertContains(
            'Location:',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function listViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewForNumericObjectNumber()
    {
        $this->fixture->render(['objectNumber' => self::$firstObjectNumber]);

        self::assertContains(
            'Location:',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function listViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewForNonNumericObjectNumber(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => 'object number']
        );
        $this->fixture->render(['objectNumber' => 'object number']);

        self::assertContains(
            'Location:',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function listViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewWithTheCorrectPid()
    {
        $this->fixture->render(['objectNumber' => self::$firstObjectNumber]);

        self::assertContains(
            '?id=' . $this->singlePid,
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function listViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewWithTheCorrectShowUid()
    {
        $this->fixture->render(['objectNumber' => self::$firstObjectNumber]);

        self::assertContains(
            '=' . $this->firstRealtyUid,
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function listViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewAnProvidesAChash()
    {
        $this->fixture->render(['objectNumber' => self::$firstObjectNumber]);

        self::assertContains(
            'cHash=',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function listViewWithOneRecordNotCausedByTheIdFilterNotRedirectsToSingleView()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_cities',
            $this->firstCityUid,
            ['title' => 'foo-bar']
        );
        $this->fixture->render(['site' => 'foo']);

        self::assertEquals(
            '',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function listViewWithTwoRecordsNotRedirectsToSingleView()
    {
        $this->fixture->render();

        self::assertEquals(
            '',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function listViewCropsObjectTitleLongerThan75Characters()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'title' => 'This title is longer than 75 Characters, so the' .
                    ' rest should be cropped and be replaced with dots',
            ]
        );

        self::assertContains(
            'This title is longer than 75 Characters, so the rest should be' .
            ' cropped and…',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function createListViewShowsValueForOldOrNewBuilding()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['old_or_new_building' => '1']
        );

        self::assertContains(
            $this->fixture->translate('label_old_or_new_building_1'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForDisabledEnableNextPreviousButtonsDoesNotAddListUidToSingleViewLink()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 0);
        $output = $this->fixture->render();

        self::assertNotContains(
            'listUid',
            $output
        );
    }

    /**
     * @test
     */
    public function listViewForEnabledEnableNextPreviousButtonsAddsListUidToSingleViewLink()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
        $output = $this->fixture->render();

        self::assertContains(
            'listUid',
            $output
        );
    }

    /**
     * @test
     */
    public function listViewForDisabledEnableNextPreviousButtonsDoesNotAddListTypeToSingleViewLink()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 0);
        $output = $this->fixture->render();

        self::assertNotContains(
            'listViewType',
            $output
        );
    }

    /**
     * @test
     */
    public function listViewForEnabledEnableNextPreviousButtonsAddsListTypeToSingleViewLink()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
        $output = $this->fixture->render();

        self::assertContains(
            'listViewType',
            $output
        );
    }

    /**
     * @test
     */
    public function listViewForEnabledEnableNextPreviousButtonsAndListTypeRealtyListAddsCorrectListViewTypeToLink()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
        $output = $this->fixture->render();

        self::assertContains('=realty_list', $output);
    }

    /**
     * @test
     */
    public function listViewForDisabledEnableNextPreviousButtonsDoesNotAddRecordPositionToSingleViewLink()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 0);

        self::assertNotContains(
            'recordPosition',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForEnabledEnableNextPreviousButtonsAddsRecordPositionToSingleViewLink()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(
            'recordPosition',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewRecordPositionSingleViewLinkParameterTakesSortingIntoAccount()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(
            '=0',
            $this->fixture->render(['orderBy' => 'title', 'descFlag' => 1])
        );
    }

    /**
     * @test
     */
    public function listViewForTwoRecordsAddsRecordPositionZeroToSingleViewLinkOfFirstRecord()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains('=0', $this->fixture->render());
    }

    /**
     * @test
     */
    public function listViewForTwoRecordsOnOnePageAddsRecordPositionOneToSingleViewLinkOfSecondRecord()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains('=1', $this->fixture->render());
    }

    /**
     * @test
     */
    public function listViewForTwoRecordsOnTwoPagesAddsRecordPositionOneToSingleViewLinkOfRecordOnSecondPage()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
        $this->fixture->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );

        self::assertContains('=1', $this->fixture->render(['pointer' => 1]));
    }

    /**
     * @test
     */
    public function listViewForEnabledNextPreviousButtonsAddsListViewLimitationToSingleViewLink()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(
            'listViewLimitation',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForDisabledNextPreviousButtonsNotAddsListViewLimitationToSingleViewLink()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(
            'listViewLimitation',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForEnabledNextPreviousButtonsBase64EncodesListViewLimitationValue()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
        $listViewLimitation = [];
        preg_match(
            '/listViewLimitation]=([^&]*)/',
            $this->fixture->render(),
            $listViewLimitation
        );

        self::assertNotSame(
            '',
            $listViewLimitation[1]
        );
    }

    /**
     * @test
     */
    public function listViewForEnabledNextPreviousButtonsSerializesListViewLimitationValue()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
        $listViewLimitation = [];
        preg_match(
            '/listViewLimitation]=([^&]*)/',
            $this->fixture->render(['orderBy' => 'foo']),
            $listViewLimitation
        );

        self::assertNotSame(
            [],
            json_decode(urldecode($listViewLimitation[1]), true)
        );
    }

    /**
     * @test
     */
    public function listViewForEnabledNextPreviousButtonsForSetOrderByContainsOrderByValue()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        $result = $this->fixture->render(['orderBy' => 'foo']);

        self::assertContains('foo', $result);
    }

    /**
     * @test
     */
    public function listViewForEnabledNextPreviousButtonsForSetDescFlagContainsDescFlagValue()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        $result = $this->fixture->render(['orderBy' => 'foo', 'descFlag' => 1]);

        self::assertContains('descFlag', $result);
    }

    /**
     * @test
     */
    public function listViewForEnabledNextPreviousButtonsForSetSearchContainsSearchValue()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains('42', $this->fixture->render(['search' => ['0' => '42']]));
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteForEnabledNextPreviousButtonsContainsFilteredSite()
    {
        $this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(self::$firstCityTitle, $this->fixture->render(['site' => self::$firstCityTitle]));
    }

    /*
     * Tests concerning additional header in list view
     */

    /**
     * @test
     */
    public function createListViewForNoPostDataSentDoesNotAddCacheControlHeader()
    {
        $this->fixture->render();

        self::assertNotEquals(
            Tx_Oelib_HeaderProxyFactory::getInstance()
                ->getHeaderProxy()->getLastAddedHeader(),
            'Cache-Control: max-age=86400, must-revalidate'
        );
    }

    /**
     * @test
     */
    public function createListViewForPostDataSentAddsCacheControlHeader()
    {
        $_POST['tx_realty_pi1'] = 'foo';
        $this->fixture->render();
        unset($_POST['tx_realty_pi1']);

        self::assertEquals(
            Tx_Oelib_HeaderProxyFactory::getInstance()
                ->getHeaderProxy()->getLastAddedHeader(),
            'Cache-Control: max-age=86400, must-revalidate'
        );
    }

    /*
     * Testing filtered list views.
     */

    /**
     * @test
     */
    public function listViewFilteredByPriceDisplaysRealtyObjectWithBuyingPriceGreaterThanTheLowerLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 11]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['priceRange' => '10-'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByPriceDisplaysRealtyObjectWithBuyingPriceLowerThanTheGreaterLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 1]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['priceRange' => '-10'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByPriceDisplaysRealtyObjectWithZeroBuyingPriceAndZeroRentForNoLowerLimitSet()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 0, 'rent_excluding_bills' => 0]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['priceRange' => '-10'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByPriceNotDisplaysRealtyObjectWithZeroBuyingPriceAndRentOutOfRangeForNoLowerLimitSet(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 0, 'rent_excluding_bills' => 11]
        );

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render(['priceRange' => '-10'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByPriceDoesNotDisplayRealtyObjectBelowRangeLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['buying_price' => 9]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->fixture->render(['priceRange' => '10-100'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByPriceDoesNotDisplayRealtyObjectSuperiorToRangeLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['buying_price' => 101]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->fixture->render(['priceRange' => '10-100'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByPriceDisplaysRealtyObjectWithPriceOfLowerRangeLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 10]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['priceRange' => '10-20'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByPriceDisplaysRealtyObjectWithPriceOfUpperRangeLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 20]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['priceRange' => '10-20'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByPriceCanDisplayTwoRealtyObjectsWithABuyingPriceInRange()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 9]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['buying_price' => 1]
        );
        $output = $this->fixture->render(['priceRange' => '-10']);

        self::assertContains(
            self::$firstObjectTitle,
            $output
        );
        self::assertContains(
            self::$secondObjectTitle,
            $output
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByPriceCanDisplayTwoRealtyObjectsWithARentInRange()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['rent_excluding_bills' => 9]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['rent_excluding_bills' => 1]
        );
        $output = $this->fixture->render(['priceRange' => '-10']);

        self::assertContains(
            self::$firstObjectTitle,
            $output
        );
        self::assertContains(
            self::$secondObjectTitle,
            $output
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteDisplaysObjectWithMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['zip' => '12345']
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['site' => '12345'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteDisplaysObjectWithMatchingCity()
    {
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['site' => self::$firstCityTitle])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteDisplaysObjectWithPartlyMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['zip' => '12345']
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['site' => '12000'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteDisplaysObjectWithPartlyMatchingCity()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_cities',
            $this->firstCityUid,
            ['title' => 'foo-bar']
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['site' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteNotDisplaysObjectWithNonMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['zip' => '12345']
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render(['site' => '34'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteNotDisplaysObjectWithNonMatchingCity()
    {
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render(['site' => self::$firstCityTitle . '-foo'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteDisplaysAllObjectsForAnEmptyString()
    {
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        $output = $this->fixture->render(['site' => '']);

        self::assertContains(
            self::$firstObjectTitle,
            $output
        );
        self::assertContains(
            self::$secondObjectTitle,
            $output
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteAndPriceDisplaysObjectInPriceRangeWithMatchingCity()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 50]
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render([
                'priceRange' => '10-100',
                'site' => self::$firstCityTitle,
            ])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteAndPriceDisplaysObjectInPriceRangeWithMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 50, 'zip' => '12345']
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['priceRange' => '10-100', 'site' => '12345']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteAndPriceNotDisplaysObjectInPriceRangeWithNonMatchingCity()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 50]
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render([
                'priceRange' => '10-100',
                'site' => self::$firstCityTitle . '-foo',
            ])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteAndPriceNotDisplaysObjectInPriceRangeWithNonMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 50, 'zip' => '12345']
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['priceRange' => '10-100', 'site' => '34']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteAndPriceNotDisplaysObjectOutOfPriceRangeWithMatchingCity()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 150]
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['priceRange' => '10-100', 'site' => self::$firstCityTitle]
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySiteAndPriceNotDisplaysObjectOutOfPriceRangeWithMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 150, 'zip' => '12345']
        );
        $this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['priceRange' => '10-100', 'site' => '12345']
            )
        );
    }

    /**
     * @test
     */
    public function listViewContainsMatchingRecordWhenFilteredByObjectNumber()
    {
        self::assertContains(
            self::$firstObjectNumber,
            $this->fixture->render(
                ['objectNumber' => self::$firstObjectNumber]
            )
        );
    }

    /**
     * @test
     */
    public function listViewNotContainsMismatchingRecordWhenFilteredByObjectNumber()
    {
        self::assertNotContains(
            self::$secondObjectTitle,
            $this->fixture->render(
                ['objectNumber' => self::$firstObjectNumber]
            )
        );
    }

    /**
     * @test
     */
    public function listViewContainsMatchingRecordWhenFilteredByUid()
    {
        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['uid' => $this->firstRealtyUid])
        );
    }

    /**
     * @test
     */
    public function listViewNotContainsMismatchingRecordWhenFilteredByUid()
    {
        self::assertNotContains(
            self::$secondObjectTitle,
            $this->fixture->render(['uid' => $this->firstRealtyUid])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByRentStatusDisplaysObjectsForRenting()
    {
        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['objectType' => 'forRent'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByRentStatusDoesNotDisplaysObjectsForSale()
    {
        self::assertNotContains(
            self::$secondObjectTitle,
            $this->fixture->render(['objectType' => 'forRent'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySaleStatusDisplaysObjectsForSale()
    {
        self::assertContains(
            self::$secondObjectTitle,
            $this->fixture->render(['objectType' => 'forSale'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredBySaleStatusDoesNotDisplaysObjectsForRenting()
    {
        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render(['objectType' => 'forSale'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByLivingAreaAndSetLowerLimitDisplaysRealtyObjectWithLivingAreaGreaterThanTheLowerLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 11]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['livingAreaFrom' => '10'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByLivingAreaAndSetUpperLimitDisplaysRealtyObjectWithLivingAreaLowerThanTheGreaterLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 1]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['livingAreaTo' => '10'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByLivingAreaForSetUpperLimitAndNotSetLowerLimitDisplaysRealtyObjectWithLivingAreaZero(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 0]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['livingAreaTo' => '10'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByLivingAreaForUpperAndLowerLimitSetDoesNotDisplayRealtyObjectBelowLivingAreaLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['living_area' => 9]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->fixture->render(
                ['livingAreaFrom' => '10', 'livingAreaTo' => '100']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByLivingAreaForUpperAndLowerLimitSetDoesNotDisplayRealtyObjectWithLivingAreaGreaterThanLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['living_area' => 101]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->fixture->render(
                ['livingAreaFrom' => '10', 'livingAreaTo' => '100']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByLivingAreaForUpperAndLowerLimitSetDisplaysRealtyObjectWithLivingAreaEqualToLowerLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 10]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['livingAreaFrom' => '10', 'livingAreaTo' => '20']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByLivingAreaForUpperAndLowerLimitSetDisplaysRealtyObjectWithLivingAreaEqualToUpperLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 20]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['livingAreaFrom' => '10', 'livingAreaTo' => '20']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByLivingAreaForUpperLimitSetCanDisplayTwoRealtyObjectsWithTheLivingAreaInRange()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 9]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['living_area' => 1]
        );
        $output = $this->fixture->render(['livingAreaTo' => '10']);

        self::assertContains(
            self::$firstObjectTitle,
            $output
        );
        self::assertContains(
            self::$secondObjectTitle,
            $output
        );
    }

    /*
     * Tests concerning the list view filtered by number of rooms
     */

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsAndSetLowerLimitDisplaysRealtyObjectWithNumberOfRoomsGreaterThanTheLowerLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 11]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['numberOfRoomsFrom' => '10'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsAndSetUpperLimitDisplaysRealtyObjectWithNumberOfRoomsLowerThanTheGreaterLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 1]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['numberOfRoomsTo' => '2'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsForSetUpperLimitAndNotSetLowerLimitDisplaysRealtyObjectWithNumberOfRoomsZero(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 0]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(['numberOfRoomsTo' => '10'])
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsForUpperAndLowerLimitSetDoesNotDisplayRealtyObjectBelowNumberOfRoomsLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['number_of_rooms' => 9]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->fixture->render(
                ['numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '100']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsForUpperAndLowerLimitSetDoesNotDisplayRealtyObjectWithNumberOfRoomsGreaterThanLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['number_of_rooms' => 101]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->fixture->render(
                ['numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '100']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsForUpperAndLowerLimitSetDisplaysRealtyObjectWithNumberOfRoomsEqualToLowerLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 10]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '20']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsForUpperAndLowerLimitSetDisplaysRealtyObjectWithNumberOfRoomsEqualToUpperLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 20]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '20']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsForUpperLimitSetCanDisplayTwoRealtyObjectsWithTheNumberOfRoomsInRange(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 9]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['number_of_rooms' => 1]
        );
        $output = $this->fixture->render(['numberOfRoomsTo' => '10']);

        self::assertContains(
            self::$firstObjectTitle,
            $output
        );
        self::assertContains(
            self::$secondObjectTitle,
            $output
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsForUpperAndLowerLimitEqualHidesRealtyObjectWithNumberOfRoomsHigherThanLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 5]
        );

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['numberOfRoomsFrom' => '4.5', 'numberOfRoomsTo' => '4.5']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsForUpperAndLowerLimitEqualAndCommaAsDecimalSeparatorHidesRealtyObjectWithNumberOfRoomsLowerThanLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 4]
        );

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['numberOfRoomsFrom' => '4,5', 'numberOfRoomsTo' => '4,5']
            )
        );
    }

    /**
     * @test
     */
    public function listViewFilteredByNumberOfRoomsForUpperAndLowerLimitFourPointFiveDisplaysObjectWithFourPointFiveRooms(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 4.5]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->fixture->render(
                ['numberOfRoomsFrom' => '4.5', 'numberOfRoomsTo' => '4.5']
            )
        );
    }

    /*
     * Tests concerning the sorting in the list view
     */

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByObjectNumberWhenNumbersToSortAreIntegers()
    {
        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, self::$firstObjectNumber),
            strpos($result, self::$secondObjectNumber)
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInDescendingOrderByObjectNumberWhenNumbersToSortAreIntegers()
    {
        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, self::$secondObjectNumber),
            strpos($result, self::$firstObjectNumber)
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByObjectNumberWhenTheLowerNumbersFirstDigitIsHigherThanTheHigherNumber(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => '9']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['object_number' => '11']
        );

        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '9'),
            strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInDescendingOrderByObjectNumberWhenTheLowerNumbersFirstDigitIsHigherThanTheHigherNumber(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => '9']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['object_number' => '11']
        );

        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '11'),
            strpos($result, '9')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByObjectNumberWhenNumbersToSortHaveDots()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => '12.34']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['object_number' => '4.10']
        );

        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '4.10'),
            strpos($result, '12.34')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInDescendingOrderByObjectNumberWhenNumbersToSortHaveDots()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => '12.34']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['object_number' => '4.10']
        );

        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '12.34'),
            strpos($result, '4.10')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByObjectNumberWhenNumbersToSortHaveDotsAndDifferOnlyInDecimals()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => '12.34']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['object_number' => '12.00']
        );

        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '12.00'),
            strpos($result, '12.34')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInDescendingOrderByObjectNumberWhenNumbersToSortHaveDotsAndDifferOnlyInDecimals()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => '12.34']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['object_number' => '12.00']
        );

        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '12.34'),
            strpos($result, '12.00')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByObjectNumberWhenNumbersToSortHaveCommasAndDifferBeforeTheComma()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => '12,34']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['object_number' => '4,10']
        );

        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '4,10'),
            strpos($result, '12,34')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInDescendingOrderByObjectNumberWhenNumbersToSortHaveCommasAndDifferBeforeTheComma()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => '12,34']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['object_number' => '4,10']
        );

        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '12,34'),
            strpos($result, '4,10')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByBuyingPrice()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => '9', 'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['buying_price' => '11', 'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE]
        );

        $this->fixture->setConfigurationValue('orderBy', 'buying_price');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '9'),
            strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByRent()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['rent_excluding_bills' => '9', 'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['rent_excluding_bills' => '11', 'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT]
        );

        $this->fixture->setConfigurationValue('orderBy', 'rent_excluding_bills');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '9'),
            strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByNumberOfRooms()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 9]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['number_of_rooms' => 11]
        );

        $this->fixture->setConfigurationValue('orderBy', 'number_of_rooms');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '9'),
            strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByLivingArea()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => '9']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['living_area' => '11']
        );

        $this->fixture->setConfigurationValue('orderBy', 'living_area');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, '9'),
            strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderByTheCitiesTitles()
    {
        $this->fixture->setConfigurationValue('orderBy', 'city');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, self::$secondCityTitle),
            strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInDescendingOrderByTheCitiesTitles()
    {
        $this->fixture->setConfigurationValue('orderBy', 'city');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, self::$firstCityTitle),
            strpos($result, self::$secondCityTitle)
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedByUidIfAnInvalidSortCriterionWasSet()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['street' => '11']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['street' => '9']
        );

        $this->fixture->setConfigurationValue('orderBy', 'street');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, self::$firstCityTitle),
            strpos($result, self::$secondCityTitle)
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderBySortingFieldForNonZeroSortingFields()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['sorting' => '11']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['sorting' => '9']
        );

        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, self::$secondCityTitle),
            strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderBySortingFieldWithTheZeroEntryBeingAfterTheNonZeroEntry()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['sorting' => '0']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['sorting' => '9']
        );

        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, self::$secondCityTitle),
            strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderBySortingFieldAlthoughAnotherOrderByOptionWasSet()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['sorting' => '11', 'living_area' => '9']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['sorting' => '9', 'living_area' => '11']
        );

        $this->fixture->setConfigurationValue('orderBy', 'living_area');
        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, self::$secondCityTitle),
            strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function listViewIsSortedInAscendingOrderBySortingFieldAlthoughTheDescendingFlagWasSet()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['sorting' => '11']
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['sorting' => '9']
        );

        $this->fixture->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->fixture->render());

        self::assertGreaterThan(
            strpos($result, self::$secondCityTitle),
            strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function listViewSortedAscendingPreselectsAscendingRadioButton()
    {
        $this->fixture->setConfigurationValue('sortCriteria', 'object_number,city');

        self::assertRegExp(
            '/sortOrderAsc[^>]+checked="checked"/',
            $this->fixture->render(['descFlag' => '0'])
        );
    }

    /**
     * @test
     */
    public function listViewSortedDescendingPreselectsDescendingRadioButton()
    {
        $this->fixture->setConfigurationValue('sortCriteria', 'object_number,city');

        self::assertRegExp(
            '/sortOrderDesc[^>]+checked="checked"/',
            $this->fixture->render(['descFlag' => '1'])
        );
    }

    /**
     * @test
     */
    public function listViewSortedByCityPreselectsCityOptionInSelectionBox()
    {
        $this->fixture->setConfigurationValue('sortCriteria', 'object_number,city');

        self::assertRegExp(
            '/value="city"[^>]+selected="selected"/',
            $this->fixture->render(['orderBy' => 'city'])
        );
    }

    /**
     * @test
     */
    public function listViewSortedByCityPreselectsCityOptionInSelectionBoxOverwritingConfiguration()
    {
        $this->fixture->setConfigurationValue('sortCriteria', 'object_number,city');
        $this->fixture->setConfigurationValue('orderBy', 'object_number');

        self::assertRegExp(
            '/value="city"[^>]+selected="selected"/',
            $this->fixture->render(['orderBy' => 'city'])
        );
    }

    /**
     * @test
     */
    public function listViewSortedByCityPreselectsFromConfiguration()
    {
        $this->fixture->setConfigurationValue('sortCriteria', 'object_number,city');
        $this->fixture->setConfigurationValue('orderBy', 'city');

        self::assertRegExp(
            '/value="city"[^>]+selected="selected"/',
            $this->fixture->render()
        );
    }

    /*
     * Tests for Google Maps in the list view
     */

    /**
     * @test
     */
    public function listViewContainsMapForGoogleMapsEnabled()
    {
        $this->fixture->setConfigurationValue('showGoogleMaps', true);
        $coordinates = [
            'has_coordinates' => true,
            'latitude' => 50.734343,
            'longitude' => 7.10211,
            'show_address' => true,
        ];
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            $coordinates
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            $coordinates
        );

        self::assertContains(
            '<div id="tx_realty_map"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewDoesNotContainMapForGoogleMapsDisabled()
    {
        $this->fixture->setConfigurationValue('showGoogleMaps', false);
        $coordinates = [
            'has_coordinates' => true,
            'latitude' => 50.734343,
            'longitude' => 7.10211,
            'show_address' => true,
        ];
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            $coordinates
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            $coordinates
        );

        self::assertNotContains(
            '<div id="tx_realty_map"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewDoesNotContainMapIfAllObjectsHaveGeoError()
    {
        $this->fixture->setConfigurationValue('showGoogleMaps', 1);
        $coordinates = [
            'coordinates_problem' => true,
            'show_address' => true,
        ];
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            $coordinates
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            $coordinates
        );

        self::assertNotContains(
            '<div id="tx_realty_map"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewForObjectOnCurrentPageHasGeoErrorAndObjectWithCoordinatesIsOnNextPageNotContainsMap()
    {
        $this->fixture->setConfigurationValue('showGoogleMaps', 1);
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'coordinates_problem' => true,
                'show_address' => true,
            ]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            [
                'has_coordinates' => true,
                'latitude' => 50.734343,
                'longitude' => 7.10211,
                'show_address' => true,
            ]
        );

        $this->fixture->setConfigurationValue('orderBy', 'object_number');
        $this->fixture->setConfigurationValue(
            'listView.',
            ['descFlag' => 0, 'results_at_a_time' => 1]
        );

        self::assertNotContains(
            '<div id="tx_realty_map"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listViewContainsLinkToSingleViewPageInHtmlHeader()
    {
        $this->fixture->setConfigurationValue('showGoogleMaps', 1);
        $coordinates = [
            'has_coordinates' => true,
            'latitude' => 50.734343,
            'longitude' => 7.10211,
            'show_address' => true,
        ];
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            $coordinates
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            $coordinates
        );

        $this->fixture->render();

        self::assertRegExp(
            '/href="\\?id=' . $this->singlePid . '/',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /*
     * Tests concerning links to external details pages
     */

    /**
     * @test
     */
    public function linkToExternalSingleViewPageContainsExternalUrlIfAccessAllowed()
    {
        $this->allowAccess();

        self::assertContains(
            'http://' . self::TX_REALTY_EXTERNAL_SINGLE_PAGE,
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                self::TX_REALTY_EXTERNAL_SINGLE_PAGE
            )
        );
    }

    /**
     * @test
     */
    public function linkToExternalSingleViewPageContainsExternalUrlIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            urlencode('http://' . self::TX_REALTY_EXTERNAL_SINGLE_PAGE),
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                self::TX_REALTY_EXTERNAL_SINGLE_PAGE
            )
        );
    }

    /**
     * @test
     */
    public function linkToExternalSingleViewPageContainsATagIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '<a href=',
            $this->fixture->createLinkToSingleViewPage(
                '&',
                0,
                self::TX_REALTY_EXTERNAL_SINGLE_PAGE
            )
        );
    }

    /**
     * @test
     */
    public function linkToExternalSingleViewPageLinksToLoginPageIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '?id=' . $this->loginPid,
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                self::TX_REALTY_EXTERNAL_SINGLE_PAGE
            )
        );
    }

    /**
     * @test
     */
    public function linkToExternalSingleViewPageContainsRedirectUrlIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            'redirect_url',
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                self::TX_REALTY_EXTERNAL_SINGLE_PAGE
            )
        );
    }

    /**
     * @test
     */
    public function linkToExternalSingleViewPageNotLinksToLoginPageIfAccessAllowed()
    {
        $this->allowAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            '?id=' . $this->loginPid,
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                self::TX_REALTY_EXTERNAL_SINGLE_PAGE
            )
        );
    }

    /**
     * @test
     */
    public function linkToExternalSingleViewPageNotContainsRedirectUrlIfAccessAllowed()
    {
        $this->allowAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            'redirect_url',
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                self::TX_REALTY_EXTERNAL_SINGLE_PAGE
            )
        );
    }

    /*
     * Tests concerning links to separate details pages
     */

    /**
     * @test
     */
    public function linkToSeparateSingleViewPageLinksToSeparateSinglePidIfAccessAllowed()
    {
        $this->allowAccess();

        self::assertContains(
            '?id=' . $this->otherSinglePid,
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                $this->otherSinglePid
            )
        );
    }

    /**
     * @test
     */
    public function linkToSeparateSingleViewPageHasSeparateSinglePidInRedirectUrlIfAccessDenied()
    {
        $this->testingFramework->createFakeFrontEnd($this->otherSinglePid);
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            urlencode('?id=' . $this->otherSinglePid),
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                $this->otherSinglePid
            )
        );
    }

    /**
     * @test
     */
    public function linkToSeparateSingleViewPageContainsATagIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '<a href=',
            $this->fixture->createLinkToSingleViewPage(
                '&',
                0,
                $this->otherSinglePid
            )
        );
    }

    /**
     * @test
     */
    public function linkToSeparateSingleViewPageLinksToLoginPageIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '?id=' . $this->loginPid,
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                $this->otherSinglePid
            )
        );
    }

    /**
     * @test
     */
    public function linkToSeparateSingleViewPageContainsRedirectUrlIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            'redirect_url',
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                $this->otherSinglePid
            )
        );
    }

    /**
     * @test
     */
    public function linkToSeparateSingleViewPageNotLinksToLoginPageIfAccessAllowed()
    {
        $this->allowAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            '?id=' . $this->loginPid,
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                $this->otherSinglePid
            )
        );
    }

    /**
     * @test
     */
    public function linkToSeparateSingleViewPageNotContainsRedirectUrlIfAccesAllowed()
    {
        $this->allowAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            'redirect_url',
            $this->fixture->createLinkToSingleViewPage(
                'foo',
                0,
                $this->otherSinglePid
            )
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageIsEmptyForEmptyLinkText()
    {
        self::assertEquals(
            '',
            $this->fixture->createLinkToSingleViewPage('', 0)
        );
        $this->allowAccess();

        self::assertEquals(
            '',
            $this->fixture->createLinkToSingleViewPage('', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageContainsLinkText()
    {
        self::assertContains(
            'foo',
            $this->fixture->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageHtmlSpecialCharsLinkText()
    {
        self::assertContains(
            'a &amp; &quot; &gt;',
            $this->fixture->createLinkToSingleViewPage('a & " >', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageHasSinglePidAsLinkTargetIfAccessAllowed()
    {
        $this->allowAccess();

        self::assertContains(
            '?id=' . $this->singlePid,
            $this->fixture->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageContainsSinglePidInRedirectUrlIfAccessDenied()
    {
        $this->testingFramework->createFakeFrontEnd($this->singlePid);
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            urlencode('?id=' . $this->singlePid),
            $this->fixture->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageEscapesAmpersandsIfAccessAllowed()
    {
        $this->allowAccess();

        self::assertContains(
            '&amp;',
            $this->fixture->createLinkToSingleViewPage('&', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageEscapesAmpersandsIfAccessDenied()
    {
        $this->denyAccess();

        self::assertContains(
            '&amp;',
            $this->fixture->createLinkToSingleViewPage('&', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageContainsATagIfAccessAllowed()
    {
        $this->allowAccess();

        self::assertContains(
            '<a href=',
            $this->fixture->createLinkToSingleViewPage('&', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageContainsATagIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '<a href=',
            $this->fixture->createLinkToSingleViewPage('&', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageLinksToLoginPageIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '?id=' . $this->loginPid,
            $this->fixture->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageContainsRedirectUrlIfAccessDenied()
    {
        $this->denyAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            'redirect_url',
            $this->fixture->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageNotLinksToLoginPageIfAccessAllowed()
    {
        $this->allowAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            '?id=' . $this->loginPid,
            $this->fixture->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageNotContainsRedirectUrlIfAccesAllowed()
    {
        $this->allowAccess();
        $this->fixture->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            'redirect_url',
            $this->fixture->createLinkToSingleViewPage('foo', 0)
        );
    }

    /*
     * Tests concerning getUidForRecordNumber
     */

    /**
     * @test
     */
    public function getUidForRecordNumberNegativeRecordNumberThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fixture->getUidForRecordNumber(-1);
    }

    /**
     * @test
     */
    public function getUidForRecordNumberZeroReturnsFirstRecordsUid()
    {
        self::assertEquals(
            $this->firstRealtyUid,
            $this->fixture->getUidForRecordNumber(0)
        );
    }

    /**
     * @test
     */
    public function getUidForRecordNumberForOneReturnsSecondRecordsUid()
    {
        self::assertEquals(
            $this->secondRealtyUid,
            $this->fixture->getUidForRecordNumber(1)
        );
    }

    /**
     * @test
     */
    public function getUidForRecordNumberForNoObjectForGivenRecordNumberReturnsZero()
    {
        $this->fixture->setPiVars(['numberOfRoomsFrom' => 41]);

        self::assertEquals(
            0,
            $this->fixture->getUidForRecordNumber(0)
        );
    }

    /**
     * @test
     */
    public function getUidForRecordNumberForFilteringSetInPiVarsConsidersFilterOptions()
    {
        $this->fixture->setPiVars(['numberOfRoomsFrom' => 41]);
        $fittingRecordUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::$firstObjectTitle,
                'object_number' => self::$firstObjectNumber,
                'pid' => $this->systemFolderPid,
                'city' => $this->firstCityUid,
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
                'number_of_rooms' => 42,
            ]
        );

        self::assertEquals(
            $fittingRecordUid,
            $this->fixture->getUidForRecordNumber(0)
        );
    }

    /*
     * Tests concerning getSelfUrl
     */

    /**
     * @test
     */
    public function getSelfUrlCreatesUrlForCurrentPage()
    {
        $pageId = $this->getFrontEndController()->id;

        self::assertContains(
            '?id=' . $pageId,
            $this->fixture->getSelfUrl()
        );
    }

    /**
     * @test
     */
    public function getSelfUrlKeepsExistingPiVar()
    {
        $this->fixture->piVars['pointer'] = 2;

        self::assertContains(
            'tx_realty_pi1%5Bpointer%5D=2',
            $this->fixture->getSelfUrl()
        );
    }

    /**
     * @test
     */
    public function getSelfUrlNotKeepsExistingDataPiVar()
    {
        $this->fixture->piVars['DATA'] = 'stuff';

        self::assertNotContains(
            'tx_realty_pi1%5BDATA%5D',
            $this->fixture->getSelfUrl()
        );
    }

    /**
     * @test
     */
    public function getSelfUrlWithKeepPiVarsFalseNotKeepsExistingPiVar()
    {
        $this->fixture->piVars['pointer'] = 2;

        self::assertNotContains(
            'tx_realty_pi1%5Bpointer%5D',
            $this->fixture->getSelfUrl(false)
        );
    }

    /**
     * @test
     */
    public function getSelfUrlWithPiVarInKeysToRemoveDropsExistingPiVar()
    {
        $this->fixture->piVars['pointer'] = 2;

        self::assertNotContains(
            'tx_realty_pi1%5Bpointer%5D',
            $this->fixture->getSelfUrl(true, ['pointer'])
        );
    }

    /**
     * @test
     */
    public function getSelfUrlWithPiVarInKeysToRemoveKeepsOtherExistingPiVar()
    {
        $this->fixture->piVars['uid'] = 42;
        $this->fixture->piVars['pointer'] = 2;

        self::assertContains(
            'tx_realty_pi1%5Buid%5D=42',
            $this->fixture->getSelfUrl(true, ['pointer'])
        );
    }
}
