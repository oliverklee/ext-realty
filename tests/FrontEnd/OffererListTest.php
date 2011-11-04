<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2011 Saskia Metzler <saskia@merlin.owl.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_Autoloader.php');

/**
 * Unit tests for the tx_realty_offererList class in the "realty"
 * extension.
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
	 * @var integer FE user group UID
	 */
	private $feUserGroupUid;

	/**
	 * @var integer FE user UID
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

		// "TRUE" enables the test mode
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

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Creates a dummy user group and a dummy offerer record in the database.
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

	public function testOffererListDisplaysOffererWhoIsOnlyInTheConfiguredGroup() {
		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListDisplaysNoUnreplacedMarkers() {
		$this->assertNotContains(
			'###',
			$this->fixture->render()
		);
	}

	public function testOffererListNotDisplaysDeletedOffererAlthoughHeIsInTheConfiguredGroup() {
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

	public function testOffererListDisplaysOffererWhoIsInTwoGroupsWithTheConfiguredGroupFirst() {
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

	public function testOffererListDisplaysOffererWhoIsInThreeGroupsWithTheConfiguredGroupSecond() {
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

	public function testOffererListDisplaysOffererWhoIsInThreeGroupsWithTheConfiguredGroupLast() {
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

	public function testOffererListDisplaysTwoOfferersWhoAreInTheSameConfiguredGroup() {
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

	public function testOffererListDisplaysTwoOfferersWhoAreInDifferentConfiguredGroups() {
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

	public function testOffererListDisplaysFeUserRecordIfNoUserGroupRestrictionIsConfigured() {
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

	public function testOffererDisplaysGrouplessFeUserRecordIfNoUserGroupRestrictionIsConfigured() {
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

	public function testOffererListDisplaysTwoOfferersWhoAreInDifferentConfiguredGroupsOrderedByGroupUid() {
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

	public function testOffererListNotDisplaysOffererWhoIsOnlyInANonConfiguredGroup() {
		$this->testingFramework->createFrontEndUser(
			'', array('username' => 'other user')
		);

		$this->assertNotContains(
			'other user',
			$this->fixture->render()
		);
	}

	public function testOffererListDisplaysNoResultViewForNoOffererInTheConfiguredGroup() {
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

	public function testOffererListItemContainsTestUserName() {
		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersName() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('name' => 'Mr. Test')
		);

		$this->assertContains(
			'Mr. Test',
			$this->fixture->render()
		);
	}

	public function testOffererListItemNotContainsTheUserNameIfTheOffererNameIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('name' => 'Mr. Test')
		);

		$this->assertNotContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersLastNameIfNoFirstNameIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('last_name' => 'User')
		);

		$this->assertContains(
			'User',
			$this->fixture->render()
		);
	}

	public function testOffererListItemNotContainsTheOfferersLastNameWithLeadingCommaIfNoCompanyIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('last_name' => 'User')
		);

		$this->assertNotContains(
			', User',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersFirstAndLastName() {
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

	public function testOffererListItemNotContainsTheUserNameIfLastNameIsSet() {
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

	public function testOffererListItemContainsTheOfferersFirstUserGroupNameForOffererWithOneUserGroup() {
		$this->assertContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersFirstUserGroupNameForTheOfferersFirstGroupMatchingConfiguration() {
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

	public function testOffererListItemContainsTheOfferersSecondUserGroupNameForTheOfferersSecondGroupMatchingConfiguration() {
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

	public function testOffererListItemNotContainsTheOfferersFirstUserGroupNameForTheOfferersSecondGroupMatchingConfiguration() {
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

	public function testOffererListItemContainsNoBranchesIfTheOfferersFirstUserGroupIsNameless() {
		$this->testingFramework->changeRecord(
			'fe_groups', $this->feUserGroupUid, array('title' => '')
		);

		$this->assertNotContains(
			'()',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersFirstUserGroupNameWhenACompanyIsSetButHiddenByConfiguration() {
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

	public function testOffererListItemContainsOfferersFirstUserGroupBeforeTitleIfWhatToDisplayIsSingleView() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');

		$result = $this->fixture->render();
		$this->assertGreaterThan(
			strpos($result, self::FE_USER_GROUP_NAME),
			strpos($result, self::FE_USER_NAME)
		);
	}

	public function testOffererListItemContainsOfferersFirstUserGroupAfterTitleIfWhatToDisplayIsOffererList() {
		$result = $this->fixture->render();
		$this->assertGreaterThan(
			strpos($result, self::FE_USER_NAME),
			strpos($result, self::FE_USER_GROUP_NAME)
		);
	}


	////////////////////////////////////////////////////////////////////
	// Testing conditionally displayed information for normal offerers
	////////////////////////////////////////////////////////////////////

	public function testOffererListItemNotContainsTheOfferersUserNameIfTheConfigurationIsEmpty() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');

		$this->assertNotContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListItemNotContainsTheOfferersUserGroupIfConfiguredButNeitherNameNorCompanyEnabled() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'usergroup'
		);

		$this->assertNotContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersUserGroupIfUserGroupAndNameAreEnabledByConfiguration() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'usergroup,offerer_label'
		);

		$this->assertContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListItemNotContainsTheOfferersUserGroupIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');

		$this->assertNotContains(
			self::FE_USER_GROUP_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersCompanyIfConfigured() {
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

	public function testOffererListItemNotContainsTheOfferersCompanyIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'Test Company')
		);

		$this->assertNotContains(
			'Test Company',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsCompanyWithClassEmphasizedForEnabledCompany() {
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

	public function testOffererListItemContainsTheOfferersLabelIfConfigured() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label'
		);

		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListItemNotContainsTheOfferersLabelIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');

		$this->assertNotContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsOffererTitleWithoutClassEmphasizedForEnabledCompany() {
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

	public function testOffererListItemContainsFirstUserGroupWithoutClassEmphasizedForEnabledCompany() {
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

	public function testOffererListItemContainsOffererTitleWithClassEmphasizedForDisabledCompany() {
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

	public function testOffererListItemContainsTheOfferersStreetIfConfigured() {
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

	public function testOffererListItemNotContainsTheOfferersStreetIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('address' => 'Main Street')
		);

		$this->assertNotContains(
			'Main Street',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersCityAndZipIfConfigured() {
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

	public function testOffererListItemNotContainsTheOfferersCityIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('city' => 'City Title')
		);

		$this->assertNotContains(
			'City Title',
			$this->fixture->render()
		);
	}

	public function testOffererListItemNotContainsCityWrapperContentIfCityNotConfigured() {
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

	public function testOffererListItemNotContainsCityWrapperContentIfCityIsConfiguredButEmpty() {
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,city'
		);

		$this->assertNotContains(
			'<dd>',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersPhoneNumberIfConfigured() {
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

	public function testOffererListItemNotContainsTheOfferersPhoneNumberIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '1234-56789')
		);

		$this->assertNotContains(
			'1234-56789',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsHtmlSpecialCharedPhoneNumber() {
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

	public function testOffererListItemContainsTheOfferersEmailIfConfigured() {
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

	public function testOffererListItemNotContainsTheOfferersEmailIfNotConfigured() {
		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('email' => 'offerer@company.org')
		);

		$this->assertNotContains(
			'offerer@company.org',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersLinkedWebsiteIfConfigured() {
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

	public function testOffererListItemContainsTheOfferersLinkedWebsiteWithEscapedAmpersand() {
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

	public function testOffererListItemNotContainsTheOfferersWebsiteIfNotConfigured() {
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

	public function testOffererListItemContainsTheSpecialOfferersPhoneNumberIfConfiguredAndOffererIsInSpecialGroup() {
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

	public function testOffererListItemNotContainsTheOfferersPhoneNumberIfConfiguredForSpecialOfferersAndOffererIsNormal() {
		$this->fixture->setConfigurationValue('displayedContactInformationSpecial', 'telephone');

		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '1234-56789')
		);

		$this->assertNotContains(
			'1234-56789',
			$this->fixture->render()
		);
	}

	public function testOffererListItemNotContainsTheSpecialOfferersPhoneNumberIfNotConfiguredAndOffererIsInSpecialGroup() {
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

	public function testOffererListItemContainsLinkToTheObjectsByOffererListIfPageConfigured() {
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

	public function test_OffererListItem_IfPageConfiguredAndConfigurationSet_ContainsConfiguredPageUidInTheLinkToTheObjectsByOffererList() {
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

	public function test_OffererListItem_IfPageConfiguredAndConfigurationSet_ContainsOwnerUidInTheLinkToTheObjectsByOffererList() {
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

	public function test_OffererListItem_ForDisabledObjectsByOwnerLink_HidesLinkToTheOffererList() {
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

	public function test_OffererListItem_ForDisabledSpecialObjectsByOwnerLinkAndOffererInSpecialGroup_HidesLinkToTheOffererList() {
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

	public function test_OffererListItem_ForEnabledObjectsByOwnerLinkAndOffererNotInSpecialGroup_ShowsLinkToTheOffererList() {
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

	public function test_OffererListItem_ForEnabledSpecialOwnerLinkAndOffererInSpecialGroup_ShowsLinkToTheOffererList() {
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

	public function testRenderOneItemReturnsHtmlForTheOffererWithTheProvidedUid() {
		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->renderOneItem($this->offererUid)
		);
	}

	public function testRenderOneItemReturnsAnEmptyStringForAUidOfADisabledOfferer() {
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

	public function testRenderOneItemWithTheDataProvidedReturnsHtmlForTheListItemForValidData() {
		$this->assertContains(
			'test offerer',
			$this->fixture->renderOneItemWithTheDataProvided(array('name' => 'test offerer'))
		);
	}

	public function testRenderOneItemWithTheDataProvidedReturnsAnEmptyStringForEmptyData() {
		$this->assertEquals(
			'',
			$this->fixture->renderOneItemWithTheDataProvided(array())
		);
	}

	public function testRenderOneItemWithTheDataProvidedReturnsAnEmptyStringForInvalidData() {
		$this->assertEquals(
			'',
			$this->fixture->renderOneItemWithTheDataProvided(array('foo' => 'bar'))
		);
	}

	public function testRenderOneItemWithTheDataProvidedReturnsHtmlWithoutTheLinkToTheObjectsByOwnerList() {
		$this->assertNotContains(
			'class="button objectsByOwner"',
			$this->fixture->renderOneItemWithTheDataProvided(array('name' => 'test offerer'))
		);
	}

	public function testRenderOneItemWithTheDataProvidedForUsergroupProvidedThrowsException() {
		$this->setExpectedException(
			Exception,
			'To process user group information you need to use render() or' .
				'renderOneItem().'
		);

		$this->fixture->renderOneItemWithTheDataProvided(array('usergroup' => 1));
	}



	/////////////////////////////////////////////////////
	// Tests concerning the sorting of the offerer list
	/////////////////////////////////////////////////////

	public function testOffererListIsSortedByCity() {
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
?>