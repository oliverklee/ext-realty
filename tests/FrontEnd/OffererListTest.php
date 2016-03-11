<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_OffererListTest extends Tx_Phpunit_TestCase
{
    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var tx_realty_offererList
     */
    private $fixture = null;

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
        $this->fixture = new tx_realty_offererList(
            array(
                'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
                'what_to_display' => 'offerer_list',
                'userGroupsForOffererList' => $this->feUserGroupUid,
                'displayedContactInformation' => 'usergroup,offerer_label',
            ),
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
            array('title' => self::FE_USER_GROUP_NAME)
        );
        $this->offererUid = $this->testingFramework->createFrontEndUser(
            $this->feUserGroupUid, array('username' => self::FE_USER_NAME)
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
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysNoUnreplacedMarkers()
    {
        self::assertNotContains(
            '###',
            $this->fixture->render()
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
            array('deleted' => 1)
        );

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->fixture->render()
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
            array('usergroup' => $this->feUserGroupUid . ',' . $otherGroupUid)
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->fixture->render()
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
            'fe_users', $this->offererUid, array(
                'usergroup' => $firstGroupUid . ',' .
                    $this->feUserGroupUid . ',' . $thirdGroupUid
                )
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->fixture->render()
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
            'fe_users', $this->offererUid, array(
                'usergroup' => $firstGroupUid . ',' .
                    $secondGroupUid . ',' . $this->feUserGroupUid
                )
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysTwoOfferersWhoAreInTheSameConfiguredGroup()
    {
        $this->testingFramework->createFrontEndUser(
            $this->feUserGroupUid, array('username' => 'other user')
        );

        $output = $this->fixture->render();
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
        $this->fixture->setConfigurationValue(
            'userGroupsForOffererList',
            $this->feUserGroupUid . ',' . $secondFeUserGroupUid
        );
        $this->testingFramework->createFrontEndUser(
            $secondFeUserGroupUid, array('username' => 'other user')
        );

        $output = $this->fixture->render();
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
        $this->fixture->setConfigurationValue('userGroupsForOffererList', '');
        $this->testingFramework->createFrontEndUser(
            '', array('username' => 'other user')
        );

        $output = $this->fixture->render();
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
        $this->fixture->setConfigurationValue('userGroupsForOffererList', '');
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('usergroup' => '')
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysTwoOfferersWhoAreInDifferentConfiguredGroupsOrderedByGroupUid()
    {
        $secondFeUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $this->fixture->setConfigurationValue(
            'userGroupsForOffererList',
            $this->feUserGroupUid . ',' . $secondFeUserGroupUid
        );
        $this->testingFramework->createFrontEndUser(
            $secondFeUserGroupUid, array('username' => 'other user')
        );

        $result = $this->fixture->render();
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
            '', array('username' => 'other user')
        );

        self::assertNotContains(
            'other user',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListDisplaysNoResultViewForNoOffererInTheConfiguredGroup()
    {
        $this->fixture->setConfigurationValue(
            'userGroupsForOffererList',
            $this->testingFramework->createFrontEndUserGroup()
        );

        self::assertContains(
            'noresults',
            $this->fixture->render()
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
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersName()
    {
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('name' => 'Mr. Test')
        );

        self::assertContains(
            'Mr. Test',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheUserNameIfTheOffererNameIsSet()
    {
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('name' => 'Mr. Test')
        );

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersLastNameIfNoFirstNameIsSet()
    {
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('last_name' => 'User')
        );

        self::assertContains(
            'User',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersLastNameWithLeadingCommaIfNoCompanyIsSet()
    {
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('last_name' => 'User')
        );

        self::assertNotContains(
            ', User',
            $this->fixture->render()
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
            array('first_name' => 'Test', 'last_name' => 'User')
        );

        self::assertContains(
            'Test User',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheUserNameIfLastNameIsSet()
    {
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('last_name' => 'User')
        );

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->fixture->render()
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
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersFirstUserGroupNameForTheOfferersFirstGroupMatchingConfiguration()
    {
        $otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
            array('title' => 'other group')
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            array('usergroup' => $this->feUserGroupUid . ',' . $otherGroupUid)
        );

        self::assertContains(
            self::FE_USER_GROUP_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersSecondUserGroupNameForTheOfferersSecondGroupMatchingConfiguration()
    {
        $otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
            array('title' => 'other group')
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            array('usergroup' =>  $otherGroupUid . ',' . $this->feUserGroupUid)
        );

        self::assertContains(
            self::FE_USER_GROUP_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersFirstUserGroupNameForTheOfferersSecondGroupMatchingConfiguration()
    {
        $otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
            array('title' => 'other group')
        );
        $this->testingFramework->changeRecord(
            'fe_users',
            $this->offererUid,
            array('usergroup' => $otherGroupUid . ',' . $this->feUserGroupUid)
        );

        self::assertNotContains(
            'other group',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsNoBranchesIfTheOfferersFirstUserGroupIsNameless()
    {
        $this->testingFramework->changeRecord(
            'fe_groups', $this->feUserGroupUid, array('title' => '')
        );

        self::assertNotContains(
            '()',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersFirstUserGroupNameWhenACompanyIsSetButHiddenByConfiguration()
    {
        $otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
            array('title' => 'other group')
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array(
                'usergroup' => $this->feUserGroupUid . ',' . $otherGroupUid,
                'company' => 'Test Company',
            )
        );

        self::assertContains(
            self::FE_USER_GROUP_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsOfferersFirstUserGroupBeforeTitleIfWhatToDisplayIsSingleView()
    {
        $this->fixture->setConfigurationValue('what_to_display', 'single_view');

        $result = $this->fixture->render();
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
        $result = $this->fixture->render();
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
        $this->fixture->setConfigurationValue('displayedContactInformation', '');

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersUserGroupIfConfiguredButNeitherNameNorCompanyEnabled()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'usergroup'
        );

        self::assertNotContains(
            self::FE_USER_GROUP_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersUserGroupIfUserGroupAndNameAreEnabledByConfiguration()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'usergroup,offerer_label'
        );

        self::assertContains(
            self::FE_USER_GROUP_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersUserGroupIfNotConfigured()
    {
        $this->fixture->setConfigurationValue('displayedContactInformation', '');

        self::assertNotContains(
            self::FE_USER_GROUP_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersCompanyIfConfigured()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'company'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('company' => 'Test Company')
        );

        self::assertContains(
            'Test Company',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersCompanyIfNotConfigured()
    {
        $this->fixture->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('company' => 'Test Company')
        );

        self::assertNotContains(
            'Test Company',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsCompanyWithClassEmphasizedForEnabledCompany()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'company'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('company' => 'Test Company')
        );

        self::assertContains(
            'class="emphasized">Test Company',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersLabelIfConfigured()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'offerer_label'
        );

        self::assertContains(
            self::FE_USER_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersLabelIfNotConfigured()
    {
        $this->fixture->setConfigurationValue('displayedContactInformation', '');

        self::assertNotContains(
            self::FE_USER_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsOffererTitleWithoutClassEmphasizedForEnabledCompany()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'company,offerer_label'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('company' => 'Test Company')
        );

        self::assertNotContains(
            'class="emphasized">' . self::FE_USER_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsFirstUserGroupWithoutClassEmphasizedForEnabledCompany()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'company,offerer_label,usergroup'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('company' => 'Test Company')
        );

        self::assertNotContains(
            'class="emphasized">(' . self::FE_USER_GROUP_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsOffererTitleWithClassEmphasizedForDisabledCompany()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'offerer_label'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('company' => 'Test Company')
        );

        self::assertContains(
            'class="emphasized">' . self::FE_USER_NAME,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersStreetIfConfigured()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'street'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('address' => 'Main Street')
        );

        self::assertContains(
            'Main Street',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersStreetIfNotConfigured()
    {
        $this->fixture->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('address' => 'Main Street')
        );

        self::assertNotContains(
            'Main Street',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersCityAndZipIfConfigured()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'city'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid,
            array('city' => 'City Title', 'zip' => '12345')
        );

        self::assertContains(
            '12345 City Title',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersCityIfNotConfigured()
    {
        $this->fixture->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('city' => 'City Title')
        );

        self::assertNotContains(
            'City Title',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsCityWrapperContentIfCityNotConfigured()
    {
        $this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid,
            array('city' => 'City Title', 'zip' => '99999')
        );

        self::assertNotContains(
            '<dd>',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsCityWrapperContentIfCityIsConfiguredButEmpty()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'offerer_label,city'
        );

        self::assertNotContains(
            '<dd>',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersPhoneNumberIfConfigured()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'telephone'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('telephone' => '1234-56789')
        );

        self::assertContains(
            '1234-56789',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersPhoneNumberIfNotConfigured()
    {
        $this->fixture->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('telephone' => '1234-56789')
        );

        self::assertNotContains(
            '1234-56789',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsHtmlSpecialCharedPhoneNumber()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'telephone'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('telephone' => '<123>3455')
        );

        self::assertContains(
            htmlspecialchars('<123>3455'),
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersEmailIfConfigured()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'email'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('email' => 'offerer@company.org')
        );

        self::assertContains(
            'offerer@company.org',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersEmailIfNotConfigured()
    {
        $this->fixture->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('email' => 'offerer@company.org')
        );

        self::assertNotContains(
            'offerer@company.org',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersLinkedWebsiteIfConfigured()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'www'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('www' => 'http://www.company.org')
        );

        self::assertContains(
            '<a href="http://www.company.org"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemContainsTheOfferersLinkedWebsiteWithEscapedAmpersand()
    {
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'www'
        );
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('www' => 'http://www.company.org/?a=b&c=d')
        );

        self::assertContains(
            '<a href="http://www.company.org/?a=b&amp;c=d"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersWebsiteIfNotConfigured()
    {
        $this->fixture->setConfigurationValue('displayedContactInformation', '');
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('www' => 'http://www.company.org')
        );

        self::assertNotContains(
            'http://www.company.org',
            $this->fixture->render()
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
        $this->fixture->setConfigurationValue('displayedContactInformation', '');
        $this->fixture->setConfigurationValue('displayedContactInformationSpecial', 'telephone');
        $this->fixture->setConfigurationValue('groupsWithSpeciallyDisplayedContactInformation', $this->feUserGroupUid);
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('telephone' => '1234-56789')
        );

        self::assertContains(
            '1234-56789',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheOfferersPhoneNumberIfConfiguredForSpecialOfferersAndOffererIsNormal()
    {
        $this->fixture->setConfigurationValue('displayedContactInformationSpecial', 'telephone');

        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('telephone' => '1234-56789')
        );

        self::assertNotContains(
            '1234-56789',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemNotContainsTheSpecialOfferersPhoneNumberIfNotConfiguredAndOffererIsInSpecialGroup()
    {
        $this->fixture->setConfigurationValue('displayedContactInformationSpecial', '');
        $this->fixture->setConfigurationValue('groupsWithSpeciallyDisplayedContactInformation', $this->feUserGroupUid);
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('telephone' => '1234-56789')
        );

        self::assertNotContains(
            '1234-56789',
            $this->fixture->render()
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
        $this->fixture->setConfigurationValue(
            'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
        );
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'objects_by_owner_link'
        );

        self::assertContains(
            'button objectsByOwner',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemIfPageConfiguredAndConfigurationSetContainsConfiguredPageUidInTheLinkToTheObjectsByOffererList()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'objects_by_owner_link'
        );

        self::assertContains(
            'id=' . $pageUid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemIfPageConfiguredAndConfigurationSetContainsOwnerUidInTheLinkToTheObjectsByOffererList()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'objects_by_owner_link'
        );

        self::assertContains(
            'tx_realty_pi1[owner]=' . $this->offererUid,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemForDisabledObjectsByOwnerLinkHidesLinkToTheOffererList()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'offerer_label'
        );

        self::assertNotContains(
            'button objectsByOwner',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemForDisabledSpecialObjectsByOwnerLinkAndOffererInSpecialGroupHidesLinkToTheOffererList()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'offerer_label'
        );
        $this->fixture->setConfigurationValue(
            'groupsWithSpeciallyDisplayedContactInformation',
            $this->feUserGroupUid
        );

        self::assertNotContains(
            'button objectsByOwner',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemForEnabledObjectsByOwnerLinkAndOffererNotInSpecialGroupShowsLinkToTheOffererList()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->fixture->setConfigurationValue(
            'displayedContactInformationSpecial', 'offerer_label'
        );
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'offerer_label, objects_by_owner_link'
        );
        $this->fixture->setConfigurationValue(
            'groupsWithSpeciallyDisplayedContactInformation',
            $this->testingFramework->getAutoincrement('fe_groups')
        );

        self::assertContains(
            'button objectsByOwner',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function offererListItemForEnabledSpecialOwnerLinkAndOffererInSpecialGroupShowsLinkToTheOffererList()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
        $this->fixture->setConfigurationValue(
            'displayedContactInformationSpecial',
            'offerer_label, objects_by_owner_link'
        );
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'offerer_label'
        );
        $this->fixture->setConfigurationValue(
            'groupsWithSpeciallyDisplayedContactInformation',
            $this->feUserGroupUid
        );

        self::assertContains(
            'button objectsByOwner',
            $this->fixture->render()
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
            $this->fixture->renderOneItem($this->offererUid)
        );
    }

    /**
     * @test
     */
    public function renderOneItemReturnsEmptyStringForUidOfDisabledOfferer()
    {
        $this->testingFramework->changeRecord(
            'fe_users', $this->offererUid, array('deleted' => 1)
        );

        self::assertEquals(
            '',
            $this->fixture->renderOneItem($this->offererUid)
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
            $this->fixture->renderOneItemWithTheDataProvided(array('name' => 'test offerer'))
        );
    }

    /**
     * @test
     */
    public function renderOneItemWithTheDataProvidedReturnsAnEmptyStringForEmptyData()
    {
        self::assertEquals(
            '',
            $this->fixture->renderOneItemWithTheDataProvided(array())
        );
    }

    /**
     * @test
     */
    public function renderOneItemWithTheDataProvidedReturnsAnEmptyStringForInvalidData()
    {
        self::assertEquals(
            '',
            $this->fixture->renderOneItemWithTheDataProvided(array('foo' => 'bar'))
        );
    }

    /**
     * @test
     */
    public function renderOneItemWithTheDataProvidedReturnsHtmlWithoutTheLinkToTheObjectsByOwnerList()
    {
        self::assertNotContains(
            'class="button objectsByOwner"',
            $this->fixture->renderOneItemWithTheDataProvided(array('name' => 'test offerer'))
        );
    }

    /**
     * @test
     */
    public function renderOneItemWithTheDataProvidedForUsergroupProvidedThrowsException()
    {
        $this->setExpectedException(
            'BadMethodCallException',
            'To process user group information you need to use render() or renderOneItem().'
        );

        $this->fixture->renderOneItemWithTheDataProvided(array('usergroup' => 1));
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
            'fe_users', $this->offererUid, array('city' => 'City A')
        );
        $this->testingFramework->createFrontEndUser(
            $this->feUserGroupUid,
            array(
                'username' => 'Testuser 2',
                'city' => 'City B',
            )
        );
        $this->fixture->setConfigurationValue(
            'displayedContactInformation', 'city'
        );

        self::assertRegExp(
            '/City A.*City B/s',
            $this->fixture->render()
        );
    }
}
