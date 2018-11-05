<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_MyObjectsListViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_MyObjectsListView
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int UID of the dummy realty object
     */
    private $realtyUid = 0;

    /**
     * @var int
     */
    private $cityUid = 0;

    /**
     * @var string title for the dummy realty object
     */
    private static $objectTitle = 'a title';

    /**
     * @var int system folder PID
     */
    private $systemFolderPid = 0;

    protected function setUp()
    {
        $GLOBALS['SIM_EXEC_TIME'] = 1524751343;

        Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();
        $this->systemFolderPid = $this->testingFramework->createSystemFolder(1);

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->fixture = new tx_realty_pi1_MyObjectsListView(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'pages' => $this->systemFolderPid,
            ],
            $frontEndController->cObj,
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
     * Prepares the "my objects" list: Creates and logs in a front-end user and
     * creates a dummy object with the front-end user as owner.
     *
     * @param array $userData
     *        data with which the user should be created, may be empty
     *
     * @return void
     */
    private function prepareMyObjects(array $userData = [])
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData($userData);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Bonn']
        );
        $this->realtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::$objectTitle,
                'object_number' => '1',
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
                'teaser' => '',
                'has_air_conditioning' => '0',
                'has_pool' => '0',
                'has_community_pool' => '0',
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
                'owner' => $user->getUid(),
            ]
        );
    }

    ////////////////////////////////////
    // Tests for the utility functions
    ////////////////////////////////////

    /**
     * @test
     */
    public function prepareMyObjectsLogsInFrontEndUser()
    {
        $this->prepareMyObjects();

        self::assertTrue(
            Tx_Oelib_FrontEndLoginManager::getInstance()->isLoggedIn()
        );
    }

    /**
     * @test
     */
    public function prepareMyObjectsCreatesDummyObject()
    {
        $this->prepareMyObjects();

        self::assertTrue(
            $this->testingFramework->existsRecordWithUid(
                'tx_realty_objects',
                $this->realtyUid
            )
        );
    }

    /**
     * @test
     */
    public function prepareMyObjectsMakesUserOwnerOfOneObject()
    {
        $this->prepareMyObjects(['uid' => 123412]);

        self::assertTrue(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'uid = ' . $this->realtyUid . ' AND owner <> 0'
            )
        );
    }

    /**
     * @test
     */
    public function prepareMyObjectsCanStoreUsernameForUser()
    {
        $this->prepareMyObjects(['username' => 'foo']);

        self::assertEquals(
            'foo',
            Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser()
                ->getUserName()
        );
    }

    /*
     * Tests concerning basic functionality
     */

    /**
     * @test
     */
    public function renderForLoggedInUserWhoHasNoObjectsDisplaysNoResultsFoundMessage()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getLoadedTestingModel([]);
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            $this->fixture->translate('message_noResultsFound_my_objects'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderDisplaysObjectsTheLoggedInUserOwns()
    {
        $this->prepareMyObjects();

        self::assertContains(
            self::$objectTitle,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderNotDisplaysObjectsOfOtherOwner()
    {
        $this->prepareMyObjects();
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getLoadedTestingModel([]);
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertNotContains(
            self::$objectTitle,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderNotDisplaysObjectsWithoutOwner()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getLoadedTestingModel([]);
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'another object',
                'object_number' => '1',
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
            ]
        );

        self::assertNotContains(
            'another object',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderHasNoUnreplacedMarkers()
    {
        $this->prepareMyObjects();

        self::assertNotContains(
            '###',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderContainsEditButton()
    {
        $this->prepareMyObjects();

        $this->fixture->setConfigurationValue(
            'editorPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            'button edit',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function editButtonInTheMyObjectsViewIsLinkedToTheFeEditor()
    {
        $this->prepareMyObjects();

        $editorPid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('editorPID', $editorPid);

        self::assertContains(
            '?id=' . $editorPid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function editButtonInTheMyObjectsViewContainsTheRecordUid()
    {
        $this->prepareMyObjects();

        $this->fixture->setConfigurationValue(
            'editorPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains('=' . $this->realtyUid, $this->fixture->render());
    }

    /**
     * @test
     */
    public function renderForDeleteUidSentDeletesObjectFromMyObjectsList()
    {
        $this->prepareMyObjects();

        self::assertContains(
            self::$objectTitle,
            $this->fixture->render()
        );

        self::assertNotContains(
            self::$objectTitle,
            $this->fixture->render(['delete' => $this->realtyUid])
        );
        self::assertFalse(
            Tx_Oelib_Db::existsRecordWithUid(
                'tx_realty_objects',
                $this->realtyUid,
                ' AND deleted = 0'
            )
        );
    }

    /**
     * @test
     */
    public function renderForLoggedInUserWithoutLimitContainsCreateNewObjectLink()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 0]);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->setConfigurationValue(
            'editorPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            'button newRecord',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForLoggedInUserWithLimitButLessObjectsThanLimitContainsCreateNewObjectLink()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 2]);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->setConfigurationValue(
            'editorPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            'button newRecord',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForLoggedInUserNoObjectsLeftToEnterHidesCreateNewObjectLink()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 1]);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->setConfigurationValue(
            'editorPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertNotContains(
            'button newRecord',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function createNewObjectLinkInTheMyObjectsViewContainsTheEditorPid()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData([]);
        $user->method('getNumberOfObjects')->will(self::returnValue(0));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $editorPid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('editorPID', $editorPid);

        self::assertContains(
            '?id=' . $editorPid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderDisplaysStatePublished()
    {
        $this->prepareMyObjects();

        self::assertContains(
            $this->fixture->translate('label_published'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderDisplaysStatePending()
    {
        $this->prepareMyObjects();
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            ['hidden' => 1]
        );

        self::assertContains(
            $this->fixture->translate('label_pending'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderHidesLimitHeadingForUserWithMaximumObjectsSetToZero()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 0]);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertNotContains(
            $this->fixture->translate('label_objects_already_entered'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderShowsLimitHeadingForUserWithMaximumObjectsSetToOne()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 1]);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            sprintf($this->fixture->translate('label_objects_already_entered'), 1, 1),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForUserWithOneObjectAndMaximumObjectsSetToOneShowsNoObjectsLeftLabel()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 1]);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            $this->fixture->translate('label_no_objects_left'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForUserWithTwoObjectsAndMaximumObjectsSetToOneShowsNoObjectsLeftLabel()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 1]);
        $user->method('getNumberOfObjects')->will(self::returnValue(2));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            $this->fixture->translate('label_no_objects_left'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForUserWithOneObjectAndMaximumObjectsSetToTwoShowsOneObjectLeftLabel()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 2]);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            $this->fixture->translate('label_one_object_left'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderForUserWithNoObjectAndMaximumObjectsSetToTwoShowsMultipleObjectsLeftLabel()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 2]);
        $user->method('getNumberOfObjects')->will(self::returnValue(0));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            sprintf($this->fixture->translate('label_multiple_objects_left'), 2),
            $this->fixture->render()
        );
    }

    ////////////////////////////////////////////
    // Tests concerning the "advertise" button
    ////////////////////////////////////////////

    /**
     * @test
     */
    public function myItemWithAdvertisePidAndNoAdvertisementDateHasAdvertiseButton()
    {
        $this->prepareMyObjects();
        $this->fixture->setConfigurationValue(
            'advertisementPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            'class="button advertise"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function myItemWithoutAdvertisePidNotHasAdvertiseButton()
    {
        $this->prepareMyObjects();

        self::assertNotContains(
            'class="button advertise"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function myItemWithAdvertisePidLinksToAdvertisePid()
    {
        $this->prepareMyObjects();
        $advertisementPid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue(
            'advertisementPID',
            $advertisementPid
        );

        self::assertContains(
            '?id=' . $advertisementPid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function myItemWithAdvertiseParameterUsesParameterWithObjectUid()
    {
        $this->prepareMyObjects();
        $advertisementPid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue(
            'advertisementPID',
            $advertisementPid
        );
        $this->fixture->setConfigurationValue(
            'advertisementParameterForObjectUid',
            'foo'
        );

        self::assertContains(
            'foo=' . $this->realtyUid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function myItemWithPastAdvertisementDateAndZeroExpiryNotHasLinkToAdvertisePid()
    {
        $this->prepareMyObjects();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            ['advertised_date' => $GLOBALS['SIM_EXEC_TIME'] - Tx_Oelib_Time::SECONDS_PER_DAY]
        );

        $this->fixture->setConfigurationValue(
            'advertisementExpirationInDays',
            0
        );
        $advertisementPid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue(
            'advertisementPID',
            $advertisementPid
        );

        self::assertNotContains(
            '?id=' . $advertisementPid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function myItemWithPastAdvertisementDateAndNonZeroSmallEnoughExpiryHasLinkToAdvertisePid()
    {
        $this->prepareMyObjects();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            ['advertised_date' => $GLOBALS['SIM_EXEC_TIME'] - 10]
        );

        $this->fixture->setConfigurationValue(
            'advertisementExpirationInDays',
            1
        );
        $advertisementPid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue(
            'advertisementPID',
            $advertisementPid
        );

        self::assertContains(
            '?id=' . $advertisementPid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function myItemWithPastAdvertisementDateAndNonZeroTooBigExpiryNotHasLinkToAdvertisePid()
    {
        $this->prepareMyObjects();

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->realtyUid,
            ['advertised_date' => $GLOBALS['SIM_EXEC_TIME'] - 2 * Tx_Oelib_Time::SECONDS_PER_DAY]
        );

        $this->fixture->setConfigurationValue(
            'advertisementExpirationInDays',
            1
        );
        $advertisementPid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue(
            'advertisementPID',
            $advertisementPid
        );

        self::assertNotContains(
            '?id=' . $advertisementPid,
            $this->fixture->render()
        );
    }
}
