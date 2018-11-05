<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_NextPreviousButtonsViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_NextPreviousButtonsView
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int the UID of the "list view" content object.
     */
    private $listViewUid = 0;

    /**
     * the UID of a dummy city for the object records
     *
     * @var int
     */
    private $dummyCityUid = 0;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $frontEndController->cObj->data['pid'] = $this->testingFramework->createFrontEndPage();
        $this->listViewUid = $this->testingFramework->createContentElement();
        $this->dummyCityUid = $this->testingFramework->createRecord('tx_realty_cities');

        $this->fixture = new tx_realty_pi1_NextPreviousButtonsView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $frontEndController->cObj
        );

        $this->fixture->setConfigurationValue('enableNextPreviousButtons', true);
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    //////////////////////
    // Utility Functions
    //////////////////////

    /**
     * Creates a realty object with a city.
     *
     * @return int the UID of the created realty object, will be > 0
     */
    private function createRealtyRecordWithCity()
    {
        return $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $this->dummyCityUid]
        );
    }

    ///////////////////////////////////////////
    // Tests concerning the utility functions
    ///////////////////////////////////////////

    /**
     * @test
     */
    public function createRealtyRecordWithCityReturnsNonZeroUid()
    {
        self::assertTrue(
            $this->createRealtyRecordWithCity() > 0
        );
    }

    /**
     * @test
     */
    public function createRealtyRecordWithCityRunTwiceCreatesTwoDifferentRecords()
    {
        self::assertNotEquals(
            $this->createRealtyRecordWithCity(),
            $this->createRealtyRecordWithCity()
        );
    }

    /**
     * @test
     */
    public function createRealtyRecordWithCityCreatesRealtyObjectRecord()
    {
        $objectUid = $this->createRealtyRecordWithCity();
        self::assertTrue(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'uid = ' . $objectUid . ' AND is_dummy_record = 1'
            )
        );
    }

    /**
     * @test
     */
    public function createRealtyRecordWithCityAddsCityToRealtyObjectRecord()
    {
        $objectUid = $this->createRealtyRecordWithCity();
        self::assertTrue(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'uid = ' . $objectUid . ' AND city > 0 AND is_dummy_record = 1'
            )
        );
    }

    ////////////////////////////////
    // Testing the basic functions
    ////////////////////////////////

    /**
     * @test
     */
    public function renderForDisabledNextPreviousButtonsReturnsEmptyString()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['object_number' => 'ABC112']);

        $this->fixture->setConfigurationValue('enableNextPreviousButtons', false);

        $this->fixture->piVars = [
            'showUid' => $realtyObject->getUid(),
            'recordPosition' => 0,
            'listViewType' => 'realty_list',
            'listUid' => $this->listViewUid,
        ];

        self::assertEquals(
            '',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForEnabledNextPreviousButtonsAndOnlyOneRecordReturnsEmptyString()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['object_number' => 'ABC112']);

        $this->fixture->piVars = [
            'showUid' => $realtyObject->getUid(),
            'recordPosition' => 0,
            'listViewLimitation' => json_encode(['objectNumber' => 'ABC112']),
            'listViewType' => 'realty_list',
            'listUid' => $this->listViewUid,
        ];

        self::assertEquals(
            '',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForEnabledNextPreviousButtonsAndMultipleRecordsReturnsNextLink()
    {
        $objectUid = $this->createRealtyRecordWithCity();
        $this->createRealtyRecordWithCity();

        $this->fixture->piVars = [
            'showUid' => $objectUid,
            'recordPosition' => 0,
            'listViewType' => 'realty_list',
            'listUid' => $this->listViewUid,
        ];

        self::assertContains(
            'nextPage',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForRecordPositionZeroNotReturnsPreviousButton()
    {
        $objectUid = $this->createRealtyRecordWithCity();
        $this->createRealtyRecordWithCity();

        $this->fixture->piVars = [
            'showUid' => $objectUid,
            'recordPosition' => 0,
            'listViewType' => 'realty_list',
            'listUid' => $this->listViewUid,
        ];

        self::assertNotContains(
            'previousPage',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForRecordPostionOneAndTwoRecordsNotReturnsNextButton()
    {
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => 'foo',
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
            ]
        );
        $objectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => 'foo',
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
            ]
        );

        $this->fixture->piVars = [
            'showUid' => $objectUid,
            'recordPosition' => 1,
            'listViewLimitation' => json_encode(['objectNumber' => 'foo']),
            'listViewType' => 'realty_list',
            'listUid' => $this->listViewUid,
        ];

        self::assertNotContains(
            'nextPage',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderAddsUidOfPreviousRecordToPreviousLink()
    {
        $objectUid1 = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => 'foo',
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
            ]
        );
        $objectUid2 = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => 'foo',
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
            ]
        );

        $this->fixture->piVars = [
            'showUid' => $objectUid2,
            'recordPosition' => 1,
            'listViewType' => 'realty_list',
            'listViewLimitation' => json_encode(['objectNumber' => 'foo']),
            'listUid' => $this->listViewUid,
        ];

        $result = $this->fixture->render();

        self::assertContains('showUid', $result);
        self::assertContains('=' . $objectUid1, $result);
    }

    /**
     * @test
     */
    public function renderAddsUidOfNextRecordToNextLink()
    {
        $objectUid1 = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => 'foo',
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
            ]
        );
        $objectUid2 = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => 'foo',
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
            ]
        );

        $this->fixture->piVars = [
            'showUid' => $objectUid1,
            'recordPosition' => 0,
            'listViewType' => 'realty_list',
            'listViewLimitation' => json_encode(['objectNumber' => 'foo']),
            'listUid' => $this->listViewUid,
        ];

        $result = $this->fixture->render();

        self::assertContains('showUid', $result);
        self::assertContains('=' . $objectUid2, $result);
    }

    /**
     * @test
     */
    public function renderForEnabledNextPreviousButtonsAndOnlyOneRecordOnListViewPageReturnsEmptyString()
    {
        $sysFolder = $this->testingFramework->createSystemFolder();
        $flexforms = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' .
            '<T3FlexForms>' .
            '<data>' .
            '<sheet index="sDEF">' .
            '<language index="lDEF">' .
            '<field index="pages">' .
            '<value index="vDEF">' . $sysFolder . '</value>' .
            '</field>' .
            '</language>' .
            '</sheet>' .
            '</data>' .
            '</T3FlexForms>';
        $listViewUid = $this->testingFramework->createContentElement(
            0,
            ['pi_flexform' => $flexforms]
        );

        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)
            ->getLoadedTestingModel(['pid' => $sysFolder]);

        $this->fixture->piVars = [
            'showUid' => $realtyObject,
            'recordPosition' => 0,
            'listViewType' => 'realty_list',
            'listUid' => $listViewUid,
        ];

        self::assertEquals(
            '',
            $this->fixture->render()
        );
    }

    //////////////////////////////////////////////////////////////////
    // Tests concerning the URL of the "next" and "previous" buttons
    //////////////////////////////////////////////////////////////////
    //
    // The following tests only test the "next" button, since the link creation
    // for the "previous" button works the same.

    /**
     * @test
     */
    public function renderAddsListViewUidToNextButton()
    {
        $objectUid = $this->createRealtyRecordWithCity();
        $this->createRealtyRecordWithCity();

        $this->fixture->piVars = [
            'showUid' => $objectUid,
            'recordPosition' => 1,
            'listUid' => $this->listViewUid,
            'listViewType' => 'realty_list',
        ];

        $result = $this->fixture->render();

        self::assertContains('listUid', $result);
        self::assertContains('=' . $this->listViewUid, $result);
    }

    /**
     * @test
     */
    public function renderAddsListViewTypeToNextButton()
    {
        $objectUid = $this->createRealtyRecordWithCity();
        $this->createRealtyRecordWithCity();

        $this->fixture->piVars = [
            'showUid' => $objectUid,
            'recordPosition' => 1,
            'listViewType' => 'favorites',
            'listUid' => $this->listViewUid,
        ];

        $result = $this->fixture->render();

        self::assertContains('listViewType', $result);
        self::assertContains('=favorites', $result);
    }

    /**
     * @test
     */
    public function renderAddsListViewLimitationToNextLink()
    {
        $objectUid = $this->createRealtyRecordWithCity();
        $this->createRealtyRecordWithCity();

        $listViewLimitation = json_encode(['objectNumber' => 'foo']);

        $this->fixture->piVars = [
            'showUid' => $objectUid,
            'recordPosition' => 1,
            'listViewType' => 'favorites',
            'listViewLimitation' => $listViewLimitation,
            'listUid' => $this->listViewUid,
        ];

        $result = $this->fixture->render();

        self::assertContains('listViewLimitation', $result);
        self::assertContains('=' . urlencode($listViewLimitation), $result);
    }

    /**
     * @test
     */
    public function renderForNoListViewTypeReturnsEmptyString()
    {
        $this->fixture->piVars = [
            'showUid' => $this->createRealtyRecordWithCity(),
            'recordPosition' => 1,
            'listUid' => $this->listViewUid,
        ];

        self::assertEquals(
            '',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForInvalidListViewTypeReturnsString()
    {
        $this->fixture->piVars = [
            'showUid' => $this->createRealtyRecordWithCity(),
            'recordPosition' => 1,
            'listViewType' => 'foo',
            'listUid' => $this->listViewUid,
        ];

        self::assertEquals(
            '',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForNegativeRecordPositionReturnsEmptyString()
    {
        $this->fixture->piVars = [
            'showUid' => $this->createRealtyRecordWithCity(),
            'recordPosition' => -1,
            'listViewType' => 'realty_list',
            'listUid' => $this->listViewUid,
        ];

        self::assertEquals(
            '',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForRecordPositionStringAddsRecordPositionOneToNextLink()
    {
        $objectUid = $this->createRealtyRecordWithCity();
        $this->createRealtyRecordWithCity();
        $this->fixture->piVars = [
            'showUid' => $objectUid,
            'recordPosition' => 'foo',
            'listViewType' => 'realty_list',
            'listUid' => $this->listViewUid,
        ];

        $result = $this->fixture->render();

        self::assertContains('recordPosition', $result);
        self::assertContains('=1', $result);
    }

    /**
     * @test
     */
    public function renderForRecordPositionStringHidesPreviousButton()
    {
        $this->fixture->piVars = [
            'showUid' => $this->createRealtyRecordWithCity(),
            'recordPosition' => 'foo',
            'listViewType' => 'realty_list',
            'listUid' => $this->listViewUid,
        ];

        self::assertNotContains(
            'previousPage',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForListUidNegativeReturnsEmptyString()
    {
        $this->fixture->piVars = [
            'showUid' => $this->createRealtyRecordWithCity(),
            'recordPosition' => 0,
            'listUid' => -1,
            'listViewType' => 'realty_list',
        ];

        self::assertEquals(
            '',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForListUidPointingToNonExistingContentElementReturnsEmptyString()
    {
        $this->fixture->piVars = [
            'showUid' => $this->createRealtyRecordWithCity(),
            'recordPosition' => 0,
            'listUid' => $this->testingFramework->getAutoIncrement('tt_content'),
            'listViewType' => 'realty_list',
        ];

        self::assertEquals(
            '',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForNoListUidSetInPiVarsReturnsEmptyString()
    {
        $this->fixture->piVars = [
            'showUid' => $this->createRealtyRecordWithCity(),
            'recordPosition' => 0,
            'listViewType' => 'realty_list',
        ];

        self::assertEquals(
            '',
            $this->fixture->render()
        );
    }
}
