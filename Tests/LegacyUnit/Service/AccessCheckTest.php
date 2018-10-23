<?php

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_Service_AccessCheckTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_AccessCheck
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int UID of the dummy object
     */
    private $dummyObjectUid;

    protected function setUp()
    {
        Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();

        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();
        $this->dummyObjectUid = $this->testingFramework->createRecord(
            'tx_realty_objects'
        );

        $this->fixture = new tx_realty_pi1_AccessCheck();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    //////////////////////////////////////////////
    // Tests concerning access to the FE editor.
    //////////////////////////////////////////////

    /**
     * @test
     */
    public function checkAccessForFeEditorThrowsExceptionWithPleaseLoginMessageForAnInvalidUidAndNoUserLoggedIn()
    {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('fe_editor', [
            'showUid' => $this->testingFramework->createRecord(
                'tx_realty_objects',
                ['deleted' => 1]
            ),
        ]);
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorThrowsExceptionWithObjectDoesNotExistMessageForAnInvalidUidAndAUserLoggedIn()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('fe_editor', [
            'showUid' => $this->testingFramework->createRecord(
                'tx_realty_objects',
                ['deleted' => 1]
            ),
        ]);
    }

    /**
     * @test
     */
    public function header404IsSentWhenCheckAccessForFeEditorThrowsExceptionWithObjectDoesNotExistMessage()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        try {
            $this->fixture->checkAccess('fe_editor', [
                'showUid' => $this->testingFramework->createRecord(
                    'tx_realty_objects',
                    ['deleted' => 1]
                ),
            ]);
        } catch (Tx_Oelib_Exception_AccessDenied $exception) {
        }

        self::assertEquals(
            'Status: 404 Not Found',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorThrowsExceptionWithPleaseLoginMessageForNewObjectIfNoUserIsLoggedIn()
    {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('fe_editor', ['showUid' => 0]);
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorThrowsExceptionWithPleaseLoginMessageForAnExistingObjectIfNoUserIsLoggedIn()
    {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess(
            'fe_editor',
            ['showUid' => $this->dummyObjectUid]
        );
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorThrowsExceptionWithAccessDeniedMessageWhenLoggedInUserAttemptsToEditAnObjectHeDoesNotOwn(
    ) {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess(
            'fe_editor',
            ['showUid' => $this->dummyObjectUid]
        );
    }

    /**
     * @test
     */
    public function header403IsSentWhenCheckAccessForFeEditorThrowsExceptionWithAccessDeniedMessage()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        try {
            $this->fixture->checkAccess(
                'fe_editor',
                ['showUid' => $this->dummyObjectUid]
            );
        } catch (Tx_Oelib_Exception_AccessDenied $exception) {
        }

        self::assertEquals(
            'Status: 403 Forbidden',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorDoesNotThrowAnExceptionIfTheObjectExistsAndTheUserIsLoggedIn()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->dummyObjectUid,
            ['owner' => $user->getUid()]
        );

        $this->fixture->checkAccess(
            'fe_editor',
            ['showUid' => $this->dummyObjectUid]
        );
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorDoesNotThrowAnExceptionIfTheNonPublishedObjectExistsAndTheUserIsLoggedIn()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->dummyObjectUid,
            [
                'owner' => $user->getUid(),
                'hidden' => true,
            ]
        );

        $this->fixture->checkAccess(
            'fe_editor',
            ['showUid' => $this->dummyObjectUid]
        );
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorDoesNotThrowAnExceptionIfTheObjectIsNewAndTheUserIsLoggedIn()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getLoadedTestingModel([]);
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->checkAccess('fe_editor', ['showUid' => 0]);
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorForLoggedInUserWithNoObjectsLeftToEnterThrowsExceptionWithNoObjectsLeftMessage(
    ) {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 1]);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('fe_editor', ['showUid' => 0]);
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorForLoggedInUserWithNoObjectsLeftToEnterAndEditingAnExistingObjectDoesNotThrowException(
    ) {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 1]);
        $user->method('getNumberOfObjects')->will(self::returnValue(1));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $objectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['owner' => $user->getUid()]
        );

        $this->fixture->checkAccess('fe_editor', ['showUid' => $objectUid]);
    }

    /**
     * @test
     */
    public function checkAccessForFeEditorForLoggedInUserWithObjectsLeftToEnterThrowsNoException()
    {
        /** @var tx_realty_Model_FrontEndUser|PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock(\tx_realty_Model_FrontEndUser::class, ['getNumberOfObjects']);
        $user->setData(['tx_realty_maximum_objects' => 1]);
        $user->method('getNumberOfObjects')->will(self::returnValue(0));
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->checkAccess('fe_editor', ['showUid' => 0]);
    }

    /////////////////////////////////////////////////
    // Tests concerning access to the image upload.
    /////////////////////////////////////////////////

    /**
     * @test
     */
    public function checkAccessForImageUploadThrowsExceptionWithPleaseLoginMessageForAnInvalidUidAndNoUserLoggedIn()
    {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('image_upload', [
            'showUid' => $this->testingFramework->createRecord(
                'tx_realty_objects',
                ['deleted' => 1]
            ),
        ]);
    }

    /**
     * @test
     */
    public function checkAccessForImageUploadThrowsExceptionWithObjectDoesNotExistMessageForZeroObjectUidAndAUserLoggedIn(
    ) {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('image_upload', ['showUid' => 0]);
    }

    /**
     * @test
     */
    public function checkAccessForImageUploadThrowsExceptionWithObjectDoesNotExistMessageForAnInvalidObjectUidAndAUserLoggedIn(
    ) {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('image_upload', [
            'showUid' => $this->testingFramework->createRecord(
                'tx_realty_objects',
                ['deleted' => 1]
            ),
        ]);
    }

    /**
     * @test
     */
    public function header404IsSentWhenCheckAccessForImageUploadThrowsExceptionWithObjectDoesNotExistMessage()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        try {
            $this->fixture->checkAccess('image_upload', [
                'showUid' => $this->testingFramework->createRecord(
                    'tx_realty_objects',
                    ['deleted' => 1]
                ),
            ]);
        } catch (Tx_Oelib_Exception_AccessDenied $exception) {
        }

        self::assertEquals(
            'Status: 404 Not Found',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function checkAccessForImageUploadThrowsExceptionWithPleaseLoginMessageForNewObjectIfNoUserIsLoggedIn()
    {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('image_upload', ['showUid' => 0]);
    }

    /**
     * @test
     */
    public function checkAccessForImageUploadThrowsExceptionWithPleaseLoginMessageForAnExistingObjectIfNoUserIsLoggedIn(
    ) {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('image_upload', [
            'showUid' => $this->dummyObjectUid,
        ]);
    }

    /**
     * @test
     */
    public function checkAccessForImageUploadThrowsExceptionWithAccessDeniedMessageWhenLoggedInUserAttemptsToEditAnObjectHeDoesNotOwn(
    ) {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess(
            'image_upload',
            ['showUid' => $this->dummyObjectUid]
        );
    }

    /**
     * @test
     */
    public function header403IsSentWhenCheckAccessForImageUploadThrowsExceptionWithAccessDeniedMessage()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        try {
            $this->fixture->checkAccess(
                'image_upload',
                ['showUid' => $this->dummyObjectUid]
            );
        } catch (Tx_Oelib_Exception_AccessDenied $exception) {
        }

        self::assertEquals(
            'Status: 403 Forbidden',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function checkAccessForImageUploadDoesNotThrowAnExceptionIfTheObjectExistsAndTheUserIsOwner()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->dummyObjectUid,
            ['owner' => $user->getUid()]
        );

        $this->fixture->checkAccess(
            'image_upload',
            ['showUid' => $this->dummyObjectUid]
        );
    }

    ////////////////////////////////////////////////////
    // Tests concerning access to the my-objects view.
    ////////////////////////////////////////////////////

    /**
     * @test
     */
    public function checkAccessForMyObjectsThrowsExceptionWithPleaseLoginMessageForAnInvalidUidAndNoUserLoggedIn()
    {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('my_objects', [
            'delete' => $this->testingFramework->createRecord(
                'tx_realty_objects',
                ['deleted' => 1]
            ),
        ]);
    }

    /**
     * @test
     */
    public function checkAccessForMyObjectsThrowsExceptionWithObjectDoesNotExistMessageForAnInvalidObjectToDeleteUidAndAUserLoggedIn(
    ) {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('my_objects', [
            'delete' => $this->testingFramework->createRecord(
                'tx_realty_objects',
                ['deleted' => 1]
            ),
        ]);
    }

    /**
     * @test
     */
    public function header404IsSentWhenCheckAccessForMyObjectsThrowsExceptionWithObjectDoesNotExistMessage()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        try {
            $this->fixture->checkAccess('my_objects', [
                'delete' => $this->testingFramework->createRecord(
                    'tx_realty_objects',
                    ['deleted' => 1]
                ),
            ]);
        } catch (Tx_Oelib_Exception_AccessDenied $exception) {
        }

        self::assertEquals(
            'Status: 404 Not Found',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function checkAccessForMyObjectsThrowsExceptionWithPleaseLoginMessageIfNoUserIsLoggedIn()
    {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('my_objects', ['delete' => 0]);
    }

    /**
     * @test
     */
    public function checkAccessForMyObjectsThrowsExceptionWithPleaseLoginMessageWhenNotLoggedInUserAttemptsToDeleteAnObject(
    ) {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('my_objects', ['delete' => $this->dummyObjectUid]);
    }

    /**
     * @test
     */
    public function checkAccessForMyObjectsThrowsExceptionWithAccessDeniedMessageWhenLoggedInUserAttemptsToDeleteAnObjectHeDoesNotOwn(
    ) {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess(
            'my_objects',
            ['delete' => $this->dummyObjectUid]
        );
    }

    /**
     * @test
     */
    public function header403IsSentWhenCheckAccessForMyObjectsThrowsExceptionWithAccessDeniedMessage()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        try {
            $this->fixture->checkAccess(
                'my_objects',
                ['delete' => $this->dummyObjectUid]
            );
        } catch (Tx_Oelib_Exception_AccessDenied $exception) {
        }

        self::assertEquals(
            'Status: 403 Forbidden',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /**
     * @test
     */
    public function checkAccessForMyObjectsDoesNotThrowAnExceptionIfTheObjectToDeleteExistsAndTheOwnerIsLoggedIn()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->dummyObjectUid,
            ['owner' => $user->getUid()]
        );

        $this->fixture->checkAccess(
            'my_objects',
            ['delete' => $this->dummyObjectUid]
        );
    }

    /**
     * @test
     */
    public function checkAccessForMyObjectsDoesNotThrowAnExceptionIfNoObjectToDeleteIsSetAndTheUserIsLoggedIn()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->checkAccess('my_objects', ['delete' => 0]);
    }

    ////////////////////////////////////////////////
    // Tests concerning access to the single view.
    ////////////////////////////////////////////////

    /**
     * @test
     */
    public function checkAccessForSingleViewThrowsExceptionWithPleaseLoginMessageForNewObjectIfNoUserIsLoggedIn()
    {
        $this->expectException(\Tx_Oelib_Exception_AccessDenied::class);

        $this->fixture->checkAccess('single_view', []);
    }

    /**
     * @test
     */
    public function checkAccessForSingleViewDoesNotThrowAnExceptionIfTheObjectIsNewAndTheUserIsLoggedIn()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        $this->fixture->checkAccess('single_view', []);
    }

    //////////////////////////////////////////////
    // Test concerning access to any other view.
    //////////////////////////////////////////////

    /**
     * @test
     */
    public function checkAccessForOtherViewDoesNotThrowAnException()
    {
        $this->fixture->checkAccess('other', []);
    }
}
