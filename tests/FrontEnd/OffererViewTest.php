<?php
/**
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
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_OffererViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_OffererView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_OffererView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'company'
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
	 * Creates a realty object with an FE user as owner.
	 *
	 * @param array $ownerData the data to store for the owner
	 *
	 * @return tx_realty_Model_RealtyObject the realty object with the owner
	 */
	private function getRealtyObjectWithOwner(array $ownerData = array()) {
		return tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'owner' => $this->testingFramework->createFrontEndUser(
					'', $ownerData
				),
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT
		));
	}


	///////////////////////////////////////////
	// Tests concerning the utility functions
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getRealtyObjectWithOwnerReturnsRealtyObjectModel() {
		$this->assertTrue(
			$this->getRealtyObjectWithOwner() instanceof tx_realty_Model_RealtyObject
		);
	}

	/**
	 * @test
	 */
	public function getRealtyObjectWithOwnerAddsAnOwnerToTheModel() {
		$this->assertTrue(
			$this->getRealtyObjectWithOwner()->hasOwner()
		);
	}

	/**
	 * @test
	 */
	public function getRealtyObjectWithCanStoreDataToOwner() {
		$owner = $this->getRealtyObjectWithOwner(array('name' => 'foo'))
			->getOwner();

		$this->assertEquals(
			'foo',
			$owner->getName()
		);
	}


	/////////////////////////////
	// Testing the offerer view
	/////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsNonEmptyResultForShowUidOfExistingRecord() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('employer' => 'foo'));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('employer' => 'foo'));

		$result = $this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertNotEquals(
			'',
			$result
		);
		$this->assertNotContains(
			'###',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsTheRealtyObjectsEmployerForValidRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('employer' => 'foo'));

		$this->assertContains(
			'foo',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForValidRealtyObjectWithoutData() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}


	//////////////////////////////////////////////
	// Testing the displayed offerer information
	//////////////////////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsContactInformationIfEnabledAndInformationIsSetInTheRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('phone_switchboard' => '12345'));

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		$this->assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsPhoneNumberIfContactDataIsEnabledAndInformationIsSetInTheRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('phone_switchboard' => '12345'));

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		$this->assertContains(
			'12345',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForDisplayContactTelephoneEnabledContactFromObjectAndDirectExtensionSetShowsDirectExtensionNumber() {
		$model = $this->getMock(
			'tx_realty_Model_RealtyObject',
			array('getContactPhoneNumber', 'getProperty')
		);
		$model->expects($this->once())->method('getContactPhoneNumber');

		$mapper = $this->getMock('tx_realty_Mapper_RealtyObject', array('find'));
		$mapper->expects($this->any())->method('find')
			->will($this->returnValue($model));
		tx_oelib_MapperRegistry::set('tx_realty_Mapper_RealtyObject', $mapper);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		$this->fixture->render(array('showUid' => 0));
	}

	/**
	 * @test
	 */
	public function renderReturnsCompanyIfContactDataIsEnabledAndInformationIsSetInTheRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('employer' => 'test company'));

		$this->assertContains(
			'test company',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsOwnersPhoneNumberIfContactDataIsEnabledAndContactDataMayBeTakenFromOwner() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('telephone' => '123123')
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		$this->assertContains(
			'123123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsOwnersCompanyIfContactDataIsEnabledAndContactDataMayBeTakenFromOwner() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('company' => 'any company')
		);

		$this->assertContains(
			'any company',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsContactInformationIfOptionIsDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('phone_switchboard' => '12345'));

		$this->fixture->setConfigurationValue('displayedContactInformation', '');

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsContactInformationForEnabledOptionAndDeletedOwner() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('company' => 'any company', 'deleted' => 1)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsContactInformationForEnabledOptionAndOwnerWithoutData() {
		$realtyObject = $this->getRealtyObjectWithOwner();

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsLabelForLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsLabelOffererIfTheLinkToTheObjectsByOwnerListIsEnabled() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);
		$objectsByOwnerPid = $this->testingFramework->createFrontEndPage();

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue('objectsByOwnerPID', $objectsByOwnerPid);

		$this->assertContains(
			'?id=' . $objectsByOwnerPid,
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsOwnerUidInLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('username' => 'foo')
		);
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'owner' => $ownerUid,
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT
		));

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'tx_realty_pi1[owner]=' . $ownerUid,
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForDisabledOptionAndOwnerSetHidesObjectsByOwnerLink() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsLinkToTheObjectsByOwnerListForEnabledOptionAndNoOwnerSet() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT
		));

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsLinkToTheObjectsByOwnerListForDisabledContactInformationAndOwnerAndPidSet() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForNoObjectsByOwnerPidSetAndOwnerSetReturnsLinkWithoutId() {
		$realtyObject = $this->getRealtyObjectWithOwner(array('username' => 'foo'));

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);

		$this->assertNotContains(
			'?id=',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}
}