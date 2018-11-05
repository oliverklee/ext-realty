<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_SingleViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_SingleView
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var tx_realty_Mapper_RealtyObject
     */
    private $realtyObjectMapper = null;

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

        $this->realtyObjectMapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);

        $this->fixture = new tx_realty_pi1_SingleView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $this->getFrontEndController()->cObj,
            true
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading,address,description,documents,furtherDescription,price,' .
            'overviewTable,imageThumbnails,addToFavoritesButton,' .
            'contactButton,offerer,status,printPageButton,backButton'
        );
        $this->dummyCityUid = $this->testingFramework->createRecord('tx_realty_cities');

        $pluginConfiguration = new Tx_Oelib_Configuration();
        Tx_Oelib_ConfigurationRegistry::getInstance()->set('plugin.tx_realty_pi1', $pluginConfiguration);
        $imagesConfiguration = new Tx_Oelib_Configuration();
        Tx_Oelib_ConfigurationRegistry::getInstance()->set('plugin.tx_realty_pi1.images', $imagesConfiguration);
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

    /////////////////////////////////////////////////////
    // Testing the conditions to render the single view
    /////////////////////////////////////////////////////

    /**
     * @test
     */
    public function singleViewReturnsEmptyResultForZeroShowUid()
    {
        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => 0])
        );
    }

    /**
     * @test
     */
    public function singleViewReturnsEmptyResultForShowUidOfDeletedRecord()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->setToDeleted();

        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewReturnsEmptyResultForShowUidOfHiddenRecordAndNoUserLoggedIn()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['hidden' => 1]);
        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewReturnsEmptyResultForShowUidOfHiddenRecordNonOwnerLoggedIn()
    {
        $userMapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class);
        /** @var tx_realty_Model_FrontEndUser $owner */
        $owner = $userMapper->getNewGhost();

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(
            [
                'hidden' => 1,
                'owner' => $owner->getUid(),
            ]
        );

        /** @var tx_realty_Model_FrontEndUser $otherUser */
        $otherUser = $userMapper->getNewGhost();
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($otherUser);

        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewReturnsNonEmptyResultForShowUidOfHiddenRecordOwnerLoggedIn()
    {
        $userMapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class);
        /** @var tx_realty_Model_FrontEndUser $owner */
        $owner = $userMapper->getNewGhost();
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(
            [
                'hidden' => 1,
                'owner' => $owner->getUid(),
            ]
        );
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($owner);

        self::assertNotEquals(
            '',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewReturnsNonEmptyResultForShowUidOfExistingRecord()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        self::assertNotEquals(
            '',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        self::assertNotContains(
            '###',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    ///////////////////////////////////////////////
    // Testing the different view parts displayed
    ///////////////////////////////////////////////

    /**
     * @test
     */
    public function singleViewDisplaysTheTitleOfARealtyObjectIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );

        self::assertContains(
            'foo',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysTheTitleOfARealtyObjectIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'description'
        );

        self::assertNotContains(
            'foo',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysTheDescriptionOfARealtyObjectIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['description' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'description'
        );

        self::assertContains(
            'foo',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysTheDescriptionOfARealtyObjectIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['description' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );

        self::assertNotContains(
            'foo',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysTheDocumentsOfARealtyObjectIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel([]);
        $realtyObject->addDocument('new document', 'readme.pdf');

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'documents'
        );

        self::assertContains(
            'new document',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysTheDocumentsOfARealtyObjectIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel([]);
        $realtyObject->addDocument('new document', 'readme.pdf');

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );

        self::assertNotContains(
            'new document',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysThePriceOfARealtyObjectIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(
            [
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
                'buying_price' => '123',
            ]
        );

        self::assertContains(
            '123',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysThePriceOfARealtyObjectIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(
            [
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
                'buying_price' => '123',
            ]
        );

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );

        self::assertNotContains(
            '123',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysTheEquipmentDescriptionOfARealtyObjectIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['equipment' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'furtherDescription'
        );

        self::assertContains(
            'foo',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysTheEquipmentDescriptionOfARealtyObjectIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['equipment' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );

        self::assertNotContains(
            'foo',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysTheAddToFavoritesButtonIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'addToFavoritesButton'
        );

        self::assertContains(
            'class="js-realty-favorites button singleViewAddToFavorites"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysTheAddToFavoritesButtonIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'backButton'
        );

        self::assertNotContains(
            'class="js-realty-favorites button singleViewAddToFavorites"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysThePrintPageButtonIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'printPageButton'
        );

        self::assertContains(
            'class="js-realty-print button printPage"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysThePrintPageButtonIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'addToFavoritesButton'
        );

        self::assertNotContains(
            'class="js-realty-print button printPage"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysTheBackButtonIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        self::assertContains(
            'class="js-realty-back button singleViewBack"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysTheBackButtonIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'printPageButton'
        );

        self::assertNotContains(
            'class="js-realty-back button singleViewBack"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplayingAnyOfTheActionButtonsHidesActionSubpart()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'header'
        );
        $this->fixture->render(['showUid' => $realtyObject->getUid()]);

        self::assertFalse(
            $this->fixture->isSubpartVisible('FIELD_WRAPPER_ACTIONBUTTONS')
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysTextPaneDivIfOnlyImagesShouldBeDisplayed()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'imageThumbnails'
        );

        self::assertNotContains(
            '<div class="text-pane',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysTextPaneDivAndWithImagesClassNameImagesAndTextShouldBeDisplayed()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading,imageThumbnails'
        );

        self::assertContains(
            '<div class="text-pane with-images',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysWithImagesClassNameIfOnlyTextShouldBeDisplayed()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'foo']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );

        self::assertNotContains(
            'with-images',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysContactButtonIfThisIsEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'test title']);

        $this->fixture->setConfigurationValue(
            'contactPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertContains(
            'class="button singleViewContact"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysContactButtonIfThisIsDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['title' => 'test title']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );
        $this->fixture->setConfigurationValue(
            'contactPID',
            $this->testingFramework->createFrontEndPage()
        );

        self::assertNotContains(
            'class="button singleViewContact"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysOffererInformationIfThisIsEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['phone_switchboard' => '12345']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'offerer'
        );
        $this->fixture->setConfigurationValue(
            'displayedContactInformation',
            'telephone'
        );

        self::assertContains(
            $this->fixture->translate('label_offerer'),
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysOffererInformationIfThisIsDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['phone_switchboard' => '12345']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );
        $this->fixture->setConfigurationValue(
            'displayedContactInformation',
            'telephone'
        );

        self::assertNotContains(
            $this->fixture->translate('label_offerer'),
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysStatusIfThisIsEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel([]);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'status'
        );

        self::assertContains(
            'tx-realty-pi1-status',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysStatusIfThisIsDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel([]);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );

        self::assertNotContains(
            'tx-realty-pi1-status',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysOverviewTableRowIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['has_air_conditioning' => '1']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'overviewTable'
        );
        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable',
            'has_air_conditioning'
        );

        self::assertContains(
            $this->fixture->translate('label_has_air_conditioning'),
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysOverviewTableRowIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['has_air_conditioning' => '1']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );
        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable',
            'has_air_conditioning'
        );

        self::assertNotContains(
            $this->fixture->translate('label_has_air_conditioning'),
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewDisplaysTheAddressOfARealtyObjectIfEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['zip' => '12345']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'address'
        );

        self::assertContains(
            '12345',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysTheAddressOfARealtyObjectIfDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(['zip' => '12345']);

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );

        self::assertNotContains(
            '12345',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /////////////////////////////////////////////
    // Tests for Google Maps in the single view
    /////////////////////////////////////////////

    /**
     * @test
     */
    public function singleViewDisplaysMapForGoogleMapsEnabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(
            [
                'has_coordinates' => true,
                'latitude' => 50.734343,
                'longitude' => 7.10211,
                'show_address' => true,
            ]
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'googleMaps'
        );

        self::assertContains(
            '<div id="tx_realty_map"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function singleViewNotDisplaysMapForGoogleMapsDisabled()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(
            [
                'has_coordinates' => true,
                'latitude' => 50.734343,
                'longitude' => 7.10211,
                'show_address' => true,
            ]
        );

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'heading'
        );

        self::assertNotContains(
            '<div id="tx_realty_map"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function googleMapsDoesNotLinkObjectTitleInMap()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(
            [
                'has_coordinates' => true,
                'latitude' => 50.734343,
                'longitude' => 7.10211,
                'show_address' => true,
            ]
        );

        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'googleMaps'
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
        self::assertNotContains(
            'href=',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /**
     * @test
     */
    public function singleViewForActivatedListViewGoogleMapsDoesNotShowGoogleMapsByDefault()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(
            [
                'has_coordinates' => true,
                'latitude' => 50.734343,
                'longitude' => 7.10211,
                'show_address' => true,
            ]
        );

        $this->fixture->setConfigurationValue('showGoogleMaps', 1);

        self::assertNotContains(
            '<div id="tx_realty_map"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    ///////////////////////////////////////////////////
    // Tests concerning the next and previous buttons
    ///////////////////////////////////////////////////

    /**
     * @test
     */
    public function singleViewForEnabledNextPreviousButtonsShowsNextPreviousButtonsSubpart()
    {
        $objectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $this->dummyCityUid]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $this->dummyCityUid]
        );
        $this->getFrontEndController()->cObj->data['pid'] = $this->testingFramework->createFrontEndPage();

        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );

        self::assertContains(
            'previousNextButtons',
            $this->fixture->render([
                'showUid' => $objectUid,
                'recordPosition' => 0,
                'listViewType' => 'realty_list',
                'listUid' => $this->testingFramework->createContentElement(),
            ])
        );
    }

    /**
     * @test
     */
    public function singleViewForEnabledNextPreviousButtonsButNotSetDisplayPartHidesNextPreviousButtonsSubpart()
    {
        $objectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $this->dummyCityUid]
        );
        $this->getFrontEndController()->cObj->data['pid'] = $this->testingFramework->createFrontEndPage();

        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            true
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            ''
        );

        self::assertNotContains(
            'previousNextButtons',
            $this->fixture->render([
                'showUid' => $objectUid,
                'recordPosition' => 0,
                'listViewType' => 'realty_list',
                'listUid' => $this->testingFramework->createContentElement(),
            ])
        );
    }

    /**
     * @test
     */
    public function singleViewForDisabledNextPreviousButtonsHidesNextPreviousButtonsSubpart()
    {
        $objectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['city' => $this->dummyCityUid]
        );
        $this->getFrontEndController()->cObj->data['pid'] = $this->testingFramework->createFrontEndPage();

        $this->fixture->setConfigurationValue(
            'enableNextPreviousButtons',
            false
        );
        $this->fixture->setConfigurationValue(
            'singleViewPartsToDisplay',
            'nextPreviousButtons'
        );

        self::assertNotContains(
            'previousNextButtons',
            $this->fixture->render([
                'showUid' => $objectUid,
                'recordPosition' => 0,
                'listViewType' => 'realty_list',
            ])
        );
    }
}
