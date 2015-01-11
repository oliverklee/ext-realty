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
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_ObjectsByOwnerListViewTest extends Tx_Phpunit_TestCase {
	/**
	 * @var string the title of a dummy object for the tests
	 */
	const OBJECT_TITLE = 'Testing object';

	/**
	 * @var tx_realty_pi1_ObjectsByOwnerListView
	 */
	private $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

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

	protected function setUp() {
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		$this->systemFolderPid = $this->testingFramework->createSystemFolder(1);

		/** @var tslib_fe $frontEndController */
		$frontEndController = $GLOBALS['TSFE'];
		$this->fixture = new tx_realty_pi1_ObjectsByOwnerListView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'pages' => $this->systemFolderPid,
			),
			$frontEndController->cObj,
			TRUE
		);
	}

	protected function tearDown() {
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
	private function createObjectWithOwner(array $userData = array()) {
		$owner = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
			->getLoadedTestingModel($userData);
		$this->ownerUid = $owner->getUid();
		$this->cityUid
			= $this->testingFramework->createRecord('tx_realty_cities');
		$this->objectUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => self::OBJECT_TITLE,
				'pid' => $this->systemFolderPid,
				'city' => $this->cityUid,
				'owner' => $this->ownerUid,
			)
		);
	}


	////////////////////////////////////
	// Tests for the utility functions
	////////////////////////////////////

	/**
	 * @test
	 */
	public function createObjectWithOwnerCreatesObjectInDatabase() {
		$this->createObjectWithOwner();

		$this->assertTrue(
			$this->testingFramework->existsRecordWithUid(
				'tx_realty_objects', $this->objectUid
			)
		);
	}

	/**
	 * @test
	 */
	public function createObjectWithOwnerCreatesCityInDatabase() {
		$this->createObjectWithOwner();

		$this->assertTrue(
			$this->testingFramework->existsRecordWithUid(
				'tx_realty_cities', $this->cityUid
			)
		);
	}

	/**
	 * @test
	 */
	public function createObjectWithOwnerCreatesFrontEndUserInMapper() {
		$this->createObjectWithOwner();

		$this->assertTrue(
			Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
				->existsModel($this->ownerUid)
		);
	}

	/**
	 * @test
	 */
	public function createObjectWithOwnerMakesUserOwnerOfOneObject() {
		$this->createObjectWithOwner();

		$this->assertTrue(
			$this->testingFramework->existsRecordWithUid(
				'tx_realty_objects', $this->objectUid,
				' AND owner = ' . $this->ownerUid
			)
		);
	}

	/**
	 * @test
	 */
	public function createObjectWithOwnerCanStoreUsernameForUser() {
		$this->createObjectWithOwner(array('username' => 'foo'));

		/** @var tx_realty_Mapper_FrontEndUser $mapper */
		$mapper = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser');
		/** @var tx_realty_Model_FrontEndUser $user */
		$user = $mapper->find($this->ownerUid);
		$this->assertEquals(
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
	public function displaysHasNoUnreplacedMarkers() {
		$this->createObjectWithOwner();

		$this->assertNotContains(
			'###',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysLabelOfferingsBy() {
		$this->createObjectWithOwner();

		$this->assertContains(
			$this->fixture->translate('label_offerings_by'),
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysObjectBySelectedOwner() {
		$this->createObjectWithOwner();

		$this->assertContains(
			self::OBJECT_TITLE,
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function notDisplaysObjectByOtherOwner() {
		$this->createObjectWithOwner();
		$ownerUid = $this->testingFramework->createFrontEndUser();

		$this->assertNotContains(
			self::OBJECT_TITLE,
			$this->fixture->render(array('owner' => $ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function forGivenOwnerUidNotDisplaysObjectWithoutOwner() {
		$this->createObjectWithOwner();
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => 'lonely object',
				'pid' => $this->systemFolderPid,
				'city' => $this->cityUid,
				'owner' => 0,
			)
		);

		$this->assertNotContains(
			'lonely object',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function notDisplaysHiddenObjectOfGivenOwner() {
		$this->createObjectWithOwner();
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => 'hidden object',
				'pid' => $this->systemFolderPid,
				'city' => $this->cityUid,
				'owner' => $this->ownerUid,
				'hidden' => 1,
			)
		);

		$this->assertNotContains(
			'hidden object',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysNoResultsViewForFeUserWithoutObjects() {
		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_objects_by_owner'),
			$this->fixture->render(
				array('owner' => $this->testingFramework->createFrontEndUser())
			)
		);
	}

	/**
	 * @test
	 */
	public function displaysNoResultsViewForFeUserWhoOnlyHasAHiddenObject() {
		$this->createObjectWithOwner();
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->objectUid,
			array('hidden' => 1)
		);

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_objects_by_owner'),
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysAddToFavoritesButton() {
		$this->createObjectWithOwner();

		$this->assertContains(
			$this->fixture->translate('label_add_to_favorites'),
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}


	///////////////////////////////////////////////////
	/// Tests concerning how the owner gets displayed
	///////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function displaysCompanyNameIfProvided() {
		$this->createObjectWithOwner(array('company' => 'realty test company'));

		$this->assertContains(
			'realty test company',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysFirstAndLastNameIfFirstAndLastNameAreSetAndNoCompanyIsSet() {
		$this->createObjectWithOwner(
			array(
				'last_name' => 'last name',
				'first_name' => 'first name',
			)
		);

		$this->assertContains(
			'first name last name',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysLastNameIfLastNameIsSetAndNeitherCompanyNorFirstNameAreSet() {
		$this->createObjectWithOwner(array('last_name' => 'last name'));

		$this->assertContains(
			'last name',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysNameIfFirstNameIsSetAndNeitherCompanyNorLastNameAreSet() {
		$this->createObjectWithOwner(
			array(
				'first_name' => 'first name',
				'name' => 'test name',
			)
		);

		$this->assertContains(
			'test name',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysNameIfNeitherCompanyNorLastNameNorFirstNameAreSet() {
		$this->createObjectWithOwner(array('name' => 'test name'));

		$this->assertContains(
			'test name',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysUsernameIfNeitherCompanyNorLastNameNorNameAreSet() {
		$this->createObjectWithOwner(
			array('username' => 'test user')
		);

		$this->assertContains(
			'test user',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}


	/////////////////////////////////////////////////////
	// Tests concerning the case of a given owner UID 0
	/////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function displaysNoSuchOwnerMessageForZeroOwnerUid() {
		$this->assertContains(
			$this->fixture->translate('message_no_such_owner'),
			$this->fixture->render(array('owner' => 0))
		);
	}

	/**
	 * @test
	 */
	public function displaysLabelSorryForZeroOwnerUid() {
		$this->assertContains(
			$this->fixture->translate('label_sorry'),
			$this->fixture->render(array('owner' => 0))
		);
	}

	/**
	 * @test
	 */
	public function notDisplaysLabelOfferingsByForZeroOwnerUid() {
		$this->assertNotContains(
			$this->fixture->translate('label_offerings_by'),
			$this->fixture->render(array('owner' => 0))
		);
	}

	/**
	 * @test
	 */
	public function notDisplaysObjectWithoutOwnerForZeroOwnerUid() {
		 $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => 'lonely object',
				'pid' => $this->systemFolderPid,
				'city' => $this->testingFramework->createRecord('tx_realty_cities'),
				'owner' => 0,
			)
		);

		$this->assertNotContains(
			'lonely object',
			$this->fixture->render(array('owner' => 0))
		);
	}


	///////////////////////////////////////////////////
	// Tests concerning non-existing or deleted users
	///////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function displaysNoSuchOwnerMessageForNonExistingOwner() {
		$ownerUid = $this->testingFramework->getAutoIncrement('fe_users');

		$this->assertContains(
			$this->fixture->translate('message_no_such_owner'),
			$this->fixture->render(array('owner' => $ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysNoSuchOwnerMessageForDeletedFeUserWithObject() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('deleted' => 1)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'owner' => $ownerUid,
				'city' => $this->testingFramework->createRecord('tx_realty_cities'),
			)
		);

		$this->assertContains(
			$this->fixture->translate('message_no_such_owner'),
			$this->fixture->render(array('owner' => $ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function notDisplaysADeletedFeUsersObject() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('deleted' => 1)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => 'object of deleted owner',
				'owner' => $ownerUid,
				'city' => $this->testingFramework->createRecord('tx_realty_cities'),
			)
		);

		$this->assertNotContains(
			'object of deleted owner',
			$this->fixture->render(array('owner' => $this->ownerUid))
		);
	}

	/**
	 * @test
	 */
	public function displaysLabelSorryForDeletedFeUserWithAnObject() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('deleted' => 1)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'owner' => $ownerUid,
				'city' => $this->testingFramework->createRecord('tx_realty_cities'),
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_sorry'),
			$this->fixture->render(array('owner' => $ownerUid))
		);
	}
}