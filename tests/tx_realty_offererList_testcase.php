<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_testingFramework.php');

require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_offererList.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_pi1.php');

/**
 * Unit tests for the tx_realty_offererList class in the 'realty'
 * extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_offererList_testcase extends tx_phpunit_testcase {
	/** @var	tx_oelib_testingFramework */
	private $testingFramework;

	/** @var	tx_realty_offererList */
	private $fixture;
	/** @var	tx_realty_pi1		plugin that uses the offerer list */
	private $pi1;

	/** @var	integer		FE user group UID */
	private $feUserGroupUid;
	/** @var	string		FE user group name */
	const FE_USER_GROUP_NAME = 'test offerers';
	/** @var	integer		FE user UID */
	private $offererUid;
	/** @var	string		FE user name */
	const FE_USER_NAME = 'test_offerer';


	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->pi1 = new tx_realty_pi1();
		$this->pi1->init(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm')
		);
		$this->pi1->getTemplateCode();
		$this->pi1->setLabels();

		$this->createDummyRecords();

		$this->pi1->setConfigurationValue(
			'userGroupsForOffererList', $this->feUserGroupUid
		);

		$this->fixture = new tx_realty_offererList($this->pi1);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();
		unset($this->fixture, $this->pi1, $this->testingFramework);
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

	public function testOffererListNotDisplaysDeletedOffererAlthoughHeIsInTheConfiguredGroup() {
		$otherGroupUid = $this->testingFramework->createFrontEndUserGroup();
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

		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
		$this->assertContains(
			'other user',
			$this->fixture->render()
		);
	}

	public function testOffererListDisplaysTwoOfferersWhoAreInDifferentConfiguredGroups() {
		$secondFeUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$this->pi1->setConfigurationValue(
			'userGroupsForOffererList',
			$this->feUserGroupUid . ',' . $secondFeUserGroupUid
		);
		$this->testingFramework->createFrontEndUser(
			$secondFeUserGroupUid, array('username' => 'other user')
		);

		$this->assertContains(
			self::FE_USER_NAME,
			$this->fixture->render()
		);
		$this->assertContains(
			'other user',
			$this->fixture->render()
		);
	}

	public function testOffererListDisplaysTwoOfferersWhoAreInDifferentConfiguredGroupsOrderedByGroupUid() {
		$secondFeUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$this->pi1->setConfigurationValue(
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
			$this->testingFramework->createFrontEndUserGroup(),
			array('username' => 'other user')
		);

		$this->assertNotContains(
			'other user',
			$this->fixture->render()
		);
	}

	public function testOffererListDisplaysNoResultViewForNoOffererInTheConfiguredGroup() {
		$this->pi1->setConfigurationValue(
			'userGroupsForOffererList',
			$this->testingFramework->createFrontEndUserGroup()
		);

		$this->assertContains(
			'noresults',
			$this->fixture->render()
		);
	}

	public function testOffererListDisplaysNoResultViewForConfiguredGroup() {
		$this->pi1->setConfigurationValue('userGroupsForOffererList', '');

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

	public function testOffererListItemNotContainsTheNameIfLastNameIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users',
			$this->offererUid,
			array('name' => 'Mr. Test', 'last_name' => 'User')
		);

		$this->assertNotContains(
			'Mr. Test',
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

	public function testOffererListItemContainsTheOfferersCompany() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'test company')
		);

		$this->assertContains(
			'test company',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsNoTrailingCommaAfterTheOfferersCompanyIfNoNameIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'test company')
		);

		$this->assertNotContains(
			'test company,',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersLastNameIfCompanyIsSetAndDisplayed() {
		$this->testingFramework->changeRecord(
			'fe_users',
			$this->offererUid,
			array('company' => 'test company', 'last_name' => 'User')
		);

		$this->assertContains(
			'test company, User',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsTheOfferersNameIfIsCompanySetAndDisplayed() {
		$this->testingFramework->changeRecord(
			'fe_users',
			$this->offererUid,
			array('company' => 'test company', 'name' => 'Mr. Test')
		);

		$this->assertContains(
			'test company, Mr. Test',
			$this->fixture->render()
		);
	}

	public function testOffererListItemNotContainsTheOfferersUserNameIfCompanyIsSet() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('company' => 'test company')
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


	//////////////////////////////////////////
	// Testing further displayed information
	//////////////////////////////////////////

	public function testOffererListItemContainsTheOfferersPhoneNumber() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '1234-56789')
		);

		$this->assertContains(
			'1234-56789',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsHtmlSpecialCharedPhoneNumber() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->offererUid, array('telephone' => '<123>3455')
		);

		$this->assertContains(
			htmlspecialchars('<123>3455'),
			$this->fixture->render()
		);
	}

	//////////////////////////////////////////////////////
	// Testing the link to the "objects by offerer" list
	//////////////////////////////////////////////////////

	public function testOffererListItemContainsLinkToTheObjectsByOffererListIfPageConfigured() {
		$this->pi1->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'button objectsByOwner',
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsConfiguredPageUidInTheLinkToTheObjectsByOffererListIfPageConfigured() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->pi1->setConfigurationValue('objectsByOwnerPID', $pageUid);

		$this->assertContains(
			'id=' . $pageUid,
			$this->fixture->render()
		);
	}

	public function testOffererListItemContainsOwnerUidInTheLinkToTheObjectsByOffererListIfPageConfigured() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->pi1->setConfigurationValue('objectsByOwnerPID', $pageUid);

		$this->assertContains(
			'tx_realty_pi1[owner]=' . $this->offererUid,
			$this->fixture->render()
		);
	}
}
?>