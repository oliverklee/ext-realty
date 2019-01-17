<?php

namespace OliverKlee\Realty\Tests\Functional\FrontEnd;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use OliverKlee\Realty\Tests\Functional\FrontEnd\Fixtures\TestingListView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class AbstractListViewTest extends FunctionalTestCase
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
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/static_info_tables',
        'typo3conf/ext/oelib',
        'typo3conf/ext/realty',
    ];

    /**
     * @var TestingListView
     */
    private $subject = null;

    /**
     * @var \Tx_Oelib_TestingFramework
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
     * @var ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contentObject = null;

    /**
     * @var array[]
     */
    private $imageConfigurations = [];

    protected function setUp()
    {
        parent::setUp();

        \Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();
        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd($this->testingFramework->createFrontEndPage());
        $this->createContentMock();

        $this->createDummyPages();
        $this->createDummyObjects();

        $this->subject = new TestingListView(
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

        parent::tearDown();
    }

    /*
     * Utility functions.
     */

    /**
     * Imports static currencies - but only if they aren't already available as static data.
     *
     * @return void
     */
    private function importCurrencies()
    {
        if (!\Tx_Oelib_Db::existsRecord('static_currencies')) {
            $this->importDataSet(__DIR__ . '/../Fixtures/Currencies.xml');
        }
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
                'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
            ]
        );
        $this->secondRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::$secondObjectTitle,
                'object_number' => self::$secondObjectNumber,
                'pid' => $this->systemFolderPid,
                'city' => $this->secondCityUid,
                'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
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
        $this->subject->setConfigurationValue('requireLoginForSingleViewPage', true);
        \Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser(null);
    }

    /**
     * Allows access to the details page by not requiring logon to display that page.
     *
     * @return void
     */
    private function allowAccess()
    {
        $this->subject->setConfigurationValue('requireLoginForSingleViewPage', false);
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
        return \htmlspecialchars($imageConfiguration['altText']);
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
        $this->subject->render();

        self::assertEquals(
            2,
            $this->subject->internal['res_count']
        );
    }

    /**
     * @test
     */
    public function paginationIsNotEmptyForMoreObjectsThanFitOnOnePage()
    {
        $this->subject->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->subject->render();

        self::assertNotEquals(
            '',
            $this->subject->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationIsEmptyIfObjectsJustFitOnOnePage()
    {
        $this->subject->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 2]
        );
        $this->subject->render();

        self::assertEquals(
            '',
            $this->subject->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationIsEmptyIfObjectsNotFillOnePage()
    {
        $this->subject->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 3]
        );
        $this->subject->render();

        self::assertEquals(
            '',
            $this->subject->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationForMoreThanOnePageContainsNumberOfTotalResults()
    {
        $this->subject->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->subject->render();

        self::assertContains(
            '(2 ',
            $this->subject->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationForTwoPagesLinksFromFirstPageToSecondPage()
    {
        $this->subject->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->subject->render();

        self::assertContains(
            'tx_realty_pi1[pointer]=1',
            $this->subject->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationForTwoPagesNotLinksFromFirstPageToFirstPage()
    {
        $this->subject->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->subject->render();

        self::assertNotContains(
            'tx_realty_pi1[pointer]=0',
            $this->subject->getSubpart('PAGINATION')
        );
    }

    /**
     * @test
     */
    public function paginationEncodesUrl()
    {
        $this->subject->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );
        $this->subject->render();

        self::assertContains(
            '&amp;tx_realty_pi1',
            $this->subject->getSubpart('PAGINATION')
        );
        self::assertNotContains(
            '&tx_realty_pi1',
            $this->subject->getSubpart('PAGINATION')
        );
    }

    /*
     * Tests for the images in the list view
     */

    /**
     * @test
     */
    public function containsRelatedImage()
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function notContainsRelatedDeletedImage()
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function notContainsRelatedHiddenImage()
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
            $this->subject->render()
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
        $output = $this->subject->render();

        self::assertContains('=' . $this->firstRealtyUid, $output);
        self::assertContains('?id=' . $this->singlePid, $output);
    }

    /**
     * @test
     */
    public function forOneImagePutsImageInRightPosition()
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forTwoImagesPutsFirstImageInLeftPosition()
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forTwoImagesPutsSecondImageInRightPosition()
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forRelatedImageWithoutThumbnailFileUsesImageFile()
    {
        $fixture = new TestingListView(
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
                'file' => \tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
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
    public function forRelatedImageWithThumbnailFileUsesThumbnailFile()
    {
        $fixture = new TestingListView(
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
                'file' => \tx_realty_Model_Image::UPLOAD_FOLDER . 'thumbnail.jpg',
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
    public function displaysNoMarkersForEmptyRenderedObject()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
            ]
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
    public function encodesObjectTitles()
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

        $this->subject->setConfigurationValue('pages', $systemFolder);

        self::assertContains(
            'a &amp; &quot; &gt;',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function fillsFloorMarkerWithFloor()
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

        $fixture = new TestingListView(
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
    public function forNegativeFloorShowsFloor()
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

        $fixture = new TestingListView(
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
    public function forZeroFloorNotContainsFloorLabel()
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

        $fixture = new TestingListView(
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
    public function withTwoObjectsOneWithOneWithoutFloorShowsFloorOfSecondObject()
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

        $fixture = new TestingListView(
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
    public function fillsMarkerForObjectNumber()
    {
        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);
        $this->subject->render();

        self::assertEquals(
            self::$secondObjectNumber,
            $this->subject->getMarker('object_number')
        );
    }

    /**
     * @test
     */
    public function fillsStatusMarkerWithStatusLabel()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => \tx_realty_Model_RealtyObject::STATUS_RENTED,
            ]
        );

        $fixture = new TestingListView(
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
    public function forVacantStatusSetsVacantStatusClass()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => \tx_realty_Model_RealtyObject::STATUS_VACANT,
            ]
        );

        $fixture = new TestingListView(
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
    public function forReservedStatusSetsReservedStatusClass()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => \tx_realty_Model_RealtyObject::STATUS_RESERVED,
            ]
        );

        $fixture = new TestingListView(
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
    public function forSoldStatusSetsSoldStatusClass()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => \tx_realty_Model_RealtyObject::STATUS_SOLD,
            ]
        );

        $fixture = new TestingListView(
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
    public function forRentedStatusSetsRentedStatusClass()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
                'status' => \tx_realty_Model_RealtyObject::STATUS_RENTED,
            ]
        );

        $fixture = new TestingListView(
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
    public function forRentedObjectWithRentShowsRentByDefault()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => \tx_realty_Model_RealtyObject::STATUS_RENTED,
                'rent_excluding_bills' => '123',
            ]
        );

        self::assertContains(
            '&euro; 123,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function withPriceOnlyIfAvailableForVacantObjectWithRentShowsRent()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => \tx_realty_Model_RealtyObject::STATUS_VACANT,
                'rent_excluding_bills' => '123',
            ]
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertContains(
            '&euro; 123,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function withPriceOnlyIfAvailableForRentedObjectWithRentNotShowsRent()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => \tx_realty_Model_RealtyObject::STATUS_RENTED,
                'rent_excluding_bills' => '134',
            ]
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertNotContains(
            '&euro; 134,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forRentedObjectWithExtraChargesShowsExtraChargesByDefault()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => \tx_realty_Model_RealtyObject::STATUS_RENTED,
                'extra_charges' => '281',
            ]
        );

        self::assertContains(
            '&euro; 281,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function withPriceOnlyIfAvailableForVacantObjectWithExtraChargesShowsExtraCharges()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => \tx_realty_Model_RealtyObject::STATUS_VACANT,
                'extra_charges' => '281',
            ]
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertContains(
            '&euro; 281,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function withPriceOnlyIfAvailableForRentedObjectWithExtraChargesNotShowsExtraCharges()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'status' => \tx_realty_Model_RealtyObject::STATUS_RENTED,
                'extra_charges' => '281',
            ]
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertNotContains(
            '&euro; 281,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forSoldObjectWithBuyingPriceShowsBuyingPriceByDefault()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
                'status' => \tx_realty_Model_RealtyObject::STATUS_SOLD,
                'buying_price' => '504',
            ]
        );

        self::assertContains(
            '&euro; 504,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function withPriceOnlyIfAvailableForVacantObjectWithBuyingPriceShowsBuyingPrice()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
                'status' => \tx_realty_Model_RealtyObject::STATUS_VACANT,
                'buying_price' => '504',
            ]
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertContains(
            '&euro; 504,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function withPriceOnlyIfAvailableForSoldObjectWithBuyingPriceNotShowsBuyingPrice()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
                'status' => \tx_realty_Model_RealtyObject::STATUS_SOLD,
                'buying_price' => '504',
            ]
        );

        $this->subject->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertNotContains(
            '&euro; 504,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function createListViewReturnsPricesWithTheCurrencyProvidedByTheObjectIfNoCurrencyIsSetInTsSetup()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => '9', 'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE, 'currency' => 'EUR']
        );
        $this->subject->setConfigurationValue('currencyUnit', '');

        self::assertContains(
            '&euro;',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function createListViewReturnsPricesWithTheCurrencyProvidedByTheObjectAlthoughCurrencyIsSetInTsSetup()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => '9', 'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE, 'currency' => 'EUR']
        );
        $this->subject->setConfigurationValue('currencyUnit', 'foo');

        self::assertContains(
            '&euro;',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function createListViewReturnsPricesWithTheCurrencyFromTsSetupIfTheObjectDoesNotProvideACurrency()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => '9', 'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE]
        );
        $this->subject->setConfigurationValue('currencyUnit', 'EUR');

        self::assertContains(
            '&euro;',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function formatsPriceUsingThousandsSeparator()
    {
        $this->importCurrencies();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 1234567.00, 'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE]
        );

        self::assertContains(
            '1.234.567,00',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function createListViewReturnsListOfRecords()
    {
        $output = $this->subject->render();

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
        $this->subject->setConfigurationValue('recursive', '1');

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['pid' => $this->subSystemFolderPid]
        );

        $output = $this->subject->render();

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
        $this->subject->setConfigurationValue('recursive', '0');

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['pid' => $this->subSystemFolderPid]
        );

        $output = $this->subject->render();

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
    public function forNonEmptyTeaserShowsTeaserText()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['teaser' => 'teaser text']
        );

        self::assertContains(
            'teaser text',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forEmptyTeaserHidesTeaserSubpart()
    {
        self::assertNotContains(
            '###TEASER###',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function displaysTheSecondObjectsTeaserIfTheFirstOneDoesNotHaveATeaser()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['teaser' => 'test teaser']
        );

        self::assertContains(
            'test teaser',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function displaysFeatureParagraphForListItemWithFeatures()
    {
        // Among other things, the object number is rendered within this paragraph.
        self::assertContains(
            '<p class="details">',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function notDisplaysFeatureParagraphForListItemWithoutFeatures()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'city' => $this->firstCityUid,
                'pid' => $systemFolder,
            ]
        );

        $this->subject->setConfigurationValue('pages', $systemFolder);

        self::assertNotContains(
            '<p class="details">',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function withOneRecordDueToTheAppliedUidFilterRedirectsToSingleView()
    {
        $this->subject->render(['uid' => $this->firstRealtyUid]);

        self::assertContains(
            'Location:',
            \Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function withOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewForNumericObjectNumber()
    {
        $this->subject->render(['objectNumber' => self::$firstObjectNumber]);

        self::assertContains(
            'Location:',
            \Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function withOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewForNonNumericObjectNumber()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['object_number' => 'object number']
        );
        $this->subject->render(['objectNumber' => 'object number']);

        self::assertContains(
            'Location:',
            \Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function withOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewWithTheCorrectPid()
    {
        $this->subject->render(['objectNumber' => self::$firstObjectNumber]);

        self::assertContains(
            '?id=' . $this->singlePid,
            \Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function withOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewWithTheCorrectShowUid()
    {
        $this->subject->render(['objectNumber' => self::$firstObjectNumber]);

        self::assertContains(
            '=' . $this->firstRealtyUid,
            \Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function withOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewAnProvidesAChash()
    {
        $this->subject->render(['objectNumber' => self::$firstObjectNumber]);

        self::assertContains(
            'cHash=',
            \Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function withOneRecordNotCausedByTheIdFilterNotRedirectsToSingleView()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_cities',
            $this->firstCityUid,
            ['title' => 'foo-bar']
        );
        $this->subject->render(['site' => 'foo']);

        self::assertEquals(
            '',
            \Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function withTwoRecordsNotRedirectsToSingleView()
    {
        $this->subject->render();

        self::assertEquals(
            '',
            \Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function cropsObjectTitleLongerThan75Characters()
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
            $this->subject->render()
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
            $this->subject->translate('label_old_or_new_building_1'),
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forDisabledEnableNextPreviousButtonsDoesNotAddListUidToSingleViewLink()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 0);
        $output = $this->subject->render();

        self::assertNotContains(
            'listUid',
            $output
        );
    }

    /**
     * @test
     */
    public function forEnabledEnableNextPreviousButtonsAddsListUidToSingleViewLink()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);
        $output = $this->subject->render();

        self::assertContains(
            'listUid',
            $output
        );
    }

    /**
     * @test
     */
    public function forDisabledEnableNextPreviousButtonsDoesNotAddListTypeToSingleViewLink()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 0);
        $output = $this->subject->render();

        self::assertNotContains(
            'listViewType',
            $output
        );
    }

    /**
     * @test
     */
    public function forEnabledEnableNextPreviousButtonsAddsListTypeToSingleViewLink()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);
        $output = $this->subject->render();

        self::assertContains(
            'listViewType',
            $output
        );
    }

    /**
     * @test
     */
    public function forEnabledEnableNextPreviousButtonsAndListTypeRealtyListAddsCorrectListViewTypeToLink()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);
        $output = $this->subject->render();

        self::assertContains('=realty_list', $output);
    }

    /**
     * @test
     */
    public function forDisabledEnableNextPreviousButtonsDoesNotAddRecordPositionToSingleViewLink()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 0);

        self::assertNotContains(
            'recordPosition',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forEnabledEnableNextPreviousButtonsAddsRecordPositionToSingleViewLink()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(
            'recordPosition',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function recordPositionSingleViewLinkParameterTakesSortingIntoAccount()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(
            '=0',
            $this->subject->render(['orderBy' => 'title', 'descFlag' => 1])
        );
    }

    /**
     * @test
     */
    public function forTwoRecordsAddsRecordPositionZeroToSingleViewLinkOfFirstRecord()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains('=0', $this->subject->render());
    }

    /**
     * @test
     */
    public function forTwoRecordsOnOnePageAddsRecordPositionOneToSingleViewLinkOfSecondRecord()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains('=1', $this->subject->render());
    }

    /**
     * @test
     */
    public function forTwoRecordsOnTwoPagesAddsRecordPositionOneToSingleViewLinkOfRecordOnSecondPage()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);
        $this->subject->setConfigurationValue(
            'listView.',
            ['results_at_a_time' => 1]
        );

        self::assertContains('=1', $this->subject->render(['pointer' => 1]));
    }

    /**
     * @test
     */
    public function forEnabledNextPreviousButtonsAddsListViewLimitationToSingleViewLink()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(
            'listViewLimitation',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forDisabledNextPreviousButtonsNotAddsListViewLimitationToSingleViewLink()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(
            'listViewLimitation',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forEnabledNextPreviousButtonsBase64EncodesListViewLimitationValue()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);
        $listViewLimitation = [];
        \preg_match(
            '/listViewLimitation]=([^&]*)/',
            $this->subject->render(),
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
    public function forEnabledNextPreviousButtonsSerializesListViewLimitationValue()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);
        $listViewLimitation = [];
        \preg_match(
            '/listViewLimitation]=([^&]*)/',
            $this->subject->render(['orderBy' => 'foo']),
            $listViewLimitation
        );

        self::assertNotSame(
            [],
            \json_decode(\urldecode($listViewLimitation[1]), true)
        );
    }

    /**
     * @test
     */
    public function forEnabledNextPreviousButtonsForSetOrderByContainsOrderByValue()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        $result = $this->subject->render(['orderBy' => 'foo']);

        self::assertContains('foo', $result);
    }

    /**
     * @test
     */
    public function forEnabledNextPreviousButtonsForSetDescFlagContainsDescFlagValue()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        $result = $this->subject->render(['orderBy' => 'foo', 'descFlag' => 1]);

        self::assertContains('descFlag', $result);
    }

    /**
     * @test
     */
    public function forEnabledNextPreviousButtonsForSetSearchContainsSearchValue()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains('42', $this->subject->render(['search' => ['0' => '42']]));
    }

    /**
     * @test
     */
    public function filteredBySiteForEnabledNextPreviousButtonsContainsFilteredSite()
    {
        $this->subject->setConfigurationValue('enableNextPreviousButtons', 1);

        self::assertContains(self::$firstCityTitle, $this->subject->render(['site' => self::$firstCityTitle]));
    }

    /*
     * Tests concerning additional header in list view
     */

    /**
     * @test
     */
    public function createListViewForNoPostDataSentDoesNotAddCacheControlHeader()
    {
        $this->subject->render();

        self::assertNotEquals(
            \Tx_Oelib_HeaderProxyFactory::getInstance()
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
        $this->subject->render();
        unset($_POST['tx_realty_pi1']);

        self::assertEquals(
            \Tx_Oelib_HeaderProxyFactory::getInstance()
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
    public function filteredByPriceDisplaysRealtyObjectWithBuyingPriceGreaterThanTheLowerLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 11]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['priceRange' => '10-'])
        );
    }

    /**
     * @test
     */
    public function filteredByPriceDisplaysRealtyObjectWithBuyingPriceLowerThanTheGreaterLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 1]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['priceRange' => '-10'])
        );
    }

    /**
     * @test
     */
    public function filteredByPriceDisplaysRealtyObjectWithZeroBuyingPriceAndZeroRentForNoLowerLimitSet()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 0, 'rent_excluding_bills' => 0]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['priceRange' => '-10'])
        );
    }

    /**
     * @test
     */
    public function filteredByPriceNotDisplaysRealtyObjectWithZeroBuyingPriceAndRentOutOfRangeForNoLowerLimitSet()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 0, 'rent_excluding_bills' => 11]
        );

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(['priceRange' => '-10'])
        );
    }

    /**
     * @test
     */
    public function filteredByPriceDoesNotDisplayRealtyObjectBelowRangeLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['buying_price' => 9]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->subject->render(['priceRange' => '10-100'])
        );
    }

    /**
     * @test
     */
    public function filteredByPriceDoesNotDisplayRealtyObjectSuperiorToRangeLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['buying_price' => 101]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->subject->render(['priceRange' => '10-100'])
        );
    }

    /**
     * @test
     */
    public function filteredByPriceDisplaysRealtyObjectWithPriceOfLowerRangeLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 10]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['priceRange' => '10-20'])
        );
    }

    /**
     * @test
     */
    public function filteredByPriceDisplaysRealtyObjectWithPriceOfUpperRangeLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 20]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['priceRange' => '10-20'])
        );
    }

    /**
     * @test
     */
    public function filteredByPriceCanDisplayTwoRealtyObjectsWithABuyingPriceInRange()
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
        $output = $this->subject->render(['priceRange' => '-10']);

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
    public function filteredByPriceCanDisplayTwoRealtyObjectsWithARentInRange()
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
        $output = $this->subject->render(['priceRange' => '-10']);

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
    public function filteredBySiteDisplaysObjectWithMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['zip' => '12345']
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['site' => '12345'])
        );
    }

    /**
     * @test
     */
    public function filteredBySiteDisplaysObjectWithMatchingCity()
    {
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['site' => self::$firstCityTitle])
        );
    }

    /**
     * @test
     */
    public function filteredBySiteDisplaysObjectWithPartlyMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['zip' => '12345']
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['site' => '12000'])
        );
    }

    /**
     * @test
     */
    public function filteredBySiteDisplaysObjectWithPartlyMatchingCity()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_cities',
            $this->firstCityUid,
            ['title' => 'foo-bar']
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['site' => 'foo'])
        );
    }

    /**
     * @test
     */
    public function filteredBySiteNotDisplaysObjectWithNonMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['zip' => '12345']
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(['site' => '34'])
        );
    }

    /**
     * @test
     */
    public function filteredBySiteNotDisplaysObjectWithNonMatchingCity()
    {
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(['site' => self::$firstCityTitle . '-foo'])
        );
    }

    /**
     * @test
     */
    public function filteredBySiteDisplaysAllObjectsForAnEmptyString()
    {
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        $output = $this->subject->render(['site' => '']);

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
    public function filteredBySiteAndPriceDisplaysObjectInPriceRangeWithMatchingCity()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 50]
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render([
                'priceRange' => '10-100',
                'site' => self::$firstCityTitle,
            ])
        );
    }

    /**
     * @test
     */
    public function filteredBySiteAndPriceDisplaysObjectInPriceRangeWithMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 50, 'zip' => '12345']
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['priceRange' => '10-100', 'site' => '12345']
            )
        );
    }

    /**
     * @test
     */
    public function filteredBySiteAndPriceNotDisplaysObjectInPriceRangeWithNonMatchingCity()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 50]
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render([
                'priceRange' => '10-100',
                'site' => self::$firstCityTitle . '-foo',
            ])
        );
    }

    /**
     * @test
     */
    public function filteredBySiteAndPriceNotDisplaysObjectInPriceRangeWithNonMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 50, 'zip' => '12345']
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['priceRange' => '10-100', 'site' => '34']
            )
        );
    }

    /**
     * @test
     */
    public function filteredBySiteAndPriceNotDisplaysObjectOutOfPriceRangeWithMatchingCity()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 150]
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['priceRange' => '10-100', 'site' => self::$firstCityTitle]
            )
        );
    }

    /**
     * @test
     */
    public function filteredBySiteAndPriceNotDisplaysObjectOutOfPriceRangeWithMatchingZip()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => 150, 'zip' => '12345']
        );
        $this->subject->setConfigurationValue('showSiteSearchInFilterForm', 'show');

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['priceRange' => '10-100', 'site' => '12345']
            )
        );
    }

    /**
     * @test
     */
    public function containsMatchingRecordWhenFilteredByObjectNumber()
    {
        self::assertContains(
            self::$firstObjectNumber,
            $this->subject->render(
                ['objectNumber' => self::$firstObjectNumber]
            )
        );
    }

    /**
     * @test
     */
    public function notContainsMismatchingRecordWhenFilteredByObjectNumber()
    {
        self::assertNotContains(
            self::$secondObjectTitle,
            $this->subject->render(
                ['objectNumber' => self::$firstObjectNumber]
            )
        );
    }

    /**
     * @test
     */
    public function containsMatchingRecordWhenFilteredByUid()
    {
        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['uid' => $this->firstRealtyUid])
        );
    }

    /**
     * @test
     */
    public function notContainsMismatchingRecordWhenFilteredByUid()
    {
        self::assertNotContains(
            self::$secondObjectTitle,
            $this->subject->render(['uid' => $this->firstRealtyUid])
        );
    }

    /**
     * @test
     */
    public function filteredByRentStatusDisplaysObjectsForRenting()
    {
        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['objectType' => 'forRent'])
        );
    }

    /**
     * @test
     */
    public function filteredByRentStatusDoesNotDisplaysObjectsForSale()
    {
        self::assertNotContains(
            self::$secondObjectTitle,
            $this->subject->render(['objectType' => 'forRent'])
        );
    }

    /**
     * @test
     */
    public function filteredBySaleStatusDisplaysObjectsForSale()
    {
        self::assertContains(
            self::$secondObjectTitle,
            $this->subject->render(['objectType' => 'forSale'])
        );
    }

    /**
     * @test
     */
    public function filteredBySaleStatusDoesNotDisplaysObjectsForRenting()
    {
        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(['objectType' => 'forSale'])
        );
    }

    /**
     * @test
     */
    public function filteredByLivingAreaAndSetLowerLimitDisplaysRealtyObjectWithLivingAreaGreaterThanTheLowerLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 11]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['livingAreaFrom' => '10'])
        );
    }

    /**
     * @test
     */
    public function filteredByLivingAreaAndSetUpperLimitDisplaysRealtyObjectWithLivingAreaLowerThanTheGreaterLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 1]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['livingAreaTo' => '10'])
        );
    }

    /**
     * @test
     */
    public function filteredByLivingAreaForSetUpperLimitAndNotSetLowerLimitDisplaysRealtyObjectWithLivingAreaZero()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 0]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['livingAreaTo' => '10'])
        );
    }

    /**
     * @test
     */
    public function filteredByLivingAreaForUpperAndLowerLimitSetDoesNotDisplayRealtyObjectBelowLivingAreaLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['living_area' => 9]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->subject->render(
                ['livingAreaFrom' => '10', 'livingAreaTo' => '100']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByLivingAreaForUpperAndLowerLimitSetDoesNotDisplayRealtyObjectWithLivingAreaGreaterThanLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['living_area' => 101]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->subject->render(
                ['livingAreaFrom' => '10', 'livingAreaTo' => '100']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByLivingAreaForUpperAndLowerLimitSetDisplaysRealtyObjectWithLivingAreaEqualToLowerLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 10]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['livingAreaFrom' => '10', 'livingAreaTo' => '20']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByLivingAreaForUpperAndLowerLimitSetDisplaysRealtyObjectWithLivingAreaEqualToUpperLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['living_area' => 20]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['livingAreaFrom' => '10', 'livingAreaTo' => '20']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByLivingAreaForUpperLimitSetCanDisplayTwoRealtyObjectsWithTheLivingAreaInRange()
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
        $output = $this->subject->render(['livingAreaTo' => '10']);

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
    public function filteredByNumberOfRoomsAndSetLowerLimitDisplaysRealtyObjectWithNumberOfRoomsGreaterThanTheLowerLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 11]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['numberOfRoomsFrom' => '10'])
        );
    }

    /**
     * @test
     */
    public function filteredByNumberOfRoomsAndSetUpperLimitDisplaysRealtyObjectWithNumberOfRoomsLowerThanTheGreaterLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 1]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['numberOfRoomsTo' => '2'])
        );
    }

    /**
     * @test
     */
    public function filteredByNumberOfRoomsForSetUpperLimitAndNotSetLowerLimitDisplaysRealtyObjectWithNumberOfRoomsZero(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 0]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(['numberOfRoomsTo' => '10'])
        );
    }

    /**
     * @test
     */
    public function filteredByNumberOfRoomsForUpperAndLowerLimitSetDoesNotDisplayRealtyObjectBelowNumberOfRoomsLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['number_of_rooms' => 9]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->subject->render(
                ['numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '100']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByNumberOfRoomsForUpperAndLowerLimitSetDoesNotDisplayRealtyObjectWithNumberOfRoomsGreaterThanLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['number_of_rooms' => 101]
        );

        self::assertNotContains(
            self::$secondObjectTitle,
            $this->subject->render(
                ['numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '100']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByNumberOfRoomsForUpperAndLowerLimitSetDisplaysRealtyObjectWithNumberOfRoomsEqualToLowerLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 10]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '20']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByNumberOfRoomsForUpperAndLowerLimitSetDisplaysRealtyObjectWithNumberOfRoomsEqualToUpperLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 20]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '20']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByNumberOfRoomsForUpperLimitSetCanDisplayTwoRealtyObjectsWithTheNumberOfRoomsInRange()
    {
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
        $output = $this->subject->render(['numberOfRoomsTo' => '10']);

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
    public function filteredByNumberOfRoomsForUpperAndLowerLimitEqualHidesRealtyObjectWithNumberOfRoomsHigherThanLimit()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 5]
        );

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['numberOfRoomsFrom' => '4.5', 'numberOfRoomsTo' => '4.5']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByNumberOfRoomsForUpperAndLowerLimitEqualAndCommaAsDecimalSeparatorHidesRealtyObjectWithNumberOfRoomsLowerThanLimit(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 4]
        );

        self::assertNotContains(
            self::$firstObjectTitle,
            $this->subject->render(
                ['numberOfRoomsFrom' => '4,5', 'numberOfRoomsTo' => '4,5']
            )
        );
    }

    /**
     * @test
     */
    public function filteredByNumberOfRoomsForUpperAndLowerLimitFourPointFiveDisplaysObjectWithFourPointFiveRooms()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['number_of_rooms' => 4.5]
        );

        self::assertContains(
            self::$firstObjectTitle,
            $this->subject->render(
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
    public function isSortedInAscendingOrderByObjectNumberWhenNumbersToSortAreIntegers()
    {
        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, self::$firstObjectNumber),
            \strpos($result, self::$secondObjectNumber)
        );
    }

    /**
     * @test
     */
    public function isSortedInDescendingOrderByObjectNumberWhenNumbersToSortAreIntegers()
    {
        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, self::$secondObjectNumber),
            \strpos($result, self::$firstObjectNumber)
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderByObjectNumberWhenTheLowerNumbersFirstDigitIsHigherThanTheHigherNumber()
    {
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

        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '9'),
            \strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function isSortedInDescendingOrderByObjectNumberWhenTheLowerNumbersFirstDigitIsHigherThanTheHigherNumber()
    {
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

        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '11'),
            \strpos($result, '9')
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderByObjectNumberWhenNumbersToSortHaveDots()
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

        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '4.10'),
            \strpos($result, '12.34')
        );
    }

    /**
     * @test
     */
    public function isSortedInDescendingOrderByObjectNumberWhenNumbersToSortHaveDots()
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

        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '12.34'),
            \strpos($result, '4.10')
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderByObjectNumberWhenNumbersToSortHaveDotsAndDifferOnlyInDecimals()
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

        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '12.00'),
            \strpos($result, '12.34')
        );
    }

    /**
     * @test
     */
    public function isSortedInDescendingOrderByObjectNumberWhenNumbersToSortHaveDotsAndDifferOnlyInDecimals()
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

        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '12.34'),
            \strpos($result, '12.00')
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderByObjectNumberWhenNumbersToSortHaveCommasAndDifferBeforeTheComma()
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

        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '4,10'),
            \
                strpos($result, '12,34')
        );
    }

    /**
     * @test
     */
    public function isSortedInDescendingOrderByObjectNumberWhenNumbersToSortHaveCommasAndDifferBeforeTheComma()
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

        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '12,34'),
            \strpos($result, '4,10')
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderByBuyingPrice()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['buying_price' => '9', 'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['buying_price' => '11', 'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_SALE]
        );

        $this->subject->setConfigurationValue('orderBy', 'buying_price');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '9'),
            \strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderByRent()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['rent_excluding_bills' => '9', 'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_RENT]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->secondRealtyUid,
            ['rent_excluding_bills' => '11', 'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_RENT]
        );

        $this->subject->setConfigurationValue('orderBy', 'rent_excluding_bills');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '9'),
            \strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderByNumberOfRooms()
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

        $this->subject->setConfigurationValue('orderBy', 'number_of_rooms');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '9'),
            \strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderByLivingArea()
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

        $this->subject->setConfigurationValue('orderBy', 'living_area');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, '9'),
            \strpos($result, '11')
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderByTheCitiesTitles()
    {
        $this->subject->setConfigurationValue('orderBy', 'city');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, self::$secondCityTitle),
            \strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function isSortedInDescendingOrderByTheCitiesTitles()
    {
        $this->subject->setConfigurationValue('orderBy', 'city');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, self::$firstCityTitle),
            \strpos($result, self::$secondCityTitle)
        );
    }

    /**
     * @test
     */
    public function isSortedByUidIfAnInvalidSortCriterionWasSet()
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

        $this->subject->setConfigurationValue('orderBy', 'street');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, self::$firstCityTitle),
            \strpos($result, self::$secondCityTitle)
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderBySortingFieldForNonZeroSortingFields()
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

        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, self::$secondCityTitle),
            \strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderBySortingFieldWithTheZeroEntryBeingAfterTheNonZeroEntry()
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

        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, self::$secondCityTitle),
            \strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderBySortingFieldAlthoughAnotherOrderByOptionWasSet()
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

        $this->subject->setConfigurationValue('orderBy', 'living_area');
        $this->subject->setConfigurationValue('listView.', ['descFlag' => 0]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = \strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, self::$secondCityTitle),
            \strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function isSortedInAscendingOrderBySortingFieldAlthoughTheDescendingFlagWasSet()
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

        $this->subject->setConfigurationValue('listView.', ['descFlag' => 1]);

        // Links inside the tags might contain numbers which could influence the
        // result. Therefore the tags are stripped.
        $result = strip_tags($this->subject->render());

        self::assertGreaterThan(
            \strpos($result, self::$secondCityTitle),
            \strpos($result, self::$firstCityTitle)
        );
    }

    /**
     * @test
     */
    public function sortedAscendingPreselectsAscendingRadioButton()
    {
        $this->subject->setConfigurationValue('sortCriteria', 'object_number,city');

        self::assertRegExp(
            '/sortOrderAsc[^>]+checked="checked"/',
            $this->subject->render(['descFlag' => '0'])
        );
    }

    /**
     * @test
     */
    public function sortedDescendingPreselectsDescendingRadioButton()
    {
        $this->subject->setConfigurationValue('sortCriteria', 'object_number,city');

        self::assertRegExp(
            '/sortOrderDesc[^>]+checked="checked"/',
            $this->subject->render(['descFlag' => '1'])
        );
    }

    /**
     * @test
     */
    public function sortedByCityPreselectsCityOptionInSelectionBox()
    {
        $this->subject->setConfigurationValue('sortCriteria', 'object_number,city');

        self::assertRegExp(
            '/value="city"[^>]+selected="selected"/',
            $this->subject->render(['orderBy' => 'city'])
        );
    }

    /**
     * @test
     */
    public function sortedByCityPreselectsCityOptionInSelectionBoxOverwritingConfiguration()
    {
        $this->subject->setConfigurationValue('sortCriteria', 'object_number,city');
        $this->subject->setConfigurationValue('orderBy', 'object_number');

        self::assertRegExp(
            '/value="city"[^>]+selected="selected"/',
            $this->subject->render(['orderBy' => 'city'])
        );
    }

    /**
     * @test
     */
    public function sortedByCityPreselectsFromConfiguration()
    {
        $this->subject->setConfigurationValue('sortCriteria', 'object_number,city');
        $this->subject->setConfigurationValue('orderBy', 'city');

        self::assertRegExp(
            '/value="city"[^>]+selected="selected"/',
            $this->subject->render()
        );
    }

    /*
     * Tests for Google Maps in the list view
     */

    /**
     * @test
     */
    public function containsMapForGoogleMapsEnabled()
    {
        $this->subject->setConfigurationValue('showGoogleMaps', true);
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function notContainsMapForGoogleMapsDisabled()
    {
        $this->subject->setConfigurationValue('showGoogleMaps', false);
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function notContainsMapIfAllObjectsHaveGeoError()
    {
        $this->subject->setConfigurationValue('showGoogleMaps', 1);
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
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function forObjectOnCurrentPageHasGeoErrorAndObjectWithCoordinatesIsOnNextPageNotContainsMap()
    {
        $this->subject->setConfigurationValue('showGoogleMaps', 1);
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

        $this->subject->setConfigurationValue('orderBy', 'object_number');
        $this->subject->setConfigurationValue(
            'listView.',
            ['descFlag' => 0, 'results_at_a_time' => 1]
        );

        self::assertNotContains(
            '<div id="tx_realty_map"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function containsLinkToSingleViewPageInHtmlHeader()
    {
        $this->subject->setConfigurationValue('showGoogleMaps', 1);
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

        $this->subject->render();

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
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            \urlencode('http://' . self::TX_REALTY_EXTERNAL_SINGLE_PAGE),
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '<a href=',
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '?id=' . $this->loginPid,
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            'redirect_url',
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            '?id=' . $this->loginPid,
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            'redirect_url',
            $this->subject->createLinkToSingleViewPage(
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
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            \urlencode('?id=' . $this->otherSinglePid),
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '<a href=',
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '?id=' . $this->loginPid,
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            'redirect_url',
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            '?id=' . $this->loginPid,
            $this->subject->createLinkToSingleViewPage(
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
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            'redirect_url',
            $this->subject->createLinkToSingleViewPage(
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
            $this->subject->createLinkToSingleViewPage('', 0)
        );
        $this->allowAccess();

        self::assertEquals(
            '',
            $this->subject->createLinkToSingleViewPage('', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageContainsLinkText()
    {
        self::assertContains(
            'foo',
            $this->subject->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageHtmlSpecialCharsLinkText()
    {
        self::assertContains(
            'a &amp; &quot; &gt;',
            $this->subject->createLinkToSingleViewPage('a & " >', 0)
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
            $this->subject->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageContainsSinglePidInRedirectUrlIfAccessDenied()
    {
        $this->testingFramework->createFakeFrontEnd($this->singlePid);
        $this->denyAccess();
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            \urlencode('?id=' . $this->singlePid),
            $this->subject->createLinkToSingleViewPage('foo', 0)
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
            $this->subject->createLinkToSingleViewPage('&', 0)
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
            $this->subject->createLinkToSingleViewPage('&', 0)
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
            $this->subject->createLinkToSingleViewPage('&', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageContainsATagIfAccessDenied()
    {
        $this->denyAccess();
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '<a href=',
            $this->subject->createLinkToSingleViewPage('&', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageLinksToLoginPageIfAccessDenied()
    {
        $this->denyAccess();
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            '?id=' . $this->loginPid,
            $this->subject->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageContainsRedirectUrlIfAccessDenied()
    {
        $this->denyAccess();
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            'redirect_url',
            $this->subject->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageNotLinksToLoginPageIfAccessAllowed()
    {
        $this->allowAccess();
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            '?id=' . $this->loginPid,
            $this->subject->createLinkToSingleViewPage('foo', 0)
        );
    }

    /**
     * @test
     */
    public function linkToSingleViewPageNotContainsRedirectUrlIfAccesAllowed()
    {
        $this->allowAccess();
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertNotContains(
            'redirect_url',
            $this->subject->createLinkToSingleViewPage('foo', 0)
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

        $this->subject->getUidForRecordNumber(-1);
    }

    /**
     * @test
     */
    public function getUidForRecordNumberZeroReturnsFirstRecordsUid()
    {
        self::assertEquals(
            $this->firstRealtyUid,
            $this->subject->getUidForRecordNumber(0)
        );
    }

    /**
     * @test
     */
    public function getUidForRecordNumberForOneReturnsSecondRecordsUid()
    {
        self::assertEquals(
            $this->secondRealtyUid,
            $this->subject->getUidForRecordNumber(1)
        );
    }

    /**
     * @test
     */
    public function getUidForRecordNumberForNoObjectForGivenRecordNumberReturnsZero()
    {
        $this->subject->setPiVars(['numberOfRoomsFrom' => 41]);

        self::assertEquals(
            0,
            $this->subject->getUidForRecordNumber(0)
        );
    }

    /**
     * @test
     */
    public function getUidForRecordNumberForFilteringSetInPiVarsConsidersFilterOptions()
    {
        $this->subject->setPiVars(['numberOfRoomsFrom' => 41]);
        $fittingRecordUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::$firstObjectTitle,
                'object_number' => self::$firstObjectNumber,
                'pid' => $this->systemFolderPid,
                'city' => $this->firstCityUid,
                'object_type' => \tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
                'number_of_rooms' => 42,
            ]
        );

        self::assertEquals(
            $fittingRecordUid,
            $this->subject->getUidForRecordNumber(0)
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
            $this->subject->getSelfUrl()
        );
    }

    /**
     * @test
     */
    public function getSelfUrlKeepsExistingPiVar()
    {
        $this->subject->piVars['pointer'] = 2;

        self::assertContains(
            'tx_realty_pi1%5Bpointer%5D=2',
            $this->subject->getSelfUrl()
        );
    }

    /**
     * @test
     */
    public function getSelfUrlNotKeepsExistingDataPiVar()
    {
        $this->subject->piVars['DATA'] = 'stuff';

        self::assertNotContains(
            'tx_realty_pi1%5BDATA%5D',
            $this->subject->getSelfUrl()
        );
    }

    /**
     * @test
     */
    public function getSelfUrlWithKeepPiVarsFalseNotKeepsExistingPiVar()
    {
        $this->subject->piVars['pointer'] = 2;

        self::assertNotContains(
            'tx_realty_pi1%5Bpointer%5D',
            $this->subject->getSelfUrl(false)
        );
    }

    /**
     * @test
     */
    public function getSelfUrlWithPiVarInKeysToRemoveDropsExistingPiVar()
    {
        $this->subject->piVars['pointer'] = 2;

        self::assertNotContains(
            'tx_realty_pi1%5Bpointer%5D',
            $this->subject->getSelfUrl(true, ['pointer'])
        );
    }

    /**
     * @test
     */
    public function getSelfUrlWithPiVarInKeysToRemoveKeepsOtherExistingPiVar()
    {
        $this->subject->piVars['uid'] = 42;
        $this->subject->piVars['pointer'] = 2;

        self::assertContains(
            'tx_realty_pi1%5Buid%5D=42',
            $this->subject->getSelfUrl(true, ['pointer'])
        );
    }
}
