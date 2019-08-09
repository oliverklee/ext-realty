<?php

use OliverKlee\PhpUnit\TestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_OffererListTest extends TestCase
{
    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var tx_realty_offererList
     */
    private $subject = null;

    /**
     * @var int FE user group UID
     */
    private $feUserGroupUid;

    /**
     * @var int FE user UID
     */
    private $offererUid;

    /**
     * @var string FE user group name
     */
    const FE_USER_GROUP_NAME = 'test offerers';

    /**
     * @var string FE user name
     */
    const FE_USER_NAME = 'test_offerer';

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        $this->createDummyRecords();

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->subject = new tx_realty_offererList(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'what_to_display' => 'offerer_list',
                'userGroupsForOffererList' => $this->feUserGroupUid,
                'displayedContactInformation' => 'usergroup,offerer_label',
            ],
            $frontEndController->cObj,
            // TRUE enables the test mode
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
     * Creates a dummy user group and a dummy offerer record in the database.
     *
     * @return void
     */
    private function createDummyRecords()
    {
        $this->feUserGroupUid = $this->testingFramework->createFrontEndUserGroup(
            ['title' => self::FE_USER_GROUP_NAME]
        );
        $this->offererUid = $this->testingFramework->createFrontEndUser(
            $this->feUserGroupUid,
            ['username' => self::FE_USER_NAME]
        );
    }

    /////////////////////////////////////////////////////////////
    // Testing the configuration for the user groups to display
    /////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function offererListDisplaysOffererWhoIsOnlyInTheConfiguredGroup()
    {
        self::assertContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysNoUnreplacedMarkers()
    {
        self::assertNotContains(
            '###',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListNotDisplaysDeletedOffererAlthoughHeIsInTheConfiguredGroup()
    {
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['deleted' => 1]
        );

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysOffererWhoIsInTwoGroupsWithTheConfiguredGroupFirst()
    {
        $otherGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['usergroup' => $this->feUserGroupUid . ',' . $otherGroupUid]
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysOffererWhoIsInThreeGroupsWithTheConfiguredGroupSecond()
    {
        $firstGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $thirdGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            [
                'usergroup' => $firstGroupUid . ',' .
                    $this->feUserGroupUid . ',' . $thirdGroupUid,
            ]
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysOffererWhoIsInThreeGroupsWithTheConfiguredGroupLast()
    {
        $firstGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $secondGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            [
                'usergroup' => $firstGroupUid . ',' .
                    $secondGroupUid . ',' . $this->feUserGroupUid,
            ]
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysTwoOfferersWhoAreInTheSameConfiguredGroup()
    {
        $this->testingFramework->createFrontEndUser(
            $this->feUserGroupUid,
            ['username' => 'other user']
        );

        $output = $this->subject->render();
        self::assertContains(
            self::FE_USER_NAME,
            $output
        );
        self::assertContains(
            'other user',
            $output
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysTwoOfferersWhoAreInDifferentConfiguredGroups()
    {
        $secondFeUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $this->subject->setConfigurationValue(
            'userGroupsForOffererList',
            $this->feUserGroupUid . ',' . $secondFeUserGroupUid
        );
        $this->testingFramework->createFrontEndUser(
            $secondFeUserGroupUid,
            ['username' => 'other user']
        );

        $output = $this->subject->render();
        self::assertContains(
            self::FE_USER_NAME,
            $output
        );
        self::assertContains(
            'other user',
            $output
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysFeUserRecordIfNoUserGroupRestrictionIsConfigured()
    {
        $this->subject->setConfigurationValue('userGroupsForOffererList', '');
        $this->testingFramework->createFrontEndUser(
            '',
            ['username' => 'other user']
        );

        $output = $this->subject->render();
        self::assertContains(
            self::FE_USER_NAME,
            $output
        );
        self::assertContains(
            'other user',
            $output
        );
    }

    /**
     * @test
     */
    public function offererDisplaysGrouplessFeUserRecordIfNoUserGroupRestrictionIsConfigured()
    {
        // This test is to document that there is no crash if there is such a
        // record in the database.
        $this->subject->setConfigurationValue('userGroupsForOffererList', '');
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['usergroup' => '']
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysTwoOfferersWhoAreInDifferentConfiguredGroupsOrderedByGroupUid()
    {
        $secondFeUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $this->subject->setConfigurationValue(
            'userGroupsForOffererList',
            $this->feUserGroupUid . ',' . $secondFeUserGroupUid
        );
        $this->testingFramework->createFrontEndUser(
            $secondFeUserGroupUid,
            ['username' => 'other user']
        );

        $result = $this->subject->render();
        self::assertSame(
            $this->feUserGroupUid < $secondFeUserGroupUid,
            strpos($result, self::FE_USER_NAME) < strpos($result, 'other user')
        );
    }

    /**
     * @test
     */
    public function offererListNotDisplaysOffererWhoIsOnlyInANonConfiguredGroup()
    {
        $this->testingFramework->createFrontEndUser(
            '',
            ['username' => 'other user']
        );

        self::assertNotContains(
            'other user',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysNoResultViewForNoOffererInTheConfiguredGroup()
    {
        $this->subject->setConfigurationValue(
            'userGroupsForOffererList',
            $this->testingFramework->createFrontEndUserGroup()
        );

        self::assertContains(
            'noresults',
            $this->subject->render()
        );
    }

    //////////////////////////////
    // Testing the offerer label
    //////////////////////////////

    /**
     * @test
     */
    public function offererListItemContainsTestUserName()
    {
        self::assertContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersName()
    {
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['name' => 'Mr. Test']
        );

        self::assertContains(
            'Mr. Test',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheUserNameIfTheOffererNameIsSet()
    {
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['name' => 'Mr. Test']
        );

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersLastNameIfNoFirstNameIsSet()
    {
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['last_name' => 'User']
        );

        self::assertContains(
            'User',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersLastNameWithLeadingCommaIfNoCompanyIsSet()
    {
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['last_name' => 'User']
        );

        self::assertNotContains(
            ', User',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersFirstAndLastName()
    {
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['first_name' => 'Test', 'last_name' => 'User']
        );

        self::assertContains(
            'Test User',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheUserNameIfLastNameIsSet()
    {
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['last_name' => 'User']
        );

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    //////////////////////////////////////
    // Testing the displayed user groups
    //////////////////////////////////////

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersFirstUserGroupNameForOffererWithOneUserGroup()
    {
        self::assertContains(
            self::FE_USER_GROUP_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersFirstUserGroupNameForTheOfferersFirstGroupMatchingConfiguration()
    {
        $otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
            ['title' => 'other group']
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['usergroup' => $this->feUserGroupUid . ',' . $otherGroupUid]
        );

        self::assertContains(
            self::FE_USER_GROUP_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersSecondUserGroupNameForTheOfferersSecondGroupMatchingConfiguration(
    ) {
        $otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
            ['title' => 'other group']
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['usergroup' => $otherGroupUid . ',' . $this->feUserGroupUid]
        );

        self::assertContains(
            self::FE_USER_GROUP_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersFirstUserGroupNameForTheOfferersSecondGroupMatchingConfiguration(
    ) {
        $otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
            ['title' => 'other group']
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['usergroup' => $otherGroupUid . ',' . $this->feUserGroupUid]
        );

        self::assertNotContains(
            'other group',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsNoBranchesIfTheOfferersFirstUserGroupIsNameless()
    {
        $this->testingFramework->changeRecord(
            'fe_groups',
            $this->feUserGroupUid,
            ['title' => '']
        );

        self::assertNotContains(
            '()',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersFirstUserGroupNameWhenACompanyIsSetButHiddenByConfiguration()
    {
        $otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
            ['title' => 'other group']
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            [
                'usergroup' => $this->feUserGroupUid . ',' . $otherGroupUid,
                'company' => 'Test Company',
            ]
        );

        self::assertContains(
            self::FE_USER_GROUP_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsOfferersFirstUserGroupBeforeTitleIfWhatToDisplayIsSingleView()
    {
        $this->subject->setConfigurationValue('what_to_display', 'single_view');

        $result = $this->subject->render();
        self::assertGreaterThan(
            strpos($result, self::FE_USER_GROUP_NAME),
            strpos($result, self::FE_USER_NAME)
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsOfferersFirstUserGroupAfterTitleIfWhatToDisplayIsOffererList()
    {
        $result = $this->subject->render();
        self::assertGreaterThan(
            strpos($result, self::FE_USER_NAME),
            strpos($result, self::FE_USER_GROUP_NAME)
        );
    }

    ////////////////////////////////////////////////////////////////////
    // Testing conditionally displayed information for normal offerers
    ////////////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersUserNameIfTheConfigurationIsEmpty()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersUserGroupIfConfiguredButNeitherNameNorCompanyEnabled()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'usergroup'
        );

        self::assertNotContains(
            self::FE_USER_GROUP_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersUserGroupIfUserGroupAndNameAreEnabledByConfiguration()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'usergroup,offerer_label'
        );

        self::assertContains(
            self::FE_USER_GROUP_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersUserGroupIfNotConfigured()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');

        self::assertNotContains(
            self::FE_USER_GROUP_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersCompanyIfConfigured()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'company'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['company' => 'Test Company']
        );

        self::assertContains(
            'Test Company',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersCompanyIfNotConfigured()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['company' => 'Test Company']
        );

        self::assertNotContains(
            'Test Company',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsCompanyWithClassEmphasizedForEnabledCompany()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'company'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['company' => 'Test Company']
        );

        self::assertContains(
            'class="emphasized">Test Company',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersLabelIfConfigured()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label'
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersLabelIfNotConfigured()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsOffererTitleWithoutClassEmphasizedForEnabledCompany()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'company,offerer_label'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['company' => 'Test Company']
        );

        self::assertNotContains(
            'class="emphasized">' . self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsFirstUserGroupWithoutClassEmphasizedForEnabledCompany()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'company,offerer_label,usergroup'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['company' => 'Test Company']
        );

        self::assertNotContains(
            'class="emphasized">(' . self::FE_USER_GROUP_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsOffererTitleWithClassEmphasizedForDisabledCompany()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['company' => 'Test Company']
        );

        self::assertContains(
            'class="emphasized">' . self::FE_USER_NAME,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersStreetIfConfigured()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'street'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['address' => 'Main Street']
        );

        self::assertContains(
            'Main Street',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersStreetIfNotConfigured()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['address' => 'Main Street']
        );

        self::assertNotContains(
            'Main Street',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersCityAndZipIfConfigured()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'city'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['city' => 'City Title', 'zip' => '12345']
        );

        self::assertContains(
            '12345 City Title',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersCityIfNotConfigured()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['city' => 'City Title']
        );

        self::assertNotContains(
            'City Title',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsCityWrapperContentIfCityNotConfigured()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', 'offerer_label');
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['city' => 'City Title', 'zip' => '99999']
        );

        self::assertNotContains(
            '<dd>',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsCityWrapperContentIfCityIsConfiguredButEmpty()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label,city'
        );

        self::assertNotContains(
            '<dd>',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersPhoneNumberIfConfigured()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'telephone'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['telephone' => '1234-56789']
        );

        self::assertContains(
            '1234-56789',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersPhoneNumberIfNotConfigured()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['telephone' => '1234-56789']
        );

        self::assertNotContains(
            '1234-56789',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsHtmlSpecialCharedPhoneNumber()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'telephone'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['telephone' => '<123>3455']
        );

        self::assertContains(
            htmlspecialchars('<123>3455'),
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersEmailIfConfigured()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'email'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['email' => 'offerer@example.com']
        );

        self::assertContains(
            'offerer@example.com',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersEmailIfNotConfigured()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['email' => 'offerer@example.com']
        );

        self::assertNotContains(
            'offerer@example.com',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersLinkedWebsiteIfConfigured()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'www'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['www' => 'http://www.example.com']
        );

        self::assertContains(
            '<a href="http://www.example.com"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersLinkedWebsiteWithEscapedAmpersand()
    {
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'www'
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['www' => 'http://www.example.com/?a=b&c=d']
        );

        self::assertContains(
            '<a href="http://www.example.com/?a=b&amp;c=d"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersWebsiteIfNotConfigured()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['www' => 'http://www.example.com']
        );

        self::assertNotContains(
            'http://www.example.com',
            $this->subject->render()
        );
    }

    //////////////////////////////////////////////////////////////////////////////
    // Testing the configuration to display special contact data for some groups
    //////////////////////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function offererListItemContainsTheSpecialOfferersPhoneNumberIfConfiguredAndOffererIsInSpecialGroup()
    {
        $this->subject->setConfigurationValue('displayedContactInformation', '');
        $this->subject->setConfigurationValue('displayedContactInformationSpecial', 'telephone');
        $this->subject->setConfigurationValue('groupsWithSpeciallyDisplayedContactInformation', $this->feUserGroupUid);
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['telephone' => '1234-56789']
        );

        self::assertContains(
            '1234-56789',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersPhoneNumberIfConfiguredForSpecialOfferersAndOffererIsNormal()
    {
        $this->subject->setConfigurationValue('displayedContactInformationSpecial', 'telephone');

        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['telephone' => '1234-56789']
        );

        self::assertNotContains(
            '1234-56789',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheSpecialOfferersPhoneNumberIfNotConfiguredAndOffererIsInSpecialGroup()
    {
        $this->subject->setConfigurationValue('displayedContactInformationSpecial', '');
        $this->subject->setConfigurationValue('groupsWithSpeciallyDisplayedContactInformation', $this->feUserGroupUid);
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['telephone' => '1234-56789']
        );

        self::assertNotContains(
            '1234-56789',
            $this->subject->render()
        );
    }

    //////////////////////////////////////////////////////
    // Testing the link to the "objects by offerer" list
    //////////////////////////////////////////////////////

    /**
     * @test
     */
    public function offererListItemContainsLinkToTheObjectsByOffererListIfPageConfigured()
    {
        $this->subject->setConfigurationValue(
            'objectsByOwnerPID',
            $this->testingFramework->createFrontEndPage()
        );
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'objects_by_owner_link'
        );

        self::assertContains(
            'button objectsByOwner',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemIfPageConfiguredAndConfigurationSetContainsConfiguredPageUidInTheLinkToTheObjectsByOffererList(
    ) {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'objects_by_owner_link'
        );

        self::assertContains(
            'id=' . $pageUid,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemIfPageConfiguredAndConfigurationSetContainsOwnerUidInTheLinkToTheObjectsByOffererList(
    ) {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'objects_by_owner_link'
        );

        $result = $this->subject->render();

        self::assertContains('owner', $result);
        self::assertContains('=' . $this->offererUid, $result);
    }

    /**
     * @test
     */
    public function offererListItemForDisabledObjectsByOwnerLinkHidesLinkToTheOffererList()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label'
        );

        self::assertNotContains(
            'button objectsByOwner',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemForDisabledSpecialObjectsByOwnerLinkAndOffererInSpecialGroupHidesLinkToTheOffererList(
    ) {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label'
        );
        $this->subject->setConfigurationValue(
            'groupsWithSpeciallyDisplayedContactInformation',
            $this->feUserGroupUid
        );

        self::assertNotContains(
            'button objectsByOwner',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemForEnabledObjectsByOwnerLinkAndOffererNotInSpecialGroupShowsLinkToTheOffererList()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->subject->setConfigurationValue(
            'displayedContactInformationSpecial',
            'offerer_label'
        );
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label, objects_by_owner_link'
        );
        $this->subject->setConfigurationValue(
            'groupsWithSpeciallyDisplayedContactInformation',
            $this->testingFramework->getAutoIncrement('fe_groups')
        );

        self::assertContains(
            'button objectsByOwner',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemForEnabledSpecialOwnerLinkAndOffererInSpecialGroupShowsLinkToTheOffererList()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->subject->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->subject->setConfigurationValue(
            'displayedContactInformationSpecial',
            'offerer_label, objects_by_owner_link'
        );
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'offerer_label'
        );
        $this->subject->setConfigurationValue(
            'groupsWithSpeciallyDisplayedContactInformation',
            $this->feUserGroupUid
        );

        self::assertContains(
            'button objectsByOwner',
            $this->subject->render()
        );
    }

    /////////////////////////////////////////////
    // Testing to get only one list item by UID
    /////////////////////////////////////////////

    /**
     * @test
     */
    public function renderOneItemReturnsHtmlForTheOffererWithTheProvidedUid()
    {
        self::assertContains(
            self::FE_USER_NAME,
            $this->subject->renderOneItem($this->offererUid)
        );
    }

    /**
     * @test
     */
    public function renderOneItemReturnsEmptyStringForUidOfDisabledOfferer()
    {
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['deleted' => 1]
        );

        self::assertEquals(
            '',
            $this->subject->renderOneItem($this->offererUid)
        );
    }

    /////////////////////////////////////////////////////////////////
    // Testing to get only one list item with a data array provided
    /////////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function renderOneItemWithTheDataProvidedReturnsHtmlForTheListItemForValidData()
    {
        self::assertContains(
            'test offerer',
            $this->subject->renderOneItemWithTheDataProvided(['name' => 'test offerer'])
        );
    }

    /**
     * @test
     */
    public function renderOneItemWithTheDataProvidedReturnsAnEmptyStringForEmptyData()
    {
        self::assertEquals(
            '',
            $this->subject->renderOneItemWithTheDataProvided([])
        );
    }

    /**
     * @test
     */
    public function renderOneItemWithTheDataProvidedReturnsAnEmptyStringForInvalidData()
    {
        self::assertEquals(
            '',
            $this->subject->renderOneItemWithTheDataProvided(['foo' => 'bar'])
        );
    }

    /**
     * @test
     */
    public function renderOneItemWithTheDataProvidedReturnsHtmlWithoutTheLinkToTheObjectsByOwnerList()
    {
        self::assertNotContains(
            'class="button objectsByOwner"',
            $this->subject->renderOneItemWithTheDataProvided(['name' => 'test offerer'])
        );
    }

    /**
     * @test
     */
    public function renderOneItemWithTheDataProvidedForUsergroupProvidedThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);

        $this->subject->renderOneItemWithTheDataProvided(['usergroup' => 1]);
    }

    /////////////////////////////////////////////////////
    // Tests concerning the sorting of the offerer list
    /////////////////////////////////////////////////////

    /**
     * @test
     */
    public function offererListIsSortedByCity()
    {
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            ['city' => 'City A']
        );
        $this->testingFramework->createFrontEndUser(
            $this->feUserGroupUid,
            [
                'username' => 'Testuser 2',
                'city' => 'City B',
            ]
        );
        $this->subject->setConfigurationValue(
            'displayedContactInformation',
            'city'
        );

        self::assertRegExp(
            '/City A.*City B/s',
            $this->subject->render()
        );
    }
}
