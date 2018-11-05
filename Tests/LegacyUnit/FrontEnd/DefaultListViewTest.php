<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_DefaultListViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_DefaultListView
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
     * @var int second dummy realty object
     */
    private $secondRealtyUid = 0;

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
     * @var int system folder PID
     */
    private $systemFolderPid = 0;

    protected function setUp()
    {
        Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();
        $this->systemFolderPid = $this->testingFramework->createSystemFolder(1);

        $this->createDummyObjects();

        $this->fixture = new tx_realty_pi1_DefaultListView(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'pages' => $this->systemFolderPid,
                'showGoogleMaps' => 0,
            ],
            $this->getFrontEndController()->cObj,
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
                'pid' => $this->systemFolderPid,
                'city' => $this->firstCityUid,
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
            ]
        );
        $this->secondRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
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

    //////////////////////////////////////////
    // Tests for the list filter checkboxes.
    //////////////////////////////////////////

    /**
     * @test
     */
    public function listFilterIsVisibleIfCheckboxesFilterSetToDistrictAndCitySelectorIsInactive()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'district' => $this->testingFramework->createRecord(
                    'tx_realty_districts',
                    ['title' => 'test district']
                ),
            ]
        );
        $this->fixture->setConfigurationValue('checkboxesFilter', 'district');

        self::assertContains(
            'id="tx_realty_pi1_search"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function checkboxesFilterDoesNotHaveUnreplacedMarkersForMinimalContent()
    {
        $systemFolder = $this->testingFramework->createSystemFolder();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                // A city is the minimum requirement for an object to be displayed,
                // though the object is rendered empty because the city has no title.
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
                'pid' => $systemFolder,
            ]
        );

        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');
        $this->fixture->setConfigurationValue('pages', $systemFolder);

        self::assertContains(
            'id="tx_realty_pi1_search"',
            $this->fixture->render()
        );
        self::assertNotContains(
            '###',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listFilterIsVisibleIfCheckboxesFilterIsSetToDistrictAndCitySelectorIsActive()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'district' => $this->testingFramework->createRecord(
                    'tx_realty_districts',
                    ['title' => 'test district']
                ),
            ]
        );
        $this->fixture->setConfigurationValue('checkboxesFilter', 'district');

        self::assertContains(
            'id="tx_realty_pi1_search"',
            $this->fixture->render(['city' => $this->firstCityUid])
        );
    }

    /**
     * @test
     */
    public function listFilterIsInvisibleIfCheckboxesFilterSetToDistrictAndNoRecordIsLinkedToADistrict()
    {
        $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['title' => 'test district']
        );
        $this->fixture->setConfigurationValue('checkboxesFilter', 'district');

        self::assertNotContains(
            'id="tx_realty_pi1_search"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listFilterIsInvisibleIfCheckboxesFilterSetToDistrictAndNoDistrictsExists()
    {
        $this->fixture->setConfigurationValue('checkboxesFilter', 'district');

        self::assertNotContains(
            'id="tx_realty_pi1_search"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listFilterIsVisibleIfCheckboxesFilterSetToCityAndCitySelectorIsInactive()
    {
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');

        self::assertContains(
            'id="tx_realty_pi1_search"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listFilterIsInvisibleIfCheckboxesFilterIsSetToCityAndCitySelectorIsActive()
    {
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');

        self::assertNotContains(
            'id="tx_realty_pi1_search"',
            $this->fixture->render(['city' => $this->firstCityUid])
        );
    }

    /**
     * @test
     */
    public function listFilterIsInvisibleIfCheckboxesFilterNotSet()
    {
        self::assertNotContains(
            'id="tx_realty_pi1_search"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listFilterDoesNotDisplayUnlinkedCity()
    {
        $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'unlinked city']
        );
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');

        $output = $this->fixture->render();

        self::assertContains(
            'id="tx_realty_pi1_search"',
            $output
        );
        self::assertNotContains(
            'unlinked city',
            $output
        );
    }

    /**
     * @test
     */
    public function listFilterDoesNotDisplayDeletedCity()
    {
        $deletedCityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'deleted city', 'deleted' => 1]
        );
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['city' => $deletedCityUid]
        );
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');

        $output = $this->fixture->render();

        self::assertContains(
            'id="tx_realty_pi1_search"',
            $output
        );
        self::assertNotContains(
            'deleted city',
            $output
        );
    }

    /**
     * @test
     */
    public function listIsFilteredForOneCriterion()
    {
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');
        $piVars = ['search' => [$this->firstCityUid]];

        // The city's title will occur twice if it is within the list view and
        // within the list filter. It will occur once if it is only a filter
        // criterion.
        // piVars would usually be set by each submit of the list filter.
        $output = $this->fixture->render($piVars);

        self::assertEquals(
            2,
            substr_count($output, self::$firstCityTitle)
        );
        self::assertEquals(
            1,
            substr_count($output, self::$secondCityTitle)
        );
    }

    /**
     * @test
     */
    public function listIsFilteredForTwoCriteria()
    {
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');
        $piVars = [
            'search' => [
                $this->firstCityUid,
                $this->secondCityUid,
            ],
        ];

        // The city's title will occur twice if it is within the list view and
        // within the list filter. It will occur once if it is only a filter
        // criterion.
        // piVars would usually be set by each submit of the list filter.
        $output = $this->fixture->render($piVars);

        self::assertEquals(
            2,
            substr_count($output, self::$firstCityTitle)
        );
        self::assertEquals(
            2,
            substr_count($output, self::$secondCityTitle)
        );
    }

    /**
     * @test
     */
    public function listFilterLinksToTheSelfUrl()
    {
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');

        self::assertContains(
            '?id=' . $this->getFrontEndController()->id,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function listFiltersLinkDoesNotContainSearchPiVars()
    {
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');

        self::assertNotContains(
            'tx_realty_pi1[search][0]=' . $this->firstCityUid,
            $this->fixture->render(['search' => [$this->firstCityUid]])
        );
    }

    /**
     * @test
     */
    public function listFiltersLinkDoesNotContainPointerPiVars()
    {
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');

        self::assertNotContains(
            'pointer',
            $this->fixture->render(['pointer' => 1])
        );
    }

    /**
     * @test
     */
    public function listFilterKeepsAlreadySetPiVars()
    {
        $this->fixture->setConfigurationValue('what_to_display', 'realty_list');
        $this->fixture->setConfigurationValue('checkboxesFilter', 'city');

        self::assertContains(
            'tx_realty_pi1%5Bowner%5D=25',
            $this->fixture->render(['owner' => 25])
        );
    }
}
