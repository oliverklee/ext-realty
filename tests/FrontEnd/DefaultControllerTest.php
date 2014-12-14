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
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_DefaultControllerTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var int login PID
	 */
	private $loginPid = 0;
	/**
	 * @var int system folder PID
	 */
	private $systemFolderPid = 0;
	/**
	 * @var int sub-system folder PID
	 */
	private $subSystemFolderPid = 0;

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
	 * @var int second dummy realty object
	 */
	private $secondRealtyUid = 0;
	/**
	 * @var string object number for the second dummy realty object
	 */
	private static $secondObjectNumber = '2';
	/**
	 * @var string title for the second dummy realty object
	 */
	private static $secondObjectTitle = 'another title';

	/**
	 * @var int static_info_tables UID of Germany
	 */
	const DE = 54;

	protected function setUp() {
		tx_oelib_configurationProxy::getInstance('realty')->setAsBoolean('enableConfigCheck', FALSE);

		tx_oelib_headerProxyFactory::getInstance()->enableTestMode();
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$configurationRegistry = tx_oelib_ConfigurationRegistry::getInstance();
		$configurationRegistry->set(
			'plugin.tx_realty_pi1.views.realty_list', new tx_oelib_Configuration()
		);
		$configurationRegistry->set(
			'plugin.tx_realty_pi1.views.single_view', new tx_oelib_Configuration()
		);
		$configurationRegistry->set(
			'plugin.tx_realty_pi1.views.my_objects', new tx_oelib_Configuration()
		);
		$configurationRegistry->set(
			'plugin.tx_realty_pi1.views.offerer_list', new tx_oelib_Configuration()
		);
		$configurationRegistry->set(
			'plugin.tx_realty_pi1.views.favorites', new tx_oelib_Configuration()
		);
		$configurationRegistry->set(
			'plugin.tx_realty_pi1.views.objects_by_owner',
			new tx_oelib_Configuration()
		);

		$this->createDummyPages();
		$this->createDummyObjects();

		// True enables the test mode which inhibits the FE editors FORMidable
		// object from being created.
		$this->fixture = new tx_realty_pi1(TRUE);
		// This passed array with configuration values becomes part of
		// $this->fixture->conf. "conf" is inherited from tslib_pibase and needs
		// to contain "pidList". "pidList" is none of our configuration values
		// but if cObj->currentRecord is set, "pidList" is set to our
		// configuration value "pages".
		// As we are in BE mode, "pidList" needs to be set directly.
		// The template file also needs to be included explicitly.
		$this->fixture->init(array(
			'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
			'pages' => $this->systemFolderPid,
			'showGoogleMaps' => 0,
			'defaultCountryUID' => self::DE,
			'displayedContactInformation' => 'company,offerer_label,telephone',
		));
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();

		setlocale(LC_NUMERIC, 'C');
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy FE pages (like login and single view).
	 *
	 * @return void
	 */
	private function createDummyPages() {
		$this->loginPid = $this->testingFramework->createFrontEndPage();
		$this->systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$this->subSystemFolderPid = $this->testingFramework->createSystemFolder(
			$this->systemFolderPid
		);
	}

	/**
	 * Creates dummy realty objects in the DB.
	 *
	 * @return void
	 */
	private function createDummyObjects() {
		$this->firstRealtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => self::$firstObjectTitle,
				'object_number' => self::$firstObjectNumber,
				'pid' => $this->systemFolderPid,
				'teaser' => '',
				'has_air_conditioning' => '0',
				'has_pool' => '0',
				'has_community_pool' => '0',
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
			)
		);
		$this->secondRealtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => self::$secondObjectTitle,
				'object_number' => self::$secondObjectNumber,
				'pid' => $this->systemFolderPid,
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
			)
		);
	}


	//////////////////////////////////////
	// Tests for the configuration check
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function configurationCheckIsActiveWhenEnabled() {
		// The configuration check is created during initialization, therefore
		// the object to test is recreated for this test.
		unset($this->fixture);
		tx_oelib_configurationProxy::getInstance('realty')
			->setAsBoolean('enableConfigCheck', TRUE);
		$this->fixture = new tx_realty_pi1();
		$this->fixture->init(array(
			'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
			'pages' => $this->systemFolderPid
		));
		// ensures there is at least one configuration error to report
		$this->fixture->setConfigurationValue('currencyUnit', 'foo');

		$this->assertContains(
			'Configuration check warning',
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function configurationCheckIsNotActiveWhenDisabled() {
		// The configuration check is created during initialization, therefore
		// the object to test is recreated for this test.
		unset($this->fixture);
		tx_oelib_configurationProxy::getInstance('realty')
			->setAsBoolean('enableConfigCheck', FALSE);
		$this->fixture = new tx_realty_pi1();
		$this->fixture->init(array(
			'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
			'pages' => $this->systemFolderPid
		));
		// ensures there is at least one configuration error to report
		$this->fixture->setConfigurationValue('currencyUnit', 'ABC');

		$this->assertNotContains(
			'Configuration check warning',
			$this->fixture->main('', array())
		);
	}


	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function pi1MustBeInitialized() {
		$this->assertNotNull(
			$this->fixture
		);
		$this->assertTrue(
			$this->fixture->isInitialized()
		);
	}


	////////////////////////////////////////////////
	// Tests for the access-restricted single view
	////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function accessToSingleViewIsAllowedWithoutLoginPerDefault() {
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser(NULL);

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	/**
	 * @test
	 */
	public function accessToSingleViewIsAllowedWithLoginPerDefault() {
		tx_oelib_FrontEndLoginManager::getInstance()
			->logInUser(new tx_realty_Model_FrontEndUser());

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	/**
	 * @test
	 */
	public function accessToSingleViewIsAllowedWithoutLoginIfNotDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 0);
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser(NULL);

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	/**
	 * @test
	 */
	public function accessToSingleViewIsAllowedWithLoginIfNotDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 0);
		tx_oelib_FrontEndLoginManager::getInstance()
			->logInUser(new tx_realty_Model_FrontEndUser());

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	/**
	 * @test
	 */
	public function accessToSingleViewIsDeniedWithoutLoginIfDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 1);
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser(NULL);

		$this->assertFalse(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	/**
	 * @test
	 */
	public function accessToSingleViewIsAllowedWithLoginIfDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 1);
		tx_oelib_FrontEndLoginManager::getInstance()
			->logInUser(new tx_realty_Model_FrontEndUser());

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}


	////////////////////////////
	// Testing the single view
	////////////////////////////

	/**
	 * @test
	 */
	public function singleViewIsDisplayedForValidRealtyObjectAndAccessAllowed() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'class="single-view"',
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function noResultViewIsDisplayedForRenderingTheSingleViewOfNonExistentObject() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->testingFramework->createRecord(
			'tx_realty_objects', array('deleted' => 1)
		);

		$this->assertContains(
			'class="noresults"',
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function errorMessageIsDisplayedForRenderingTheSingleViewOfNonExistentObject() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->testingFramework->createRecord(
			'tx_realty_objects', array('deleted' => 1)
		);

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function errorMessageIsDisplayedForRenderingTheSingleViewOfHiddenObject() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->firstRealtyUid, array('hidden' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function errorMessageIsDisplayedForRenderingTheSingleViewOfDeletedObject() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->firstRealtyUid, array('deleted' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function errorMessageIsDisplayedForRenderingTheSingleViewOfHiddenObjectForLoggedInNonOwner() {
		tx_oelib_FrontEndLoginManager::getInstance()
			->logInUser(new tx_realty_Model_FrontEndUser());

		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->firstRealtyUid,
			array(
				'hidden' => 1,
				'owner' => $this->testingFramework->createFrontEndUser(),
			)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function headerIsSetIfRenderingTheSingleViewLeadsToNoResultsMessage() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->testingFramework->createRecord(
			'tx_realty_objects', array('deleted' => 1)
		);
		$this->fixture->main('', array());

		$this->assertEquals(
			'Status: 404 Not Found',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}


	/////////////////////////////////////////////////////////
	// Tests concerning the access to the "my objects" list.
	/////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function accessToMyObjectsViewIsForbiddenForNotLoggedInUser() {
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$output = $this->fixture->main('', array());
		$this->assertContains(
			$this->fixture->translate('message_please_login'),
			$output
		);

		$this->assertContains(
			'?id=' . $this->loginPid,
			$output
		);
	}

	/**
	 * @test
	 */
	public function accessToMyObjectsViewContainsRedirectUrlWithPidIfAccessDenied() {
		$myObjectsPid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createFakeFrontEnd($myObjectsPid);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$output = $this->fixture->main('', array());
		$this->assertContains(
			'redirect_url',
			$output
		);

		$this->assertContains(
			urlencode('?id=' . $myObjectsPid),
			$output
		);
	}

	/**
	 * @test
	 */
	public function headerIsSentWhenTheMyObjectsViewShowsPleaseLoginMessage() {
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			$this->fixture->translate('message_please_login'),
			$this->fixture->main('', array())
		);

		$this->assertEquals(
			'Status: 403 Forbidden',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}


	/////////////////////////////
	// Testing the offerer list
	/////////////////////////////

	/**
	 * @test
	 */
	public function offererListIsDisplayedIfWhatToDisplayIsOffererList() {
		$groupId = $this->testingFramework->createFrontEndUserGroup();
		$this->testingFramework->createFrontEndUser($groupId);

		$this->fixture->setConfigurationValue('what_to_display', 'offerer_list');
		$this->fixture->setConfigurationValue('userGroupsForOffererList', $groupId);

		$this->assertContains(
			'offerer-list',
			$this->fixture->main('', array())
		);
	}


	////////////////////////////////////
	// Tests concerning the list views
	////////////////////////////////////

	/**
	 * @test
	 */
	public function forNoWhatToDisplaySetRealtyListViewWillBeRendered() {
		$this->fixture->setConfigurationValue('what_to_display', '');

		$this->assertContains(
			$this->fixture->translate('label_weofferyou'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function realtyListViewCanBeRendered() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertContains(
			$this->fixture->translate('label_weofferyou'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function favoritesViewCanBeRendered() {
		$this->fixture->setConfigurationValue('what_to_display', 'favorites');

		$this->assertContains(
			$this->fixture->translate('label_yourfavorites'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function myObjectsViewCanBeRendered() {
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');

		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(array());
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->assertContains(
			$this->fixture->translate('label_your_objects'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function objectByOwnerViewCanBeRendered() {
		$this->fixture->setConfigurationValue(
			'what_to_display', 'objects_by_owner'
		);

		$this->assertContains(
			$this->fixture->translate('label_sorry'),
			$this->fixture->main('', array())
		);
	}
}