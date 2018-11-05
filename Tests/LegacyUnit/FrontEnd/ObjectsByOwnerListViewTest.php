<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_ObjectsByOwnerListViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var string the title of a dummy object for the tests
     */
    const OBJECT_TITLE = 'Testing object';

    /**
     * @var tx_realty_pi1_ObjectsByOwnerListView
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int the UID of a dummy object
     */
    private $objectUid = 0;

    /**
     * @var int the UID of a dummy city
     */
    private $cityUid = 0;

    /**
     * @var int the UID of the FE user who is the owner of the dummy object
     */
    private $ownerUid = 0;

    /**
     * @var int system folder PID
     */
    private $systemFolderPid = 0;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();
        $this->systemFolderPid = $this->testingFramework->createSystemFolder(1);

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->fixture = new tx_realty_pi1_ObjectsByOwnerListView(
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

    ///////////////////////
    // Utility functions.
    ///////////////////////

    /**
     * Creates a front-end user in the mapper (in memory), a city in the
     * database and a object in the database with that user as owner and that
     * city.
     *
     * @param array $userData
     *        data with which the user should be created, may be empty
     *
     * @return void
     */
    private function createObjectWithOwner(array $userData = [])
    {
        $owner = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)
            ->getLoadedTestingModel($userData);
        $this->ownerUid = $owner->getUid();
        $this->cityUid
            = $this->testingFramework->createRecord('tx_realty_cities');
        $this->objectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::OBJECT_TITLE,
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
                'owner' => $this->ownerUid,
            ]
        );
    }

    ////////////////////////////////////
    // Tests for the utility functions
    ////////////////////////////////////

    /**
     * @test
     */
    public function createObjectWithOwnerCreatesObjectInDatabase()
    {
        $this->createObjectWithOwner();

        self::assertTrue(
            $this->testingFramework->existsRecordWithUid(
                'tx_realty_objects',
                $this->objectUid
            )
        );
    }

    /**
     * @test
     */
    public function createObjectWithOwnerCreatesCityInDatabase()
    {
        $this->createObjectWithOwner();

        self::assertTrue(
            $this->testingFramework->existsRecordWithUid(
                'tx_realty_cities',
                $this->cityUid
            )
        );
    }

    /**
     * @test
     */
    public function createObjectWithOwnerCreatesFrontEndUserInMapper()
    {
        $this->createObjectWithOwner();

        self::assertTrue(
            Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)
                ->existsModel($this->ownerUid)
        );
    }

    /**
     * @test
     */
    public function createObjectWithOwnerMakesUserOwnerOfOneObject()
    {
        $this->createObjectWithOwner();

        self::assertTrue(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'owner = ' . $this->ownerUid . ' AND uid = ' . $this->objectUid
            )
        );
    }

    /**
     * @test
     */
    public function createObjectWithOwnerCanStoreUsernameForUser()
    {
        $this->createObjectWithOwner(['username' => 'foo']);

        /** @var tx_realty_Mapper_FrontEndUser $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class);
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = $mapper->find($this->ownerUid);
        self::assertEquals(
            'foo',
            $user->getUserName()
        );
    }

    ////////////////////////////////////////
    // Tests concerning basic functionality
    ////////////////////////////////////////

    /**
     * @test
     */
    public function displaysHasNoUnreplacedMarkers()
    {
        $this->createObjectWithOwner();

        self::assertNotContains(
            '###',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysLabelOfferingsBy()
    {
        $this->createObjectWithOwner();

        self::assertContains(
            $this->fixture->translate('label_offerings_by'),
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysObjectBySelectedOwner()
    {
        $this->createObjectWithOwner();

        self::assertContains(
            self::OBJECT_TITLE,
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function notDisplaysObjectByOtherOwner()
    {
        $this->createObjectWithOwner();
        $ownerUid = $this->testingFramework->createFrontEndUser();

        self::assertNotContains(
            self::OBJECT_TITLE,
            $this->fixture->render(['owner' => $ownerUid])
        );
    }

    /**
     * @test
     */
    public function forGivenOwnerUidNotDisplaysObjectWithoutOwner()
    {
        $this->createObjectWithOwner();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'lonely object',
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
                'owner' => 0,
            ]
        );

        self::assertNotContains(
            'lonely object',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function notDisplaysHiddenObjectOfGivenOwner()
    {
        $this->createObjectWithOwner();
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'hidden object',
                'pid' => $this->systemFolderPid,
                'city' => $this->cityUid,
                'owner' => $this->ownerUid,
                'hidden' => 1,
            ]
        );

        self::assertNotContains(
            'hidden object',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysNoResultsViewForFeUserWithoutObjects()
    {
        self::assertContains(
            $this->fixture->translate('message_noResultsFound_objects_by_owner'),
            $this->fixture->render(
                ['owner' => $this->testingFramework->createFrontEndUser()]
            )
        );
    }

    /**
     * @test
     */
    public function displaysNoResultsViewForFeUserWhoOnlyHasAHiddenObject()
    {
        $this->createObjectWithOwner();
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['hidden' => 1]
        );

        self::assertContains(
            $this->fixture->translate('message_noResultsFound_objects_by_owner'),
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysAddToFavoritesButton()
    {
        $this->createObjectWithOwner();

        self::assertContains(
            $this->fixture->translate('label_add_to_favorites'),
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    ///////////////////////////////////////////////////
    /// Tests concerning how the owner gets displayed
    ///////////////////////////////////////////////////

    /**
     * @test
     */
    public function displaysCompanyNameIfProvided()
    {
        $this->createObjectWithOwner(['company' => 'realty test company']);

        self::assertContains(
            'realty test company',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysFirstAndLastNameIfFirstAndLastNameAreSetAndNoCompanyIsSet()
    {
        $this->createObjectWithOwner(
            [
                'last_name' => 'last name',
                'first_name' => 'first name',
            ]
        );

        self::assertContains(
            'first name last name',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysLastNameIfLastNameIsSetAndNeitherCompanyNorFirstNameAreSet()
    {
        $this->createObjectWithOwner(['last_name' => 'last name']);

        self::assertContains(
            'last name',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysNameIfFirstNameIsSetAndNeitherCompanyNorLastNameAreSet()
    {
        $this->createObjectWithOwner(
            [
                'first_name' => 'first name',
                'name' => 'test name',
            ]
        );

        self::assertContains(
            'test name',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysNameIfNeitherCompanyNorLastNameNorFirstNameAreSet()
    {
        $this->createObjectWithOwner(['name' => 'test name']);

        self::assertContains(
            'test name',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysUsernameIfNeitherCompanyNorLastNameNorNameAreSet()
    {
        $this->createObjectWithOwner(
            ['username' => 'test user']
        );

        self::assertContains(
            'test user',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /////////////////////////////////////////////////////
    // Tests concerning the case of a given owner UID 0
    /////////////////////////////////////////////////////

    /**
     * @test
     */
    public function displaysNoSuchOwnerMessageForZeroOwnerUid()
    {
        self::assertContains(
            $this->fixture->translate('message_no_such_owner'),
            $this->fixture->render(['owner' => 0])
        );
    }

    /**
     * @test
     */
    public function displaysLabelSorryForZeroOwnerUid()
    {
        self::assertContains(
            $this->fixture->translate('label_sorry'),
            $this->fixture->render(['owner' => 0])
        );
    }

    /**
     * @test
     */
    public function notDisplaysLabelOfferingsByForZeroOwnerUid()
    {
        self::assertNotContains(
            $this->fixture->translate('label_offerings_by'),
            $this->fixture->render(['owner' => 0])
        );
    }

    /**
     * @test
     */
    public function notDisplaysObjectWithoutOwnerForZeroOwnerUid()
    {
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'lonely object',
                'pid' => $this->systemFolderPid,
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
                'owner' => 0,
            ]
        );

        self::assertNotContains(
            'lonely object',
            $this->fixture->render(['owner' => 0])
        );
    }

    ///////////////////////////////////////////////////
    // Tests concerning non-existing or deleted users
    ///////////////////////////////////////////////////

    /**
     * @test
     */
    public function displaysNoSuchOwnerMessageForNonExistingOwner()
    {
        $ownerUid = $this->testingFramework->getAutoIncrement('fe_users');

        self::assertContains(
            $this->fixture->translate('message_no_such_owner'),
            $this->fixture->render(['owner' => $ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysNoSuchOwnerMessageForDeletedFeUserWithObject()
    {
        $ownerUid = $this->testingFramework->createFrontEndUser(
            '',
            ['deleted' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'owner' => $ownerUid,
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
            ]
        );

        self::assertContains(
            $this->fixture->translate('message_no_such_owner'),
            $this->fixture->render(['owner' => $ownerUid])
        );
    }

    /**
     * @test
     */
    public function notDisplaysADeletedFeUsersObject()
    {
        $ownerUid = $this->testingFramework->createFrontEndUser(
            '',
            ['deleted' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'object of deleted owner',
                'owner' => $ownerUid,
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
            ]
        );

        self::assertNotContains(
            'object of deleted owner',
            $this->fixture->render(['owner' => $this->ownerUid])
        );
    }

    /**
     * @test
     */
    public function displaysLabelSorryForDeletedFeUserWithAnObject()
    {
        $ownerUid = $this->testingFramework->createFrontEndUser(
            '',
            ['deleted' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'owner' => $ownerUid,
                'city' => $this->testingFramework->createRecord('tx_realty_cities'),
            ]
        );

        self::assertContains(
            $this->fixture->translate('label_sorry'),
            $this->fixture->render(['owner' => $ownerUid])
        );
    }
}
