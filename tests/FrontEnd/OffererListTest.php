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

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_OffererListTest extends tx_phpunit_testcase {
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var tx_realty_offererList
	 */
	private $fixture;

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

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->createDummyRecords();

		// TRUE enables the test mode
		$this->fixture = new tx_realty_offererList(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'what_to_display' => 'offerer_list',
				'userGroupsForOffererList' => $this->feUserGroupUid,
				'displayedContactInformation' => 'usergroup,offerer_label',
			),
			$GLOBALS['TSFE']->cObj,
			TRUE
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Creates a dummy user group and a dummy offerer record in the database.
	 *
	 * @return void
	 */
	private function createDummyRecords() {
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
	public function offererListDisplaysOffererWhoIsOnlyInTheConfiguredGroup() {
		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListDisplaysNoUnreplacedMarkers() {
		$this->assertNotContains(
			'###',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListNotDisplaysDeletedOffererAlthoughHeIsInTheConfiguredGroup() {
		$this->testingFramework->changeRecord(
			'fe_users',
			$this->offererUid,
			array('deleted' => 1)
		);

		$this->assertNotContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListDisplaysOffererWhoIsInTwoGroupsWithTheConfiguredGroupFirst() {
		$otherGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$this->testingFramework->changeRecord(
			'fe_users',
			$this->offererUid,
			array('usergroup' => $this->feUserGroupUid . ',' . $otherGroupUid)
		);

		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListDisplaysOffererWhoIsInThreeGroupsWithTheConfiguredGroupSecond() {
		$firstGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$thirdGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array(
				'usergroup' => $firstGroupUid . ',' .
					$this->feUserGroupUid . ',' . $thirdGroupUid
				)
		);

		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListDisplaysOffererWhoIsInThreeGroupsWithTheConfiguredGroupLast() {
		$firstGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$secondGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array(
				'usergroup' => $firstGroupUid . ',' .
					$secondGroupUid . ',' . $this->feUserGroupUid
				)
		);

		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListDisplaysTwoOfferersWhoAreInTheSameConfiguredGroup() {
		$this->testingFramework->createFrontEndUser(
			$this->feUserGroupUid, array('username' => 'other user')
		);

		$output = $this->fixture->render();
		$this->assertContains(
			self::FE_USER_NAME,
			$output
		);
		$this->assertContains(
			'other user',
			$output
		);
	}

	/**
	 * @test
	 */
	public function offererListDisplaysTwoOfferersWhoAreInDifferentConfiguredGroups() {
		$secondFeUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$this->fixture->setConfigurationValue(
			'userGroupsForOffererList',
			$this->feUserGroupUid . ',' . $secondFeUserGroupUid
		);
		$this->testingFramework->createFrontEndUser(
			$secondFeUserGroupUid, array('username' => 'other user')
		);

		$output = $this->fixture->render();
		$this->assertContains(
			self::FE_USER_NAME,
			$output
		);
		$this->assertContains(
			'other user',
			$output
		);
	}

	/**
	 * @test
	 */
	public function offererListDisplaysFeUserRecordIfNoUserGroupRestrictionIsConfigured() {
		$this->fixture->setConfigurationValue('userGroupsForOffererList', '');
		$this->testingFramework->createFrontEndUser(
			'', array('username' => 'other user')
		);

		$output = $this->fixture->render();
		$this->assertContains(
			self::FE_USER_NAME,
			$output
		);
		$this->assertContains(
			'other user',
			$output
		);
	}

	/**
	 * @test
	 */
	public function offererDisplaysGrouplessFeUserRecordIfNoUserGroupRestrictionIsConfigured() {
		// This test is to document that there is no crash if there is such a
		// record in the database.
		$this->fixture->setConfigurationValue('userGroupsForOffererList', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('usergroup' => '')
		);

		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListDisplaysTwoOfferersWhoAreInDifferentConfiguredGroupsOrderedByGroupUid() {
		$secondFeUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$this->fixture->setConfigurationValue(
			'userGroupsForOffererList',
			$this->feUserGroupUid . ',' . $secondFeUserGroupUid
		);
		$this->testingFramework->createFrontEndUser(
			$secondFeUserGroupUid, array('username' => 'other user')
		);

		$result = $this->fixture->render();
		$this->assertEquals(
			$this->userGroupUid < $secondFeUserGroupUid,
			strpos($result, self::FE_USER_NAME) < strpos($result, 'other user')
		);
	}

	/**
	 * @test
	 */
	public function offererListNotDisplaysOffererWhoIsOnlyInANonConfiguredGroup() {
		$this->testingFramework->createFrontEndUser(
			'', array('username' => 'other user')
		);

		$this->assertNotContains(
			'other user',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListDisplaysNoResultViewForNoOffererInTheConfiguredGroup() {
		$this->fixture->setConfigurationValue(
			'userGroupsForOffererList',
			$this->testingFramework->createFrontEndUserGroup()
		);

		$this->assertContains(
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
	public function offererListItemContainsTestUserName() {
		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersName() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('name' => 'Mr. Test')
		);

		$this->assertContains(
			'Mr. Test',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheUserNameIfTheOffererNameIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('name' => 'Mr. Test')
		);

		$this->assertNotContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersLastNameIfNoFirstNameIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('last_name' => 'User')
		);

		$this->assertContains(
			'User',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersLastNameWithLeadingCommaIfNoCompanyIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('last_name' => 'User')
		);

		$this->assertNotContains(
			', User',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersFirstAndLastName() {
		$this->testingFramework->changeRecord(
			'fe_users',
			$this->offererUid,
			array('first_name' => 'Test', 'last_name' => 'User')
		);

		$this->assertContains(
			'Test User',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheUserNameIfLastNameIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('last_name' => 'User')
		);

		$this->assertNotContains(
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
	public function offererListItemContainsTheOfferersFirstUserGroupNameForOffererWithOneUserGroup() {
		$this->assertContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersFirstUserGroupNameForTheOfferersFirstGroupMatchingConfiguration() {
		$otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
			array('title' => 'other group')
		);
		$this->testingFramework->changeRecord(
			'fe_users',
			$this->offererUid,
			array('usergroup' => $this->feUserGroupUid . ',' . $otherGroupUid)
		);

		$this->assertContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersSecondUserGroupNameForTheOfferersSecondGroupMatchingConfiguration() {
		$otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
			array('title' => 'other group')
		);
		$this->testingFramework->changeRecord(
			'fe_users',
			$this->offererUid,
			array('usergroup' =>  $otherGroupUid . ',' . $this->feUserGroupUid)
		);

		$this->assertContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersFirstUserGroupNameForTheOfferersSecondGroupMatchingConfiguration() {
		$otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
			array('title' => 'other group')
		);
		$this->testingFramework->changeRecord(
			'fe_users',
			$this->offererUid,
			array('usergroup' => $otherGroupUid . ',' . $this->feUserGroupUid)
		);

		$this->assertNotContains(
			'other group',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsNoBranchesIfTheOfferersFirstUserGroupIsNameless() {
		$this->testingFramework->changeRecord(
			'fe_groups', $this->feUserGroupUid, array('title' => '')
		);

		$this->assertNotContains(
			'()',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersFirstUserGroupNameWhenACompanyIsSetButHiddenByConfiguration() {
		$otherGroupUid = $this->testingFramework->createFrontEndUserGroup(
			array('title' => 'other group')
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array(
				'usergroup' => $this->feUserGroupUid . ',' . $otherGroupUid,
				'company' => 'Test Company',
			)
		);

		$this->assertContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsOfferersFirstUserGroupBeforeTitleIfWhatToDisplayIsSingleView() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');

		$result = $this->fixture->render();
		$this->assertGreaterThan(
			strpos($result, self::FE_USER_GROUP_NAME),
			strpos($result, self::FE_USER_NAME)
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsOfferersFirstUserGroupAfterTitleIfWhatToDisplayIsOffererList() {
		$result = $this->fixture->render();
		$this->assertGreaterThan(
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
	public function offererListItemNotContainsTheOfferersUserNameIfTheConfigurationIsEmpty() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');

		$this->assertNotContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersUserGroupIfConfiguredButNeitherNameNorCompanyEnabled() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'usergroup'
		);

		$this->assertNotContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersUserGroupIfUserGroupAndNameAreEnabledByConfiguration() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'usergroup,offerer_label'
		);

		$this->assertContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersUserGroupIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');

		$this->assertNotContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersCompanyIfConfigured() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'company'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'Test Company')
		);

		$this->assertContains(
			'Test Company',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersCompanyIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'Test Company')
		);

		$this->assertNotContains(
			'Test Company',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsCompanyWithClassEmphasizedForEnabledCompany() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'company'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'Test Company')
		);

		$this->assertContains(
			'class="emphasized">Test Company',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersLabelIfConfigured() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label'
		);

		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersLabelIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');

		$this->assertNotContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsOffererTitleWithoutClassEmphasizedForEnabledCompany() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'company,offerer_label'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'Test Company')
		);

		$this->assertNotContains(
			'class="emphasized">' . self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsFirstUserGroupWithoutClassEmphasizedForEnabledCompany() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'company,offerer_label,usergroup'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'Test Company')
		);

		$this->assertNotContains(
			'class="emphasized">(' . self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsOffererTitleWithClassEmphasizedForDisabledCompany() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'Test Company')
		);

		$this->assertContains(
			'class="emphasized">' . self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersStreetIfConfigured() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'street'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('address' => 'Main Street')
		);

		$this->assertContains(
			'Main Street',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersStreetIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('address' => 'Main Street')
		);

		$this->assertNotContains(
			'Main Street',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersCityAndZipIfConfigured() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'city'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid,
			array('city' => 'City Title', 'zip' => '12345')
		);

		$this->assertContains(
			'12345 City Title',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersCityIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('city' => 'City Title')
		);

		$this->assertNotContains(
			'City Title',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsCityWrapperContentIfCityNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid,
			array('city' => 'City Title', 'zip' => '99999')
		);

		$this->assertNotContains(
			'<dd>',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsCityWrapperContentIfCityIsConfiguredButEmpty() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,city'
		);

		$this->assertNotContains(
			'<dd>',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersPhoneNumberIfConfigured() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'telephone'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '1234-56789')
		);

		$this->assertContains(
			'1234-56789',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersPhoneNumberIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '1234-56789')
		);

		$this->assertNotContains(
			'1234-56789',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsHtmlSpecialCharedPhoneNumber() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'telephone'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '<123>3455')
		);

		$this->assertContains(
			htmlspecialchars('<123>3455'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersEmailIfConfigured() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'email'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('email' => 'offerer@company.org')
		);

		$this->assertContains(
			'offerer@company.org',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersEmailIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('email' => 'offerer@company.org')
		);

		$this->assertNotContains(
			'offerer@company.org',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersLinkedWebsiteIfConfigured() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'www'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('www' => 'http://www.company.org')
		);

		$this->assertContains(
			'<a href="http://www.company.org"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemContainsTheOfferersLinkedWebsiteWithEscapedAmpersand() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'www'
		);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('www' => 'http://www.company.org/?a=b&c=d')
		);

		$this->assertContains(
			'<a href="http://www.company.org/?a=b&amp;c=d"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersWebsiteIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('www' => 'http://www.company.org')
		);

		$this->assertNotContains(
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
	public function offererListItemContainsTheSpecialOfferersPhoneNumberIfConfiguredAndOffererIsInSpecialGroup() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->fixture->setConfigurationValue('displayedContactInformationSpecial', 'telephone');
		$this->fixture->setConfigurationValue('groupsWithSpeciallyDisplayedContactInformation', $this->feUserGroupUid);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '1234-56789')
		);

		$this->assertContains(
			'1234-56789',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheOfferersPhoneNumberIfConfiguredForSpecialOfferersAndOffererIsNormal() {
		$this->fixture->setConfigurationValue('displayedContactInformationSpecial', 'telephone');

		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '1234-56789')
		);

		$this->assertNotContains(
			'1234-56789',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemNotContainsTheSpecialOfferersPhoneNumberIfNotConfiguredAndOffererIsInSpecialGroup() {
		$this->fixture->setConfigurationValue('displayedContactInformationSpecial', '');
		$this->fixture->setConfigurationValue('groupsWithSpeciallyDisplayedContactInformation', $this->feUserGroupUid);
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '1234-56789')
		);

		$this->assertNotContains(
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
	public function offererListItemContainsLinkToTheObjectsByOffererListIfPageConfigured() {
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'objects_by_owner_link'
		);

		$this->assertContains(
			'button objectsByOwner',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemIfPageConfiguredAndConfigurationSetContainsConfiguredPageUidInTheLinkToTheObjectsByOffererList() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'objects_by_owner_link'
		);

		$this->assertContains(
			'id=' . $pageUid,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemIfPageConfiguredAndConfigurationSetContainsOwnerUidInTheLinkToTheObjectsByOffererList() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'objects_by_owner_link'
		);

		$this->assertContains(
			'tx_realty_pi1[owner]=' . $this->offererUid,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemForDisabledObjectsByOwnerLinkHidesLinkToTheOffererList() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label'
		);

		$this->assertNotContains(
			'button objectsByOwner',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemForDisabledSpecialObjectsByOwnerLinkAndOffererInSpecialGroupHidesLinkToTheOffererList() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('objectsByOwnerPID', $pageUid);
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label'
		);
		$this->fixture->setConfigurationValue(
			'groupsWithSpeciallyDisplayedContactInformation',
			$this->feUserGroupUid
		);

		$this->assertNotContains(
			'button objectsByOwner',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemForEnabledObjectsByOwnerLinkAndOffererNotInSpecialGroupShowsLinkToTheOffererList() {
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

		$this->assertContains(
			'button objectsByOwner',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function offererListItemForEnabledSpecialOwnerLinkAndOffererInSpecialGroupShowsLinkToTheOffererList() {
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

		$this->assertContains(
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
	public function renderOneItemReturnsHtmlForTheOffererWithTheProvidedUid() {
		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->renderOneItem($this->offererUid)
		);
	}

	/**
	 * @test
	 */
	public function renderOneItemReturnsEmptyStringForUidOfDisabledOfferer() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('deleted' => 1)
		);

		$this->assertEquals(
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
	public function renderOneItemWithTheDataProvidedReturnsHtmlForTheListItemForValidData() {
		$this->assertContains(
			'test offerer',
			$this->fixture->renderOneItemWithTheDataProvided(array('name' => 'test offerer'))
		);
	}

	/**
	 * @test
	 */
	public function renderOneItemWithTheDataProvidedReturnsAnEmptyStringForEmptyData() {
		$this->assertEquals(
			'',
			$this->fixture->renderOneItemWithTheDataProvided(array())
		);
	}

	/**
	 * @test
	 */
	public function renderOneItemWithTheDataProvidedReturnsAnEmptyStringForInvalidData() {
		$this->assertEquals(
			'',
			$this->fixture->renderOneItemWithTheDataProvided(array('foo' => 'bar'))
		);
	}

	/**
	 * @test
	 */
	public function renderOneItemWithTheDataProvidedReturnsHtmlWithoutTheLinkToTheObjectsByOwnerList() {
		$this->assertNotContains(
			'class="button objectsByOwner"',
			$this->fixture->renderOneItemWithTheDataProvided(array('name' => 'test offerer'))
		);
	}

	/**
	 * @test
	 */
	public function renderOneItemWithTheDataProvidedForUsergroupProvidedThrowsException() {
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
	public function offererListIsSortedByCity() {
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

		$this->assertRegExp(
			'/City A.*City B/s',
			$this->fixture->render()
		);
	}
}