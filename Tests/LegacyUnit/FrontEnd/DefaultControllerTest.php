<?php

use OliverKlee\PhpUnit\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_DefaultControllerTest extends TestCase
{
    /**
     * @var tx_realty_pi1
     */
    private $subject = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int login PID
     */
    private $loginPid = 0;

    /**
     * @var int system folder PID
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
     * @var int static_info_tables UID of Germany
     */
    const DE = 54;

    protected function setUp()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')->setAsBoolean('enableConfigCheck', false);

        Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        $configurationRegistry = Tx_Oelib_ConfigurationRegistry::getInstance();
        $configurationRegistry->set(
            'plugin.tx_realty_pi1.views.realty_list',
            new Tx_Oelib_Configuration()
        );
        $configurationRegistry->set(
            'plugin.tx_realty_pi1.views.single_view',
            new Tx_Oelib_Configuration()
        );
        $configurationRegistry->set(
            'plugin.tx_realty_pi1.views.my_objects',
            new Tx_Oelib_Configuration()
        );
        $configurationRegistry->set(
            'plugin.tx_realty_pi1.views.offerer_list',
            new Tx_Oelib_Configuration()
        );
        $configurationRegistry->set(
            'plugin.tx_realty_pi1.views.favorites',
            new Tx_Oelib_Configuration()
        );
        $configurationRegistry->set(
            'plugin.tx_realty_pi1.views.objects_by_owner',
            new Tx_Oelib_Configuration()
        );

        $this->createDummyPages();
        $this->createDummyObjects();

        // True enables the test mode which inhibits the FE editor mkforms object from being created.
        $this->subject = new tx_realty_pi1(true);
        // This passed array with configuration values becomes part of
        // $this->subject->conf. "conf" is inherited from AbstractPlugin and needs
        // to contain "pidList". "pidList" is none of our configuration values
        // but if cObj->currentRecord is set, "pidList" is set to our
        // configuration value "pages".
        // As we are in BE mode, "pidList" needs to be set directly.
        // The template file also needs to be included explicitly.
        $this->subject->init([
            'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
            'pages' => $this->systemFolderPid,
            'showGoogleMaps' => 0,
            'defaultCountryUID' => self::DE,
            'displayedContactInformation' => 'company,offerer_label,telephone',
        ]);
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    ///////////////////////
    // Utility functions.
    ///////////////////////

    /**
     * Creates dummy FE pages (like login and single view).
     *
     * @return void
     */
    private function createDummyPages()
    {
        $this->loginPid = $this->testingFramework->createFrontEndPage();
        $this->systemFolderPid = $this->testingFramework->createSystemFolder(1);
    }

    /**
     * Creates dummy realty objects in the DB.
     *
     * @return void
     */
    private function createDummyObjects()
    {
        $this->firstRealtyUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => self::$firstObjectTitle,
                'object_number' => self::$firstObjectNumber,
                'pid' => $this->systemFolderPid,
                'teaser' => '',
                'has_air_conditioning' => '0',
                'has_pool' => '0',
                'has_community_pool' => '0',
                'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
            ]
        );
    }

    //////////////////////////////////////
    // Tests for the configuration check
    //////////////////////////////////////

    /**
     * @test
     */
    public function configurationCheckIsActiveWhenEnabled()
    {
        // The configuration check is created during initialization, therefore
        // the object to test is recreated for this test.
        Tx_Oelib_ConfigurationProxy::getInstance('realty')->setAsBoolean('enableConfigCheck', true);
        $subject = new tx_realty_pi1();
        $subject->init([
            'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
            'pages' => $this->systemFolderPid,
        ]);
        // ensures there is at least one configuration error to report
        $subject->setConfigurationValue('isStaticTemplateLoaded', false);

        self::assertContains(
            'Configuration check warning',
            $subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function configurationCheckIsNotActiveWhenDisabled()
    {
        // The configuration check is created during initialization, therefore
        // the object to test is recreated for this test.
        Tx_Oelib_ConfigurationProxy::getInstance('realty')->setAsBoolean('enableConfigCheck', false);
        $subject = new tx_realty_pi1();
        $subject->init([
            'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
            'pages' => $this->systemFolderPid,
        ]);
        // ensures there is at least one configuration error to report
        $subject->setConfigurationValue('isStaticTemplateLoaded', false);

        self::assertNotContains(
            'Configuration check warning',
            $subject->main('', [])
        );
    }

    //////////////////////////////////////
    // Tests for the basic functionality
    //////////////////////////////////////

    /**
     * @test
     */
    public function pi1MustBeInitialized()
    {
        self::assertNotNull(
            $this->subject
        );
        self::assertTrue(
            $this->subject->isInitialized()
        );
    }

    ////////////////////////////////////////////////
    // Tests for the access-restricted single view
    ////////////////////////////////////////////////

    /**
     * @test
     */
    public function accessToSingleViewIsAllowedWithoutLoginPerDefault()
    {
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser(null);

        self::assertTrue(
            $this->subject->isAccessToSingleViewPageAllowed()
        );
    }

    /**
     * @test
     */
    public function accessToSingleViewIsAllowedWithLoginPerDefault()
    {
        Tx_Oelib_FrontEndLoginManager::getInstance()
            ->logInUser(new tx_realty_Model_FrontEndUser());

        self::assertTrue(
            $this->subject->isAccessToSingleViewPageAllowed()
        );
    }

    /**
     * @test
     */
    public function accessToSingleViewIsAllowedWithoutLoginIfNotDeniedPerConfiguration()
    {
        $this->subject->setConfigurationValue('requireLoginForSingleViewPage', 0);
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser(null);

        self::assertTrue(
            $this->subject->isAccessToSingleViewPageAllowed()
        );
    }

    /**
     * @test
     */
    public function accessToSingleViewIsAllowedWithLoginIfNotDeniedPerConfiguration()
    {
        $this->subject->setConfigurationValue('requireLoginForSingleViewPage', 0);
        Tx_Oelib_FrontEndLoginManager::getInstance()
            ->logInUser(new tx_realty_Model_FrontEndUser());

        self::assertTrue(
            $this->subject->isAccessToSingleViewPageAllowed()
        );
    }

    /**
     * @test
     */
    public function accessToSingleViewIsDeniedWithoutLoginIfDeniedPerConfiguration()
    {
        $this->subject->setConfigurationValue('requireLoginForSingleViewPage', 1);
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser(null);

        self::assertFalse(
            $this->subject->isAccessToSingleViewPageAllowed()
        );
    }

    /**
     * @test
     */
    public function accessToSingleViewIsAllowedWithLoginIfDeniedPerConfiguration()
    {
        $this->subject->setConfigurationValue('requireLoginForSingleViewPage', 1);
        Tx_Oelib_FrontEndLoginManager::getInstance()
            ->logInUser(new tx_realty_Model_FrontEndUser());

        self::assertTrue(
            $this->subject->isAccessToSingleViewPageAllowed()
        );
    }

    ////////////////////////////
    // Testing the single view
    ////////////////////////////

    /**
     * @test
     */
    public function singleViewIsDisplayedForValidRealtyObjectAndAccessAllowed()
    {
        $this->subject->setConfigurationValue('what_to_display', 'single_view');
        $this->subject->piVars['showUid'] = $this->firstRealtyUid;

        self::assertContains(
            'class="single-view"',
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function noResultViewIsDisplayedForRenderingTheSingleViewOfNonExistentObject()
    {
        $this->subject->setConfigurationValue('what_to_display', 'single_view');
        $this->subject->piVars['showUid'] = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['deleted' => 1]
        );

        self::assertContains(
            'class="noresults"',
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function errorMessageIsDisplayedForRenderingTheSingleViewOfNonExistentObject()
    {
        $this->subject->setConfigurationValue('what_to_display', 'single_view');
        $this->subject->piVars['showUid'] = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['deleted' => 1]
        );

        self::assertContains(
            $this->subject->translate('message_noResultsFound_single_view'),
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function errorMessageIsDisplayedForRenderingTheSingleViewOfHiddenObject()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['hidden' => 1]
        );
        $this->subject->setConfigurationValue('what_to_display', 'single_view');
        $this->subject->piVars['showUid'] = $this->firstRealtyUid;

        self::assertContains(
            $this->subject->translate('message_noResultsFound_single_view'),
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function errorMessageIsDisplayedForRenderingTheSingleViewOfDeletedObject()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            ['deleted' => 1]
        );
        $this->subject->setConfigurationValue('what_to_display', 'single_view');
        $this->subject->piVars['showUid'] = $this->firstRealtyUid;

        self::assertContains(
            $this->subject->translate('message_noResultsFound_single_view'),
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function errorMessageIsDisplayedForRenderingTheSingleViewOfHiddenObjectForLoggedInNonOwner()
    {
        Tx_Oelib_FrontEndLoginManager::getInstance()
            ->logInUser(new tx_realty_Model_FrontEndUser());

        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->firstRealtyUid,
            [
                'hidden' => 1,
                'owner' => $this->testingFramework->createFrontEndUser(),
            ]
        );
        $this->subject->setConfigurationValue('what_to_display', 'single_view');
        $this->subject->piVars['showUid'] = $this->firstRealtyUid;

        self::assertContains(
            $this->subject->translate('message_noResultsFound_single_view'),
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function headerIsSetIfRenderingTheSingleViewLeadsToNoResultsMessage()
    {
        $this->subject->setConfigurationValue('what_to_display', 'single_view');
        $this->subject->piVars['showUid'] = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['deleted' => 1]
        );
        $this->subject->main('', []);

        self::assertEquals(
            'Status: 404 Not Found',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /////////////////////////////////////////////////////////
    // Tests concerning the access to the "my objects" list.
    /////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function accessToMyObjectsViewIsForbiddenForNotLoggedInUser()
    {
        $this->subject->setConfigurationValue('what_to_display', 'my_objects');
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        $output = $this->subject->main('', []);
        self::assertContains(
            $this->subject->translate('message_please_login'),
            $output
        );

        self::assertContains(
            '?id=' . $this->loginPid,
            $output
        );
    }

    /**
     * @test
     */
    public function accessToMyObjectsViewContainsRedirectUrlWithPidIfAccessDenied()
    {
        $myObjectsPid = $this->testingFramework->createFrontEndPage();
        $this->testingFramework->createFakeFrontEnd($myObjectsPid);
        $this->subject->setConfigurationValue('what_to_display', 'my_objects');
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        $output = $this->subject->main('', []);
        self::assertContains(
            'redirect_url',
            $output
        );

        self::assertContains(
            urlencode('?id=' . $myObjectsPid),
            $output
        );
    }

    /**
     * @test
     */
    public function headerIsSentWhenTheMyObjectsViewShowsPleaseLoginMessage()
    {
        $this->subject->setConfigurationValue('what_to_display', 'my_objects');
        $this->subject->setConfigurationValue('loginPID', $this->loginPid);

        self::assertContains(
            $this->subject->translate('message_please_login'),
            $this->subject->main('', [])
        );

        self::assertEquals(
            'Status: 403 Forbidden',
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
        );
    }

    /////////////////////////////
    // Testing the offerer list
    /////////////////////////////

    /**
     * @test
     */
    public function offererListIsDisplayedIfWhatToDisplayIsOffererList()
    {
        $groupId = $this->testingFramework->createFrontEndUserGroup();
        $this->testingFramework->createFrontEndUser($groupId);

        $this->subject->setConfigurationValue('what_to_display', 'offerer_list');
        $this->subject->setConfigurationValue('userGroupsForOffererList', $groupId);

        self::assertContains(
            'offerer-list',
            $this->subject->main('', [])
        );
    }

    ////////////////////////////////////
    // Tests concerning the list views
    ////////////////////////////////////

    /**
     * @test
     */
    public function forNoWhatToDisplaySetRealtyListViewWillBeRendered()
    {
        $this->subject->setConfigurationValue('what_to_display', '');

        self::assertContains(
            $this->subject->translate('label_weofferyou'),
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function realtyListViewCanBeRendered()
    {
        $this->subject->setConfigurationValue('what_to_display', 'realty_list');

        self::assertContains(
            $this->subject->translate('label_weofferyou'),
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function favoritesViewCanBeRendered()
    {
        $this->subject->setConfigurationValue('what_to_display', 'favorites');

        self::assertContains(
            $this->subject->translate('label_yourfavorites'),
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function myObjectsViewCanBeRendered()
    {
        $this->subject->setConfigurationValue('what_to_display', 'my_objects');

        $user = new tx_realty_Model_FrontEndUser();
        $user->setData([]);
        Tx_Oelib_FrontEndLoginManager::getInstance()->logInUser($user);

        self::assertContains(
            $this->subject->translate('label_your_objects'),
            $this->subject->main('', [])
        );
    }

    /**
     * @test
     */
    public function objectByOwnerViewCanBeRendered()
    {
        $this->subject->setConfigurationValue(
            'what_to_display',
            'objects_by_owner'
        );

        self::assertContains(
            $this->subject->translate('label_sorry'),
            $this->subject->main('', [])
        );
    }
}
